<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Challenge;
use App\Models\GoogleRegistration;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetAcademicDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_and_missing_backup_never_delete_data(): void
    {
        $challenge = Challenge::factory()->create();
        $this->artisan('erronk2d:reset-academics')->assertSuccessful();
        $this->artisan('erronk2d:reset-academics', ['--execute' => true])->assertFailed();
        $this->assertModelExists($challenge);
    }

    public function test_missing_preserved_accounts_roll_back_without_deleting_anything(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $challenge = Challenge::factory()->create();
        $this->artisan('erronk2d:reset-academics', ['--execute' => true, '--backup-confirmed' => true])->assertFailed();
        $this->assertModelExists($challenge);
        $this->assertModelExists($admin);
        $this->assertDatabaseMissing('audit_events', ['action' => 'system.academic_reset']);
    }

    public function test_reset_preserves_credentials_removes_all_academics_and_cannot_run_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->artisan('erronk2d:demo')->assertSuccessful();
        $student = User::where('demo_key', 'demo.student.1')->firstOrFail();
        $student->update(['name' => 'Nombre conservado', 'password' => 'CredencialConservada123']);
        $hashes = User::whereNotNull('demo_key')->orWhere('role', 'admin')->orderBy('id')->pluck('password', 'id')->all();
        $challenge = Challenge::factory()->create();
        $this->enrollInClass($student, $challenge->classroom);
        $challenge->students()->attach($student);
        $team = $challenge->teams()->create(['name' => 'Equipo anterior']);
        $team->memberships()->create(['challenge_id' => $challenge->id, 'student_id' => $student->id]);
        $challenge->publications()->create(['version' => 1, 'snapshot' => [], 'published_by' => $admin->id]);
        Rubric::create(['owner_id' => $challenge->classroom->owner_id, 'name' => 'Anterior', 'kind' => 'team', 'items' => []]);
        GoogleRegistration::factory()->create();
        $this->artisan('erronk2d:reset-academics', ['--execute' => true, '--backup-confirmed' => true])->assertSuccessful();
        $this->assertSame($hashes, User::orderBy('id')->pluck('password', 'id')->all());
        $this->assertSame('Nombre conservado', $student->fresh()->name);
        foreach (['academic_years', 'cycles', 'classrooms', 'modules', 'enrollments', 'rubrics', 'challenges', 'publications', 'memberships', 'google_registrations'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'system.academic_reset']);
        $newYear = AcademicYear::factory()->create();
        $this->artisan('erronk2d:reset-academics', ['--execute' => true, '--backup-confirmed' => true])->assertSuccessful();
        $this->artisan('erronk2d:demo')->assertSuccessful();
        $this->assertModelExists($newYear);
        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('users', 51);
    }
}
