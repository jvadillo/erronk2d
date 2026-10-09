<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Cycle;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RubricEditorTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $levels = [['score' => '4', 'description' => 'Inicial'], ['score' => '6', 'description' => 'En desarrollo'], ['score' => '8', 'description' => 'Autónomo'], ['score' => '10', 'description' => 'Excelente']];

        return ['name' => 'Rúbrica de proyecto', 'kind' => 'team', 'cycle_id' => null, 'level' => null, 'items' => [
            ['key' => 'quality', 'name' => 'Calidad', 'description' => 'Descripción opcional', 'module_id' => null, 'weight' => '33.33', 'levels' => $levels],
            ['key' => 'delivery', 'name' => 'Entrega', 'description' => '', 'module_id' => null, 'weight' => '66.67', 'levels' => $levels],
        ]];
    }

    public function test_editor_requires_authentication_and_a_teaching_role(): void
    {
        $this->get(route('rubrics.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'student']))->get(route('rubrics.create'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'teacher']))->get(route('rubrics.create'))
            ->assertInertia(fn (Assert $page) => $page->component('RubricEditor')->where('rubric', null)->where('saveUrl', route('setup.store', ['entity' => 'rubric'])));
    }

    public function test_editor_respects_ownership_and_sharing(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $reader = User::factory()->create(['role' => 'teacher']);
        $rubric = Rubric::create([...$this->payload(), 'owner_id' => $owner->id]);
        $url = route('rubrics.edit', $rubric->id);

        $this->actingAs($reader)->get($url)->assertNotFound();
        $rubric->sharedUsers()->attach($reader);
        $this->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertInertia(fn (Assert $page) => $page->component('RubricEditor')->where('rubric.id', $rubric->id));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get($url)->assertOk();
    }

    public function test_creates_a_matrix_with_exact_percentages_and_shared_columns(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $reader = User::factory()->create(['role' => 'teacher']);
        $data = [...$this->payload(), 'shared_user_ids' => [$reader->id], 'owner_id' => $reader->id];

        $this->actingAs($owner)->post(route('setup.store', ['entity' => 'rubric']), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('setup.section', ['section' => 'rubrics']));

        $rubric = Rubric::firstOrFail();
        $this->assertSame($owner->id, $rubric->owner_id);
        $this->assertSame('33.33', $rubric->items[0]['weight']);
        $this->assertSame($data['items'][0]['levels'], $rubric->items[0]['levels']);
        $this->assertSame($data['items'][1]['levels'], $rubric->items[1]['levels']);
        $this->assertSame([$reader->id], $rubric->sharedUsers()->pluck('users.id')->all());
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidMatrices(): array
    {
        return [
            'different number of levels' => ['count', 'items.1.levels', 'Todos los criterios deben tener los mismos niveles y la misma nota en cada columna.'],
            'different column score' => ['score', 'items.1.levels', 'Todos los criterios deben tener los mismos niveles y la misma nota en cada columna.'],
            'percentage below 100' => ['under', 'items', 'Los pesos de los criterios deben sumar exactamente 100 %.'],
            'percentage above 100' => ['over', 'items', 'Los pesos de los criterios deben sumar exactamente 100 %.'],
        ];
    }

    #[DataProvider('invalidMatrices')]
    public function test_invalid_matrix_returns_errors_without_writes(string $case, string $field, string $message): void
    {
        $data = $this->payload();
        if ($case === 'count') {
            array_pop($data['items'][1]['levels']);
        } elseif ($case === 'score') {
            $data['items'][1]['levels'][0]['score'] = '5';
        } else {
            $data['items'][1]['weight'] = $case === 'under' ? '66.66' : '66.68';
        }

        $this->actingAs(User::factory()->create(['role' => 'teacher']))->post('/setup/rubric', $data)
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('rubrics', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_editing_a_template_preserves_existing_challenge_rubrics_and_assessments(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $rubric = Rubric::create([...$this->payload(), 'owner_id' => $owner->id]);
        $snapshot = ['name' => $rubric->name, 'items' => $rubric->items];
        $challenge = Challenge::factory()->create(['team_rubric' => $snapshot]);
        $team = $challenge->teams()->create(['name' => 'Equipo']);
        $assessment = $challenge->assessments()->create(['kind' => 'team', 'subject_id' => $team->id, 'scope_id' => 0, 'criterion' => 'quality', 'level' => 1, 'updated_by' => $owner->id]);
        $data = $this->payload();
        foreach ($data['items'] as &$item) {
            $item['levels'][] = ['score' => '10', 'description' => 'Nivel adicional'];
        }
        unset($item);

        $this->actingAs($owner)->post('/setup/rubric', ['id' => $rubric->id, ...$data])->assertSessionHasNoErrors();

        $this->assertCount(5, $rubric->fresh()->items[0]['levels']);
        $this->assertSame($snapshot, $challenge->fresh()->team_rubric);
        $this->assertSame(1, $assessment->fresh()->level);
    }

    public function test_rubric_linked_to_a_challenge_is_only_edited_from_that_challenge(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $rubric = Rubric::create([...$this->payload(), 'owner_id' => $owner->id]);
        $challenge = Challenge::factory()->create(['team_rubric' => ['name' => $rubric->name, 'items' => $rubric->items], 'team_rubric_id' => $rubric->id]);
        $editor = route('challenges.rubrics.edit', ['challenge' => $challenge, 'kind' => 'team']);

        $this->actingAs($owner)->get('/setup/rubrics')->assertInertia(fn (Assert $page) => $page->where('rubrics.0.challenge', ['name' => $challenge->name, 'editor_url' => $editor]));
        $this->get(route('rubrics.edit', $rubric->id))->assertRedirect($editor);
        $this->post('/setup/rubric', ['id' => $rubric->id, ...$this->payload(), 'name' => 'Cambio'])->assertSessionHasErrors('name');
        $this->assertSame('Rúbrica de proyecto', $rubric->fresh()->name);

        $this->post('/setup/rubric-copy', ['id' => $rubric->id])->assertSessionHasNoErrors();
        $copy = Rubric::whereKeyNot($rubric->id)->sole();
        $this->assertNull($copy->linkedChallenge());
        $this->post('/setup/rubric', ['id' => $copy->id, ...$this->payload(), 'name' => 'Copia editable'])->assertSessionHasNoErrors();
    }

    public function test_team_module_must_match_cycle_and_level(): void
    {
        $data = $this->payload();
        $data['cycle_id'] = Cycle::factory()->create()->id;
        $data['level'] = 1;
        $data['items'][0]['module_id'] = Module::factory()->create()->id;

        $this->actingAs(User::factory()->create(['role' => 'teacher']))->post('/setup/rubric', $data)->assertSessionHasErrors('items');

        $this->assertDatabaseCount('rubrics', 0);
    }
}
