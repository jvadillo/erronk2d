<?php

namespace Tests\Feature;

use App\Domain\Grades\ChallengeRubricEditor;
use App\Models\Assessment;
use App\Models\AuditEvent;
use App\Models\Challenge;
use App\Models\Publication;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChallengeRubricEditingTest extends TestCase
{
    use RefreshDatabase;

    private Challenge $challenge;

    private User $teacher;

    private array $teams;

    private array $students;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $levels = [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo'], ['score' => '10', 'description' => 'Excelente']];
        $rubric = ['name' => 'Proyecto', 'items' => [
            ['key' => 'quality', 'name' => 'Calidad', 'description' => 'Evidencias de calidad', 'module_id' => null, 'weight' => '50', 'levels' => $levels],
            ['key' => 'delivery', 'name' => 'Entrega', 'description' => 'Evidencias de entrega', 'module_id' => null, 'weight' => '50', 'levels' => $levels],
        ]];
        $this->challenge = Challenge::factory()->create(['team_rubric' => $rubric, 'transversal_rubric' => $rubric, 'status' => 'evaluating', 'distribution_enabled' => true]);
        $class = $this->challenge->classroom;
        $class->users()->attach($this->teacher);
        $responsible = User::factory()->create(['role' => 'teacher']);
        $module = $this->moduleForClass($class, [], $responsible);
        $this->challenge->modules()->attach($module);
        $this->students = User::factory()->count(4)->create()->all();
        $this->challenge->students()->attach(array_column($this->students, 'id'));
        $this->teams = [];
        foreach (array_chunk($this->students, 2) as $index => $students) {
            $team = $this->challenge->teams()->create(['name' => 'Equipo '.($index + 1)]);
            foreach ($students as $student) {
                $this->enrollInClass($student, $class);
                $team->memberships()->create(['challenge_id' => $this->challenge->id, 'student_id' => $student->id, 'allocation' => '10']);
            }
            $this->teams[] = $team;
        }
        $this->withHeader('X-Academic-Year', (string) $class->academic_year_id)->actingAs($this->teacher);
    }

    private function payload(string $kind = 'team'): array
    {
        $rubric = $this->challenge->fresh()->{$kind === 'team' ? 'team_rubric' : 'transversal_rubric'};
        foreach ($rubric['items'] as &$item) {
            foreach ($item['levels'] as $index => &$level) {
                $level['source_index'] = $index;
            }
            unset($level);
        }
        unset($item);

        return ['action' => 'rubric', 'kind' => $kind, 'revision' => $this->challenge->fresh()->revision, 'rubric' => $rubric, 'reason' => null];
    }

    private function preview(array $input): TestResponse
    {
        return $this->postJson(route('challenges.rubrics.preview', [$this->challenge, $input['kind']]), $input);
    }

    private function save(array $input, ?string $token): TestResponse
    {
        return $this->postJson(route('challenges.update', $this->challenge), [...$input, 'preview_token' => $token]);
    }

    private function assessment(string $kind, int $subject, string $criterion, int $level, int $scope = 0): Assessment
    {
        return $this->challenge->assessments()->create(['kind' => $kind, 'subject_id' => $subject, 'scope_id' => $scope, 'criterion' => $criterion, 'level' => $level, 'updated_by' => $this->teacher->id]);
    }

    public function test_group_teacher_can_edit_both_rubrics_without_module_responsibility(): void
    {
        foreach (['team', 'transversal'] as $kind) {
            $this->get(route('challenges.rubrics.edit', [$this->challenge, $kind]))->assertInertia(fn (Assert $page) => $page
                ->component('RubricEditor')->where('challengeContext.kind', $kind)->where('rubric.items.0.levels.2.source_index', 2)->has('modules', 1));
            $input = $this->payload($kind);
            $input['rubric']['name'] = 'Edición '.$kind;
            $token = $this->preview($input)->assertOk()->json('token');
            $this->save($input, $token)->assertOk();
            $this->assertSame('Edición '.$kind, $this->challenge->fresh()->{$kind === 'team' ? 'team_rubric' : 'transversal_rubric'}['name']);
        }
    }

    public static function rubricKinds(): array
    {
        return ['technical' => ['team', 'team_rubric'], 'transversal' => ['transversal', 'transversal_rubric']];
    }

    #[DataProvider('rubricKinds')]
    public function test_empty_rubric_can_receive_first_criteria_with_frozen_challenge_context(string $kind, string $field): void
    {
        $input = $this->payload($kind);
        foreach ($input['rubric']['items'] as &$item) {
            foreach ($item['levels'] as &$level) {
                $level['source_index'] = null;
            }
            unset($level);
        }
        unset($item);
        $this->challenge->update([$field => ['name' => 'Nueva rúbrica', 'items' => []], 'catalog_snapshot' => ['cycle' => 'Ciclo del reto', 'level' => 2]]);
        $this->challenge->classroom->update(['cycle_name' => 'Nombre posterior', 'level' => 1]);
        $otherField = $kind === 'team' ? 'transversal_rubric' : 'team_rubric';
        $otherRubric = $this->challenge->$otherField;

        $this->get(route('challenges.rubrics.edit', [$this->challenge, $kind]))->assertInertia(fn (Assert $page) => $page
            ->component('RubricEditor')->where('rubric.items', [])->where('rubric.kind', $kind)
            ->where('challengeContext.cycle', 'Ciclo del reto')->where('challengeContext.level', 2));
        $token = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 0)->json('token');
        $response = $this->save($input, $token)->assertOk()->assertJsonPath('book.complete', false);

        $this->assertCount(2, $this->challenge->fresh()->$field['items']);
        $this->assertSame($otherRubric, $this->challenge->fresh()->$otherField);
        $this->assertDatabaseCount('assessments', 0);
        $this->assertNull($response->json($kind === 'team' ? 'book.teams.0.grade' : 'book.rows.0.transversal'));
        $this->assertDatabaseHas('audit_events', ['challenge_id' => $this->challenge->id, 'action' => 'rubric']);
    }

    public function test_rubric_created_with_the_challenge_is_kept_in_its_author_library(): void
    {
        $items = $this->challenge->team_rubric['items'];
        $this->challenge->update(['team_rubric' => ['name' => ChallengeRubricEditor::DEFAULT_NAMES['team'], 'items' => []]]);
        $this->get(route('challenges.rubrics.edit', [$this->challenge, 'team']))->assertInertia(fn (Assert $page) => $page->where('challengeContext.savesToLibrary', true));
        $input = $this->payload();
        $input['rubric']['items'] = array_map(fn (array $item) => [...$item, 'levels' => array_map(fn (array $level) => [...$level, 'source_index' => null], $item['levels'])], $items);

        $this->save($input, $this->preview($input)->json('token'))->assertOk();

        $library = Rubric::sole();
        $this->assertSame([$this->teacher->id, 'team', 'Rúbrica técnica · '.$this->challenge->name, $items], [$library->owner_id, $library->kind, $library->name, $library->items]);
        $this->assertSame($library->id, $this->challenge->fresh()->team_rubric_id);

        $input = $this->payload();
        $input['rubric']['items'][0]['name'] = 'Calidad revisada';
        $this->save($input, $this->preview($input)->json('token'))->assertOk();
        $this->assertSame('Calidad revisada', Rubric::sole()->items[0]['name']);

        $colleague = User::factory()->create(['role' => 'teacher']);
        $this->challenge->classroom->users()->attach($colleague);
        $this->actingAs($colleague);
        $input = $this->payload();
        $input['rubric']['items'][0]['name'] = 'Cambio de otra persona';
        $this->save($input, $this->preview($input)->json('token'))->assertOk();
        $this->assertSame('Cambio de otra persona', $this->challenge->fresh()->team_rubric['items'][0]['name']);
        $this->assertSame(['Cambio de otra persona', $this->teacher->id], [Rubric::sole()->items[0]['name'], Rubric::sole()->owner_id]);
    }

    public static function assessmentKinds(): array
    {
        return ['team' => ['team'], 'teacher' => ['teacher'], 'self' => ['self'], 'peer' => ['peer']];
    }

    #[DataProvider('assessmentKinds')]
    public function test_empty_rubric_rejects_assessments_without_writes(string $kind): void
    {
        $this->challenge->update(['team_rubric' => ['name' => 'Nueva', 'items' => []], 'transversal_rubric' => ['name' => 'Nueva', 'items' => []]]);
        $actor = in_array($kind, ['self', 'peer'], true) ? $this->students[0] : $this->teacher;
        $subject = $kind === 'team' ? $this->teams[0]->id : ($kind === 'peer' ? $this->students[1]->id : $this->students[0]->id);

        $this->actingAs($actor)->postJson(route('challenges.update', $this->challenge), [
            'revision' => 1, 'action' => 'assess', 'kind' => $kind,
            'entries' => [['subject_id' => $subject, 'criterion' => 'quality', 'level' => 0]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['entries' => 'La rúbrica no contiene todavía criterios. Debe completarse antes de evaluar.']);

        $this->assertSame(1, $this->challenge->fresh()->revision);
        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_empty_rubrics_keep_results_pending_and_block_allocation_finish_and_publish(): void
    {
        $this->challenge->update(['team_rubric' => ['name' => 'Nueva', 'items' => []], 'transversal_rubric' => ['name' => 'Nueva', 'items' => []]]);
        $this->get(route('challenges.show', $this->challenge))->assertInertia(fn (Assert $page) => $page
            ->where('book.complete', false)->where('book.teams.0.grade', null)->where('book.teams.0.points', null)
            ->where('book.teams.0.distribution_valid', false)->where('book.rows.0.transversal', null)->where('book.rows.0.challenge_final', null));
        $this->postJson(route('challenges.update', $this->challenge), [
            'revision' => 1, 'action' => 'allocation', 'team_id' => $this->teams[0]->id,
            'allocations' => [$this->students[0]->id => '0', $this->students[1]->id => '0'],
        ])->assertUnprocessable()->assertJsonValidationErrors('allocations');
        $this->postJson(route('challenges.update', $this->challenge), ['revision' => 1, 'action' => 'status', 'status' => 'finished'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->postJson(route('challenges.update', $this->challenge), ['revision' => 1, 'action' => 'publish'])
            ->assertUnprocessable()->assertJsonValidationErrors('action');
        $this->assertSame(1, $this->challenge->fresh()->revision);
        $this->assertSame('evaluating', $this->challenge->fresh()->status);
        $this->assertDatabaseCount('publications', 0);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertSame('10.0000', $this->teams[0]->memberships()->first()->allocation);
    }

    public function test_rubric_scores_reject_more_than_two_decimal_places(): void
    {
        $input = $this->payload();
        $input['rubric']['items'][0]['levels'][0]['score'] = '4.001';

        $this->preview($input)->assertUnprocessable()->assertJsonValidationErrors('rubric.items.0.levels.0.score');
    }

    public function test_preview_is_read_only_and_deleting_a_middle_level_preserves_other_selections_and_history(): void
    {
        $deleted = $this->assessment('team', $this->teams[0]->id, 'quality', 1);
        $kept = $this->assessment('team', $this->teams[1]->id, 'quality', 2);
        $otherRubric = $this->assessment('teacher', $this->students[0]->id, 'quality', 1);
        $input = $this->payload();
        $original = $this->challenge->team_rubric;
        foreach ($input['rubric']['items'] as &$item) {
            array_splice($item['levels'], 1, 1);
        }
        unset($item);

        $preview = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 1)->assertJsonPath('impact.affected_subjects', ['Equipo 1']);
        $this->assertModelExists($deleted);
        $this->assertSame($original, $this->challenge->fresh()->team_rubric);
        $this->assertDatabaseCount('audit_events', 0);
        $this->save($input, $preview->json('token'))->assertOk();

        $this->assertModelMissing($deleted);
        $this->assertSame(1, $kept->fresh()->level);
        $this->assertSame($kept->updated_by, $kept->fresh()->updated_by);
        $this->assertTrue($kept->updated_at->equalTo($kept->fresh()->updated_at));
        $this->assertSame(1, $otherRubric->fresh()->level);
        $this->assertSame('10', $this->challenge->fresh()->team_rubric['items'][0]['levels'][1]['score']);
        $event = AuditEvent::where('action', 'rubric')->sole();
        $this->assertSame($original, $event->before['challenge']['team_rubric']);
        $this->assertCount(3, $event->before['assessments']);
        $this->assertCount(2, $event->after['assessments']);
    }

    public function test_deleting_transversal_criterion_removes_teacher_self_and_peer_only_for_that_criterion(): void
    {
        $deleted = [];
        foreach (['teacher' => 0, 'self' => $this->students[0]->id, 'peer' => $this->students[1]->id] as $kind => $scope) {
            $deleted[] = $this->assessment($kind, $this->students[0]->id, 'quality', 1, $scope);
            $this->assessment($kind, $this->students[0]->id, 'delivery', 2, $scope);
        }
        $team = $this->assessment('team', $this->teams[0]->id, 'quality', 1);
        $input = $this->payload('transversal');
        array_shift($input['rubric']['items']);
        $input['rubric']['items'][0]['weight'] = '100';
        $preview = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 3)
            ->assertJsonPath('impact.removed_by_kind', ['teacher' => 1, 'self' => 1, 'peer' => 1]);

        $this->save($input, $preview->json('token'))->assertOk();

        foreach ($deleted as $assessment) {
            $this->assertModelMissing($assessment);
        }
        $this->assertModelExists($team);
        $this->assertSame(3, $this->challenge->assessments()->where('criterion', 'delivery')->count());
    }

    public function test_score_changes_preserve_selections_and_warn_about_invalid_allocations(): void
    {
        foreach ($this->teams as $team) {
            foreach (['quality', 'delivery'] as $criterion) {
                $this->assessment('team', $team->id, $criterion, 2);
            }
        }
        $input = $this->payload();
        foreach ($input['rubric']['items'] as &$item) {
            $item['levels'][2]['score'] = '8';
        }
        unset($item);
        $preview = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 0)
            ->assertJsonPath('impact.invalid_allocations', ['Equipo 1', 'Equipo 2']);

        $response = $this->save($input, $preview->json('token'))->assertOk();

        $response->assertJsonPath('book.teams.0.grade', '8.0000')->assertJsonPath('book.teams.0.distribution_valid', false);
        $this->assertSame(4, $this->challenge->assessments()->where('level', 2)->count());
        $this->assertSame(4, $this->challenge->memberships()->where('allocation', '10')->count());
    }

    public function test_reordering_and_adding_levels_preserves_level_identity(): void
    {
        $kept = $this->assessment('team', $this->teams[0]->id, 'quality', 2);
        $input = $this->payload();
        $input['rubric']['items'] = array_reverse($input['rubric']['items']);
        foreach ($input['rubric']['items'] as &$item) {
            $item['levels'] = array_reverse($item['levels']);
            array_unshift($item['levels'], ['score' => '0', 'description' => 'Sin evidencia', 'source_index' => null]);
        }
        unset($item);
        $token = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 0)->json('token');

        $this->save($input, $token)->assertOk();

        $this->assertSame(1, $kept->fresh()->level);
        $this->assertSame('10', $this->challenge->fresh()->team_rubric['items'][1]['levels'][1]['score']);
    }

    public function test_confirmation_is_bound_to_reviewed_content_and_revision(): void
    {
        $input = $this->payload();
        $input['rubric']['name'] = 'Nuevo nombre';
        $token = $this->preview($input)->assertOk()->json('token');
        $changed = $input;
        $changed['rubric']['items'][0]['levels'][0]['score'] = '5';
        $this->save($input, null)->assertConflict();
        $this->save($changed, $token)->assertConflict();
        $this->assertDatabaseCount('audit_events', 0);
        $this->assessment('team', $this->teams[0]->id, 'quality', 0);
        $this->challenge->increment('revision');

        $this->save($input, $token)->assertConflict();
        $this->preview($input)->assertConflict();

        $this->assertSame('Proyecto', $this->challenge->fresh()->team_rubric['name']);
        $this->assertDatabaseCount('assessments', 1);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_new_criterion_becomes_pending_and_old_evaluation_tabs_cannot_save_shifted_levels(): void
    {
        $input = $this->payload();
        $originalRevision = $input['revision'];
        $kept = $this->assessment('team', $this->teams[0]->id, 'quality', 2);
        $this->assessment('team', $this->teams[0]->id, 'delivery', 2);
        $input['rubric']['items'][0]['weight'] = '40';
        $input['rubric']['items'][1]['weight'] = '40';
        $new = $input['rubric']['items'][0];
        $new['key'] = 'new_criterion';
        $new['name'] = 'Nuevo criterio';
        $new['weight'] = '20';
        foreach ($new['levels'] as &$level) {
            $level['source_index'] = null;
        }
        unset($level);
        $input['rubric']['items'][] = $new;
        $token = $this->preview($input)->assertOk()->assertJsonPath('impact.removed_assessments', 0)->json('token');

        $this->save($input, $token)->assertOk()->assertJsonPath('book.teams.0.grade', null);

        $this->assertSame(2, $kept->fresh()->level);
        $this->assertSame(2, $this->challenge->assessments()->count());
        $this->postJson(route('challenges.update', $this->challenge), ['action' => 'assess', 'revision' => $originalRevision, 'kind' => 'team', 'entries' => [
            ['subject_id' => $this->teams[0]->id, 'criterion' => 'quality', 'level' => 0],
        ]])->assertConflict();
        $this->assertSame(2, $kept->fresh()->level);
    }

    public function test_edit_requires_reopening_preserves_publication_and_requires_a_reason(): void
    {
        $snapshot = ['challenge' => ['team_rubric' => $this->challenge->team_rubric], 'rows' => [['id' => 1, 'grade' => '8']]];
        $publication = Publication::create(['challenge_id' => $this->challenge->id, 'version' => 1, 'snapshot' => $snapshot, 'published_by' => $this->teacher->id]);
        $this->challenge->update(['status' => 'published']);
        $input = $this->payload();
        $input['reason'] = 'Corregir la descripción';
        $this->get(route('challenges.rubrics.edit', [$this->challenge, 'team']))->assertUnprocessable();
        $this->preview($input)->assertUnprocessable();
        $this->save($input, null)->assertUnprocessable();
        $this->postJson(route('challenges.update', $this->challenge), ['action' => 'reopen', 'revision' => $this->challenge->fresh()->revision, 'reason' => 'Corregir rúbrica'])->assertOk();
        $input = $this->payload();
        $this->preview($input)->assertUnprocessable()->assertJsonValidationErrors('reason');
        $input['reason'] = 'Corregir la descripción';
        $input['rubric']['items'][0]['description'] = 'Descripción corregida';
        $token = $this->preview($input)->assertOk()->json('token');

        $this->save($input, $token)->assertOk();

        $this->assertSame($snapshot, $publication->fresh()->snapshot);
        $this->assertDatabaseHas('audit_events', ['action' => 'rubric', 'reason' => 'Corregir la descripción']);
    }

    public function test_preserves_legacy_scores_weights_library_and_other_challenges(): void
    {
        $rubric = $this->challenge->team_rubric;
        $rubric['items'][0]['weight'] = '1';
        $rubric['items'][1]['weight'] = '3';
        $rubric['items'][1]['levels'][1]['score'] = '6';
        array_pop($rubric['items'][1]['levels']);
        $this->challenge->update(['team_rubric' => $rubric]);
        $library = Rubric::create([...$rubric, 'kind' => 'team', 'owner_id' => $this->teacher->id]);
        $other = Challenge::factory()->create(['classroom_id' => $this->challenge->classroom_id, 'period_id' => $this->challenge->period_id, 'team_rubric' => $rubric]);
        $input = $this->payload();
        $input['rubric']['name'] = 'Nombre corregido';
        $token = $this->preview($input)->assertOk()->json('token');

        $this->save($input, $token)->assertOk();

        $this->assertSame($rubric['items'], $this->challenge->fresh()->team_rubric['items']);
        $this->assertSame($rubric['items'], $library->fresh()->items);
        $this->assertSame($rubric, $other->fresh()->team_rubric);
        $this->assertDatabaseCount('rubrics', 1);
        $this->assertNull($this->challenge->fresh()->team_rubric_id);
    }

    public static function invalidChanges(): array
    {
        return [
            'missing level origins' => ['rubric.items.0.levels.0.source_index', 'missing'],
            'repeated origin' => ['rubric.items.0.levels.1.source_index', 0],
            'unknown origin' => ['rubric.items.0.levels.0.source_index', 19],
            'wrong total weight' => ['rubric.items.0.weight', '45'],
            'foreign module' => ['rubric.items.0.module_id', 999999],
            'duplicate criterion' => ['rubric.items.1.key', 'quality'],
            'out of range score' => ['rubric.items.0.levels.0.score', '11'],
        ];
    }

    #[DataProvider('invalidChanges')]
    public function test_invalid_changes_leave_all_data_untouched(string $path, mixed $value): void
    {
        $input = $this->payload();
        if ($value === 'missing') {
            Arr::forget($input, $path);
        } else {
            data_set($input, $path, $value);
        }

        $this->preview($input)->assertUnprocessable();
        $this->save($input, null)->assertUnprocessable();

        $this->assertSame($this->challenge->team_rubric, $this->challenge->fresh()->team_rubric);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_guest_student_and_unrelated_teacher_cannot_edit_or_preview(): void
    {
        $input = $this->payload();
        $url = route('challenges.rubrics.edit', [$this->challenge, 'team']);
        auth()->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->students[0]);
        $this->get($url)->assertForbidden();
        $this->preview($input)->assertForbidden();
        $this->save($input, null)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'teacher']));
        $this->get($url)->assertNotFound();
        $this->preview($input)->assertNotFound();
        $this->save($input, null)->assertNotFound();
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_admin_can_edit_but_closed_courses_and_finished_challenges_remain_protected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $input = $this->payload();
        $token = $this->preview($input)->assertOk()->json('token');
        $this->save($input, $token)->assertOk();
        $this->challenge->update(['status' => 'finished']);
        $this->preview($this->payload())->assertUnprocessable();
        $this->challenge->update(['status' => 'evaluating']);
        $this->challenge->classroom->academicYear->update(['is_open' => false]);
        $this->get(route('challenges.rubrics.edit', [$this->challenge, 'team']))->assertForbidden();
        $this->preview($this->payload())->assertForbidden();
        $this->save($this->payload(), $token)->assertForbidden();
    }
}
