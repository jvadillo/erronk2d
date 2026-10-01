<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Domain\Grades\ChallengeWriter;
use App\Domain\Grades\Gradebook;
use App\Models\AuditEvent;
use App\Models\Challenge;
use App\Models\ChallengeEvidence;
use App\Models\Rubric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function __construct(private AcademicContext $context) {}

    private function authorizeChallenge(Request $request, Challenge $challenge): void
    {
        $this->context->classrooms($request->user())->findOrFail($challenge->classroom_id);
        if ($request->user()->role === 'student') {
            abort_unless($challenge->students()->where('users.id', $request->user()->id)->exists(), 404);
        }
    }

    public function index(Request $request, Gradebook $book): Response
    {
        $query = Challenge::whereIn('classroom_id', $this->context->classrooms($request->user())->select('classrooms.id'))->with(['classroom.academicYear', 'period', 'modules', 'teams'])->orderByDesc('id');
        if ($request->user()->role === 'student') {
            $query->whereHas('students', fn ($q) => $q->where('users.id', $request->user()->id));
        }

        return Inertia::render('Dashboard', [
            'challenges' => $query->get()->map(function ($ch) {
                return [...$ch->only(['id', 'name', 'description', 'status', 'weight']), 'classroom' => $ch->catalog_snapshot['classroom'] ?? $ch->classroom->name, 'year' => $ch->catalog_snapshot['year'] ?? $ch->classroom->academicYear->name, 'period' => $ch->catalog_snapshot['period'] ?? $ch->period->name, 'modules' => $ch->modules->map(fn ($module) => $ch->catalog_snapshot['modules'][$module->id]['code'] ?? $module->code), 'teams_count' => $ch->teams->count()];
            }),
            'classrooms' => $request->user()->role === 'student' ? [] : $this->context->classrooms($request->user())->with('academicYear', 'periods', 'modules')->get()->each(function ($classroom) {
                foreach ($classroom->modules as $module) {
                    $module->name = $module->pivot->name;
                    $module->code = $module->pivot->code;
                }
            }),
            'rubrics' => $request->user()->role === 'student' ? [] : Rubric::availableTo($request->user())->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('manage_challenges'), 403);
        $this->context->requireWritable($request);
        $data = $request->validate(['name' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'classroom_id' => 'required|exists:classrooms,id', 'period_id' => 'required|exists:periods,id', 'module_ids' => 'required|array|min:1', 'module_ids.*' => 'required|integer|distinct', 'team_rubric_id' => ['present', 'nullable', 'integer', Rule::exists('rubrics', 'id')->where('kind', 'team')], 'transversal_rubric_id' => ['present', 'nullable', 'integer', Rule::exists('rubrics', 'id')->where('kind', 'transversal')], 'weight' => ['required', 'numeric', 'gt:0', 'max:10000', ChallengeWriter::DECIMAL], 'distribution_enabled' => 'required|boolean'], [
            'team_rubric_id.exists' => 'Selecciona una rúbrica de equipo válida.',
            'transversal_rubric_id.exists' => 'Selecciona una rúbrica transversal válida.',
        ]);
        $ch = DB::transaction(function () use ($data, $request) {
            $class = $this->context->classrooms($request->user())->lockForUpdate()->with('modules', 'academicYear', 'periods')->findOrFail($data['classroom_id']);
            if (! $class->periods->contains('id', (int) $data['period_id'])) {
                throw ValidationException::withMessages(['period_id' => 'La Evaluación debe pertenecer al grupo seleccionado.']);
            }
            if (collect($data['module_ids'])->diff($class->modules->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['module_ids' => 'Los módulos deben pertenecer al grupo seleccionado.']);
            }
            $rubrics = [];
            foreach (['team' => 'Rúbrica técnica', 'transversal' => 'Rúbrica transversal'] as $kind => $name) {
                $field = $kind.'_rubric_id';
                $rubric = $data[$field] === null ? null : Rubric::availableTo($request->user())->where('kind', $kind)->findOrFail($data[$field]);
                if ($rubric?->cycle_id && ($rubric->cycle_id !== $class->cycle_id || $rubric->level !== $class->level)) {
                    throw ValidationException::withMessages([$field => 'La rúbrica debe corresponder al ciclo y nivel del grupo.']);
                }
                $rubrics[$kind.'_rubric'] = ['name' => $rubric?->name ?? $name, 'items' => $rubric?->items ?? []];
            }
            foreach ($rubrics['team_rubric']['items'] as $item) {
                if (! empty($item['module_id'])) {
                    if (! in_array((int) $item['module_id'], array_map('intval', $data['module_ids']), true)) {
                        throw ValidationException::withMessages(['team_rubric_id' => 'La rúbrica incluye criterios de módulos que no participan. Selecciona todos sus módulos o elige otra rúbrica.']);
                    }
                }
            }
            $ch = Challenge::create([...collect($data)->only(['name', 'description', 'classroom_id', 'period_id', 'weight', 'distribution_enabled'])->all(),
                'catalog_snapshot' => ['classroom' => $class->name, 'cycle' => $class->cycle_name, 'level' => $class->level, 'year' => $class->academicYear->name, 'period' => $class->periods->firstWhere('id', (int) $data['period_id'])->name, 'modules' => $class->modules->whereIn('id', $data['module_ids'])->mapWithKeys(fn ($module) => [$module->id => ['name' => $module->pivot->name, 'code' => $module->pivot->code]])->all()],
                'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30],
                ...$rubrics]);
            $ch->modules()->sync($data['module_ids']);
            $ch->students()->sync($class->students()->where('active', true)->pluck('users.id'));
            AuditEvent::create(['user_id' => $request->user()->id, 'challenge_id' => $ch->id, 'action' => 'create', 'after' => $ch->toArray()]);

            return $ch;
        });

        return redirect('/challenges/'.$ch->id)->with('success', 'Reto creado. Las rúbricas y participantes quedan vinculados a este reto.');
    }

    private function studentBook(Request $request, Challenge $challenge, array $book): array
    {
        $id = $request->user()->id;
        abort_unless($challenge->students()->where('users.id', $id)->exists(), 403);
        $team = collect($book['teams'])->first(fn ($team) => collect($team['members'])->contains('student_id', $id));
        $peers = $team ? collect($book['rows'])->where('team_id', $team['id'])->map(fn ($row) => collect($row)->only(['id', 'name'])->all())->values()->all() : [];
        $publication = $challenge->status === 'published' ? $challenge->publications()->latest('version')->first() : null;

        return [
            'challenge' => collect($book['challenge'])->only(['id', 'name', 'description', 'status', 'revision', 'transversal_rubric'])->all(),
            'classroom' => $book['classroom'], 'year' => $book['year'], 'period' => $book['period'],
            'peers' => $peers,
            'assessments' => collect($book['assessments'])->filter(fn ($a) => in_array($a['kind'], ['self', 'peer'], true) && $a['scope_id'] === $id)->values()->all(),
            'result' => $publication ? collect($publication->snapshot['rows'])->firstWhere('id', $id) : null,
            'modules' => $book['modules'],
        ];
    }

    public function show(Request $request, Challenge $challenge, Gradebook $book): Response
    {
        $this->authorizeChallenge($request, $challenge);
        $data = $book->challenge($challenge);
        if ($request->user()->role === 'student') {
            return Inertia::render('Student', ['book' => $this->studentBook($request, $challenge, $data)]);
        }

        return Inertia::render('Challenge', ['book' => $data,
            'evidences' => $challenge->evidences()->with(['student:id,name', 'author:id,name'])->latest('created_at')->latest('id')->get()->map(fn (ChallengeEvidence $evidence): array => [
                'id' => $evidence->id,
                'student_id' => $evidence->student_id,
                'student_name' => $evidence->student->name,
                'team_name' => collect($data['rows'])->firstWhere('id', $evidence->student_id)['team_name'] ?? null,
                'author_name' => $evidence->author->name,
                'note' => $evidence->note,
                'created_at' => $evidence->created_at->toIso8601String(),
            ]),
            'challengeUrl' => route('challenges.show', $challenge),
            'evidenceStoreUrl' => route('challenges.evidence.store', $challenge), 'history' => $challenge->publications()->select('id', 'version', 'created_at')->orderByDesc('version')->get(),
            'rubricEditorUrls' => collect(['team', 'transversal'])->mapWithKeys(fn (string $kind) => [$kind => route('challenges.rubrics.edit', ['challenge' => $challenge, 'kind' => $kind])])->all()]);
    }

    public function update(Request $request, Challenge $challenge, ChallengeWriter $writer): JsonResponse
    {
        $this->authorizeChallenge($request, $challenge);
        $this->context->requireWritable($request);
        $book = $writer->change($request->user(), $challenge->id, $request->all());
        if ($request->user()->role === 'student') {
            $book = $this->studentBook($request, $challenge->fresh(), $book);
        }

        return response()->json(['book' => $book]);
    }

    public function history(Request $request, Challenge $challenge): JsonResponse
    {
        $this->authorizeChallenge($request, $challenge);
        abort_if($request->user()->role === 'student', 403);

        return response()->json(['publications' => $challenge->publications()->orderByDesc('version')->get(), 'events' => AuditEvent::where('challenge_id', $challenge->id)->latest()->limit(100)->get(['id', 'user_id', 'action', 'reason', 'created_at'])]);
    }
}
