<?php

namespace App\Domain\Grades;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ChallengeRubricEditor
{
    public function __construct(private Gradebook $book, private Calculator $calc) {}

    public function authorize(User $actor, Challenge $challenge): void
    {
        abort_unless($actor->canAccessClassroom($challenge->classroom), 404);
        abort_unless($actor->allows('manage_challenges'), 403);
        abort_unless($challenge->classroom->academicYear->is_open, 403, 'El curso académico está cerrado.');
        abort_if(in_array($challenge->status, ['published', 'finished'], true), 422, 'Reabre el reto con un motivo antes de editar su rúbrica.');
    }

    /**
     * source_index identifies a level in the submitted revision, not its new position.
     * It is discarded after remapping, so legacy snapshots need no conversion.
     *
     * @param  array<string, mixed>  $input
     * @return array{field: string, rubric: array<string, mixed>, removed: array<int>, updates: array<int, int>, impact: array<string, mixed>, token: string}
     */
    public function prepare(User $actor, Challenge $challenge, array $input): array
    {
        $this->authorize($actor, $challenge);
        $data = Validator::make($input, [
            'revision' => 'required|integer|min:1', 'kind' => 'required|in:team,transversal',
            'reason' => [$challenge->publications()->exists() ? 'required' : 'nullable', 'string', 'min:5', 'max:2000'],
            'rubric' => 'required|array:name,items', 'rubric.name' => 'required|string|max:150',
            'rubric.items' => 'required|array|list|min:1|max:40',
            'rubric.items.*' => 'required|array:key,name,description,module_id,weight,levels',
            'rubric.items.*.key' => 'required|string|max:80|distinct|regex:/^[a-zA-Z0-9_-]+$/',
            'rubric.items.*.name' => 'required|string|max:150', 'rubric.items.*.description' => 'nullable|string|max:2000',
            'rubric.items.*.module_id' => 'nullable|integer',
            'rubric.items.*.weight' => ['required', 'numeric', 'gt:0', 'max:10000', ChallengeWriter::DECIMAL],
            'rubric.items.*.levels' => 'required|array|list|min:2|max:20',
            'rubric.items.*.levels.*' => 'required|array:score,description,source_index',
            'rubric.items.*.levels.*.source_index' => 'present|nullable|integer|min:0|max:19',
            'rubric.items.*.levels.*.score' => ['required', 'numeric', 'between:0,10', ChallengeWriter::DECIMAL],
            'rubric.items.*.levels.*.description' => 'required|string|max:2000',
        ], ['reason.required' => 'Indica el motivo de la corrección de la rúbrica publicada.'])->validate();
        abort_if($challenge->revision !== (int) $data['revision'], 409, 'El reto ha cambiado. Vuelve a abrir el editor y revisa los cambios antes de guardar.');
        $field = $data['kind'] === 'team' ? 'team_rubric' : 'transversal_rubric';
        $oldItems = collect($challenge->$field['items'])->keyBy('key');
        $rubric = $data['rubric'];
        $maps = [];
        $total = $this->calc->number(0);
        $sameWeights = count($rubric['items']) === $oldItems->count();
        $moduleIds = $challenge->modules()->pluck('modules.id')->all();
        foreach ($rubric['items'] as $index => &$item) {
            $key = $item['key'];
            $old = $oldItems->get($key);
            $total = $total->plus((string) $item['weight']);
            $sameWeights = $sameWeights && $old && $this->calc->number((string) $item['weight'])->isEqualTo((string) $old['weight']);
            $module = $item['module_id'] ?? null;
            if ($module !== null && ($data['kind'] === 'transversal' || ! in_array((int) $module, $moduleIds, true))) {
                throw ValidationException::withMessages(["rubric.items.$index.module_id" => 'Selecciona un módulo que participe en este reto, o GENERAL.']);
            }
            $item['module_id'] = $module === null ? null : (int) $module;
            $maps[$key] = [];
            foreach ($item['levels'] as $position => &$level) {
                $source = $level['source_index'];
                if ($source !== null) {
                    $source = (int) $source;
                    if (! isset($old['levels'][$source]) || array_key_exists($source, $maps[$key])) {
                        throw ValidationException::withMessages(["rubric.items.$index.levels" => 'El nivel de origen no existe o está repetido. Vuelve a abrir el editor.']);
                    }
                    $maps[$key][$source] = $position;
                }
                unset($level['source_index']);
            }
            unset($level);
        }
        unset($item);
        if (! $total->isEqualTo(100) && ! $sameWeights) {
            throw ValidationException::withMessages(['rubric.items' => 'Los pesos de los criterios deben sumar exactamente 100 %.']);
        }

        $before = $this->book->challenge($challenge);
        $kinds = $data['kind'] === 'team' ? ['team'] : ['teacher', 'self', 'peer'];
        $removed = [];
        $updates = [];
        $deletedByKind = array_fill_keys($kinds, 0);
        $affected = [];
        $assessments = collect();
        foreach ($challenge->assessments as $assessment) {
            $copy = clone $assessment;
            if (in_array($assessment->kind, $kinds, true)) {
                $newLevel = $maps[$assessment->criterion][$assessment->level] ?? null;
                if ($newLevel === null) {
                    $removed[] = $assessment->id;
                    $deletedByKind[$assessment->kind]++;
                    $affected[] = $assessment->subject_id;

                    continue;
                }
                if ($newLevel !== $assessment->level) {
                    $updates[$assessment->id] = $newLevel;
                    $copy->level = $newLevel;
                }
            }
            $assessments->push($copy);
        }
        $preview = clone $challenge;
        $preview->$field = $rubric;
        $after = $this->book->challenge($preview, $assessments);
        $subjects = collect($before[$data['kind'] === 'team' ? 'teams' : 'rows'])->whereIn('id', $affected)->pluck('name')->values()->all();
        $oldTeams = collect($before['teams'])->keyBy('id');
        $oldRows = collect($before['rows'])->keyBy('id');
        $impact = [
            'removed_assessments' => count($removed), 'removed_by_kind' => $deletedByKind,
            'affected_subjects' => $subjects,
            'removed_criteria' => $oldItems->except(array_column($rubric['items'], 'key'))->pluck('name')->values()->all(),
            'changed_team_grades' => collect($after['teams'])->filter(fn (array $team) => $team['rubric_exact'] !== $oldTeams[$team['id']]['rubric_exact'])->pluck('name')->values()->all(),
            'invalid_allocations' => collect($after['teams'])->filter(fn (array $team) => $challenge->distribution_enabled && ! $team['distribution_valid'] && $oldTeams[$team['id']]['distribution_valid'])->pluck('name')->values()->all(),
            'changed_results' => collect($after['rows'])->filter(fn (array $row) => $row['modules'] !== $oldRows[$row['id']]['modules'] || $row['transversal'] !== $oldRows[$row['id']]['transversal'] || $row['pending'] !== $oldRows[$row['id']]['pending'])->pluck('name')->values()->all(),
        ];
        $token = hash_hmac('sha256', json_encode([$actor->id, $challenge->id, $data, $impact], JSON_THROW_ON_ERROR), config('app.key'));

        return compact('field', 'rubric', 'removed', 'updates', 'impact', 'token');
    }

    /** @param array<string, mixed> $input */
    public function apply(User $actor, Challenge $challenge, array $input): void
    {
        $change = $this->prepare($actor, $challenge, $input);
        abort_unless(is_string($input['preview_token'] ?? null) && hash_equals($change['token'], $input['preview_token']), 409, 'Revisa de nuevo el impacto y confirma los cambios antes de guardar.');
        $challenge->assessments()->whereIn('id', $change['removed'])->delete();
        foreach ($change['updates'] as $id => $level) {
            DB::table('assessments')->where('challenge_id', $challenge->id)->where('id', $id)->update(['level' => $level]);
        }
        $challenge->{$change['field']} = $change['rubric'];
    }
}
