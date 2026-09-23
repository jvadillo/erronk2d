<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Domain\Grades\ChallengeRubricEditor;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeRubricController extends Controller
{
    public function __construct(private AcademicContext $context, private ChallengeRubricEditor $editor) {}

    public function edit(Request $request, Challenge $challenge, string $kind): Response
    {
        $this->context->classrooms($request->user())->findOrFail($challenge->classroom_id);
        $this->editor->authorize($request->user(), $challenge);
        $rubric = $kind === 'team' ? $challenge->team_rubric : $challenge->transversal_rubric;
        foreach ($rubric['items'] as &$item) {
            foreach ($item['levels'] as $index => &$level) {
                $level['source_index'] = $index;
            }
            unset($level);
        }
        unset($item);
        $evaluation = $kind === 'team' ? 'team' : 'teacher';

        return Inertia::render('RubricEditor', [
            'rubric' => [...$rubric, 'id' => null, 'kind' => $kind, 'cycle_id' => null, 'level' => null, 'shared_users' => []],
            'cycles' => [], 'teachers' => [],
            'modules' => $challenge->modules->map(fn ($module) => ['id' => $module->id, 'name' => $challenge->catalog_snapshot['modules'][$module->id]['name'] ?? $module->name, 'code' => $challenge->catalog_snapshot['modules'][$module->id]['code'] ?? $module->code]),
            'saveUrl' => route('challenges.update', $challenge),
            'libraryUrl' => route('challenges.show', ['challenge' => $challenge, 'evaluation' => $evaluation, 'subject' => $request->integer('subject') ?: null]),
            'challengeContext' => ['id' => $challenge->id, 'name' => $challenge->name, 'kind' => $kind, 'revision' => $challenge->revision, 'requiresReason' => $challenge->publications()->exists(), 'previewUrl' => route('challenges.rubrics.preview', ['challenge' => $challenge, 'kind' => $kind])],
        ]);
    }

    public function preview(Request $request, Challenge $challenge, string $kind): JsonResponse
    {
        $this->context->classrooms($request->user())->findOrFail($challenge->classroom_id);
        $this->context->requireWritable($request);
        $result = DB::transaction(function () use ($request, $challenge, $kind) {
            $locked = Challenge::lockForUpdate()->findOrFail($challenge->id);
            $change = $this->editor->prepare($request->user(), $locked, [...$request->all(), 'kind' => $kind]);

            return ['impact' => $change['impact'], 'token' => $change['token']];
        });

        return response()->json($result);
    }
}
