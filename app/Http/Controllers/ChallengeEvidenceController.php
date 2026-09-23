<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Models\Challenge;
use App\Models\ChallengeEvidence;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeEvidenceController extends Controller
{
    public function index(Request $request, Challenge $challenge, AcademicContext $context): Response
    {
        $this->authorizeChallenge($request, $challenge, $context);

        $teamNames = $challenge->memberships()
            ->with('team:id,name')
            ->get()
            ->mapWithKeys(fn (Membership $membership): array => [$membership->student_id => $membership->team?->name]);

        $students = $challenge->students()
            ->get(['users.id', 'users.name'])
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'team_name' => $teamNames->get($student->id),
            ])
            ->values();

        $evidences = $challenge->evidences()
            ->with(['student:id,name', 'author:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(function (ChallengeEvidence $evidence) use ($teamNames): array {
                return [
                    'id' => $evidence->id,
                    'student_id' => $evidence->student_id,
                    'student_name' => $evidence->student->name,
                    'team_name' => $teamNames->get($evidence->student_id),
                    'author_name' => $evidence->author->name,
                    'note' => $evidence->note,
                    'created_at' => $evidence->created_at->toIso8601String(),
                ];
            })
            ->values();

        return Inertia::render('Evidence', [
            'challenge' => $challenge->only(['id', 'name']),
            'students' => $students,
            'evidences' => $evidences,
            'canAdd' => (bool) $context->year()?->is_open && ! in_array($challenge->status, ['published', 'finished'], true),
        ]);
    }

    public function store(Request $request, Challenge $challenge, AcademicContext $context): RedirectResponse
    {
        $this->authorizeChallenge($request, $challenge, $context);
        $context->requireWritable($request);
        abort_if(in_array($challenge->status, ['published', 'finished'], true), 403, 'El reto está cerrado y sus evidencias son de solo lectura.');

        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('challenge_student', 'user_id')->where('challenge_id', $challenge->id),
            ],
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'student_id.exists' => 'Selecciona un estudiante participante en este reto.',
            'note.required' => 'Escribe una anotación antes de guardarla.',
        ]);

        ChallengeEvidence::create([
            'challenge_id' => $challenge->id,
            'student_id' => $data['student_id'],
            'author_id' => $request->user()->id,
            'note' => trim($data['note']),
        ]);

        return redirect()->route('challenges.evidence.index', $challenge)->with('success', 'Evidencia guardada.');
    }

    private function authorizeChallenge(Request $request, Challenge $challenge, AcademicContext $context): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'teacher'], true), 403);
        $context->classrooms($request->user())->findOrFail($challenge->classroom_id);
    }
}
