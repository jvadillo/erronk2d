<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_module_frees_it_without_removing_existing_group_records(): void
    {
        $module = Module::factory()->create();
        $class = Classroom::factory()->create(['cycle_id' => $module->cycle_id, 'level' => $module->level]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/setup/cycle-module', ['cycle_id' => $module->cycle_id, 'module_id' => $module->id, 'active' => false, 'level' => $module->level])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertNull($module->fresh()->cycle_id);
        $this->assertDatabaseHas('classroom_module', ['classroom_id' => $class->id, 'module_id' => $module->id, 'name' => $module->name, 'ended_at' => null]);
        $newClass = Classroom::factory()->create(['cycle_id' => $class->cycle_id, 'level' => $class->level]);
        $this->assertCount(0, $newClass->modules);
    }

    public function test_adding_unassigned_module_preserves_identity_and_assigns_cycle_and_course(): void
    {
        $module = Module::factory()->create(['cycle_id' => null]);
        $cycle = Cycle::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/cycle-module', ['cycle_id' => $cycle->id, 'module_id' => $module->id, 'active' => true, 'level' => 1])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'cycle_id' => $cycle->id, 'level' => 1]);
        $this->assertDatabaseCount('modules', 1);
    }

    public function test_associated_module_cannot_be_added_to_another_cycle(): void
    {
        $module = Module::factory()->create();
        $cycle = Cycle::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/cycle-module', ['cycle_id' => $cycle->id, 'module_id' => $module->id, 'active' => true, 'level' => 1])->assertSessionHasErrors('module_id');

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'cycle_id' => $module->cycle_id, 'level' => $module->level]);
    }

    public function test_cannot_remove_module_from_another_cycle(): void
    {
        $module = Module::factory()->create();
        $cycle = Cycle::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/cycle-module', ['cycle_id' => $cycle->id, 'module_id' => $module->id, 'active' => false, 'level' => 1])->assertNotFound();

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'cycle_id' => $module->cycle_id]);
    }

    public function test_duplicate_code_in_destination_course_is_rejected(): void
    {
        $existing = Module::factory()->create();
        $module = Module::factory()->create(['cycle_id' => null, 'code' => $existing->code]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/cycle-module', ['cycle_id' => $existing->cycle_id, 'module_id' => $module->id, 'active' => true, 'level' => $existing->level])->assertSessionHasErrors('module_id');

        $this->assertNull($module->fresh()->cycle_id);
    }

    public function test_teacher_cannot_manage_cycle_modules(): void
    {
        $module = Module::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'teacher']))->post('/setup/cycle-module', ['cycle_id' => $module->cycle_id, 'module_id' => $module->id, 'active' => false, 'level' => $module->level])->assertForbidden();

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'cycle_id' => $module->cycle_id]);
    }

    public function test_invalid_course_does_not_assign_module(): void
    {
        $module = Module::factory()->create(['cycle_id' => null]);
        $cycle = Cycle::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/cycle-module', ['cycle_id' => $cycle->id, 'module_id' => $module->id, 'active' => true, 'level' => 5])->assertSessionHasErrors('level');

        $this->assertNull($module->fresh()->cycle_id);
    }

    public function test_admin_can_create_and_edit_unassigned_module(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post('/setup/module', ['name' => 'Módulo disponible', 'code' => 'DISP', 'cycle_id' => null, 'level' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $module = Module::where('code', 'DISP')->firstOrFail();

        $this->post('/setup/module', ['id' => $module->id, 'name' => 'Nombre actualizado', 'code' => 'DISP', 'cycle_id' => null, 'level' => 1])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'name' => 'Nombre actualizado', 'cycle_id' => null]);
    }
}
