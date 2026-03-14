<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class SalaryCalculationService
{
    /**
     * Calculate the daily rate from monthly salary.
     */
    public function calculateDailyRate(float $monthlySalary, bool $roundUp = false): float
    {
        $rate = $monthlySalary / 26;

        return $roundUp ? ceil($rate) : round($rate, 2);
    }

    /**
     * Calculate the number of Sundays in a date range.
     */
    public function countSundaysInRange(CarbonImmutable $start, CarbonImmutable $end): int
    {
        $count = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isSunday()) {
                $count++;
            }
            $current = $current->addDay();
        }

        return $count;
    }

    /**
     * Calculate pro-rated salary amount.
     * Full month returns base salary exactly to avoid rounding errors.
     * Partial month: working days = calendar days - sundays.
     */
    public function calculateProRatedAmount(
        float $dailyRate,
        int $totalCalendarDays,
        int $sundaysInPeriod,
        float $baseSalary,
        bool $isFullMonth,
    ): float {
        if ($isFullMonth) {
            return $baseSalary;
        }

        $workingDays = $totalCalendarDays - $sundaysInPeriod;

        return round($dailyRate * max($workingDays, 0), 2);
    }

    /**
     * Calculate extra rest day pay.
     */
    public function calculateRestDayPay(float $dailyRate, int $extraDaysWorked): float
    {
        return round($dailyRate * $extraDaysWorked, 2);
    }

    /**
     * Calculate total amount.
     */
    public function calculateTotal(float $proRatedAmount, float $extraRestDayPay, float $adHocTotal = 0.0): float
    {
        return round($proRatedAmount + $extraRestDayPay + $adHocTotal, 2);
    }
}
