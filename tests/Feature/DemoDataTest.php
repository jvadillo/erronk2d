<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_demo_load_keeps_exact_counts_and_preserves_manual_accounts_and_edits(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $manual = User::factory()->create(['role' => 'student']);
        $this->artisan('erronk2d:demo')->assertSuccessful();
        $student = User::where('demo_key', 'demo.student.1')->firstOrFail();
        $student->update(['name' => 'Nombre editado', 'password' => 'ClaveModificada123', 'active' => false]);
        $hash = $student->fresh()->password;
        $this->artisan('erronk2d:demo')->assertSuccessful();
        $this->assertSame(40, User::where('role', 'student')->whereNotNull('demo_key')->where('active', true)->count());
        $this->assertSame(10, User::where('role', 'teacher')->whereNotNull('demo_key')->where('active', true)->count());
        $this->assertDatabaseCount('academic_years', 0);
        $this->assertDatabaseCount('classrooms', 0);
        $this->assertDatabaseCount('modules', 0);
        $this->assertDatabaseCount('rubrics', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame($hash, $student->fresh()->password);
        $this->assertSame('Nombre editado', $student->fresh()->name);
        $this->assertModelExists($manual);
        $this->assertModelExists($admin);
        $this->assertDatabaseCount('users', 52);
        Mail::assertNothingSent();
    }

    public function test_collision_rolls_back_entire_load_without_claiming_manual_account(): void
    {
        $manual = User::factory()->create(['email' => 'STUDENT2@demo.erronk2d.test', 'role' => 'student']);
        $this->artisan('erronk2d:demo')->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertNull($manual->fresh()->demo_key);
        $this->assertSame(0, AcademicYear::count());
    }

    public function test_deleted_demo_account_is_replenished_without_duplicates(): void
    {
        $this->artisan('erronk2d:demo')->assertSuccessful();
        User::where('demo_key', 'demo.student.40')->firstOrFail()->delete();
        $teacher = User::where('demo_key', 'demo.teacher.10')->firstOrFail();
        $teacher->delete();
        $this->artisan('erronk2d:demo')->assertSuccessful();
        $this->assertSame(40, User::where('role', 'student')->whereNotNull('demo_key')->count());
        $this->assertDatabaseHas('users', ['demo_key' => 'demo.student.40', 'active' => true]);
        $this->assertSame(10, User::where('role', 'teacher')->whereNotNull('demo_key')->count());
    }
}
