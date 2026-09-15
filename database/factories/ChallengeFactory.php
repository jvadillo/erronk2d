<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChallengeFactory extends Factory
{
    public function definition(): array
    {
        $rubric = ['name' => 'Calidad', 'items' => [['key' => 'quality', 'name' => 'Calidad', 'weight' => '1', 'module_id' => null, 'levels' => [['score' => '4', 'description' => 'Inicial'], ['score' => '8', 'description' => 'Autónomo']]]]];

        return ['name' => fake()->sentence(3), 'classroom_id' => Classroom::factory(), 'period_id' => fn (array $attributes) => Classroom::findOrFail($attributes['classroom_id'])->periods()->firstOrCreate(['position' => 1], ['name' => 'Primera'])->id, 'team_rubric' => $rubric, 'transversal_rubric' => $rubric, 'distribution_enabled' => false, 'component_weights' => ['transversal' => 30, 'challenge' => 40, 'exam' => 30], 'transversal_weights' => ['self' => 10, 'peer' => 60, 'teacher' => 30]];
    }
}
