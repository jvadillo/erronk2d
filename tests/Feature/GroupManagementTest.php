<?php

namespace Tests\Feature;

use App\Domain\Grades\Gradebook;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GroupManagementTest extends TestCase
{
    use RefreshDatabase;

    private function select(User $actor, Classroom $group): void
    {
        $this->actingAs($actor)->post('/academic-context', ['academic_year_id' => $group->academic_year_id])->assertRedirect();
        $this->withHeader('X-Academic-Year', (string) $group->academic_year_id);
    }

    #[TestWith(['teacher'])]
    #[TestWith(['admin'])]
    public function test_member_or_admin_can_add_and_remove_empty_evaluations_preserving_used_ids(string $role): void
    {
        $group = Classroom::factory()->create();
        $actor = User::factory()->create(['role' => $role]);
        $group->users()->attach($actor);
        $challenge = Challenge::factory()->create(['classroom_id' => $group->id]);
        $unused = $group->periods()->create(['name' => 'Eliminar', 'position' => 2]);
        $other = Classroom::factory()->create(['academic_year_id' => $group->academic_year_id]);
        $otherPeriod = $other->periods()->create(['name' => 'Independiente', 'position' => 1]);
        $this->select($actor, $group);

        $this->post('/setup/group-periods', ['classroom_id' => $group->id, 'periods' => [['name' => 'Nueva'], ['id' => $challenge->period_id, 'name' => 'Primera']]])->assertSessionHasNoErrors();

        $this->assertSame(['Nueva', 'Primera'], $group->periods()->pluck('name')->all());
        $this->assertSame($challenge->period_id, $challenge->fresh()->period_id);
        $this->assertDatabaseMissing('periods', ['id' => $unused->id]);
        $this->assertSame(['Independiente'], $other->periods()->pluck('name')->all());
        $this->assertModelExists($otherPeriod);
    }

    #[TestWith(['remove'])]
    #[TestWith(['rename'])]
    #[TestWith(['foreign'])]
    #[TestWith(['empty'])]
    #[TestWith(['too_many'])]
    public function test_invalid_evaluation_changes_leave_group_and_challenge_unchanged(string $case): void
    {
        $group = Classroom::factory()->create();
        $challenge = Challenge::factory()->create(['classroom_id' => $group->id]);
        $this->select($group->owner, $group);
        $periods = match ($case) {
            'remove' => [['name' => 'Otra']],
            'rename' => [['id' => $challenge->period_id, 'name' => 'Renombrada']],
            'foreign' => [['id' => Challenge::factory()->create()->period_id, 'name' => 'Otra']],
            'empty' => [],
            'too_many' => array_map(fn (int $index) => ['name' => 'Evaluación '.$index], range(1, 13)),
        };

        $this->postJson('/setup/group-periods', ['classroom_id' => $group->id, 'periods' => $periods])->assertUnprocessable()->assertJsonValidationErrors('periods');

        $this->assertSame(['Primera'], $group->periods()->pluck('name')->all());
        $this->assertSame($challenge->period_id, $challenge->fresh()->period_id);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_member_can_retire_and_restore_module_without_changing_reports_or_responsibilities(): void
    {
        $this->freezeTime();
        $group = Classroom::factory()->create();
        $actor = User::factory()->create(['role' => 'teacher']);
        $group->users()->attach($actor);
        $module = $this->moduleForClass($group, ['name' => 'Original', 'code' => 'MOD'], $actor);
        $student = User::factory()->create();
        $this->enrollInClass($student, $group);
        $challenge = Challenge::factory()->create(['classroom_id' => $group->id]);
        $challenge->modules()->attach($module);
        $challenge->students()->attach($student);
        $report = app(Gradebook::class)->report($group)['rows'];
        $this->select($actor, $group);

        $this->post('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $module->id, 'active' => false])->assertSessionHasNoErrors();

        $this->assertSame(0, $group->modules()->count());
        $this->assertSame([$module->id], $challenge->modules()->pluck('modules.id')->all());
        $this->assertTrue($actor->teaches($module->id, $group->id));
        $this->assertSame($report, app(Gradebook::class)->report($group->fresh())['rows']);
        $this->get('/setup/classrooms')->assertInertia(fn (Assert $page) => $page->where('title', 'Grupos')->has('classrooms.0.modules', 0)->has('classrooms.0.retired_modules', 1));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('classrooms.0.modules', 0));
        $module->update(['name' => 'Nuevo nombre']);
        $group->syncCatalog();
        $this->assertSame(0, $group->modules()->count());

        $this->post('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $module->id, 'active' => true])->assertSessionHasNoErrors();

        $this->assertSame('Original', $group->modules()->firstOrFail()->pivot->name);
        $this->assertSame(1, $group->catalogModules()->count());
    }

    public function test_new_catalog_modules_require_explicit_addition_to_existing_group(): void
    {
        $group = Classroom::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->select($admin, $group);
        $this->post('/setup/module', ['name' => 'Nuevo', 'code' => 'NEW', 'cycle_id' => $group->cycle_id, 'level' => $group->level])->assertSessionHasNoErrors();
        $module = Module::where('code', 'NEW')->firstOrFail();
        $this->assertSame(0, $group->modules()->count());

        $this->post('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $module->id, 'active' => true])->assertSessionHasNoErrors();

        $this->assertSame([$module->id], $group->modules()->pluck('modules.id')->all());
    }

    public function test_imported_modules_do_not_change_existing_group_selection(): void
    {
        $group = Classroom::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->select($admin, $group);

        $this->post('/imports', ['kind' => 'module', 'commit' => true, 'cycle_id' => $group->cycle_id, 'level' => $group->level, 'file' => UploadedFile::fake()->createWithContent('modules.csv', "name,code\nImportado,IMP\n")])->assertJsonPath('committed', true);

        $this->assertDatabaseHas('modules', ['cycle_id' => $group->cycle_id, 'code' => 'IMP']);
        $this->assertSame(0, $group->catalogModules()->count());
    }

    #[TestWith(['cycle'])]
    #[TestWith(['level'])]
    public function test_module_from_different_cycle_or_level_cannot_be_added(string $difference): void
    {
        $group = Classroom::factory()->create();
        $module = Module::factory()->create($difference === 'level' ? ['cycle_id' => $group->cycle_id, 'level' => 1] : []);
        $this->select($group->owner, $group);

        $this->postJson('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $module->id, 'active' => true])->assertUnprocessable()->assertJsonValidationErrors('module_id');

        $this->assertSame(0, $group->catalogModules()->count());
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_member_can_withdraw_inactive_student_without_removing_history_or_other_enrollments(): void
    {
        $this->freezeTime();
        $group = Classroom::factory()->create();
        $member = User::factory()->create(['role' => 'teacher']);
        $group->users()->attach($member);
        $other = Classroom::factory()->create(['academic_year_id' => $group->academic_year_id]);
        $student = User::factory()->create(['active' => false]);
        $this->enrollInClass($student, $group);
        $this->enrollInClass($student, $other);
        $challenge = Challenge::factory()->create(['classroom_id' => $group->id]);
        $challenge->students()->attach($student);
        $this->select($member, $group);

        $this->post('/setup/enrollment', ['classroom_id' => $group->id, 'email' => $student->email, 'active' => false])->assertSessionHasNoErrors();

        $this->assertSame(0, $group->students()->count());
        $this->assertDatabaseHas('enrollments', ['classroom_id' => $other->id, 'student_id' => $student->id, 'ended_at' => null]);
        $this->assertDatabaseHas('enrollments', ['classroom_id' => $group->id, 'student_id' => $student->id, 'ended_at' => now()]);
        $this->assertSame([$student->id], $challenge->students()->pluck('users.id')->all());
        $this->postJson('/setup/enrollment', ['classroom_id' => $group->id, 'email' => $student->email, 'active' => true])->assertUnprocessable();
        $this->assertSame(0, $group->students()->count());
    }

    #[TestWith(['group-module'])]
    #[TestWith(['group-periods'])]
    public function test_group_configuration_respects_membership_year_and_authentication(string $endpoint): void
    {
        $group = Classroom::factory()->create();
        $module = $this->moduleForClass($group);
        $data = ['classroom_id' => $group->id, 'module_id' => $module->id, 'active' => false, 'periods' => [['name' => 'Nueva']]];
        $this->post('/setup/'.$endpoint, $data)->assertRedirect('/login');
        $outsider = User::factory()->create(['role' => 'teacher']);
        $this->select($outsider, $group);
        $this->postJson('/setup/'.$endpoint, $data)->assertNotFound();
        $student = User::factory()->create();
        $this->enrollInClass($student, $group);
        $this->select($student, $group);
        $this->postJson('/setup/'.$endpoint, $data)->assertForbidden();
        $this->select($group->owner, $group);
        $this->withHeader('X-Academic-Year', '999999');
        $this->postJson('/setup/'.$endpoint, $data)->assertConflict();
        $this->withHeader('X-Academic-Year', (string) $group->academic_year_id);
        $group->academicYear->update(['is_open' => false]);
        $this->postJson('/setup/'.$endpoint, $data)->assertForbidden();

        $this->assertSame([$module->id], $group->modules()->pluck('modules.id')->all());
        $this->assertSame(0, $group->periods()->count());
        $this->assertDatabaseCount('audit_events', 0);
    }
}
