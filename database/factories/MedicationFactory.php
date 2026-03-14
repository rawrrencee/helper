<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'name' => fake()->randomElement(['Paracetamol', 'Metformin', 'Amlodipine', 'Omeprazole', 'Atorvastatin']),
            'dosage' => fake()->optional()->randomElement(['500mg', '250mg', '10mg', '5mg']),
            'frequency' => fake()->randomElement(['After Breakfast', 'After Lunch', 'After Dinner', 'Before Sleep', '08:00', '12:00', '20:00']),
            'notes' => fake()->optional()->sentence(),
            'is_optional' => fake()->boolean(20),
        ];
    }
}
