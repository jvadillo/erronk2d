<?php

namespace Tests;

use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function moduleForClass(Classroom $classroom, array $attributes = [], ?User $teacher = null): Module
    {
        if (! $classroom->cycle_id) {
            $cycle = Cycle::factory()->create();
            $classroom->update(['cycle_id' => $cycle->id, 'cycle_name' => $cycle->name]);
        }
        $classroom->refresh();
        $module = Module::factory()->create(['cycle_id' => $classroom->cycle_id, 'level' => $classroom->level, ...$attributes]);
        $classroom->syncCatalog();
        if ($teacher) {
            $classroom->users()->syncWithoutDetaching([$teacher->id]);
            $module->teachersFor($classroom->id)->attach($teacher, ['classroom_id' => $classroom->id]);
        }

        return $module;
    }

    protected function enrollInClass(User $student, Classroom $classroom): void
    {
        Enrollment::updateOrCreate(['classroom_id' => $classroom->id, 'student_id' => $student->id], ['ended_at' => null]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
    }
}
