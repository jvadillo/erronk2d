<?php

namespace Tests\Feature;

use App\Domain\Grades\Gradebook;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\ModuleGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChallengeParticipantsTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $group;

    private User $creator;

    private User $colleague;

    private User $bystander;

    /** @var list<Module> */
    private array $modules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->group = Classroom::factory()->create();
        $this->group->periods()->create(['name' => 'Primera', 'position' => 1]);
        $this->modules = [$this->moduleForClass($this->group, ['code' => 'PROG']), $this->moduleForClass($this->group, ['code' => 'BD']), $this->moduleForClass($this->group, ['code' => 'ED'])];
        [$this->creator, $this->colleague, $this->bystander] = User::factory()->count(3)->create(['role' => 'teacher'])->all();
        $this->group->users()->attach([$this->creator->id, $this->colleague->id, $this->bystander->id]);
        $this->enrollInClass(User::factory()->create(), $this->group);
        $this->withHeader('X-Academic-Year', (string) $this->group->academic_year_id);
    }

    private function create(array $teacherIds): Challenge
    {
        $this->actingAs($this->creator)->post('/challenges', ['name' => 'Reto', 'classroom_id' => $this->group->id, 'period_id' => $this->group->periods()->first()->id, 'module_ids' => [$this->modules[0]->id, $this->modules[1]->id], 'teacher_ids' => $teacherIds, 'team_rubric_id' => null, 'transversal_rubric_id' => null, 'weight' => '1', 'distribution_enabled' => false])->assertSessionHasNoErrors()->assertRedirect();

        return $this->group->challenges()->sole();
    }

    private function change(User $actor, Challenge $challenge, array $data)
    {
        return $this->actingAs($actor)->postJson(route('challenges.update', $challenge), ['revision' => $challenge->fresh()->revision, ...$data]);
    }

    public function test_creator_and_selected_teachers_participate_and_other_group_teachers_do_not_see_the_challenge(): void
    {
        $outsider = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($this->creator)->postJson('/challenges', ['name' => 'Reto', 'classroom_id' => $this->group->id, 'period_id' => $this->group->periods()->first()->id, 'module_ids' => [$this->modules[0]->id], 'teacher_ids' => [$outsider->id], 'team_rubric_id' => null, 'transversal_rubric_id' => null, 'weight' => '1', 'distribution_enabled' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('teacher_ids');
        $challenge = $this->create([$this->colleague->id]);

        $this->assertEqualsCanonicalizing([$this->creator->id, $this->colleague->id], $challenge->teachers()->pluck('users.id')->all());
        $this->get(route('challenges.show', $challenge))->assertInertia(fn (Assert $page) => $page->has('book.teachers', 2)->has('groupTeachers', 4)->has('groupModules', 3));
        $this->actingAs($this->colleague)->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 1));
        foreach ([$this->bystander, $this->group->owner] as $teacher) {
            $this->actingAs($teacher)->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 0));
            $this->get(route('challenges.show', $challenge))->assertNotFound();
            $this->get('/reports?classroom='.$this->group->id)->assertInertia(fn (Assert $page) => $page->where('report.partial', true));
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('challenges.show', $challenge))->assertOk();
    }

    public function test_participants_add_colleagues_who_can_then_grade_but_only_admin_withdraws_them(): void
    {
        $challenge = $this->create([]);
        $this->change($this->bystander, $challenge, ['action' => 'teachers', 'teacher_ids' => [$this->creator->id, $this->bystander->id]])->assertNotFound();
        $this->change($this->creator, $challenge, ['action' => 'teachers', 'teacher_ids' => [$this->creator->id, User::factory()->create(['role' => 'teacher'])->id]])->assertUnprocessable()->assertJsonValidationErrors('teacher_ids');

        $this->change($this->creator, $challenge, ['action' => 'teachers', 'teacher_ids' => [$this->creator->id, $this->colleague->id]])->assertOk();
        $this->change($this->colleague, $challenge, ['action' => 'teachers', 'teacher_ids' => [$this->colleague->id, $this->bystander->id]])->assertUnprocessable()->assertJsonValidationErrors(['teacher_ids' => 'Solo administración puede retirar profesores de un reto.']);
        $student = $challenge->students()->first();
        $this->change($this->colleague, $challenge, ['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[1]->id, 'entries' => [['student_id' => $student->id, 'value' => '7']]])->assertOk();
        $this->assertSame($this->colleague->id, ModuleGrade::sole()->updated_by);

        $this->change(User::factory()->create(['role' => 'admin']), $challenge, ['action' => 'teachers', 'teacher_ids' => [$this->creator->id]])->assertOk();
        $this->assertSame([$this->creator->id], $challenge->teachers()->pluck('users.id')->all());
        $this->actingAs($this->colleague)->get(route('challenges.show', $challenge))->assertNotFound();
        $this->assertSame($this->colleague->id, ModuleGrade::sole()->updated_by);
    }

    public function test_participants_add_group_modules_and_remove_only_modules_without_grades_or_criteria(): void
    {
        $challenge = $this->create([$this->colleague->id]);
        $other = Module::factory()->create();
        [$prog, $bd, $ed] = $this->modules;
        $this->change($this->colleague, $challenge, ['action' => 'modules', 'module_ids' => [$prog->id, $bd->id, $other->id]])->assertUnprocessable()->assertJsonValidationErrors('module_ids');

        $this->change($this->colleague, $challenge, ['action' => 'modules', 'module_ids' => [$prog->id, $bd->id, $ed->id]])->assertOk();
        $this->assertEqualsCanonicalizing([$prog->id, $bd->id, $ed->id], $challenge->modules()->pluck('modules.id')->all());
        $this->assertSame($ed->code, $challenge->fresh()->catalog_snapshot['modules'][$ed->id]['code']);
        $student = $challenge->students()->first();
        $this->change($this->colleague, $challenge, ['action' => 'grades', 'field' => 'exam', 'module_id' => $prog->id, 'entries' => [['student_id' => $student->id, 'value' => '6']]])->assertOk();
        $challenge->update(['team_rubric' => ['name' => 'Equipo', 'items' => [['key' => 'bd', 'name' => 'Datos', 'weight' => '1', 'module_id' => $bd->id, 'levels' => [['score' => '5', 'description' => 'Base'], ['score' => '10', 'description' => 'Completo']]]]]]);

        $this->change($this->creator, $challenge, ['action' => 'modules', 'module_ids' => [$bd->id, $ed->id]])->assertUnprocessable()->assertJsonValidationErrors(['module_ids' => 'PROG ya tiene notas en este reto y no se puede retirar.']);
        $this->change($this->creator, $challenge, ['action' => 'modules', 'module_ids' => [$prog->id, $ed->id]])->assertUnprocessable()->assertJsonValidationErrors('module_ids');
        $this->change($this->creator, $challenge, ['action' => 'modules', 'module_ids' => []])->assertUnprocessable();
        $this->change($this->creator, $challenge, ['action' => 'modules', 'module_ids' => [$prog->id, $bd->id]])->assertOk();

        $this->assertEqualsCanonicalizing([$prog->id, $bd->id], $challenge->modules()->pluck('modules.id')->all());
        $this->assertArrayNotHasKey($ed->id, $challenge->fresh()->catalog_snapshot['modules']);
        $this->assertSame(['PROG', 'BD'], collect(app(Gradebook::class)->challenge($challenge->fresh())['modules'])->pluck('code')->sortDesc()->values()->all());
    }
}
