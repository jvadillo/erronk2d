<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Classroom $class) {
            $class->users()->syncWithoutDetaching([$class->owner_id]);
            $class->update(['cycle_name' => $class->cycle->name]);
            $class->syncCatalog();
        });
    }

    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('CL-###??'), 'academic_year_id' => AcademicYear::factory(), 'cycle_id' => Cycle::factory(), 'level' => 2, 'owner_id' => User::factory()->create(['role' => 'teacher'])->id];
    }
}
