<?php

namespace App\Domain;

use App\Models\AcademicYear;
use App\Models\Cycle;
use App\Models\Module;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared academic catalog: academic years, cycles and modules that every teacher can reuse.
 */
final class Catalog
{
    /**
     * Normalizes a name or code so that case and irrelevant spaces do not create duplicates.
     */
    public static function key(string $value): string
    {
        return mb_strtolower(preg_replace(['/\s+/u', '/\s*([\/-])\s*/u'], [' ', '$1'], trim($value)));
    }

    public function saveYear(AcademicYear $year, string $name): AcademicYear
    {
        $year->name = trim($name);
        $existing = AcademicYear::where('name_key', self::key($name))->when($year->exists, fn ($years) => $years->whereKeyNot($year->id))->first();
        if ($existing) {
            throw ValidationException::withMessages(['name' => "Ya existe el curso académico «{$existing->name}». Selecciónalo en lugar de crearlo."]);
        }

        return $this->persist($year, 'name', 'Otra persona acaba de crear ese curso académico. Selecciónalo en la lista.');
    }

    public function saveCycle(Cycle $cycle, string $name, string $code): Cycle
    {
        $cycle->fill(['name' => trim($name), 'code' => trim($code)]);
        $existing = Cycle::query()->when($cycle->exists, fn ($cycles) => $cycles->whereKeyNot($cycle->id))->where(fn ($cycles) => $cycles->where('name_key', self::key($name))->orWhere('code_key', self::key($code)))->first();
        if ($existing) {
            $field = $existing->name_key === self::key($name) ? 'name' : 'code';
            throw ValidationException::withMessages([$field => "Ya existe el ciclo «{$existing->name}» ({$existing->code}). Selecciónalo en lugar de crearlo."]);
        }

        return $this->persist($cycle, 'name', 'Otra persona acaba de crear ese ciclo. Selecciónalo en la lista.');
    }

    /**
     * @param  array{name: string, code: string, cycle_id: int|null, level: int}  $data
     */
    public function saveModule(Module $module, array $data): Module
    {
        $module->fill(['name' => trim($data['name']), 'code' => trim($data['code']), 'cycle_id' => $data['cycle_id'], 'level' => $data['level']]);
        $this->assertModuleIsNew($module);

        return $this->persist($module, 'code', 'Otra persona acaba de añadir ese módulo al ciclo. Selecciónalo en la lista.');
    }

    /**
     * Rejects a module whose name or code already exists in its cycle, whatever the level.
     */
    public function assertModuleIsNew(Module $module): void
    {
        if ($module->cycle_id === null) {
            return;
        }
        $existing = Module::where('cycle_id', $module->cycle_id)->when($module->exists, fn ($modules) => $modules->whereKeyNot($module->id))
            ->where(fn ($modules) => $modules->where('name_key', self::key($module->name))->orWhere('code_key', self::key($module->code)))->first();
        if ($existing) {
            $field = $existing->code_key === self::key($module->code) ? 'code' : 'name';
            throw ValidationException::withMessages([$field => "El ciclo ya incluye el módulo {$existing->code} · {$existing->name} (curso / nivel {$existing->level}.º). Selecciónalo en lugar de crearlo."]);
        }
    }

    /**
     * Saves inside a savepoint so that a concurrent duplicate becomes a validation error
     * instead of aborting the surrounding transaction.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    public function persist(Model $model, string $field, string $message): Model
    {
        try {
            DB::transaction(fn () => $model->save());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([$field => $message]);
        }

        return $model;
    }
}
