<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CycleFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->sentence(3), 'code' => fake()->unique()->bothify('C-????##')];
    }
}
