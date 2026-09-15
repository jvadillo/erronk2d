<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GoogleRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public static function nonAdministrators(): array
    {
        return ['student' => ['student'], 'teacher' => ['teacher']];
    }

    #[DataProvider('nonAdministrators')]
    public function test_only_administrator_can_approve_google_registration(string $role): void
    {
        $registration = GoogleRegistration::factory()->create();
        $user = User::factory()->create(['role' => $role, 'permissions' => User::PERMISSIONS]);

        $this->actingAs($user)->post('/registrations/'.$registration->id, ['decision' => 'approve', 'role' => 'teacher'])->assertForbidden();

        $this->assertSame('pending', $registration->fresh()->status);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_guest_cannot_approve_registration(): void
    {
        $registration = GoogleRegistration::factory()->create();

        $this->post('/registrations/'.$registration->id, ['decision' => 'reject'])->assertRedirect('/login');

        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_administrator_approves_student_with_class_and_no_injected_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = GoogleRegistration::factory()->create();
        $year = AcademicYear::create(['name' => 'Curso de prueba']);
        $classroom = Classroom::create(['academic_year_id' => $year->id, 'name' => 'Clase de prueba']);

        $this->actingAs($admin)->withHeader('X-Academic-Year', $year->id)->from('/setup')->post('/registrations/'.$registration->id, [
            'decision' => 'approve', 'role' => 'student', 'classroom_id' => $classroom->id, 'permissions' => ['publish_results'],
        ])->assertRedirect('/setup')->assertSessionHasNoErrors();

        $student = User::where('email', $registration->email)->firstOrFail();
        $this->assertTrue($student->active);
        $this->assertSame('student', $student->role);
        $this->assertDatabaseHas('enrollments', ['classroom_id' => $classroom->id, 'student_id' => $student->id, 'ended_at' => null]);
        $this->assertSame([], $student->permissions);
        $this->assertSame($registration->google_id, $student->google_id);
        $this->assertSame($student->id, $registration->fresh()->user_id);
        $this->assertDatabaseHas('audit_events', ['action' => 'registration.approved', 'user_id' => $admin->id]);
        $this->assertArrayNotHasKey('google_id', $student->toArray());
        $this->assertArrayNotHasKey('google_id', $registration->toArray());
    }

    public function test_teacher_approval_does_not_grant_permissions_or_class_and_cannot_be_replayed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = GoogleRegistration::factory()->create();

        $this->actingAs($admin)->post('/registrations/'.$registration->id, [
            'decision' => 'approve', 'role' => 'teacher', 'classroom_id' => 999, 'permissions' => User::PERMISSIONS,
        ])->assertSessionHasNoErrors();

        $teacher = User::where('email', $registration->email)->firstOrFail();
        $this->assertSame('teacher', $teacher->role);
        $this->assertSame([], $teacher->permissions);
        $this->assertNull($teacher->classroom_id);
        $this->post('/registrations/'.$registration->id, ['decision' => 'approve', 'role' => 'teacher'])->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_student_approval_requires_class_and_administrator_role_cannot_be_requested(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = GoogleRegistration::factory()->create();
        $this->actingAs($admin);

        $this->post('/registrations/'.$registration->id, ['decision' => 'approve', 'role' => 'student'])
            ->assertSessionHasErrors(['classroom_id' => 'Selecciona un grupo para el estudiante.']);
        $this->post('/registrations/'.$registration->id, ['decision' => 'approve', 'role' => 'admin'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_rejecting_registration_creates_no_user_and_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = GoogleRegistration::factory()->create();

        $this->actingAs($admin)->post('/registrations/'.$registration->id, ['decision' => 'reject'])->assertSessionHasNoErrors();

        $this->assertSame('rejected', $registration->fresh()->status);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'registration.rejected']);
    }

    public function test_existing_email_is_never_taken_over_during_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $registration = GoogleRegistration::factory()->create(['email' => 'person@example.test']);
        $existing = User::factory()->create(['email' => 'Person@example.test']);

        $this->actingAs($admin)->post('/registrations/'.$registration->id, ['decision' => 'approve', 'role' => 'teacher'])
            ->assertSessionHasErrors('decision');

        $this->assertNull($existing->fresh()->google_id);
        $this->assertSame('pending', $registration->fresh()->status);
    }

    public function test_pending_requests_are_visible_only_to_administrators(): void
    {
        GoogleRegistration::factory()->create(['name' => '<script>untrusted</script>']);
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($admin)->get('/setup/registrations')->assertInertia(fn (Assert $page) => $page->has('registrations', 1)
            ->where('registrations.0.name', '<script>untrusted</script>')->missing('registrations.0.google_id'));
        $this->actingAs($teacher)->get('/setup/classrooms')->assertInertia(fn (Assert $page) => $page->has('registrations', 0));
    }

    public function test_changing_account_email_removes_google_link_without_changing_other_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'google_id' => 'old-google-subject']);

        $this->actingAs($admin)->post('/setup/teacher', [
            'id' => $teacher->id, 'name' => $teacher->name, 'email' => 'changed@example.test', 'active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertNull($teacher->fresh()->google_id);
        $this->assertNull($teacher->fresh()->email_verified_at);
        $this->assertSame('changed@example.test', $teacher->fresh()->email);
    }
}
