<?php

namespace Database\Factories;

use App\Models\Helper;
use App\Models\SalaryPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryPayment>
 */
class SalaryPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salary = fake()->randomElement([600, 700, 800]);
        $dailyRate = round($salary / 26, 2);

        return [
            'helper_id' => Helper::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2026),
            'base_salary' => $salary,
            'total_calendar_days' => 30,
            'sundays_in_period' => 4,
            'pro_rated_amount' => $salary,
            'extra_rest_days_worked' => 0,
            'sundays_worked_dates' => null,
            'ad_hoc_payments' => null,
            'rest_day_rate' => $dailyRate,
            'extra_rest_day_pay' => 0,
            'total_amount' => $salary,
            'payment_method' => 'bank_transfer',
        ];
    }

    /**
     * Indicate the payment includes ad-hoc payments.
     */
    public function withAdHocPayments(): static
    {
        return $this->state(function (array $attributes) {
            $adHocPayments = [
                ['description' => 'Groceries', 'amount' => 50.00],
                ['description' => 'Transport', 'amount' => 20.00],
            ];
            $adHocTotal = collect($adHocPayments)->sum('amount');

            return [
                'ad_hoc_payments' => $adHocPayments,
                'total_amount' => $attributes['total_amount'] + $adHocTotal,
            ];
        });
    }

    /**
     * Indicate the payment has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_at' => now(),
        ]);
    }
}
