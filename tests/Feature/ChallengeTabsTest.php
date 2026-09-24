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
        $this->challenge->classroom->users()->attach($this->teacher);
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
        ])->assertRedirect($tab);
        $this->get($tab)->assertInertia(fn (Assert $page) => $page
            ->component('Challenge')
            ->has('book.rows', 1)
            ->has('evidences', 1)
            ->where('evidences.0.student_id', $this->student->id)
            ->where('evidences.0.note', 'Observación del profesorado.')
            ->where('evidences.0.author_name', $this->teacher->name));
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
        $this->challenge->update(['status' => 'published']);
        $this->actingAs($this->teacher)->post(route('challenges.evidence.store', $this->challenge), [
            'student_id' => $this->student->id, 'note' => 'No permitido.',
        ])->assertForbidden();
        $this->assertDatabaseCount('challenge_evidences', 0);
    }
}
