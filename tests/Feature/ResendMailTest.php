<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Resend\Client;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ResendMailTest extends TestCase
{
    use RefreshDatabase;

    private function interceptResend(Response $response, array &$history): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => 're_test_only', 'mail.from.address' => 'no-reply@erronk2d.jonvadillo.com', 'mail.from.name' => 'Erronk2D']);
        URL::forceRootUrl('https://erronk2d.jonvadillo.com');
        URL::forceScheme('https');
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($history));
        $client = new Client(new HttpTransporter(new HttpClient(['handler' => $stack]), BaseUri::from('api.resend.com'), Headers::withAuthorization(ApiKey::from('re_test_only'))));
        $mailer = Mail::mailer('resend');
        $this->assertInstanceOf(ResendTransport::class, $mailer->getSymfonyTransport());
        $mailer->setSymfonyTransport(new ResendTransport($client));
    }

    public function test_password_recovery_sends_spanish_email_through_resend_with_https_link(): void
    {
        $history = [];
        $this->interceptResend(new Response(200, ['Content-Type' => 'application/json'], '{"id":"test-email-id"}'), $history);
        $user = User::factory()->create(['email' => 'student@example.test']);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])->assertRedirect('/forgot-password')->assertSessionHas('success');

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame('https://api.resend.com/emails', (string) $request->getUri());
        $this->assertSame('Bearer re_test_only', $request->getHeaderLine('Authorization'));
        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(['student@example.test'], $payload['to']);
        $sender = Address::create($payload['from']);
        $this->assertSame('no-reply@erronk2d.jonvadillo.com', $sender->getAddress());
        $this->assertSame('Erronk2D', $sender->getName());
        $this->assertSame('Restablece tu contraseña de Erronk2D', $payload['subject']);
        $this->assertStringContainsString('https://erronk2d.jonvadillo.com/reset-password/', $payload['html']);
        $this->assertStringContainsString('Establecer contraseña', $payload['html']);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_resend_failure_returns_safe_feedback_and_keeps_password_unchanged(): void
    {
        $history = [];
        $this->interceptResend(new Response(403, ['Content-Type' => 'application/json'], '{"name":"validation_error","message":"Provider internal detail"}'), $history);
        $user = User::factory()->create();
        $password = $user->password;
        Log::shouldReceive('warning')->once()->with('No se pudo entregar el correo de recuperación.', ['mailer' => 'resend']);

        $this->postJson('/forgot-password', ['email' => $user->email])->assertUnprocessable()->assertJsonValidationErrors('email')->assertDontSee('Provider internal detail');

        $this->assertSame($password, $user->fresh()->password);
        $this->assertCount(1, $history);
    }
}
