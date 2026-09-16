<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use App\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

class ImportOfficialCatalog extends Command
{
    protected $signature = 'erronk2d:catalog {--execute : Añade los registros que faltan} {--cycle=* : Limita la carga a códigos de ciclo del catálogo}';

    protected $description = 'Previsualiza o añade el catálogo oficial incluido, sin descargar ni sobrescribir datos';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $lock = Cache::lock('erronk2d:catalog', 120);
        if ($execute && ! $lock->get()) {
            $this->error('Ya hay otra importación del catálogo en curso.');

            return self::FAILURE;
        }
        try {
            $catalog = $this->catalog();
            $selected = $this->option('cycle');
            if (array_diff($selected, array_column($catalog['cycles'], 'code')) !== []) {
                throw new RuntimeException('Hay códigos de ciclo que no están en el catálogo incluido.');
            }
            $cycles = collect($catalog['cycles'])->filter(fn (array $cycle): bool => $selected === [] || in_array($cycle['code'], $selected, true));
            $counts = DB::transaction(fn (): array => $this->import($cycles, $execute));
            $this->line($catalog['scope']);
            $this->info(($execute ? 'Carga terminada.' : 'Previsualización; no se ha modificado ningún dato.').
                " Ciclos nuevos: {$counts['cycles']}; existentes: {$counts['existing_cycles']}.".
                " Módulos nuevos: {$counts['modules']}; existentes: {$counts['existing_modules']}.");
            $this->line('Los grupos existentes conservan sus módulos. Los nuevos se añaden desde Grupos.');

            return self::SUCCESS;
        } catch (JsonException|ValidationException|RuntimeException|QueryException $exception) {
            $this->error('No se ha importado ningún dato. Revisa el catálogo y las coincidencias con registros existentes.');
            if ($exception instanceof RuntimeException && ! $exception instanceof QueryException) {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        } finally {
            if ($execute) {
                $lock->release();
            }
        }
    }

    /**
     * @return array{scope: string, cycles: list<array{code: string, name: string, aliases: list<string>, modules: list<array{code: string, name: string, level: int}>}>}
     */
    private function catalog(): array
    {
        $catalog = json_decode(File::get(database_path('seeders/official-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        Validator::make($catalog, [
            'scope' => ['required', 'string'],
            'cycles' => ['required', 'array', 'min:1'],
            'cycles.*.code' => ['required', 'string', 'max:30', 'distinct'],
            'cycles.*.name' => ['required', 'string', 'max:150', 'distinct'],
            'cycles.*.aliases' => ['present', 'array'],
            'cycles.*.aliases.*' => ['required', 'string', 'max:150'],
            'cycles.*.source' => ['required', 'url:https'],
            'cycles.*.modules' => ['required', 'array', 'min:1'],
            'cycles.*.modules.*.code' => ['required', 'string', 'max:30'],
            'cycles.*.modules.*.name' => ['required', 'string', 'max:150'],
            'cycles.*.modules.*.level' => ['required', 'integer', 'between:1,4'],
        ])->validate();
        foreach ($catalog['cycles'] as $cycle) {
            $keys = array_map(fn (array $module): string => $module['level'].':'.$module['code'], $cycle['modules']);
            if (count($keys) !== count(array_unique($keys))) {
                throw new RuntimeException('El catálogo contiene módulos duplicados dentro de un ciclo y curso.');
            }
        }

        return $catalog;
    }

    /**
     * @param  Collection<int, array{code: string, name: string, aliases: list<string>, modules: list<array{code: string, name: string, level: int}>}>  $catalog
     * @return array{cycles: int, existing_cycles: int, modules: int, existing_modules: int}
     */
    private function import(Collection $catalog, bool $execute): array
    {
        $existing = Cycle::with('modules')->get();
        $counts = ['cycles' => 0, 'existing_cycles' => 0, 'modules' => 0, 'existing_modules' => 0];
        foreach ($catalog as $entry) {
            $names = array_map($this->normalize(...), [$entry['name'], ...$entry['aliases']]);
            $matches = $existing->filter(fn (Cycle $cycle): bool => $this->normalize($cycle->code) === $this->normalize($entry['code']) || in_array($this->normalize($cycle->name), $names, true));
            if ($matches->count() > 1) {
                throw new RuntimeException('Coincidencia ambigua para el ciclo '.$entry['code'].'.');
            }
            $cycle = $matches->first();
            if ($cycle === null) {
                $cycle = new Cycle(['code' => $entry['code'], 'name' => $entry['name']]);
                if ($execute) {
                    $cycle->save();
                }
                $modules = collect();
                $counts['cycles']++;
            } else {
                $modules = $cycle->modules;
                $counts['existing_cycles']++;
            }
            foreach ($entry['modules'] as $module) {
                $matches = $modules->filter(fn (Module $existingModule): bool => (int) $existingModule->level === $module['level'] &&
                    ($this->normalize($existingModule->code) === $this->normalize($module['code']) || $this->normalize($existingModule->name) === $this->normalize($module['name'])));
                if ($matches->count() > 1) {
                    throw new RuntimeException('Coincidencia ambigua para un módulo del ciclo '.$entry['code'].'.');
                }
                if ($matches->isNotEmpty()) {
                    $counts['existing_modules']++;

                    continue;
                }
                if ($execute) {
                    $created = $cycle->modules()->create($module);
                    $modules->push($created);
                }
                $counts['modules']++;
            }
        }

        return $counts;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(Str::squish($value)));
    }
}
