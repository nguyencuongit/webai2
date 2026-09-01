<?php

namespace Database\Factories;

use App\Models\RoboNeoAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoboNeoAccount>
 */
class RoboNeoAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->company(),
            'access_token' => 'pat_'.fake()->unique()->sha256(),
            'uid' => fake()->unique()->numerify('##########'),
            'is_default' => false,
            'is_active' => true,
            'last_verified_at' => now(),
            'last_error' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
