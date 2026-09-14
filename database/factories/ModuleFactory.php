<?php

namespace Database\Factories;

use App\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    public function definition(): array
    {
        return ['cycle_id' => Cycle::factory(), 'level' => 2, 'name' => fake()->sentence(2), 'code' => fake()->unique()->bothify('MOD-###??')];
    }
}
