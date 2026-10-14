<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChallengeTabsTest extends TestCase
{
    use RefreshDatabase;

    private Challenge $challenge;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->challenge = Challenge::factory()->create(['status' => 'active']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->challenge->modules()->attach($this->moduleForClass($this->challenge->classroom, [], $this->teacher));
        $this->enrollInClass($this->student, $this->challenge->classroom);
        $this->challenge->students()->attach($this->student);
        $this->withHeader('X-Academic-Year', (string) $this->challenge->classroom->academic_year_id);
    }

    public function test_evidence_link_and_save_return_to_the_challenge_tab(): void
    {
        $tab = route('challenges.show', ['challenge' => $this->challenge, 'tab' => 'evidence']);
        $this->actingAs($this->teacher)->get(route('challenges.evidence.index', $this->challenge))->assertRedirect($tab);
        $this->post(route('challenges.evidence.store', $this->challenge), [
            'student_id' => $this->student->id,
            'note' => 'Observación del profesorado.',
            'sentiment' => 'positive',
        ])->assertRedirect($tab);
        $this->get($tab)->assertInertia(fn (Assert $page) => $page
            ->component('Challenge')
            ->has('book.rows', 1)
            ->has('evidences', 1)
            ->where('evidences.0.student_id', $this->student->id)
            ->where('evidences.0.note', 'Observación del profesorado.')
            ->where('evidences.0.sentiment', 'positive')
            ->where('evidences.0.author_name', $this->teacher->name));
    }

    public function test_evidence_sentiment_defaults_to_neutral_and_rejects_unknown_values(): void
    {
        $this->actingAs($this->teacher)->post(route('challenges.evidence.store', $this->challenge), [
            'student_id' => $this->student->id,
            'note' => 'Sin valoración explícita.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('challenge_evidences', ['note' => 'Sin valoración explícita.', 'sentiment' => 'neutral']);

        $this->post(route('challenges.evidence.store', $this->challenge), [
            'student_id' => $this->student->id,
            'note' => 'Valoración desconocida.',
            'sentiment' => 'excelente',
        ])->assertSessionHasErrors('sentiment');
        $this->assertDatabaseMissing('challenge_evidences', ['note' => 'Valoración desconocida.']);
    }

    public function test_student_view_never_receives_teacher_evidence(): void
    {
        $this->challenge->evidences()->create(['student_id' => $this->student->id, 'author_id' => $this->teacher->id, 'note' => 'Nota privada.']);
        $this->actingAs($this->student)->get(route('challenges.show', ['challenge' => $this->challenge, 'tab' => 'evidence']))
            ->assertInertia(fn (Assert $page) => $page->component('Student')->missing('evidences'));
        $this->get(route('challenges.evidence.index', $this->challenge))->assertForbidden();
        $this->post(route('challenges.evidence.store', $this->challenge), ['student_id' => $this->student->id, 'note' => 'No permitido.'])->assertForbidden();
        $this->assertDatabaseCount('challenge_evidences', 1);
    }

    public function test_evidence_history_of_former_participants_remains_available_to_teachers(): void
    {
        $formerStudent = User::factory()->create(['role' => 'student']);
        $this->challenge->evidences()->create(['student_id' => $formerStudent->id, 'author_id' => $this->teacher->id, 'note' => 'Historial conservado.']);
        $this->actingAs($this->teacher)->get(route('challenges.show', $this->challenge))
            ->assertInertia(fn (Assert $page) => $page->component('Challenge')->has('book.rows', 1)
                ->where('evidences.0.student_name', $formerStudent->name));
        $this->post(route('challenges.evidence.store', $this->challenge), ['student_id' => $formerStudent->id, 'note' => 'Nueva anotación.'])
            ->assertSessionHasErrors('student_id');
    }

    public function test_unrelated_teacher_cannot_read_evidence_and_closed_challenge_rejects_writes(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($otherTeacher)->get(route('challenges.show', $this->challenge))->assertNotFound();
        $this->get(route('challenges.evidence.index', $this->challenge))->assertNotFound();
        $this->post(route('challenges.evidence.store', $this->challenge), ['student_id' => $this->student->id, 'note' => 'No permitido.'])->assertNotFound();
        $this->assertDatabaseCount('challenge_evidences', 0);
        $this->challenge->update(['status' => 'published']);
        $this->actingAs($this->teacher)->post(route('challenges.evidence.store', $this->challenge), [
            'student_id' => $this->student->id, 'note' => 'No permitido.',
        ])->assertForbidden();
        $this->assertDatabaseCount('challenge_evidences', 0);
    }

    public function test_group_teacher_only_sees_challenges_with_a_module_they_teach(): void
    {
        $classroom = $this->challenge->classroom;
        $otherModule = $this->moduleForClass($classroom);
        $hidden = Challenge::factory()->create(['classroom_id' => $classroom->id, 'period_id' => $this->challenge->period_id]);
        $hidden->modules()->attach($otherModule);
        $moduleLess = Challenge::factory()->create(['classroom_id' => $classroom->id, 'period_id' => $this->challenge->period_id]);

        $this->actingAs($this->teacher)->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 1)->where('challenges.0.id', $this->challenge->id));
        foreach ([$hidden, $moduleLess] as $challenge) {
            $this->get(route('challenges.show', $challenge))->assertNotFound();
            $this->get(route('challenges.evidence.index', $challenge))->assertNotFound();
        }

        $otherModule->teachersFor($classroom->id)->attach($this->teacher, ['classroom_id' => $classroom->id]);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 2));
        $this->get(route('challenges.show', $hidden))->assertOk();
        $this->get(route('challenges.show', $moduleLess))->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/')->assertInertia(fn (Assert $page) => $page->has('challenges', 3));
    }
}
