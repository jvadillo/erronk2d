<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Models\Challenge;
use App\Models\ChallengeEvidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChallengeEvidenceController extends Controller
{
    public function index(Request $request, Challenge $challenge, AcademicContext $context): RedirectResponse
    {
        $this->authorizeChallenge($request, $challenge, $context);

        return redirect()->route('challenges.show', ['challenge' => $challenge, 'tab' => 'evidence']);
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
            'sentiment' => ['sometimes', 'string', Rule::in(ChallengeEvidence::SENTIMENTS)],
        ], [
            'student_id.exists' => 'Selecciona un estudiante participante en este reto.',
            'note.required' => 'Escribe una anotación antes de guardarla.',
            'sentiment.in' => 'Elige si la anotación es positiva, neutra o negativa.',
        ]);

        ChallengeEvidence::create([
            'challenge_id' => $challenge->id,
            'student_id' => $data['student_id'],
            'author_id' => $request->user()->id,
            'note' => trim($data['note']),
            'sentiment' => $data['sentiment'] ?? 'neutral',
        ]);

        return redirect()->route('challenges.show', ['challenge' => $challenge, 'tab' => 'evidence'])->with('success', 'Evidencia guardada.');
    }

    private function authorizeChallenge(Request $request, Challenge $challenge, AcademicContext $context): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'teacher'], true), 403);
        $context->classrooms($request->user())->findOrFail($challenge->classroom_id);
    }
}
