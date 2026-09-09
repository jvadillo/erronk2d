<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    public function show(Request $request): Response
    {
        $link = $request->session()->get('google_link');

        return Inertia::render('Login', [
            'googleUrl' => GoogleAuthController::enabled() ? route('google.redirect') : null,
            'googleLink' => is_array($link) && ($link['expires_at'] ?? 0) > now()->timestamp ? $link['email'] : null,
            'googleLinkUrl' => route('google.link'),
            'googleCancelUrl' => route('google.cancel'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:200']);
        $key = Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Inténtalo dentro de un minuto.']);
        }
        if (! Auth::attempt([...$data, 'active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink($data);
        } catch (TransportExceptionInterface $exception) {
            Log::warning('No se pudo entregar el correo de recuperación.', ['mailer' => config('mail.default')]);
            throw ValidationException::withMessages(['email' => 'No se ha podido enviar el enlace. Inténtalo de nuevo más tarde o contacta con la administración.']);
        }

        return back()->with('success', 'Si la cuenta existe, recibirás un enlace para restablecer la contraseña.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', 'min:10', 'max:200']]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'El enlace no es válido o ha caducado.']);
        }

        return redirect('/login')->with('success', 'Contraseña actualizada.');
    }
}
