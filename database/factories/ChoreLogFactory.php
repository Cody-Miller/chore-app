<?php

namespace Database\Factories;

use App\Models\Chore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Action>
 */
class ChoreLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chore_id' => Chore::factory(),
            'user_id' => User::factory(),
            'completed_at' => $this->faker->dateTimeBetween('-10 month', '+0 day')->format('Y-m-d H:i:s'),
        ];
    }
}
