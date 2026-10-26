<?php

namespace Tests\Feature;

use App\Domain\Catalog;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GroupPreparationTest extends TestCase
{
    use RefreshDatabase;

    private function select(User $user, AcademicYear $year): void
    {
        $this->actingAs($user)->post('/academic-context', ['academic_year_id' => $year->id])->assertRedirect('/');
        $this->withHeader('X-Academic-Year', (string) $year->id);
    }

    /**
     * @return array{source: Classroom, member: User, kept: Module, retired: Module, inactive: User, students: list<User>, challenge: Challenge}
     */
    private function sourceGroup(): array
    {
        $source = Classroom::factory()->create(['name' => '1DAW-A']);
        $source->periods()->create(['name' => 'Otoño', 'position' => 1]);
        $source->periods()->create(['name' => 'Primavera', 'position' => 2]);
        $kept = $this->moduleForClass($source, ['code' => 'PROG', 'name' => 'Programación']);
        $retired = $this->moduleForClass($source, ['code' => 'BD', 'name' => 'Bases de datos']);
        $source->catalogModules()->updateExistingPivot($retired->id, ['ended_at' => now()]);
        $member = User::factory()->create(['role' => 'teacher']);
        $inactive = User::factory()->create(['role' => 'teacher', 'active' => false]);
        $source->users()->attach([$member->id, $inactive->id]);
        $students = User::factory()->count(3)->create()->all();
        foreach ($students as $student) {
            $this->enrollInClass($student, $source);
        }
        $challenge = Challenge::factory()->create(['classroom_id' => $source->id, 'period_id' => $source->periods()->first()->id]);
        $challenge->modules()->attach($kept);
        $challenge->students()->attach(array_column($students, 'id'));
        $team = $challenge->teams()->create(['name' => 'Equipo']);
        $team->memberships()->create(['challenge_id' => $challenge->id, 'student_id' => $students[0]->id]);
        Assessment::create(['challenge_id' => $challenge->id, 'kind' => 'team', 'subject_id' => $team->id, 'scope_id' => 0, 'criterion' => 'quality', 'level' => 1, 'updated_by' => $member->id]);

        return compact('source', 'member', 'kept', 'retired', 'inactive', 'students', 'challenge');
    }

    public function test_teacher_without_academic_structure_prepares_a_group_and_a_colleague_creates_its_first_challenge(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $colleague = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->get('/setup/classrooms')->assertInertia(fn (Assert $page) => $page->where('academic.year', null)->has('openYears', 0)->has('cycles', 0));
        $this->postJson('/setup/year', ['name' => '2027-2028'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->postJson('/setup/year', ['name' => '2027-2028'])->assertOk()->assertJsonPath('item.is_open', true);
        $year = AcademicYear::sole()->toArray();
        $this->actingAs($teacher)->get('/setup/classrooms')->assertInertia(fn (Assert $page) => $page->has('openYears', 1));
        $cycle = $this->postJson('/setup/cycle', ['name' => 'Desarrollo de Aplicaciones Web', 'code' => 'DAW'])->assertOk()->json('item');
        $programming = $this->postJson('/setup/module', ['name' => 'Programación', 'code' => '0485', 'cycle_id' => $cycle['id'], 'level' => 1])->assertOk()->json('item');
        $this->postJson('/setup/module', ['name' => 'Sistemas informáticos', 'code' => '0483', 'cycle_id' => $cycle['id'], 'level' => 1])->assertOk();
        $this->postJson('/setup/module', ['name' => 'Despliegue', 'code' => '0614', 'cycle_id' => $cycle['id'], 'level' => 2])->assertOk();

        $response = $this->post('/setup/classroom', ['name' => '1DAW-A', 'academic_year_id' => $year['id'], 'cycle_id' => $cycle['id'], 'level' => 1, 'module_ids' => [$programming['id']], 'user_ids' => [$colleague->id]])->assertSessionHasNoErrors();

        $group = Classroom::sole();
        $response->assertRedirect(route('groups.show', $group));
        $this->assertSame([$year['id'], $teacher->id, 1, 'Desarrollo de Aplicaciones Web'], [$group->academic_year_id, $group->owner_id, $group->level, $group->cycle_name]);
        $this->assertEqualsCanonicalizing([$teacher->id, $colleague->id], $group->users()->pluck('users.id')->all());
        $this->assertSame([$programming['id']], $group->modules()->pluck('modules.id')->all());
        $this->assertSame(['1.ª Evaluación', '2.ª Evaluación', '3.ª Evaluación'], $group->periods()->pluck('name')->all());
        $this->assertSame($year['id'], $teacher->fresh()->last_academic_year_id);

        $this->withHeader('X-Academic-Year', (string) $year['id']);
        $this->post('/setup/student', ['name' => 'Ane', 'email' => 'ane@example.test', 'password' => 'TestingOnly123', 'active' => true, 'classroom_id' => $group->id])->assertSessionHasNoErrors();
        $this->select($colleague, $group->academicYear);
        $this->post('/challenges', ['name' => 'Primer reto', 'classroom_id' => $group->id, 'period_id' => $group->periods()->first()->id, 'module_ids' => [$programming['id']], 'team_rubric_id' => null, 'transversal_rubric_id' => null, 'weight' => '1', 'distribution_enabled' => true])->assertRedirect();

        $challenge = $group->challenges()->sole();
        $this->assertSame(['ane@example.test'], $challenge->students()->pluck('email')->all());
        $this->assertSame([$colleague->id], $challenge->teachers()->pluck('users.id')->all());
        $this->actingAs($teacher)->get(route('challenges.show', $challenge))->assertNotFound();
        $this->actingAs($colleague);
        $this->post('/setup/student', ['name' => 'Jon', 'email' => 'jon@example.test', 'password' => 'TestingOnly123', 'active' => true, 'classroom_id' => $group->id])->assertSessionHasNoErrors();
        $this->assertSame(1, $challenge->students()->count());
    }

    public function test_shared_catalog_rejects_duplicates_that_only_differ_in_case_or_spaces(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/setup/year', ['name' => '2027-2028'])->assertOk();
        $this->postJson('/setup/year', ['name' => ' 2027 - 2028 '])->assertUnprocessable()->assertJsonValidationErrors(['name' => 'Ya existe el curso académico «2027-2028». Selecciónalo en lugar de crearlo.']);
        $this->actingAs(User::factory()->create(['role' => 'teacher']));
        $cycle = $this->postJson('/setup/cycle', ['name' => 'Desarrollo de Aplicaciones Web', 'code' => 'DAW'])->assertOk()->json('item');
        $this->postJson('/setup/module', ['name' => 'Programación', 'code' => '0485', 'cycle_id' => $cycle['id'], 'level' => 1])->assertOk();

        $this->postJson('/setup/cycle', ['name' => 'desarrollo de  aplicaciones WEB', 'code' => 'NUEVO'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/setup/cycle', ['name' => 'Otro ciclo', 'code' => ' daw '])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/setup/module', ['name' => 'Otro nombre', 'code' => '0485 ', 'cycle_id' => $cycle['id'], 'level' => 2])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/setup/module', ['name' => 'PROGRAMACIÓN', 'code' => '9999', 'cycle_id' => $cycle['id'], 'level' => 1])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/setup/module', ['name' => 'Sin ciclo', 'code' => 'X1', 'level' => 1])->assertUnprocessable()->assertJsonValidationErrors(['cycle_id' => 'Selecciona el ciclo del módulo.']);
        $other = $this->postJson('/setup/cycle', ['name' => 'Desarrollo de Aplicaciones Multiplataforma', 'code' => 'DAM'])->assertOk()->json('item');
        $this->postJson('/setup/module', ['name' => 'Programación', 'code' => '0485', 'cycle_id' => $other['id'], 'level' => 1])->assertOk();

        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('cycles', 2);
        $this->assertDatabaseCount('modules', 2);
    }

    public function test_concurrent_duplicate_is_rejected_by_the_database_without_breaking_the_transaction(): void
    {
        $cycle = Cycle::factory()->create(['name' => 'Ciclo común', 'code' => 'COM']);
        $racing = new Cycle(['name' => 'CICLO  común', 'code' => 'com']);

        DB::transaction(function () use ($racing, $cycle) {
            try {
                app(Catalog::class)->persist($racing, 'name', 'Otra persona acaba de crear ese ciclo.');
                $this->fail('The unique index should reject the duplicate.');
            } catch (ValidationException $exception) {
                $this->assertSame(['name' => ['Otra persona acaba de crear ese ciclo.']], $exception->errors());
            }
            $this->assertSame($cycle->id, Cycle::sole()->id);
        });
        $this->assertDatabaseCount('cycles', 1);
    }

    public function test_only_admin_creates_and_changes_academic_years_and_new_years_do_not_close_others(): void
    {
        $existing = AcademicYear::factory()->create(['name' => '2026-2027']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->postJson('/setup/year', ['name' => '2027-2028'])->assertForbidden();
        $this->get('/setup/courses')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/setup/year', ['name' => '2027-2028'])->assertOk();

        $this->assertTrue($existing->fresh()->is_open);
        $created = AcademicYear::where('name', '2027-2028')->sole();
        $this->assertTrue($created->is_open);
        $this->actingAs($teacher)->postJson('/setup/year-status', ['id' => $created->id, 'is_open' => false])->assertForbidden();
        $this->postJson('/setup/year', ['id' => $created->id, 'name' => 'Cambio'])->assertForbidden();
        $this->assertSame(['2027-2028', true], [$created->fresh()->name, $created->fresh()->is_open]);

        $this->actingAs($admin)->post('/setup/year-status', ['id' => $created->id, 'is_open' => false])->assertSessionHasNoErrors();
        $cycle = Cycle::factory()->create();
        $this->actingAs($teacher)->postJson('/setup/classroom', ['name' => 'Cerrado', 'academic_year_id' => $created->id, 'cycle_id' => $cycle->id, 'level' => 1, 'user_ids' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('academic_year_id');
        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_owner_manages_structure_and_members_manage_activity(): void
    {
        ['source' => $group, 'member' => $member, 'retired' => $retired] = $this->sourceGroup();
        $student = User::factory()->create(['email' => 'nuevo@example.test']);
        $this->select($member, $group->academicYear);
        $structure = ['id' => $group->id, 'name' => 'Cambio', 'cycle_id' => $group->cycle_id, 'level' => $group->level, 'user_ids' => [$member->id]];

        $this->postJson('/setup/classroom', $structure)->assertForbidden();
        $this->postJson('/setup/group-periods', ['classroom_id' => $group->id, 'periods' => [['name' => 'Nueva']]])->assertForbidden();
        $this->postJson('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $retired->id, 'active' => true])->assertForbidden();
        $this->post('/setup/enrollment', ['classroom_id' => $group->id, 'email' => $student->email, 'active' => true])->assertSessionHasNoErrors();
        $this->get(route('groups.show', $group))->assertInertia(fn (Assert $page) => $page->where('group.can_manage', false)->has('group.students', 4));

        $this->select($group->owner, $group->academicYear);
        $this->postJson('/setup/classroom', [...$structure, 'owner_id' => $member->id])->assertForbidden();
        $this->post('/setup/classroom', $structure)->assertSessionHasNoErrors();
        $this->post('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $retired->id, 'active' => true])->assertSessionHasNoErrors();
        $this->assertSame(['Cambio', $group->owner_id, 2], [$group->fresh()->name, $group->fresh()->owner_id, $group->modules()->count()]);
        $this->postJson('/setup/classroom', [...$structure, 'level' => 3])->assertUnprocessable()->assertJsonValidationErrors('cycle_id');
    }

    public function test_teacher_outside_the_group_cannot_reach_it_even_after_creating_its_module(): void
    {
        ['source' => $group, 'challenge' => $challenge] = $this->sourceGroup();
        $outsider = User::factory()->create(['role' => 'teacher']);
        $this->select($outsider, $group->academicYear);
        $module = $this->postJson('/setup/module', ['name' => 'Entornos de desarrollo', 'code' => 'ED', 'cycle_id' => $group->cycle_id, 'level' => $group->level])->assertOk()->json('item');
        $this->select($group->owner, $group->academicYear);
        $this->post('/setup/group-module', ['classroom_id' => $group->id, 'module_id' => $module['id'], 'active' => true])->assertSessionHasNoErrors();

        $this->select($outsider, $group->academicYear);
        $this->get(route('groups.show', $group))->assertNotFound();
        $this->postJson(route('groups.duplicate', $group), ['academic_year_id' => $group->academic_year_id, 'name' => 'Copia', 'user_ids' => [], 'student_ids' => []])->assertNotFound();
        $this->postJson('/setup/enrollment', ['classroom_id' => $group->id, 'email' => 'x@example.test', 'active' => true])->assertNotFound();
        $this->get(route('challenges.show', $challenge))->assertNotFound();
        $this->postJson(route('challenges.update', $challenge), ['revision' => $challenge->revision, 'action' => 'grades', 'field' => 'exam', 'module_id' => $module['id'], 'entries' => [['student_id' => 1, 'value' => '5']]])->assertNotFound();
        $this->get('/reports?format=csv&classroom='.$group->id)->assertNotFound();

        $student = $group->students()->first();
        $this->actingAs($student)->postJson(route('groups.duplicate', $group), ['academic_year_id' => $group->academic_year_id, 'name' => 'Copia', 'user_ids' => [], 'student_ids' => []])->assertForbidden();
        $this->postJson('/setup/cycle', ['name' => 'Ciclo', 'code' => 'C'])->assertForbidden();
        $this->assertDatabaseCount('classrooms', 1);
    }

    public function test_member_duplicates_group_structure_into_a_new_year_without_activity_or_enrollments(): void
    {
        ['source' => $source, 'member' => $member, 'kept' => $kept, 'inactive' => $inactive, 'challenge' => $challenge] = $this->sourceGroup();
        $target = AcademicYear::factory()->create(['name' => '2027-2028']);
        $before = [$source->fresh()->toArray(), $source->catalogModules()->get()->toArray(), $source->periods()->get()->toArray(), $source->enrollments()->get()->toArray(), $challenge->fresh()->toArray()];
        $this->select($member, $source->academicYear);
        $this->get(route('groups.show', $source))->assertInertia(fn (Assert $page) => $page->has('openYears', 2)->where('duplicateUrl', route('groups.duplicate', $source)));

        $this->postJson(route('groups.duplicate', $source), ['academic_year_id' => $target->id, 'name' => '2DAW-A', 'user_ids' => [$source->owner_id, $inactive->id], 'student_ids' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('user_ids.1');
        $response = $this->post(route('groups.duplicate', $source), ['academic_year_id' => $target->id, 'name' => '2DAW-A', 'user_ids' => [$source->owner_id], 'student_ids' => []])->assertSessionHasNoErrors();

        $copy = Classroom::where('academic_year_id', $target->id)->sole();
        $response->assertRedirect(route('groups.show', $copy));
        $this->assertSame([$member->id, $source->cycle_id, $source->level], [$copy->owner_id, $copy->cycle_id, $copy->level]);
        $this->assertEqualsCanonicalizing([$member->id, $source->owner_id], $copy->users()->pluck('users.id')->all());
        $this->assertSame([$kept->id], $copy->catalogModules()->pluck('modules.id')->all());
        $this->assertSame(['Otoño', 'Primavera'], $copy->periods()->pluck('name')->all());
        $this->assertEmpty(array_intersect($copy->periods()->pluck('id')->all(), $source->periods()->pluck('id')->all()));
        $this->assertSame(0, $copy->enrollments()->count());
        $this->assertSame(0, $copy->challenges()->count());
        $this->assertDatabaseCount('challenges', 1);
        $this->assertDatabaseCount('teams', 1);
        $this->assertDatabaseCount('assessments', 1);
        $this->assertSame($before, [$source->fresh()->toArray(), $source->catalogModules()->get()->toArray(), $source->periods()->get()->toArray(), $source->enrollments()->get()->toArray(), $challenge->fresh()->toArray()]);
        $this->assertSame($target->id, $member->fresh()->last_academic_year_id);
        $this->withHeader('X-Academic-Year', (string) $target->id)->get(route('groups.show', $copy))->assertInertia(fn (Assert $page) => $page->where('group.can_manage', true));
    }

    public function test_duplication_can_enroll_selected_active_students_reusing_their_accounts(): void
    {
        ['source' => $source, 'students' => $students] = $this->sourceGroup();
        $students[2]->update(['active' => false]);
        $passwords = array_map(fn (User $student) => $student->password, $students);
        $target = AcademicYear::factory()->create();
        $this->select($source->owner, $source->academicYear);
        $payload = ['academic_year_id' => $target->id, 'name' => 'Copia', 'user_ids' => []];

        $this->postJson(route('groups.duplicate', $source), [...$payload, 'student_ids' => [$students[0]->id, $students[2]->id]])->assertUnprocessable()->assertJsonValidationErrors('student_ids');
        $this->assertDatabaseCount('classrooms', 1);
        $this->post(route('groups.duplicate', $source), [...$payload, 'student_ids' => [$students[0]->id, $students[1]->id]])->assertSessionHasNoErrors();

        $copy = Classroom::where('academic_year_id', $target->id)->sole();
        $this->assertEqualsCanonicalizing([$students[0]->id, $students[1]->id], $copy->students()->pluck('users.id')->all());
        $this->assertSame(3, $source->students()->count());
        $this->assertSame($passwords, array_map(fn (User $student) => $student->fresh()->password, $students));
        $this->select($source->owner, $source->academicYear);
        $this->postJson(route('groups.duplicate', $source), [...$payload, 'student_ids' => []])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('classrooms', 2);
    }

    public function test_group_of_a_closed_year_can_be_duplicated_into_an_open_year_only(): void
    {
        ['source' => $source] = $this->sourceGroup();
        $source->academicYear->update(['is_open' => false]);
        $closedTarget = AcademicYear::factory()->create(['is_open' => false]);
        $target = AcademicYear::factory()->create();
        $this->select($source->owner, $source->academicYear);
        $this->postJson('/setup/group-periods', ['classroom_id' => $source->id, 'periods' => [['name' => 'Nueva']]])->assertForbidden();

        $this->postJson(route('groups.duplicate', $source), ['academic_year_id' => $closedTarget->id, 'name' => 'Copia', 'user_ids' => [], 'student_ids' => []])->assertUnprocessable()->assertJsonValidationErrors('academic_year_id');
        $this->post(route('groups.duplicate', $source), ['academic_year_id' => $target->id, 'name' => 'Copia', 'user_ids' => [], 'student_ids' => []])->assertSessionHasNoErrors();

        $this->assertSame(1, Classroom::where('academic_year_id', $target->id)->count());
        $this->assertSame(['Otoño', 'Primavera'], $source->periods()->pluck('name')->all());
    }
}
