<?php

use App\Services\SalaryCalculationService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->service = new SalaryCalculationService;
});

test('calculates daily rate correctly', function () {
    expect($this->service->calculateDailyRate(600))->toBe(23.08);
    expect($this->service->calculateDailyRate(700))->toBe(26.92);
    expect($this->service->calculateDailyRate(800))->toBe(30.77);
});

test('rounds up daily rate when flag is set', function () {
    expect($this->service->calculateDailyRate(600, true))->toBe(24.0);
    expect($this->service->calculateDailyRate(700, true))->toBe(27.0);
});

test('counts sundays in a date range', function () {
    $start = CarbonImmutable::parse('2026-03-01');
    $end = CarbonImmutable::parse('2026-03-31');

    expect($this->service->countSundaysInRange($start, $end))->toBe(5);
});

test('full month returns exact base salary', function () {
    $dailyRate = 32.69; // 850 / 26
    $result = $this->service->calculateProRatedAmount($dailyRate, 31, 5, 850, true);

    expect($result)->toBe(850.0);
});

test('full month returns exact base salary for 600', function () {
    $dailyRate = 23.08; // 600 / 26
    $result = $this->service->calculateProRatedAmount($dailyRate, 30, 4, 600, true);

    expect($result)->toBe(600.0);
});

test('partial month pro-rates correctly', function () {
    $dailyRate = 23.08;
    // 20 calendar days - 3 sundays = 17 working days
    $result = $this->service->calculateProRatedAmount($dailyRate, 20, 3, 600, false);

    expect($result)->toBe(round(23.08 * 17, 2));
});

test('pro-rated amount handles zero working days', function () {
    expect($this->service->calculateProRatedAmount(23.08, 4, 4, 600, false))->toBe(0.0);
});

test('calculates rest day pay correctly', function () {
    expect($this->service->calculateRestDayPay(23.08, 2))->toBe(46.16);
    expect($this->service->calculateRestDayPay(23.08, 0))->toBe(0.0);
});

test('calculates total correctly', function () {
    expect($this->service->calculateTotal(507.76, 46.16))->toBe(553.92);
});

test('calculates total with ad-hoc payments', function () {
    expect($this->service->calculateTotal(600, 46.16, 70.00))->toBe(716.16);
});

test('calculates total with zero ad-hoc payments', function () {
    expect($this->service->calculateTotal(600, 46.16, 0.0))->toBe(646.16);
});
