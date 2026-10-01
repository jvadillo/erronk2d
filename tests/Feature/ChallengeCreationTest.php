<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChallengeCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{admin: User, classroom: Classroom, module: Module, rubric: Rubric, data: array<string, mixed>} */
    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $year = AcademicYear::create(['name' => 'Curso']);
        $classroom = $year->classrooms()->create(['name' => 'Grupo']);
        $period = $classroom->periods()->create(['name' => 'Primera', 'position' => 1]);
        $module = $this->moduleForClass($classroom, ['name' => 'Programación', 'code' => 'PROG'], $admin);
        $admin->forceFill(['last_academic_year_id' => $year->id])->save();
        $this->withHeader('X-Academic-Year', (string) $year->id);
        $items = [['key' => 'quality', 'name' => 'Calidad', 'weight' => '1', 'module_id' => null, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo']]]];
        $rubric = Rubric::create(['name' => 'Equipo', 'kind' => 'team', 'items' => $items]);
        $transversal = Rubric::create(['name' => 'Transversales', 'kind' => 'transversal', 'items' => $items]);
        $data = ['name' => 'Reto nuevo', 'description' => 'Texto que debe conservarse', 'classroom_id' => $classroom->id, 'period_id' => $period->id, 'module_ids' => [$module->id], 'team_rubric_id' => $rubric->id, 'transversal_rubric_id' => $transversal->id, 'weight' => '1', 'distribution_enabled' => false];

        return compact('admin', 'classroom', 'module', 'rubric', 'data');
    }

    public static function invalidCombinations(): array
    {
        return [
            'evaluation from another group in the same year' => ['period', 'period_id', 'La Evaluación debe pertenecer al grupo seleccionado.'],
            'module from another class' => ['module', 'module_ids', 'Los módulos deben pertenecer al grupo seleccionado.'],
            'retired module' => ['retired', 'module_ids', 'Los módulos deben pertenecer al grupo seleccionado.'],
            'team rubric with unselected module' => ['rubric_module', 'team_rubric_id', 'La rúbrica incluye criterios de módulos que no participan. Selecciona todos sus módulos o elige otra rúbrica.'],
            'wrong team rubric kind' => ['team_kind', 'team_rubric_id', 'Selecciona una rúbrica de equipo válida.'],
            'wrong transversal rubric kind' => ['transversal_kind', 'transversal_rubric_id', 'Selecciona una rúbrica transversal válida.'],
        ];
    }

    #[DataProvider('invalidCombinations')]
    public function test_invalid_combinations_return_to_form_with_input_and_no_writes(string $case, string $field, string $message): void
    {
        ['admin' => $admin, 'classroom' => $classroom, 'rubric' => $rubric, 'data' => $data] = $this->scenario();
        if ($case === 'period') {
            $data['period_id'] = Classroom::factory()->create(['academic_year_id' => $classroom->academic_year_id])->periods()->create(['name' => 'Otra', 'position' => 1])->id;
        } elseif ($case === 'module') {
            $other = $classroom->academicYear->classrooms()->create(['name' => 'Otra clase']);
            $data['module_ids'] = [$this->moduleForClass($other, ['name' => 'Otro', 'code' => 'OTRO'])->id];
        } elseif ($case === 'retired') {
            $classroom->catalogModules()->updateExistingPivot($data['module_ids'][0], ['ended_at' => now()]);
        } elseif ($case === 'rubric_module') {
            $otherModule = $this->moduleForClass($classroom, ['name' => 'Otro módulo', 'code' => 'OTRO']);
            $items = $rubric->items;
            $items[0]['module_id'] = $otherModule->id;
            $rubric->update(['items' => $items]);
        } elseif ($case === 'team_kind') {
            $data['team_rubric_id'] = $data['transversal_rubric_id'];
        } else {
            $data['transversal_rubric_id'] = $data['team_rubric_id'];
        }

        $this->actingAs($admin)->from('/')->withHeader('X-Inertia', 'true')->post('/challenges', $data)
            ->assertRedirect('/')->assertSessionHasErrors([$field => $message])
            ->assertSessionHasInput('name', $data['name'])->assertSessionHasInput('description', $data['description']);
        $this->assertDatabaseCount('challenges', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_valid_creation_copies_rubrics_and_enrolls_only_active_students(): void
    {
        ['admin' => $admin, 'classroom' => $classroom, 'module' => $module, 'rubric' => $rubric, 'data' => $data] = $this->scenario();
        $student = User::factory()->create(['role' => 'student']);
        $this->enrollInClass($student, $classroom);
        $this->enrollInClass(User::factory()->create(['role' => 'student', 'active' => false]), $classroom);
        $items = $rubric->items;
        $items[0]['module_id'] = $module->id;
        $rubric->update(['items' => $items]);

        $response = $this->actingAs($admin)->post('/challenges', $data);
        $challenge = $classroom->challenges()->firstOrFail();
        $response->assertRedirect('/challenges/'.$challenge->id);
        $this->assertSame([$student->id], $challenge->students()->pluck('users.id')->all());
        $this->assertSame($items, $challenge->team_rubric['items']);
        $this->assertDatabaseHas('audit_events', ['challenge_id' => $challenge->id, 'action' => 'create']);
    }

    public static function newRubricChoices(): array
    {
        return ['both new' => [true, true], 'new technical' => [true, false], 'new transversal' => [false, true]];
    }

    #[DataProvider('newRubricChoices')]
    public function test_creates_independent_empty_rubrics_without_library_entries(bool $newTeam, bool $newTransversal): void
    {
        ['admin' => $admin, 'classroom' => $classroom, 'data' => $data] = $this->scenario();
        $data['team_rubric_id'] = $newTeam ? null : $data['team_rubric_id'];
        $data['transversal_rubric_id'] = $newTransversal ? null : $data['transversal_rubric_id'];

        $response = $this->actingAs($admin)->post('/challenges', $data);

        $challenge = $classroom->challenges()->sole();
        $response->assertRedirect('/challenges/'.$challenge->id);
        $this->assertCount($newTeam ? 0 : 1, $challenge->team_rubric['items']);
        $this->assertCount($newTransversal ? 0 : 1, $challenge->transversal_rubric['items']);
        $this->assertNotEmpty($challenge->team_rubric['name']);
        $this->assertNotEmpty($challenge->transversal_rubric['name']);
        $this->assertDatabaseCount('rubrics', 2);
        $this->assertDatabaseHas('audit_events', ['challenge_id' => $challenge->id, 'action' => 'create']);
    }

    public function test_new_rubric_does_not_bypass_compatibility_of_the_other_rubric(): void
    {
        ['admin' => $admin, 'data' => $data] = $this->scenario();
        $data['team_rubric_id'] = null;
        Rubric::findOrFail($data['transversal_rubric_id'])->update(['cycle_id' => Cycle::factory()->create()->id, 'level' => 2]);

        $this->actingAs($admin)->postJson('/challenges', $data)->assertUnprocessable()
            ->assertJsonValidationErrors(['transversal_rubric_id' => 'La rúbrica debe corresponder al ciclo y nivel del grupo.']);
        $this->assertDatabaseCount('challenges', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_missing_rubric_choices_are_not_silently_treated_as_new(): void
    {
        ['admin' => $admin, 'data' => $data] = $this->scenario();
        unset($data['team_rubric_id'], $data['transversal_rubric_id']);

        $this->actingAs($admin)->postJson('/challenges', $data)->assertUnprocessable()
            ->assertJsonValidationErrors(['team_rubric_id', 'transversal_rubric_id']);
        $this->assertDatabaseCount('challenges', 0);
    }

    public function test_student_cannot_create_challenges(): void
    {
        ['data' => $data] = $this->scenario();
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->postJson('/challenges', $data)->assertForbidden();
        $this->assertDatabaseCount('challenges', 0);
    }
}
