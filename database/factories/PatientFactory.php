<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = fake()->randomElement(['S', 'T', 'F', 'G', 'M']);
        $digits = str_pad((string) fake()->unique()->randomNumber(7), 7, '0', STR_PAD_LEFT);
        $suffix = strtoupper(fake()->randomLetter());

        return [
            'name' => fake()->name(),
            'nric' => $prefix.$digits.$suffix,
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'date_of_birth' => fake()->optional()->dateTimeBetween('-90 years', '-30 years'),
        ];
    }
}
