<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function classroom(): Classroom
    {
        return AcademicYear::create(['name' => 'Curso de prueba'])->classrooms()->create(['name' => 'Clase A']);
    }

    private function challenge(Classroom $classroom): Challenge
    {
        $period = $classroom->academicYear->periods()->create(['name' => 'Primera', 'position' => 1]);

        return Challenge::create(['name' => 'Reto', 'classroom_id' => $classroom->id, 'period_id' => $period->id, 'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30], 'team_rubric' => ['items' => []], 'transversal_rubric' => ['items' => []]]);
    }

    public function test_student_requires_one_existing_classroom_and_authorization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = $this->classroom();
        $data = ['name' => 'Estudiante', 'email' => 'student@example.test', 'password' => '0123456789', 'active' => true];
        $this->actingAs($admin)->postJson('/setup/student', $data)->assertUnprocessable()->assertJsonValidationErrors('classroom_id');
        $this->postJson('/setup/student', [...$data, 'classroom_id' => [$classroom->id]])->assertUnprocessable();
        $this->postJson('/setup/student', [...$data, 'classroom_id' => 999999])->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $this->post('/setup/student', [...$data, 'classroom_id' => $classroom->id])->assertRedirect();
        $student = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame($classroom->id, $student->classroom_id);
        $this->assertTrue(Hash::check($data['password'], $student->password));
        $this->actingAs($student)->postJson('/setup/student', [...$data, 'email' => 'other@example.test', 'classroom_id' => $classroom->id])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'other@example.test']);
    }

    public function test_transfer_preserves_challenge_participants_and_unchanged_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = $this->classroom();
        $other = $classroom->academicYear->classrooms()->create(['name' => 'Clase B']);
        $student = User::factory()->create(['role' => 'student', 'classroom_id' => $classroom->id]);
        $challenge = $this->challenge($classroom);
        $challenge->students()->attach($student);
        $oldHash = $student->password;
        $this->actingAs($admin)->post('/setup/student', ['id' => $student->id, 'name' => $student->name, 'email' => $student->email, 'active' => true, 'password' => '', 'classroom_id' => $other->id])->assertRedirect();
        $this->assertSame($other->id, $student->fresh()->classroom_id);
        $this->assertSame($oldHash, $student->fresh()->password);
        $this->assertSame(0, $classroom->students()->count());
        $this->assertDatabaseHas('challenge_student', ['challenge_id' => $challenge->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'setup.student']);
    }

    #[TestWith([9, false])]
    #[TestWith([10, true])]
    #[TestWith([11, true])]
    public function test_password_length_boundary(int $length, bool $valid): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['name' => 'Profesor', 'email' => 'teacher@example.test', 'password' => str_repeat('a', $length), 'active' => true];
        $response = $this->actingAs($admin)->postJson('/setup/teacher', $data);
        if ($valid) {
            $response->assertRedirect();
            $this->assertTrue(Hash::check($data['password'], User::where('email', $data['email'])->firstOrFail()->password));
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('password');
            $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        }
    }

    public function test_course_rename_preserves_periods_with_challenges_and_rejects_structure_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = $this->classroom();
        $challenge = $this->challenge($classroom);
        $year = $classroom->academicYear;
        $this->actingAs($admin)->post('/setup/year', ['id' => $year->id, 'name' => 'Nombre corregido', 'periods' => ['Primera']])->assertRedirect();
        $this->assertSame('Nombre corregido', $year->fresh()->name);
        $this->assertSame($challenge->period_id, $year->periods()->firstOrFail()->id);
        $this->postJson('/setup/year', ['id' => $year->id, 'name' => 'No guardar', 'periods' => ['Nueva']])->assertUnprocessable()->assertJsonValidationErrors('periods');
        $this->assertSame('Nombre corregido', $year->fresh()->name);
        $this->assertSame('Primera', $year->periods()->firstOrFail()->name);
    }

    public function test_class_edit_keeps_teacher_membership_multiple_and_student_assignment_separate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $classroom = $this->classroom();
        $this->challenge($classroom);
        $other = $classroom->academicYear->classrooms()->create(['name' => 'Clase B']);
        foreach ([$classroom, $other] as $class) {
            $this->actingAs($admin)->post('/setup/classroom', ['id' => $class->id, 'name' => $class->name, 'academic_year_id' => $class->academic_year_id, 'user_ids' => [$teacher->id]])->assertRedirect();
        }
        $this->assertSame(2, DB::table('classroom_user')->where('user_id', $teacher->id)->count());
        $student = User::factory()->create(['role' => 'student', 'classroom_id' => $classroom->id]);
        $this->postJson('/setup/classroom', ['id' => $other->id, 'name' => $other->name, 'academic_year_id' => $other->academic_year_id, 'user_ids' => [$student->id]])->assertUnprocessable();
        $this->assertSame($classroom->id, $student->fresh()->classroom_id);
    }

    public function test_migration_preserves_unambiguous_enrollments_and_retains_ambiguous_links(): void
    {
        $classroom = $this->classroom();
        $other = $classroom->academicYear->classrooms()->create(['name' => 'Clase B']);
        $single = User::factory()->create(['role' => 'student']);
        $multiple = User::factory()->create(['role' => 'student']);
        $unassigned = User::factory()->create(['role' => 'student']);
        $migration = require database_path('migrations/2026_09_08_204238_add_current_classroom_and_demo_identifiers.php');
        $migration->down();
        DB::table('classroom_user')->insert([
            ['classroom_id' => $classroom->id, 'user_id' => $single->id],
            ['classroom_id' => $classroom->id, 'user_id' => $multiple->id],
            ['classroom_id' => $other->id, 'user_id' => $multiple->id],
        ]);
        $migration->up();
        $this->assertSame($classroom->id, $single->fresh()->classroom_id);
        $this->assertNull($multiple->fresh()->classroom_id);
        $this->assertNull($unassigned->fresh()->classroom_id);
        $this->assertSame(2, DB::table('classroom_user')->where('user_id', $multiple->id)->count());
    }
}
