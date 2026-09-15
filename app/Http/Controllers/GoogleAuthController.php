<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\GoogleRegistration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public static function enabled(): bool
    {
        return config('services.google.enabled') && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret')) && filled(config('services.google.redirect'));
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (! self::enabled()) {
            return $this->error('El acceso con Google todavía no está disponible.');
        }
        $request->session()->forget('google_link');
        $state = Str::random(64);
        $verifier = Str::random(64);
        $request->session()->put('google_oauth', ['state' => $state, 'verifier' => $verifier, 'expires_at' => now()->addMinutes(10)->timestamp]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        $flow = $request->session()->pull('google_oauth');
        $request->session()->forget('google_link');
        if (! self::enabled()) {
            return $this->error('El acceso con Google todavía no está disponible.');
        }
        if (! is_array($flow) || ($flow['expires_at'] ?? 0) <= now()->timestamp
            || ! is_string($request->query('state')) || ! hash_equals($flow['state'], $request->query('state'))) {
            return $this->error('La solicitud de Google ha caducado o no es válida. Vuelve a intentarlo.');
        }
        if ($request->has('error')) {
            return $this->error('No se ha completado el acceso con Google. Puedes volver a intentarlo.');
        }
        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 4096) {
            return $this->error('Google no ha devuelto un código de acceso válido.');
        }

        try {
            $token = Http::asForm()->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(10)
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri' => config('services.google.redirect'),
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'code_verifier' => $flow['verifier'],
                ])->throw()->json('access_token');
            if (! is_string($token) || $token === '') {
                return $this->error('No se ha podido verificar la cuenta de Google. Inténtalo de nuevo.');
            }
            $identity = Http::withToken($token)->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(10)
                ->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();
        } catch (ConnectionException|RequestException) {
            return $this->error('No se ha podido conectar con Google. Inténtalo de nuevo más tarde.');
        }
        if (! is_array($identity) || Validator::make($identity, [
            'sub' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ])->fails() || ($identity['email_verified'] ?? false) !== true) {
            return $this->error('Google debe proporcionar un correo electrónico verificado para continuar.');
        }
        $profile = [
            'google_id' => $identity['sub'],
            'email' => Str::lower($identity['email']),
            'name' => Str::limit(is_string($identity['name'] ?? null) && trim($identity['name']) !== '' ? trim($identity['name']) : $identity['email'], 150, ''),
        ];

        try {
            return DB::transaction(function () use ($request, $profile) {
                $linked = User::where('google_id', $profile['google_id'])->lockForUpdate()->first();
                if ($linked) {
                    return $linked->active ? $this->signIn($request, $linked) : $this->error('Esta cuenta está desactivada. Contacta con la administración.');
                }
                $matches = User::whereRaw('LOWER(email) = ?', [$profile['email']])->lockForUpdate()->get();
                if ($matches->count() > 1) {
                    return $this->error('Contacta con la administración para revisar las cuentas asociadas a este correo.');
                }
                if ($user = $matches->first()) {
                    if (! $user->active || $user->google_id !== null) {
                        return $this->error('No se puede vincular esta cuenta. Contacta con la administración.');
                    }
                    $request->session()->regenerate();
                    $request->session()->put('google_link', [...$profile, 'user_id' => $user->id, 'expires_at' => now()->addMinutes(10)->timestamp]);

                    return redirect()->route('login');
                }
                $registration = GoogleRegistration::where('google_id', $profile['google_id'])
                    ->orWhere('email', $profile['email'])->lockForUpdate()->first();
                if (! $registration) {
                    GoogleRegistration::create($profile);

                    return redirect()->route('login')->with('success', 'Solicitud de registro recibida. La administración debe asignarte un rol y, si eres estudiante, un grupo antes de que puedas entrar.');
                }
                if ($registration->status !== 'pending' || $registration->google_id !== $profile['google_id']) {
                    return $this->error('Contacta con la administración para revisar tu solicitud de acceso.');
                }

                return redirect()->route('login')->with('success', 'Tu solicitud de registro está pendiente de aprobación. Todavía no tienes acceso a los datos del centro.');
            });
        } catch (UniqueConstraintViolationException) {
            return $this->error('La cuenta se ha actualizado mientras accedías. Vuelve a intentarlo.');
        }
    }

    public function link(Request $request): RedirectResponse
    {
        $profile = $request->session()->get('google_link');
        if (! self::enabled() || ! is_array($profile) || ($profile['expires_at'] ?? 0) <= now()->timestamp) {
            $request->session()->forget('google_link');

            return $this->error('La vinculación ha caducado. Vuelve a entrar con Google.');
        }
        $data = $request->validate(['password' => 'required|string|max:200']);
        $key = 'google-link:'.$profile['user_id'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->error('Demasiados intentos. Inténtalo dentro de un minuto.');
        }
        RateLimiter::hit($key, 60);
        try {
            return DB::transaction(function () use ($request, $profile, $data, $key) {
                $user = User::lockForUpdate()->find($profile['user_id']);
                if (! $user || ! $user->active || Str::lower($user->email) !== $profile['email']
                    || ($user->google_id !== null && $user->google_id !== $profile['google_id'])) {
                    $request->session()->forget('google_link');

                    return $this->error('La cuenta ha cambiado. Vuelve a entrar con Google o contacta con la administración.');
                }
                if (! Hash::check($data['password'], $user->password)) {
                    return redirect()->route('login')->withErrors(['password' => 'La contraseña de Erronk2D no es correcta.']);
                }
                $user->forceFill(['google_id' => $profile['google_id']])->save();
                AuditEvent::create(['user_id' => $user->id, 'action' => 'auth.google_link', 'after' => ['user_id' => $user->id]]);
                RateLimiter::clear($key);
                $request->session()->forget('google_link');

                return $this->signIn($request, $user);
            });
        } catch (UniqueConstraintViolationException) {
            $request->session()->forget('google_link');

            return $this->error('Esta cuenta de Google ya está vinculada. Contacta con la administración.');
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['google_oauth', 'google_link']);

        return redirect()->route('login');
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function error(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}
