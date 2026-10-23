<?php

namespace Database\Factories;

use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pill>
 */
class PillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word()).' '.$this->faker->numberBetween(1, 999);

        return [
            'pet_id' => Pet::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'dosage' => '1 tablet',
            'scheduled_times' => ['08:00', '20:00'],
            'is_active' => true,
        ];
    }
}
