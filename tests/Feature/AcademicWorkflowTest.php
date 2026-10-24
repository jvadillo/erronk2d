<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function select(User $user, AcademicYear $year): void
    {
        $this->actingAs($user)->post('/academic-context', ['academic_year_id' => $year->id])->assertRedirect('/');
        $this->withHeader('X-Academic-Year', (string) $year->id);
    }

    private function rubric(User $owner): Rubric
    {
        return Rubric::create(['name' => 'Privada', 'kind' => 'team', 'owner_id' => $owner->id, 'items' => [['key' => 'quality', 'name' => 'Calidad', 'weight' => '1', 'module_id' => null, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo']]]]]);
    }

    public function test_teacher_creates_class_with_automatic_modules_but_cannot_modify_shared_catalog(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'permissions' => []]);
        $year = AcademicYear::factory()->create();
        $module = Module::factory()->create();
        $this->select($teacher, $year);

        $response = $this->post('/setup/classroom', ['name' => '142GA', 'cycle_id' => $module->cycle_id, 'level' => 2, 'user_ids' => []])->assertSessionHasNoErrors();

        $class = Classroom::firstOrFail();
        $response->assertRedirect(route('groups.show', $class));
        $this->assertSame($teacher->id, $class->owner_id);
        $this->assertSame([$module->id], $class->modules()->pluck('modules.id')->all());
        $this->postJson('/setup/year', ['id' => $year->id, 'name' => 'Otro'])->assertForbidden();
        $this->postJson('/setup/year-status', ['id' => $year->id, 'is_open' => false])->assertForbidden();
        $this->postJson('/setup/cycle', ['id' => $module->cycle_id, 'name' => 'Otro', 'code' => 'OTRO'])->assertForbidden();
        $this->postJson('/setup/module', ['id' => $module->id, 'name' => 'Otro', 'code' => $module->code, 'cycle_id' => $module->cycle_id, 'level' => 2])->assertForbidden();
        $this->postJson('/setup/cycle-module', ['cycle_id' => $module->cycle_id, 'module_id' => $module->id, 'active' => false, 'level' => 2])->assertForbidden();
        $this->assertSame([$year->name, true], [$year->fresh()->name, $year->fresh()->is_open]);
        $this->assertSame($module->name, $module->fresh()->name);
        $this->assertSame($module->cycle_id, $module->fresh()->cycle_id);
    }

    public function test_member_cannot_edit_class_and_admin_can_transfer_ownership(): void
    {
        $class = Classroom::factory()->create();
        $member = User::factory()->create(['role' => 'teacher']);
        $class->users()->attach($member);
        $data = ['id' => $class->id, 'name' => 'Cambio', 'cycle_id' => $class->cycle_id, 'level' => 2, 'user_ids' => [$member->id]];
        $this->select($member, $class->academicYear);

        $this->postJson('/setup/classroom', $data)->assertForbidden();
        $this->assertDatabaseHas('classrooms', ['id' => $class->id, 'name' => $class->name]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->select($admin, $class->academicYear);
        $this->post('/setup/classroom', [...$data, 'owner_id' => $member->id])->assertSessionHasNoErrors();
        $this->assertSame($member->id, $class->fresh()->owner_id);
    }

    public function test_foreign_class_and_other_year_challenge_cannot_be_read_written_or_exported(): void
    {
        $class = Classroom::factory()->create();
        $foreign = Classroom::factory()->create(['academic_year_id' => $class->academic_year_id]);
        $challenge = Challenge::factory()->create(['classroom_id' => $foreign->id]);
        $otherYear = Classroom::factory()->create(['owner_id' => $class->owner_id]);
        $otherChallenge = Challenge::factory()->create(['classroom_id' => $otherYear->id]);
        $this->select($class->owner, $class->academicYear);

        foreach ([$challenge, $otherChallenge] as $hidden) {
            $this->get('/challenges/'.$hidden->id)->assertNotFound();
            $this->get('/challenges/'.$hidden->id.'/history')->assertNotFound();
            $this->postJson('/challenges/'.$hidden->id, ['revision' => 1, 'action' => 'status', 'status' => 'active'])->assertNotFound();
            $this->get('/reports?format=csv&classroom='.$hidden->classroom_id)->assertNotFound();
        }
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 0)->has('classrooms', 1));
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_closed_year_blocks_all_class_mutations_even_for_admin_and_reopening_restores_writes(): void
    {
        $class = Classroom::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->select($admin, $class->academicYear);
        $this->post('/setup/year-status', ['id' => $class->academic_year_id, 'is_open' => false])->assertSessionHasNoErrors();
        $data = ['name' => 'Nueva', 'cycle_id' => $class->cycle_id, 'level' => 2, 'user_ids' => []];

        $this->postJson('/setup/classroom', $data)->assertForbidden();
        $this->get('/setup/classrooms')->assertOk();
        $this->assertDatabaseCount('classrooms', 1);
        $this->post('/setup/year-status', ['id' => $class->academic_year_id, 'is_open' => true])->assertSessionHasNoErrors();
        $this->post('/setup/classroom', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('classrooms', 2);
    }

    public function test_member_can_create_and_enroll_student_in_multiple_classes_without_editing_account(): void
    {
        $class = Classroom::factory()->create();
        $other = Classroom::factory()->create(['academic_year_id' => $class->academic_year_id, 'owner_id' => $class->owner_id]);
        $this->select($class->owner, $class->academicYear);
        $this->post('/setup/student', ['name' => 'Estudiante', 'email' => 'student@example.test', 'password' => 'TestingOnly123', 'active' => true, 'classroom_id' => $class->id])->assertSessionHasNoErrors();
        $student = User::where('email', 'student@example.test')->firstOrFail();

        $this->post('/setup/enrollment', ['classroom_id' => $other->id, 'email' => $student->email, 'active' => true])->assertSessionHasNoErrors();
        $this->assertSame(2, $student->enrollments()->count());
        $this->post('/setup/enrollment', ['classroom_id' => $class->id, 'email' => $student->email, 'active' => false])->assertSessionHasNoErrors();
        $this->assertSame(2, $student->enrollments()->count());
        $this->assertSame(0, $class->students()->count());
        $this->assertSame(1, $other->students()->count());
        $this->postJson('/setup/student', ['id' => $student->id])->assertForbidden();
        $this->assertSame('Estudiante', $student->fresh()->name);
    }

    public function test_student_search_requires_exact_email_and_never_lists_foreign_students(): void
    {
        $class = Classroom::factory()->create();
        $student = User::factory()->create(['email' => 'specific@example.test']);
        $this->select($class->owner, $class->academicYear);

        $this->get('/setup/students')->assertInertia(fn (Assert $page) => $page->has('users', 0));
        $this->postJson('/students/lookup', ['email' => 'specific', 'classroom_id' => $class->id])->assertUnprocessable();
        $this->postJson('/students/lookup', ['email' => 'specific@example.test', 'classroom_id' => $class->id])->assertExactJson(['student' => $student->only(['id', 'name', 'email'])]);
        $this->postJson('/students/lookup', ['email' => 'missing@example.test', 'classroom_id' => $class->id])->assertExactJson(['student' => null]);
    }

    public function test_student_suggestions_match_name_or_email_and_skip_current_members_inactive_accounts_and_staff(): void
    {
        $class = Classroom::factory()->create();
        $ane = User::factory()->create(['name' => 'Ane Etxeberria', 'email' => 'ane@example.test']);
        $withdrawn = User::factory()->create(['name' => 'Ane Baja', 'email' => 'baja@example.test']);
        $this->enrollInClass($withdrawn, $class);
        $withdrawn->enrollments()->update(['ended_at' => now()]);
        $this->enrollInClass(User::factory()->create(['name' => 'Ane Matriculada']), $class);
        User::factory()->create(['name' => 'Ane Inactiva', 'active' => false]);
        User::factory()->create(['name' => 'Ane Profesora', 'role' => 'teacher']);
        $iker = User::factory()->create(['name' => 'Iker', 'email' => 'iker@example.test']);
        $url = fn (string $query): string => route('students.search', ['classroom_id' => $class->id, 'q' => $query]);
        $this->select($class->owner, $class->academicYear);

        $this->getJson($url('ANE'))->assertExactJson(['students' => [$withdrawn->only(['id', 'name', 'email']), $ane->only(['id', 'name', 'email'])]]);
        $this->getJson($url('IKER@EXAMPLE'))->assertExactJson(['students' => [$iker->only(['id', 'name', 'email'])]]);
        $this->getJson($url('%%%'))->assertExactJson(['students' => []]);
        $this->getJson($url('an'))->assertUnprocessable();

        $this->select(User::factory()->create(['role' => 'teacher']), $class->academicYear);
        $this->getJson($url('Ane'))->assertNotFound();
        $this->enrollInClass($ane, $class);
        $this->select($ane, $class->academicYear);
        $this->getJson($url('Ane'))->assertForbidden();
    }

    public function test_rubric_is_private_to_its_owner_who_can_duplicate_it(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $rubric = $this->rubric($owner);
        $this->actingAs($other)->get('/setup/rubrics')->assertInertia(fn (Assert $page) => $page->has('rubrics', 0));
        $this->postJson('/setup/rubric-copy', ['id' => $rubric->id])->assertNotFound();
        $this->postJson('/setup/rubric', ['id' => $rubric->id, 'name' => 'Cambio'])->assertNotFound();
        $this->assertSame('Privada', $rubric->fresh()->name);

        $this->actingAs($owner)->post('/setup/rubric-copy', ['id' => $rubric->id])->assertSessionHasNoErrors();

        $copy = Rubric::where('owner_id', $owner->id)->whereKeyNot($rubric->id)->firstOrFail();
        $this->assertSame($rubric->items, $copy->items);
        $this->assertSame('Privada (copia)', $copy->name);
    }

    public function test_group_membership_grants_and_revokes_access_to_all_its_challenges(): void
    {
        $class = Classroom::factory()->create();
        $this->moduleForClass($class);
        $challenge = Challenge::factory()->create(['classroom_id' => $class->id]);
        $challenge->modules()->sync($class->modules()->pluck('modules.id'));
        $teacher = User::factory()->create(['role' => 'teacher']);
        $class->users()->attach($teacher);

        $this->assertTrue($teacher->canAccessChallenge($challenge));
        $this->actingAs($class->owner)->postJson('/setup/responsibility', ['classroom_id' => $class->id])->assertNotFound();
        $class->users()->detach($teacher);
        $this->assertFalse($teacher->canAccessClassroom($class));
        $this->assertFalse($teacher->canAccessChallenge($challenge));
    }

    public function test_catalog_renaming_keeps_original_class_module_names(): void
    {
        $module = Module::factory()->create(['name' => 'Nombre original']);
        $class = Classroom::factory()->create(['cycle_id' => $module->cycle_id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post('/setup/module', ['id' => $module->id, 'name' => 'Nombre nuevo', 'code' => $module->code, 'cycle_id' => $module->cycle_id, 'level' => 2])->assertSessionHasNoErrors();

        $this->assertSame('Nombre original', $class->modules()->first()->pivot->name);
        $newClass = Classroom::factory()->create(['cycle_id' => $module->cycle_id]);
        $this->assertSame('Nombre nuevo', $newClass->modules()->first()->pivot->name);
    }

    public function test_import_reuses_student_without_changing_credentials_or_other_enrollments(): void
    {
        $class = Classroom::factory()->create();
        $student = User::factory()->create(['email' => 'ReUsed@example.test']);
        $password = $student->password;
        $this->select($class->owner, $class->academicYear);

        $this->post('/imports', ['kind' => 'student', 'commit' => true, 'classroom_id' => $class->id, 'file' => UploadedFile::fake()->createWithContent('students.csv', "nombre,email\nOtro nombre,reused@example.test\n")])->assertJsonPath('committed', true);

        $this->assertSame($password, $student->fresh()->password);
        $this->assertSame($student->name, $student->fresh()->name);
        $this->assertDatabaseHas('enrollments', ['classroom_id' => $class->id, 'student_id' => $student->id, 'ended_at' => null]);
    }

    public function test_stale_tab_cannot_create_class_in_newly_selected_year(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $old = AcademicYear::factory()->create();
        $latest = AcademicYear::factory()->create();
        $cycle = Cycle::factory()->create();
        $this->select($teacher, $latest);
        $this->withHeader('X-Academic-Year', (string) $old->id);

        $this->postJson('/setup/classroom', ['name' => 'Obsoleta', 'cycle_id' => $cycle->id, 'level' => 2, 'user_ids' => []])->assertConflict();

        $this->assertDatabaseCount('classrooms', 0);
    }
}
