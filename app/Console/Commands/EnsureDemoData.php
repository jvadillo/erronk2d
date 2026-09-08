<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Module;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
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
                $year = $this->record(AcademicYear::class, 'demo.year', ['name' => 'Curso de prueba Erronk2D']);
                if ($year->wasRecentlyCreated) {
                    foreach (['Primera', 'Segunda', 'Tercera'] as $position => $name) {
                        $year->periods()->create(['name' => $name, 'position' => $position + 1]);
                    }
                }
                $classes = [];
                for ($i = 1; $i <= 2; $i++) {
                    $classes[] = $this->record(Classroom::class, 'demo.class.'.$i, ['academic_year_id' => $year->id, 'name' => 'Clase de prueba '.$i]);
                }
                for ($i = 1; $i <= 10; $i++) {
                    $teacher = $this->person('teacher', $i, null);
                    $classroom = $classes[intdiv($i - 1, 5)];
                    $module = $this->record(Module::class, 'demo.module.'.$i, ['classroom_id' => $classroom->id, 'name' => 'Módulo de prueba '.$i, 'code' => 'PR'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
                    if ($module->wasRecentlyCreated || ! $module->teachers()->exists()) {
                        $module->teachers()->attach($teacher);
                        $module->classroom->users()->syncWithoutDetaching([$teacher->id]);
                    }
                }
                for ($i = 1; $i <= 40; $i++) {
                    $this->person('student', $i, $classes[intdiv($i - 1, 20)]->id);
                }
                foreach (['team' => 'Reto', 'transversal' => 'Transversales'] as $kind => $name) {
                    $this->record(Rubric::class, 'demo.rubric.'.$kind, ['name' => $name.' · prueba', 'kind' => $kind, 'items' => [['key' => 'progress', 'name' => 'Desempeño', 'description' => 'Criterio ficticio para probar la evaluación.', 'module_id' => null, 'weight' => '1', 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '6', 'description' => 'En desarrollo'], ['score' => '8', 'description' => 'Autónomo'], ['score' => '10', 'description' => 'Excelente']]]]]);
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

    /** @param array<string, mixed> $attributes */
    private function record(string $modelClass, string $key, array $attributes): Model
    {
        $record = $modelClass::where('demo_key', $key)->first();
        if ($record) {
            return $record;
        }
        $record = new $modelClass;
        $record->forceFill(['demo_key' => $key, ...$attributes])->save();

        return $record;
    }

    private function person(string $role, int $number, ?int $classroomId): User
    {
        $key = 'demo.'.$role.'.'.$number;
        $email = $role.$number.'@demo.erronk2d.test';
        $user = User::where('demo_key', $key)->first();
        if (! $user) {
            if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
                throw new RuntimeException('Identidad reservada ocupada.');
            }
            $user = new User;
            $user->forceFill(['demo_key' => $key, 'role' => $role, 'name' => ($role === 'student' ? 'Estudiante' : 'Profesor').' de prueba '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'email' => $email, 'password' => Str::random(64), 'classroom_id' => $classroomId, 'permissions' => $role === 'teacher' ? User::PERMISSIONS : []]);
        }
        if ($user->role !== $role || ($role === 'student' && ! $user->classroom_id)) {
            throw new RuntimeException('Revisa la clase o el rol de una cuenta ficticia.');
        }
        $user->active = true;
        $user->save();

        return $user;
    }
}
