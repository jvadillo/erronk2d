<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResetAcademicData extends Command
{
    protected $signature = 'erronk2d:reset-academics {--execute : Ejecutar el reinicio único autorizado} {--backup-confirmed : La copia previa ha sido comprobada por ops/deploy}';

    protected $description = 'Reinicia una sola vez la actividad académica conservando administradores y las 50 cuentas de ejemplo';

    public function handle(): int
    {
        if (AuditEvent::where('action', 'system.academic_reset')->exists()) {
            $this->info('El reinicio inicial ya se ejecutó. No se ha modificado ningún dato.');

            return self::SUCCESS;
        }
        $keys = [];
        foreach (['student' => 40, 'teacher' => 10] as $role => $count) {
            for ($number = 1; $number <= $count; $number++) {
                $keys['demo.'.$role.'.'.$number] = $role;
            }
        }
        $this->table(['Entidad', 'Registros actuales'], collect(['users', 'academic_years', 'cycles', 'classrooms', 'modules', 'enrollments', 'rubrics', 'challenges'])->map(fn ($table) => [$table, DB::table($table)->count()])->all());
        if (! $this->option('execute')) {
            $this->info('Vista previa. No se ha modificado ningún dato.');

            return self::SUCCESS;
        }
        if (! $this->option('backup-confirmed') || (app()->environment('production') && ! app()->isDownForMaintenance())) {
            $this->error('El reinicio requiere una copia previa comprobada y producción en mantenimiento.');

            return self::FAILURE;
        }
        $lock = Cache::lock('erronk2d:reset-academics', 300);
        if (! $lock->get()) {
            $this->error('Ya hay un reinicio en curso.');

            return self::FAILURE;
        }
        try {
            DB::transaction(function () use ($keys) {
                if (AuditEvent::where('action', 'system.academic_reset')->exists()) {
                    return;
                }
                $admin = User::where('role', 'admin')->where('active', true)->lockForUpdate()->first();
                $people = User::whereIn('demo_key', array_keys($keys))->lockForUpdate()->get();
                if (! $admin || $people->count() !== 50 || $people->contains(fn ($person) => $keys[$person->demo_key] !== $person->role)) {
                    throw new RuntimeException('Falta el acceso administrativo o alguna de las 50 identidades de ejemplo.');
                }
                User::query()->update(['classroom_id' => null, 'last_academic_year_id' => null, 'remember_token' => null]);
                foreach (['audit_events', 'publications', 'assessments', 'module_grades', 'memberships', 'teams', 'challenge_student', 'challenge_module', 'challenges', 'classroom_module_user', 'classroom_module', 'enrollments', 'classroom_user', 'module_user', 'rubric_user', 'rubrics', 'modules', 'periods', 'classrooms', 'academic_years', 'cycles', 'google_registrations', 'sessions', 'password_reset_tokens', 'jobs', 'job_batches', 'failed_jobs', 'cache'] as $table) {
                    DB::table($table)->delete();
                }
                User::where('role', '!=', 'admin')->whereNotIn('id', $people->modelKeys())->delete();
                AuditEvent::create(['user_id' => $admin->id, 'action' => 'system.academic_reset', 'after' => ['students_retained' => 40, 'teachers_retained' => 10], 'reason' => 'Reinicio inicial autorizado para la arquitectura de cursos independientes.']);
            });
        } catch (RuntimeException $exception) {
            $this->error('Reinicio cancelado; la transacción no ha aplicado cambios. Revisa la integridad de las cuentas conservadas.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
        $this->info('Reinicio terminado. Cuentas de ejemplo y administradores conservados con sus credenciales; actividad académica vacía.');

        return self::SUCCESS;
    }
}
