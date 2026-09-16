<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ImportOfficialCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_leaves_the_database_unchanged(): void
    {
        $this->artisan('erronk2d:catalog')
            ->expectsOutputToContain('Ciclos nuevos: 181; existentes: 0. Módulos nuevos: 2691; existentes: 0.')
            ->assertSuccessful();

        $this->assertDatabaseCount('cycles', 0);
        $this->assertDatabaseCount('modules', 0);
    }

    public function test_full_catalog_import_is_repeatable_without_duplicates(): void
    {
        $this->artisan('erronk2d:catalog', ['--execute' => true])->assertSuccessful();

        $this->assertDatabaseCount('cycles', 181);
        $this->assertDatabaseCount('modules', 2691);
        $cycle = Cycle::where('code', '121_0101')->firstOrFail();
        $this->assertDatabaseHas('modules', ['cycle_id' => $cycle->id, 'code' => '0408', 'level' => 1, 'name' => 'Infraestructuras e instalaciones agrícolas']);

        $this->artisan('erronk2d:catalog', ['--execute' => true])
            ->expectsOutputToContain('Ciclos nuevos: 0; existentes: 181. Módulos nuevos: 0; existentes: 2691.')
            ->assertSuccessful();

        $this->assertDatabaseCount('cycles', 181);
        $this->assertDatabaseCount('modules', 2691);
    }

    public function test_selected_cycle_preserves_existing_names_and_group_modules(): void
    {
        $cycle = Cycle::factory()->create(['code' => 'CENTRO', 'name' => 'Producción Agroecológica']);
        $group = Classroom::factory()->create(['cycle_id' => $cycle->id, 'level' => 1]);
        $module = $this->moduleForClass($group, ['code' => '0408', 'name' => 'Nombre personalizado']);
        $otherLevel = Module::factory()->create(['cycle_id' => $cycle->id, 'code' => '0409', 'level' => 2]);
        $originalCycle = $cycle->fresh()->getAttributes();
        $originalModule = $module->fresh()->getAttributes();
        $groupModules = $group->catalogModules()->pluck('modules.id')->all();

        $this->artisan('erronk2d:catalog', ['--execute' => true, '--cycle' => ['121_0101']])->assertSuccessful();

        $this->assertDatabaseCount('cycles', 1);
        $this->assertDatabaseCount('modules', 17);
        $this->assertSame($originalCycle, $cycle->fresh()->getAttributes());
        $this->assertSame($originalModule, $module->fresh()->getAttributes());
        $this->assertSame($groupModules, $group->catalogModules()->pluck('modules.id')->all());
        $this->assertModelExists($otherLevel);
        $this->assertDatabaseHas('modules', ['cycle_id' => $cycle->id, 'code' => '0409', 'level' => 1]);
    }

    public function test_ambiguous_cycle_rolls_back_earlier_inserts(): void
    {
        Cycle::factory()->create(['code' => '121_0102']);
        Cycle::factory()->create(['name' => 'Producción Agropecuaria']);

        $this->artisan('erronk2d:catalog', ['--execute' => true, '--cycle' => ['121_0101', '121_0102']])
            ->expectsOutputToContain('Coincidencia ambigua')
            ->assertFailed();

        $this->assertDatabaseCount('cycles', 2);
        $this->assertDatabaseCount('modules', 0);
        $this->assertDatabaseMissing('cycles', ['code' => '121_0101']);
    }

    public function test_ambiguous_module_does_not_partially_import(): void
    {
        $cycle = Cycle::factory()->create(['code' => '121_0101']);
        Module::factory()->create(['cycle_id' => $cycle->id, 'code' => '0409', 'level' => 1]);
        Module::factory()->create(['cycle_id' => $cycle->id, 'name' => 'Principios de sanidad vegetal', 'level' => 1]);

        $this->artisan('erronk2d:catalog', ['--execute' => true, '--cycle' => ['121_0101']])
            ->expectsOutputToContain('Coincidencia ambigua')
            ->assertFailed();

        $this->assertDatabaseCount('modules', 2);
        $this->assertDatabaseMissing('modules', ['code' => '0408']);
    }

    public function test_unknown_cycle_is_rejected_without_writes(): void
    {
        $this->artisan('erronk2d:catalog', ['--execute' => true, '--cycle' => ['INEXISTENTE']])->assertFailed();

        $this->assertDatabaseCount('cycles', 0);
        $this->assertDatabaseCount('modules', 0);
    }

    public function test_concurrent_import_is_rejected(): void
    {
        $lock = Cache::lock('erronk2d:catalog', 120);
        $lock->get();

        try {
            $this->artisan('erronk2d:catalog', ['--execute' => true])
                ->expectsOutput('Ya hay otra importación del catálogo en curso.')
                ->assertFailed();

            $this->assertDatabaseCount('cycles', 0);
            $this->assertDatabaseCount('modules', 0);
        } finally {
            $lock->release();
        }
    }
}
