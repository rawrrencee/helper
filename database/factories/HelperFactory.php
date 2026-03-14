<?php

namespace Database\Factories;

use App\Models\Helper;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Helper>
 */
class HelperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fin = fake()->randomElement(['G', 'S', 'T', 'F'])
            .str_pad((string) fake()->unique()->randomNumber(7), 7, '0', STR_PAD_LEFT)
            .fake()->randomLetter();

        $fin = strtoupper($fin);

        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'fin' => $fin,
            'passport_no' => strtoupper(fake()->bothify('??#######')),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'nationality' => fake()->randomElement(['Filipino', 'Indonesian', 'Myanmar', 'Indian', 'Sri Lankan']),
            'occupation' => 'Domestic Worker',
            'monthly_salary' => fake()->randomElement([600, 700, 800]),
            'monthly_levy_rate' => 300,
            'rest_days_per_month' => 4,
            'round_up_rest_day_rate' => false,
        ];
    }
}
