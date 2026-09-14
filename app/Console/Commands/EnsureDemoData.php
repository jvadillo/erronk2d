<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EnsureDemoData extends Command
{
    protected $signature = 'erronk2d:demo';

    protected $description = 'Mantiene 40 estudiantes y 10 profesores ficticios sin sobrescribir datos existentes';

    public function handle(): int
    {
        $lock = Cache::lock('erronk2d:demo', 120);
        if (! $lock->get()) {
            $this->error('Ya hay otra carga de datos de prueba en curso.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () {
                for ($i = 1; $i <= 10; $i++) {
                    $this->person('teacher', $i);
                }
                for ($i = 1; $i <= 40; $i++) {
                    $this->person('student', $i);
                }
            });
        } catch (RuntimeException|QueryException $exception) {
            $this->error('No se ha completado la carga. Hay una identidad o estructura de prueba en conflicto; no se ha modificado ningún dato.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
        $this->info('Datos de prueba preparados: 40 estudiantes y 10 profesores activos. Cuentas manuales conservadas.');

        return self::SUCCESS;
    }

    private function person(string $role, int $number): User
    {
        $key = 'demo.'.$role.'.'.$number;
        $email = $role.$number.'@demo.erronk2d.test';
        $user = User::where('demo_key', $key)->first();
        if (! $user) {
            if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
                throw new RuntimeException('Identidad reservada ocupada.');
            }
            $user = new User;
            $user->forceFill(['demo_key' => $key, 'role' => $role, 'name' => ($role === 'student' ? 'Estudiante' : 'Profesor').' de prueba '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'email' => $email, 'password' => Str::random(64)]);
        }
        if ($user->role !== $role) {
            throw new RuntimeException('Revisa el rol de una cuenta ficticia.');
        }
        $user->active = true;
        $user->save();

        return $user;
    }
}
