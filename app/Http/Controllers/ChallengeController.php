<?php

namespace App\Http\Controllers;

use App\Domain\Grades\ChallengeWriter;
use App\Domain\Grades\Gradebook;
use App\Models\AuditEvent;
use App\Models\Challenge;
use App\Models\Classroom;
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
    public function index(Request $request, Gradebook $book): Response
    {
        $query = Challenge::with(['classroom.academicYear', 'period', 'modules', 'teams'])->orderByDesc('id');
        if ($request->user()->role === 'student') {
            $query->whereHas('students', fn ($q) => $q->where('users.id', $request->user()->id));
        }

        return Inertia::render('Dashboard', [
            'challenges' => $query->get()->map(function ($ch) {
                return [...$ch->only(['id', 'name', 'description', 'status', 'weight']), 'classroom' => $ch->classroom->name, 'year' => $ch->classroom->academicYear->name, 'period' => $ch->period->name, 'modules' => $ch->modules->pluck('code'), 'teams_count' => $ch->teams->count()];
            }),
            'classrooms' => $request->user()->role === 'student' ? [] : Classroom::with('academicYear.periods', 'modules')->get(),
            'rubrics' => $request->user()->role === 'student' ? [] : Rubric::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('manage_challenges'), 403);
        $data = $request->validate(['name' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'classroom_id' => 'required|exists:classrooms,id', 'period_id' => 'required|exists:periods,id', 'module_ids' => 'required|array|min:1', 'module_ids.*' => 'required|integer|distinct', 'team_rubric_id' => ['required', Rule::exists('rubrics', 'id')->where('kind', 'team')], 'transversal_rubric_id' => ['required', Rule::exists('rubrics', 'id')->where('kind', 'transversal')], 'weight' => ['required', 'numeric', 'gt:0', 'max:10000', ChallengeWriter::DECIMAL], 'distribution_enabled' => 'required|boolean'], [
            'team_rubric_id.exists' => 'Selecciona una rúbrica de equipo válida.',
            'transversal_rubric_id.exists' => 'Selecciona una rúbrica transversal válida.',
        ]);
        $class = Classroom::with('modules', 'academicYear.periods')->findOrFail($data['classroom_id']);
        if (! $class->academicYear->periods->contains('id', (int) $data['period_id'])) {
            throw ValidationException::withMessages(['period_id' => 'La Evaluación debe pertenecer al curso de la clase.']);
        }
        if (collect($data['module_ids'])->diff($class->modules->pluck('id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['module_ids' => 'Los módulos deben pertenecer a la clase seleccionada.']);
        }
        $team = Rubric::where('kind', 'team')->findOrFail($data['team_rubric_id']);
        $transversal = Rubric::where('kind', 'transversal')->findOrFail($data['transversal_rubric_id']);
        foreach ($team->items as $item) {
            if (! empty($item['module_id'])) {
                if (! in_array((int) $item['module_id'], array_map('intval', $data['module_ids']), true)) {
                    throw ValidationException::withMessages(['team_rubric_id' => 'La rúbrica incluye criterios de módulos que no participan. Selecciona todos sus módulos o elige otra rúbrica.']);
                }
            }
        }
        $ch = DB::transaction(function () use ($data, $class, $team, $transversal, $request) {
            $ch = Challenge::create([...collect($data)->only(['name', 'description', 'classroom_id', 'period_id', 'weight', 'distribution_enabled'])->all(),
                'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30],
                'team_rubric' => ['name' => $team->name, 'items' => $team->items], 'transversal_rubric' => ['name' => $transversal->name, 'items' => $transversal->items]]);
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
        $data = $book->challenge($challenge);
        if ($request->user()->role === 'student') {
            return Inertia::render('Student', ['book' => $this->studentBook($request, $challenge, $data)]);
        }

        return Inertia::render('Challenge', ['book' => $data, 'history' => $challenge->publications()->select('id', 'version', 'created_at')->orderByDesc('version')->get()]);
    }

    public function update(Request $request, Challenge $challenge, ChallengeWriter $writer): JsonResponse
    {
        $book = $writer->change($request->user(), $challenge->id, $request->all());
        if ($request->user()->role === 'student') {
            $book = $this->studentBook($request, $challenge->fresh(), $book);
        }

        return response()->json(['book' => $book]);
    }

    public function history(Request $request, Challenge $challenge): JsonResponse
    {
        abort_if($request->user()->role === 'student', 403);

        return response()->json(['publications' => $challenge->publications()->orderByDesc('version')->get(), 'events' => AuditEvent::where('challenge_id', $challenge->id)->latest()->limit(100)->get(['id', 'user_id', 'action', 'reason', 'created_at'])]);
    }
}
