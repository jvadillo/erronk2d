<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('20##-##-??'), 'is_open' => true];
    }
}
