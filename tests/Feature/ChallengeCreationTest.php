<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
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
        $period = $year->periods()->create(['name' => 'Primera', 'position' => 1]);
        $classroom = $year->classrooms()->create(['name' => 'Clase']);
        $module = $classroom->modules()->create(['name' => 'Programación', 'code' => 'PROG']);
        $module->teachers()->attach($admin);
        $items = [['key' => 'quality', 'name' => 'Calidad', 'weight' => '1', 'module_id' => null, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo']]]];
        $rubric = Rubric::create(['name' => 'Equipo', 'kind' => 'team', 'items' => $items]);
        $transversal = Rubric::create(['name' => 'Transversales', 'kind' => 'transversal', 'items' => $items]);
        $data = ['name' => 'Reto nuevo', 'description' => 'Texto que debe conservarse', 'classroom_id' => $classroom->id, 'period_id' => $period->id, 'module_ids' => [$module->id], 'team_rubric_id' => $rubric->id, 'transversal_rubric_id' => $transversal->id, 'weight' => '1', 'distribution_enabled' => false];

        return compact('admin', 'classroom', 'module', 'rubric', 'data');
    }

    public static function invalidCombinations(): array
    {
        return [
            'evaluation from another year' => ['period', 'period_id', 'La Evaluación debe pertenecer al curso de la clase.'],
            'module from another class' => ['module', 'module_ids', 'Los módulos deben pertenecer a la clase seleccionada.'],
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
            $year = AcademicYear::create(['name' => 'Otro curso']);
            $data['period_id'] = $year->periods()->create(['name' => 'Otra', 'position' => 1])->id;
        } elseif ($case === 'module') {
            $other = $classroom->academicYear->classrooms()->create(['name' => 'Otra clase']);
            $data['module_ids'] = [$other->modules()->create(['name' => 'Otro', 'code' => 'OTRO'])->id];
        } elseif ($case === 'rubric_module') {
            $otherModule = $classroom->modules()->create(['name' => 'Otro módulo', 'code' => 'OTRO']);
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
        $student = User::factory()->create(['role' => 'student', 'classroom_id' => $classroom->id]);
        User::factory()->create(['role' => 'student', 'classroom_id' => $classroom->id, 'active' => false]);
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

    public function test_student_cannot_create_challenges(): void
    {
        ['data' => $data] = $this->scenario();
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->postJson('/challenges', $data)->assertForbidden();
        $this->assertDatabaseCount('challenges', 0);
    }
}
