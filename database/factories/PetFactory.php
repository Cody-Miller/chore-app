<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pet>
 */
class PetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->firstName();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'species' => 'Cat',
            'breed' => null,
            'birth_date' => $this->faker->dateTimeBetween('-15 years', '-1 year')->format('Y-m-d'),
        ];
    }
}
