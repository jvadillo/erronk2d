<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_environment_identifies_the_testing_instance(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('test_environment', true));
    }

    public function test_browser_environment_marker_is_absent_in_production(): void
    {
        app()->instance('env', 'production');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->missing('test_environment'));
    }

    public function test_inactive_account_cannot_login_or_use_existing_session(): void
    {
        $user = User::factory()->create(['active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get('/')->assertForbidden();
    }

    public function test_student_cannot_access_setup_reports_or_create_academic_records(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->get('/setup')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->postJson('/setup/year', ['name' => 'Forbidden', 'periods' => ['Primera']])->assertForbidden();
        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_login_and_logout_protect_the_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
    }

    public function test_password_recovery_is_generic_and_token_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $response->assertRedirect('/forgot-password');
        $message = session('success');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertSessionHas('success', $message);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345'];
        $this->post('/reset-password', $data)->assertRedirect('/login');
        $this->assertTrue(Hash::check('Clave12345', $user->fresh()->password));
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
    }

    public function test_first_administrator_command_creates_account_and_rejects_a_second(): void
    {
        $this->artisan('erronk2d:admin', ['email' => 'admin@example.test', '--name' => 'Administradora'])
            ->expectsQuestion('Contraseña (mínimo 10 caracteres)', 'Clave12345')
            ->expectsQuestion('Repite la contraseña', 'Clave12345')
            ->assertSuccessful();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('Clave12345', $admin->password));
        $this->artisan('erronk2d:admin', ['email' => 'another@example.test', '--name' => 'Otro'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('audit_events', ['user_id' => $admin->id, 'action' => 'setup.initial_admin']);
    }
}
