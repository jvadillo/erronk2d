<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\Cycle;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_year_and_group_with_two_evaluations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/setup/year', ['name' => '2027-2028'])->assertRedirect();
        $year = AcademicYear::firstOrFail();

        $this->post('/setup/classroom', ['cycle_id' => Cycle::factory()->create()->id, 'level' => 2, 'name' => '2DAW', 'period_count' => 2, 'academic_year_id' => $year->id, 'user_ids' => []])->assertRedirect();

        $this->assertSame(['1.ª Evaluación', '2.ª Evaluación'], $year->classrooms()->firstOrFail()->periods()->pluck('name')->all());
        $this->assertDatabaseHas('classrooms', ['name' => '2DAW', 'academic_year_id' => $year->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'setup.classroom', 'user_id' => $admin->id]);
    }

    public function test_user_creation_hashes_password_and_omits_it_from_audit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/setup/teacher', ['name' => 'Docente', 'email' => 'docente@example.test', 'password' => 'UnaClaveParaPruebas123', 'active' => true, 'permissions' => ['evaluate_transversal']])->assertRedirect();

        $teacher = User::where('email', 'docente@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('UnaClaveParaPruebas123', $teacher->password));
        $this->assertTrue($teacher->allows('evaluate_transversal'));
        $this->assertTrue($teacher->allows('publish_results'));
        $this->assertFalse($teacher->allows('manage_teachers'));
        $this->assertArrayNotHasKey('password', AuditEvent::firstOrFail()->after);
    }

    public function test_teacher_cannot_grant_permissions_or_edit_an_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'permissions' => ['manage_teachers']]);
        $data = ['name' => 'Otro', 'email' => 'otro@example.test', 'password' => 'UnaClaveParaPruebas123', 'active' => true, 'permissions' => ['publish_results']];
        $this->actingAs($teacher)->postJson('/setup/teacher', $data)->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'otro@example.test']);
        unset($data['permissions']);
        $this->postJson('/setup/teacher', ['id' => $admin->id, ...$data])->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'email' => $admin->email]);
    }

    public function test_transversal_template_always_removes_module_scope(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $year = AcademicYear::create(['name' => '2026-2027']);
        $class = $year->classrooms()->create(['name' => '2DAW']);
        $module = $this->moduleForClass($class, ['name' => 'Programación', 'code' => 'PROG']);
        $this->actingAs($admin)->post('/setup/rubric', ['name' => 'Transversales', 'kind' => 'transversal', 'items' => [['key' => 'teamwork', 'name' => 'Colaboración', 'weight' => '100', 'module_id' => $module->id, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo']]]]])->assertRedirect();

        $this->assertNull(Rubric::firstOrFail()->items[0]['module_id']);
    }
}
