<?php

namespace Tests\Feature;

use App\Domain\Grades\Gradebook;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AuditEvent;
use App\Models\Challenge;
use App\Models\Classroom;
use App\Models\Membership;
use App\Models\Module;
use App\Models\ModuleGrade;
use App\Models\Publication;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GradebookTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Challenge $challenge;

    private Classroom $classroom;

    private array $students;

    private array $modules;

    private array $rubric;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $year = AcademicYear::create(['name' => '2026-2027']);
        $p = $year->periods()->create(['name' => '1.ª Evaluación', 'position' => 1]);
        $year->periods()->create(['name' => '2.ª Evaluación', 'position' => 2]);
        $this->classroom = Classroom::create(['academic_year_id' => $year->id, 'name' => '2DAW-A']);
        $this->students = User::factory()->count(3)->create(['role' => 'student', 'classroom_id' => $this->classroom->id])->all();
        $this->modules = [];
        foreach (['PROG', 'DWEC'] as $code) {
            $m = Module::create(['classroom_id' => $this->classroom->id, 'name' => $code, 'code' => $code]);
            $m->teachers()->attach($this->admin);
            $this->modules[] = $m;
        }
        $this->rubric = ['name' => 'Rúbrica', 'items' => [['key' => 'quality', 'name' => 'Calidad', 'weight' => '1', 'module_id' => null, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo'], ['score' => '10', 'description' => 'Excelente']]]]];
        $this->challenge = Challenge::create(['name' => 'Reto A', 'classroom_id' => $this->classroom->id, 'period_id' => $p->id, 'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30], 'team_rubric' => $this->rubric, 'transversal_rubric' => $this->rubric, 'status' => 'evaluating']);
        $this->challenge->modules()->attach(array_map(fn ($m) => $m->id, $this->modules));
        $this->challenge->students()->attach(array_map(fn ($s) => $s->id, $this->students));
        $team = $this->challenge->teams()->create(['name' => 'Equipo 1']);
        foreach ($this->students as $s) {
            $team->memberships()->create(['challenge_id' => $this->challenge->id, 'student_id' => $s->id]);
        }
    }

    private function write(array $data, ?User $actor = null)
    {
        return $this->actingAs($actor ?? $this->admin)->postJson('/challenges/'.$this->challenge->id, ['revision' => $this->challenge->fresh()->revision, ...$data]);
    }

    public function test_empty_challenge_can_import_current_students_before_organizing_teams(): void
    {
        $this->challenge->teams()->delete();
        $this->challenge->students()->detach();
        $this->write(['action' => 'teams', 'teams' => [['name' => 'Equipo 1', 'students' => []]]])
            ->assertUnprocessable()->assertJsonFragment(['teams.0.students' => ['Selecciona entre 2 y 5 estudiantes para el equipo 1.']]);
        $this->write(['action' => 'participants'])->assertOk()->assertJsonCount(3, 'book.rows');
        $this->write(['action' => 'teams', 'teams' => [['name' => 'Equipo 1', 'students' => array_map(fn ($student) => $student->id, $this->students)]]])->assertOk();
        $this->assertSame(3, $this->challenge->memberships()->count());
        $this->assertDatabaseHas('audit_events', ['challenge_id' => $this->challenge->id, 'action' => 'participants']);
    }

    public function test_reports_retain_students_after_their_current_class_changes(): void
    {
        $this->complete();
        $before = app(Gradebook::class)->report($this->classroom)['rows'];
        $other = $this->classroom->academicYear->classrooms()->create(['name' => 'Otra clase']);
        $this->students[0]->update(['classroom_id' => $other->id]);
        $this->assertSame($before, app(Gradebook::class)->report($this->classroom)['rows']);
    }

    public function test_participant_repair_rejects_populated_historical_unauthorized_and_stale_requests(): void
    {
        $this->write(['action' => 'participants'])->assertUnprocessable();
        $this->challenge->teams()->delete();
        $this->challenge->students()->detach();
        $this->write(['action' => 'participants'], $this->students[0])->assertForbidden();
        $this->write(['action' => 'participants', 'revision' => 0])->assertUnprocessable();
        $this->write(['action' => 'participants', 'revision' => 999])->assertConflict();
        Publication::create(['challenge_id' => $this->challenge->id, 'version' => 1, 'snapshot' => [], 'published_by' => $this->admin->id]);
        $this->write(['action' => 'participants'])->assertUnprocessable();
        $this->assertSame(0, $this->challenge->students()->count());
        $this->assertDatabaseMissing('audit_events', ['action' => 'participants']);
    }

    public function test_participant_repair_excludes_inactive_students_and_rejects_empty_class(): void
    {
        $this->challenge->teams()->delete();
        $this->challenge->students()->detach();
        foreach ($this->students as $student) {
            $student->update(['active' => false]);
        }
        $this->write(['action' => 'participants'])->assertUnprocessable()->assertJsonValidationErrors('participants');
        $this->students[0]->update(['active' => true]);
        $this->write(['action' => 'participants'])->assertOk()->assertJsonCount(1, 'book.rows');
        $this->assertSame([$this->students[0]->id], $this->challenge->students()->pluck('users.id')->all());
    }

    private function teamGrade(): void
    {
        $this->write(['action' => 'assess', 'kind' => 'team', 'entries' => [['subject_id' => $this->challenge->teams()->first()->id, 'criterion' => 'quality', 'level' => 1]]])->assertOk();
    }

    private function allocate(): void
    {
        $this->teamGrade();
        $this->write(['action' => 'allocation', 'team_id' => $this->challenge->teams()->first()->id, 'allocations' => [$this->students[0]->id => '7', $this->students[1]->id => '8', $this->students[2]->id => '9']])->assertOk();
    }

    private function complete(): void
    {
        $this->allocate();
        foreach ($this->students as $s) {
            foreach (['self', 'teacher'] as $kind) {
                $this->write(['action' => 'assess', 'kind' => $kind, 'entries' => [['subject_id' => $s->id, 'criterion' => 'quality', 'level' => 1]]], $kind === 'self' ? $s : null)->assertOk();
            }
            foreach ($this->students as $peer) {
                if ($peer->id !== $s->id) {
                    $this->write(['action' => 'assess', 'kind' => 'peer', 'entries' => [['subject_id' => $s->id, 'criterion' => 'quality', 'level' => 1]]], $peer)->assertOk();
                }
            }
        }
        foreach ($this->modules as $m) {
            $this->write(['action' => 'grades', 'module_id' => $m->id, 'field' => 'exam', 'entries' => array_map(fn ($s) => ['student_id' => $s->id, 'value' => '7'], $this->students)])->assertOk();
            $this->write(['action' => 'grades', 'module_id' => $m->id, 'field' => 'defense', 'entries' => array_map(fn ($s) => ['student_id' => $s->id, 'value' => '0'], $this->students)])->assertOk();
        }
    }

    public function test_invalid_distribution_rolls_back_and_valid_distribution_is_saved(): void
    {
        $this->teamGrade();
        $this->write(['action' => 'allocation', 'team_id' => $this->challenge->teams()->first()->id, 'allocations' => [$this->students[0]->id => '7', $this->students[1]->id => '8', $this->students[2]->id => '8']])->assertUnprocessable();
        $this->assertSame(0, Membership::whereNotNull('allocation')->count());
        $this->allocate();
        $this->assertSame('7.0000', Membership::where('student_id', $this->students[0]->id)->first()->allocation);
    }

    public function test_optional_distribution_and_optional_defense(): void
    {
        $this->challenge->update(['distribution_enabled' => false]);
        foreach ($this->modules as $m) {
            $this->challenge->modules()->updateExistingPivot($m->id, ['defense_enabled' => false]);
        }
        $this->teamGrade();
        $book = app(Gradebook::class)->challenge($this->challenge->fresh());
        $this->assertSame('8.0000', $book['rows'][0]['challenge_final']);
        $this->write(['action' => 'grades', 'field' => 'defense', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => 0]]])->assertUnprocessable();
    }

    public function test_defense_change_preserves_allocation_and_updates_every_module(): void
    {
        $this->complete();
        $student = $this->students[0];
        foreach (['0.5', '-0.25'] as $i => $value) {
            $this->write(['action' => 'grades', 'field' => 'defense', 'module_id' => $this->modules[$i]->id, 'entries' => [['student_id' => $student->id, 'value' => $value]]])->assertOk();
        }
        $book = app(Gradebook::class)->challenge($this->challenge->fresh());
        $row = collect($book['rows'])->firstWhere('id', $student->id);
        $this->assertSame('7.2500', $row['challenge_final']);
        $this->assertSame('7.0000', $row['allocation']);
        foreach ($this->modules as $m) {
            $this->assertSame('7.40', $row['modules'][$m->id]['final']);
        }
        $this->write(['action' => 'grades', 'field' => 'defense', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $student->id, 'value' => '1']]])->assertOk();
        $row = collect(app(Gradebook::class)->challenge($this->challenge->fresh())['rows'])->firstWhere('id', $student->id);
        $this->assertSame('7.7500', $row['challenge_final']);
        $this->assertSame('7.0000', $row['allocation']);
        $this->assertSame(6, ModuleGrade::count());
    }

    public function test_all_teachers_can_read_but_only_module_responsibles_can_write(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'permissions' => User::PERMISSIONS]);
        $this->actingAs($teacher)->get('/challenges/'.$this->challenge->id)->assertOk();
        $data = ['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => '8']]];
        $this->write($data, $teacher)->assertForbidden();
        $this->modules[0]->teachers()->attach($teacher);
        $this->write($data, $teacher)->assertOk();
        $this->write($data)->assertOk();
        $this->assertSame(1, ModuleGrade::count());
    }

    public function test_shared_transversal_has_no_module_or_teacher_average(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'permissions' => ['evaluate_transversal', 'modify_grades']]);
        $data = ['action' => 'assess', 'kind' => 'teacher', 'entries' => [['subject_id' => $this->students[0]->id, 'criterion' => 'quality', 'level' => 1]]];
        $this->write($data)->assertOk();
        $data['entries'][0]['level'] = 2;
        $this->write($data, $teacher)->assertOk();
        $this->assertSame(1, Assessment::where('kind', 'teacher')->count());
        $this->assertSame(0, Assessment::first()->scope_id);
        $row = collect(app(Gradebook::class)->challenge($this->challenge->fresh())['rows'])->firstWhere('id', $this->students[0]->id);
        $this->assertSame('10.00', $row['teacher']);
    }

    public function test_students_cannot_evaluate_themselves_as_peers_or_another_team(): void
    {
        $s = $this->students[0];
        $this->write(['action' => 'assess', 'kind' => 'peer', 'entries' => [['subject_id' => $s->id, 'criterion' => 'quality', 'level' => 1]]], $s)->assertForbidden();
        $this->write(['action' => 'assess', 'kind' => 'self', 'entries' => [['subject_id' => $this->students[1]->id, 'criterion' => 'quality', 'level' => 1]]], $s)->assertForbidden();
        $this->write(['action' => 'assess', 'kind' => 'teacher', 'entries' => [['subject_id' => $s->id, 'criterion' => 'quality', 'level' => 1]]], $s)->assertForbidden();
        $this->write(['action' => 'assess', 'kind' => 'peer', 'entries' => [['subject_id' => $this->students[1]->id, 'criterion' => 'quality', 'level' => 1]]], $s)->assertOk();
        $outsider = User::factory()->create(['role' => 'student']);
        $this->actingAs($outsider)->get('/challenges/'.$this->challenge->id)->assertForbidden();
    }

    public function test_revision_conflict_prevents_lost_updates_and_batch_is_atomic(): void
    {
        $this->teamGrade();
        $this->actingAs($this->admin)->postJson('/challenges/'.$this->challenge->id, ['revision' => 1, 'action' => 'status', 'status' => 'active'])->assertConflict();
        $this->write(['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => '7'], ['student_id' => $this->students[1]->id, 'value' => '11']]])->assertUnprocessable();
        $this->assertSame(0, ModuleGrade::count());
    }

    public function test_publication_is_immutable_and_correction_requires_reopening(): void
    {
        $this->write(['action' => 'publish'])->assertUnprocessable();
        $this->complete();
        $this->write(['action' => 'publish'])->assertOk();
        $snapshot = Publication::first()->snapshot;
        $this->write(['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => '9']]])->assertUnprocessable();
        $this->write(['action' => 'reopen', 'reason' => ''])->assertUnprocessable();
        $this->write(['action' => 'reopen', 'reason' => 'Corrección del examen'])->assertOk();
        $this->write(['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => '9']]])->assertOk();
        $this->assertSame($snapshot, Publication::first()->snapshot);
        $this->write(['action' => 'publish'])->assertOk();
        $this->assertSame(2, Publication::count());
        $this->assertTrue(AuditEvent::where('action', 'reopen')->where('reason', 'Corrección del examen')->exists());
    }

    public function test_students_only_receive_their_published_result_and_own_assessments(): void
    {
        $this->complete();
        $s = $this->students[0];
        $this->actingAs($s)->get('/challenges/'.$this->challenge->id)->assertInertia(fn (Assert $page) => $page->component('Student')->where('book.result', null)->missing('book.rows')->missing('book.teams'));
        $this->write(['action' => 'publish'])->assertOk();
        $this->actingAs($s)->get('/challenges/'.$this->challenge->id)->assertInertia(fn (Assert $page) => $page->where('book.result.id', $s->id)->missing('book.rows'));
        $this->actingAs($s)->get('/challenges/'.$this->challenge->id.'/history')->assertForbidden();
    }

    public function test_teams_validate_size_duplicates_and_preserve_other_challenges(): void
    {
        $ids = array_map(fn ($s) => $s->id, $this->students);
        $this->write(['action' => 'teams', 'teams' => [['name' => 'A', 'students' => [$ids[0]]]]])->assertUnprocessable();
        $this->write(['action' => 'teams', 'teams' => [['name' => 'A', 'students' => [$ids[0], $ids[1]]], ['name' => 'B', 'students' => [$ids[0], $ids[2]]]]])->assertUnprocessable();
        $other = $this->challenge->replicate();
        $other->name = 'Otro reto';
        $other->save();
        $team = $other->teams()->create(['name' => 'Otro equipo']);
        $team->memberships()->create(['challenge_id' => $other->id, 'student_id' => $ids[0]]);
        $this->write(['action' => 'teams', 'teams' => [['name' => 'Nuevo', 'students' => $ids]]])->assertOk();
        $this->assertSame($team->id, Membership::where('challenge_id', $other->id)->first()->team_id);
        $this->teamGrade();
        $this->write(['action' => 'teams', 'teams' => [['name' => 'Cambio', 'students' => $ids]]])->assertUnprocessable();
    }

    public function test_weighted_evaluations_and_annual_average_do_not_skip_missing_period(): void
    {
        $this->complete();
        $this->challenge->update(['weight' => 1]);
        $other = $this->challenge->replicate();
        $other->name = 'Reto B';
        $other->weight = 3;
        $other->save();
        $other->modules()->attach(array_map(fn ($m) => $m->id, $this->modules));
        $other->students()->attach(array_map(fn ($s) => $s->id, $this->students));
        // Complete a no-distribution challenge with component weights that isolate the exam.
        $other->update(['component_weights' => ['transversal' => 0, 'challenge' => 0, 'exam' => 100]]);
        foreach ($this->students as $s) {
            foreach ($this->modules as $m) {
                ModuleGrade::create(['challenge_id' => $other->id, 'student_id' => $s->id, 'module_id' => $m->id, 'exam' => '10', 'updated_by' => $this->admin->id]);
            }
        }
        $row = collect(app(Gradebook::class)->report($this->classroom)['rows'])->firstWhere('student_id', $this->students[0]->id);
        $this->assertSame('9.33', $row['periods'][0]['grade']); // (7.30 + 10*3) / 4 = 9.325
        $this->assertNull($row['annual']);
    }

    public function test_rubric_edits_do_not_change_challenge_snapshots(): void
    {
        $rubric = Rubric::create(['name' => 'Plantilla', 'kind' => 'team', 'items' => $this->rubric['items']]);
        $this->teamGrade();
        $items = $rubric->items;
        $items[0]['levels'][1]['score'] = '1';
        $rubric->update(['items' => $items]);
        $this->assertSame('8.0000', app(Gradebook::class)->challenge($this->challenge->fresh())['teams'][0]['grade']);
    }

    public function test_optional_distribution_preserves_exact_rubric_precision(): void
    {
        $rubric = $this->rubric;
        $rubric['items'][0]['levels'][1]['score'] = '1';
        $second = $rubric['items'][0];
        $second['key'] = 'second';
        $second['weight'] = '2';
        $second['levels'][1]['score'] = '0';
        $rubric['items'][] = $second;
        $this->challenge->update(['distribution_enabled' => false, 'team_rubric' => $rubric, 'component_weights' => ['transversal' => 0, 'challenge' => 100, 'exam' => 0]]);
        foreach ($this->modules as $module) {
            $this->challenge->modules()->updateExistingPivot($module->id, ['defense_enabled' => false]);
        }
        $this->teamGrade();
        $this->write(['action' => 'assess', 'kind' => 'team', 'entries' => [['subject_id' => $this->challenge->teams()->first()->id, 'criterion' => 'second', 'level' => 1]]])->assertOk();

        $book = app(Gradebook::class)->challenge($this->challenge->fresh());

        $this->assertSame('1/3', $book['rows'][0]['modules'][$this->modules[0]->id]['exact']);
    }

    public function test_missing_grade_is_rejected_but_explicit_null_clears_it(): void
    {
        $data = ['action' => 'grades', 'field' => 'exam', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id]]];
        $this->write($data)->assertUnprocessable()->assertJsonValidationErrors('entries.0.value');
        $this->assertDatabaseCount('module_grades', 0);
        $data['entries'][0]['value'] = '7';
        $this->write($data)->assertOk();
        $data['entries'][0]['value'] = null;
        $this->write($data)->assertOk();
        $this->assertDatabaseHas('module_grades', ['student_id' => $this->students[0]->id, 'exam' => null]);
    }

    public function test_positive_signed_defense_is_accepted_and_recorded_once(): void
    {
        $this->allocate();
        $this->write(['action' => 'grades', 'field' => 'defense', 'module_id' => $this->modules[0]->id, 'entries' => [['student_id' => $this->students[0]->id, 'value' => '+0.5', 'notes' => 'Buena explicación']]])->assertOk();
        $grade = ModuleGrade::firstOrFail();
        $this->assertSame('0.5000', $grade->defense);
        $this->assertSame($this->admin->id, $grade->defense_teacher_id);
        $this->assertSame('Buena explicación', $grade->defense_notes);
    }
}
