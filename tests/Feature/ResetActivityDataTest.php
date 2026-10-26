<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Challenge;
use App\Models\Cycle;
use App\Models\GoogleRegistration;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetActivityDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_and_unconfirmed_backup_never_delete_data(): void
    {
        User::factory()->create(['role' => 'admin']);
        $challenge = Challenge::factory()->create();

        $this->artisan('erronk2d:reset-activity')->assertSuccessful();
        $this->artisan('erronk2d:reset-activity', ['--execute' => true])->assertFailed();

        $this->assertModelExists($challenge);
    }

    public function test_reset_keeps_accounts_catalog_and_empty_years_and_removes_all_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $challenge = Challenge::factory()->create();
        $group = $challenge->classroom;
        $module = $this->moduleForClass($group);
        $challenge->modules()->attach($module);
        $student = User::factory()->create();
        $this->enrollInClass($student, $group);
        $student->forceFill(['classroom_id' => $group->id, 'last_academic_year_id' => $group->academic_year_id])->save();
        $challenge->students()->attach($student);
        $team = $challenge->teams()->create(['name' => 'Equipo']);
        $team->memberships()->create(['challenge_id' => $challenge->id, 'student_id' => $student->id]);
        $challenge->publications()->create(['version' => 1, 'snapshot' => [], 'published_by' => $admin->id]);
        Rubric::create(['owner_id' => $group->owner_id, 'name' => 'Biblioteca', 'kind' => 'team', 'items' => []]);
        GoogleRegistration::factory()->create();
        $accounts = User::orderBy('id')->get(['id', 'name', 'email', 'password', 'role', 'active'])->toArray();
        $catalog = [Cycle::orderBy('id')->get()->toArray(), Module::orderBy('id')->get()->toArray(), AcademicYear::orderBy('id')->get()->toArray()];

        $this->artisan('erronk2d:reset-activity', ['--execute' => true, '--backup-confirmed' => true])->assertSuccessful();

        $this->assertSame($accounts, User::orderBy('id')->get(['id', 'name', 'email', 'password', 'role', 'active'])->toArray());
        $this->assertSame($catalog, [Cycle::orderBy('id')->get()->toArray(), Module::orderBy('id')->get()->toArray(), AcademicYear::orderBy('id')->get()->toArray()]);
        foreach (['classrooms', 'classroom_user', 'classroom_module', 'periods', 'enrollments', 'challenges', 'challenge_teacher', 'challenge_student', 'teams', 'memberships', 'publications', 'rubrics', 'google_registrations'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertNull($student->fresh()->classroom_id);
        $this->assertSame($group->academic_year_id, $student->fresh()->last_academic_year_id);
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'system.activity_reset', 'user_id' => $admin->id]);
        $this->artisan('erronk2d:reset-activity', ['--execute' => true, '--backup-confirmed' => true])->assertSuccessful();
    }
}
