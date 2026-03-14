<?php

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'doctor' => fake()->optional()->name(),
            'appointment_date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'appointment_time' => fake()->time('H:i'),
            'location' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'scheduled',
        ];
    }

    /**
     * Indicate the appointment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'appointment_date' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate the appointment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
