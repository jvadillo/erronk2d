<?php

namespace Tests\Feature;

use App\Domain\AcademicContext;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AcademicContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_access_selects_latest_created_open_year_and_remembers_it(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        AcademicYear::factory()->create(['created_at' => '2026-01-01']);
        $latest = AcademicYear::factory()->create(['created_at' => '2026-02-01']);
        AcademicYear::factory()->create(['created_at' => '2026-03-01', 'is_open' => false]);

        $this->actingAs($teacher)->get('/')->assertOk();

        $this->assertSame($latest->id, $teacher->fresh()->last_academic_year_id);
        $this->assertSame($latest->id, session('academic_context.year_id'));
    }

    public function test_switch_persists_preference_for_next_session(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $old = AcademicYear::factory()->create();
        AcademicYear::factory()->create();

        $this->actingAs($teacher)->post('/academic-context', ['academic_year_id' => $old->id])->assertRedirect('/');

        $this->assertSame($old->id, $teacher->fresh()->last_academic_year_id);
        session()->forget('academic_context');
        $this->get('/')->assertOk();
        $this->assertSame($old->id, session('academic_context.year_id'));
    }

    public function test_student_only_has_years_of_enrollment_including_closed_history(): void
    {
        $student = User::factory()->create();
        $closed = AcademicYear::factory()->create(['is_open' => false]);
        $class = Classroom::factory()->create(['academic_year_id' => $closed->id]);
        Enrollment::factory()->create(['classroom_id' => $class->id, 'student_id' => $student->id, 'ended_at' => '2026-06-30']);
        $other = AcademicYear::factory()->create();

        $this->actingAs($student)->post('/academic-context', ['academic_year_id' => $closed->id])->assertRedirect('/');
        $this->post('/academic-context', ['academic_year_id' => $other->id])->assertNotFound();

        $this->assertSame($closed->id, $student->fresh()->last_academic_year_id);
    }

    public function test_teacher_loses_closed_year_access_after_removal_but_keeps_authorship(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $closed = AcademicYear::factory()->create(['is_open' => false]);
        $class = Classroom::factory()->create(['academic_year_id' => $closed->id]);
        $class->users()->attach($teacher);
        $context = app(AcademicContext::class);
        $this->assertSame([$closed->id], $context->availableYears($teacher)->pluck('id')->all());

        $class->users()->detach($teacher);

        $this->assertSame([], $context->availableYears($teacher)->pluck('id')->all());
        $this->assertModelExists($teacher);
        $this->assertSame($class->owner_id, $class->fresh()->owner_id);
    }

    public function test_class_visibility_is_limited_to_memberships_and_selected_year(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $year = AcademicYear::factory()->create();
        $owned = Classroom::factory()->create(['owner_id' => $teacher->id, 'academic_year_id' => $year->id]);
        $shared = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $shared->users()->attach($teacher);
        Classroom::factory()->create(['academic_year_id' => $year->id]);
        Classroom::factory()->create(['owner_id' => $teacher->id]);
        Route::middleware(['web', 'auth', 'academic'])->get('/context-test', fn (Request $request, AcademicContext $context) => $context->classrooms($request->user())->orderBy('id')->pluck('id'));

        $this->actingAs($teacher)->post('/academic-context', ['academic_year_id' => $year->id]);
        $this->get('/context-test')->assertExactJson([$owned->id, $shared->id]);
    }

    public function test_missing_or_stale_context_cannot_write_and_closed_year_is_read_only(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $old = AcademicYear::factory()->create();
        $latest = AcademicYear::factory()->create();
        Route::middleware(['web', 'auth', 'academic'])->post('/context-write-test', function (Request $request, AcademicContext $context) {
            $context->requireWritable($request);

            return response()->noContent();
        });

        $this->actingAs($teacher)->post('/context-write-test')->assertConflict();
        $this->withHeader('X-Academic-Year', (string) $old->id)->post('/context-write-test')->assertConflict();
        $this->withHeader('X-Academic-Year', (string) $latest->id)->post('/context-write-test')->assertNoContent();
        Classroom::factory()->create(['academic_year_id' => $latest->id, 'owner_id' => $teacher->id]);
        $latest->update(['is_open' => false]);
        $this->post('/context-write-test')->assertForbidden();
        $this->assertDatabaseCount('classrooms', 1);
    }

    public function test_no_available_year_does_not_prevent_admin_global_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/setup/courses')->assertOk();

        $this->assertNull(session('academic_context.year_id'));
    }

    public function test_multiple_enrollments_do_not_change_accounts_or_other_enrollments(): void
    {
        $student = User::factory()->create();
        $class = Classroom::factory()->create();
        $second = Classroom::factory()->create(['academic_year_id' => $class->academic_year_id]);
        Enrollment::factory()->create(['classroom_id' => $class->id, 'student_id' => $student->id]);

        Enrollment::factory()->create(['classroom_id' => $second->id, 'student_id' => $student->id]);

        $this->assertSame([$student->id], $class->enrollments()->pluck('student_id')->all());
        $this->assertSame([$student->id], $second->enrollments()->pluck('student_id')->all());
        $this->assertModelExists($student);
    }
}
