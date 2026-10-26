<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetActivityData extends Command
{
    /**
     * Activity tables in deletion order (children before parents).
     */
    public const TABLES = ['audit_events', 'publications', 'assessments', 'module_grades', 'memberships', 'teams', 'challenge_evidences', 'challenge_student', 'challenge_teacher', 'challenge_module', 'challenges', 'rubrics', 'classroom_module', 'enrollments', 'classroom_user', 'module_user', 'periods', 'classrooms', 'google_registrations'];

    protected $signature = 'erronk2d:reset-activity {--execute : Borrar la actividad} {--backup-confirmed : La copia previa ha sido comprobada por ops/deploy}';

    protected $description = 'Borra grupos, matrículas, retos, rúbricas, notas y solicitudes conservando cuentas, ciclos, módulos y cursos académicos';

    public function handle(): int
    {
        $tables = array_values(array_filter(self::TABLES, fn (string $table) => Schema::hasTable($table)));
        $this->table(['Entidad', 'Registros actuales'], collect([...$tables, 'users', 'cycles', 'modules', 'academic_years'])->map(fn ($table) => [$table, DB::table($table)->count()])->all());
        if (! $this->option('execute')) {
            $this->info('Vista previa. No se ha modificado ningún dato.');

            return self::SUCCESS;
        }
        if (! $this->option('backup-confirmed') || (app()->environment('production') && ! app()->isDownForMaintenance())) {
            $this->error('El borrado requiere una copia previa comprobada y producción en mantenimiento.');

            return self::FAILURE;
        }
        $admin = User::where('role', 'admin')->where('active', true)->orderBy('id')->first();
        if (! $admin) {
            $this->error('Falta un administrador activo. No se ha modificado ningún dato.');

            return self::FAILURE;
        }
        $lock = Cache::lock('erronk2d:reset-activity', 300);
        if (! $lock->get()) {
            $this->error('Ya hay un borrado en curso.');

            return self::FAILURE;
        }
        try {
            $counts = DB::transaction(function () use ($tables, $admin) {
                $counts = [];
                foreach ($tables as $table) {
                    if ($table === 'classrooms') {
                        DB::table('modules')->whereNotNull('classroom_id')->update(['classroom_id' => null]);
                        DB::table('users')->whereNotNull('classroom_id')->update(['classroom_id' => null]);
                    }
                    $counts[$table] = DB::table($table)->delete();
                }
                AuditEvent::create(['user_id' => $admin->id, 'action' => 'system.activity_reset', 'after' => $counts, 'reason' => 'Punto de partida: se conservan cuentas, ciclos, módulos y cursos académicos.']);

                return $counts;
            });
        } finally {
            $lock->release();
        }
        $this->info('Actividad borrada: '.array_sum($counts).' registros. Cuentas, ciclos, módulos y cursos académicos conservados.');

        return self::SUCCESS;
    }
}
