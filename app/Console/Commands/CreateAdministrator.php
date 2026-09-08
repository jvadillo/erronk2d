<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdministrator extends Command
{
    protected $signature = 'erronk2d:admin {email} {--name= : Nombre de la persona administradora}';

    protected $description = 'Crea la primera cuenta administradora sin mostrar ni pasar contraseñas en argumentos';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('Ya existe una cuenta administradora. No se ha modificado ninguna cuenta.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Ejecuta el comando desde un terminal interactivo para introducir la contraseña de forma oculta.');

            return self::FAILURE;
        }
        $data = ['email' => strtolower($this->argument('email')), 'name' => $this->option('name') ?: $this->ask('Nombre'), 'password' => $this->secret('Contraseña (mínimo 10 caracteres)'), 'password_confirmation' => $this->secret('Repite la contraseña')];
        $validator = Validator::make($data, ['email' => 'required|email|max:255|unique:users,email', 'name' => 'required|string|max:150', 'password' => 'required|string|min:10|max:200|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'admin', 'active' => true]);
            AuditEvent::create(['user_id' => $user->id, 'action' => 'setup.initial_admin', 'after' => $user->only(['id', 'name', 'email', 'role'])]);
        });
        $this->info('Cuenta administradora creada.');

        return self::SUCCESS;
    }
}
