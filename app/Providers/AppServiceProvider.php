<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Restablece tu contraseña de Erronk2D')
                ->greeting('Hola, '.$user->name)
                ->line('Has solicitado un enlace para establecer una nueva contraseña en Erronk2D.')
                ->action('Establecer contraseña', $url)
                ->line('Este enlace caduca en '.config('auth.passwords.users.expire').' minutos.')
                ->line('Si no has solicitado este cambio, puedes ignorar el mensaje.')
                ->salutation('El equipo de Erronk2D');
        });
    }
}
