<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\Helper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'helper_id' => Helper::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2026),
            'title' => fake()->randomElement(['Groceries', 'Transport', 'Medical', 'Phone top-up']),
            'amount' => fake()->randomFloat(2, 5, 200),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    /**
     * Indicate the claim is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate the claim is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
