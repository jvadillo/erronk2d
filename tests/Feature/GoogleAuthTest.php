<?php

namespace Tests\Feature;

use App\Models\GoogleRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.enabled' => true, 'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret', 'services.google.redirect' => 'https://erronk2d.example.test/auth/google/callback']);
        Http::preventStrayRequests();
    }

    private function begin(): string
    {
        $response = $this->get('/auth/google');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        return $query['state'];
    }

    private function fakeGoogle(array $overrides = []): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response(array_replace([
                'sub' => 'google-person-1', 'email' => 'person@example.test', 'name' => 'Persona de prueba', 'email_verified' => true,
            ], $overrides)),
        ]);
    }

    private function googleCallback(string $state): TestResponse
    {
        return $this->get('/auth/google/callback?'.http_build_query(['state' => $state, 'code' => 'fake-code']));
    }

    public function test_disabled_google_hides_button_and_refuses_requests_without_network(): void
    {
        Http::fake();
        config(['services.google.enabled' => false]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('googleUrl', null));
        $this->get('/auth/google')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('google');

        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_authorization_uses_fixed_callback_state_and_pkce_without_exposing_secret(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('googleUrl', route('google.redirect'))
            ->missing('googleClientSecret')->missing('google_id'));
        $response = $this->get('/auth/google?redirect=https://attacker.example.test');
        $url = $response->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('accounts.google.com', parse_url($url, PHP_URL_HOST));
        $this->assertSame('https://erronk2d.example.test/auth/google/callback', $query['redirect_uri']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('google_oauth.state'), $query['state']);
        $this->assertGreaterThanOrEqual(43, strlen($query['code_challenge']));
        $this->assertStringNotContainsString('test-secret', $url);
        $this->assertStringNotContainsString(session('google_oauth.verifier'), $url);
    }

    public static function invalidCallbacks(): array
    {
        return ['missing state' => ['missing'], 'wrong state' => ['wrong'], 'expired' => ['expired'], 'cancelled' => ['cancelled'], 'missing code' => ['code']];
    }

    #[DataProvider('invalidCallbacks')]
    public function test_invalid_callback_never_calls_google_or_creates_account(string $case): void
    {
        Http::fake();
        $this->freezeTime();
        $state = $this->begin();
        $parameters = ['state' => $state, 'code' => 'fake-code'];
        if ($case === 'missing') {
            unset($parameters['state']);
        } elseif ($case === 'wrong') {
            $parameters['state'] = 'wrong';
        } elseif ($case === 'expired') {
            $this->travel(11)->minutes();
        } elseif ($case === 'cancelled') {
            $parameters['error'] = 'access_denied';
        } else {
            unset($parameters['code']);
        }

        $this->get('/auth/google/callback?'.http_build_query($parameters))
            ->assertRedirect('/login')->assertSessionHasErrors('google')->assertSessionMissing('google_oauth');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();
    }

    public function test_registration_is_pending_idempotent_and_ignores_requested_privileges(): void
    {
        $this->fakeGoogle(['role' => 'admin', 'permissions' => ['publish_results']]);
        $state = $this->begin();
        $verifier = session('google_oauth.verifier');

        $this->googleCallback($state)->assertRedirect('/login')->assertSessionHas('success');
        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHas('success');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('google_registrations', 1);
        $this->assertDatabaseHas('google_registrations', ['email' => 'person@example.test', 'status' => 'pending']);
        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['code_verifier'] === $verifier && $request['client_secret'] === 'test-secret');
        Http::assertSent(fn ($request) => $request->url() === 'https://openidconnect.googleapis.com/v1/userinfo'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token'));
    }

    public static function invalidProfiles(): array
    {
        return ['unverified' => [['email_verified' => false]], 'string false' => [['email_verified' => 'false']],
            'missing email' => [['email' => null]], 'invalid email' => [['email' => 'invalid']],
            'missing subject' => [['sub' => null]]];
    }

    #[DataProvider('invalidProfiles')]
    public function test_unverified_or_incomplete_google_profile_is_rejected(array $profile): void
    {
        $this->fakeGoogle($profile);

        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('google_registrations', 0);
        Http::assertSentCount(2);
    }

    public function test_provider_failure_is_reported_without_exposing_response_or_tokens(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error' => 'private-provider-detail'], 503)]);

        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHasErrors([
            'google' => 'No se ha podido conectar con Google. Inténtalo de nuevo más tarde.',
        ]);

        Http::assertSentCount(1);
        $this->assertGuest();
    }

    public function test_connection_failure_is_handled_without_creating_an_account(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::failedConnection()]);

        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHasErrors('google');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('google_registrations', 0);
        $this->assertGuest();
    }

    public function test_missing_access_token_does_not_request_profile(): void
    {
        Http::fake(['https://oauth2.googleapis.com/token' => Http::response([])]);

        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHasErrors('google');

        Http::assertSentCount(1);
        $this->assertGuest();
    }

    public function test_linked_user_logs_in_by_subject_without_overwriting_role_or_contact_email(): void
    {
        $user = User::factory()->create(['role' => 'teacher', 'permissions' => ['enter_exams'], 'google_id' => 'google-person-1']);
        $this->fakeGoogle();
        $state = $this->begin();

        $this->googleCallback($state)->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('teacher', $user->fresh()->role);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame(['enter_exams'], $user->fresh()->permissions);
        $this->assertDatabaseCount('google_registrations', 0);
        Http::assertSentCount(2);
    }

    public function test_disabled_linked_account_cannot_use_google(): void
    {
        User::factory()->create(['active' => false, 'google_id' => 'google-person-1']);
        $this->fakeGoogle();

        $this->googleCallback($this->begin())->assertRedirect('/login')->assertSessionHasErrors('google');

        $this->assertGuest();
        Http::assertSentCount(2);
    }

    public function test_existing_email_requires_local_password_before_linking_and_state_cannot_be_replayed(): void
    {
        $user = User::factory()->create(['email' => 'Person@example.test', 'role' => 'admin']);
        $this->fakeGoogle();
        $state = $this->begin();

        $this->googleCallback($state)->assertRedirect('/login');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('googleLink', 'person@example.test'));
        $this->post('/auth/google/link', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->post('/auth/google/link', ['password' => 'password', 'role' => 'student'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-person-1', $user->fresh()->google_id);
        $this->assertSame('admin', $user->fresh()->role);
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.google_link', 'user_id' => $user->id]);
        $this->post('/logout');
        $this->googleCallback($state)->assertSessionHasErrors('google');
        Http::assertSentCount(2);
    }

    public function test_link_expires_and_rechecks_account_activation(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['email' => 'person@example.test']);
        $this->fakeGoogle();
        $this->googleCallback($this->begin());
        $user->update(['active' => false]);

        $this->post('/auth/google/link', ['password' => 'password'])->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
        Http::assertSentCount(2);
    }

    public function test_expired_link_and_direct_link_without_google_cannot_authenticate(): void
    {
        $this->freezeTime();
        User::factory()->create(['email' => 'person@example.test']);
        $this->fakeGoogle();
        $this->googleCallback($this->begin());
        $this->travel(11)->minutes();

        $this->post('/auth/google/link', ['password' => 'password'])->assertSessionHasErrors('google')->assertSessionMissing('google_link');
        $this->post('/auth/google/link', ['password' => 'password'])->assertSessionHasErrors('google');
        $this->assertGuest();
        Http::assertSentCount(2);
    }

    public function test_link_password_attempts_are_limited_and_cancellation_clears_identity(): void
    {
        User::factory()->create(['email' => 'person@example.test']);
        $this->fakeGoogle();
        $this->googleCallback($this->begin());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/auth/google/link', ['password' => 'wrong'])->assertSessionHasErrors('password');
        }

        $this->post('/auth/google/link', ['password' => 'password'])->assertSessionHasErrors('google');
        $this->get('/auth/google/cancel')->assertRedirect('/login')->assertSessionMissing('google_link');
        $this->assertGuest();
        Http::assertSentCount(2);
    }

    public function test_rejected_registration_is_not_reopened_by_another_google_login(): void
    {
        GoogleRegistration::factory()->create(['google_id' => 'google-person-1', 'status' => 'rejected']);
        $this->fakeGoogle();

        $this->googleCallback($this->begin())->assertSessionHasErrors('google');

        $this->assertDatabaseCount('google_registrations', 1);
        $this->assertDatabaseHas('google_registrations', ['status' => 'rejected']);
        $this->assertGuest();
        Http::assertSentCount(2);
    }
}
