<?php

use App\Models\Helper;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view salary payments index', function () {
    $admin = User::factory()->admin()->create();
    SalaryPayment::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/salary-payments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('salary-payments/Index')
            ->has('payments.data', 3)
        );
});

test('helper can view own salary payments', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    SalaryPayment::factory()->create(['helper_id' => $helper->id]);
    SalaryPayment::factory()->create(); // another helper's payment

    $this->actingAs($user)
        ->get('/salary-payments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('payments.data', 1)
        );
});

test('admin can create a salary payment', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertRedirect('/salary-payments');

    $this->assertDatabaseHas('salary_payments', [
        'helper_id' => $helper->id,
        'month' => 3,
        'year' => 2026,
    ]);
});

test('helper cannot create a salary payment', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertForbidden();
});

test('admin can view a salary payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->get("/salary-payments/{$payment->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('salary-payments/Show')
        );
});

test('helper can view own salary payment', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $payment = SalaryPayment::factory()->create(['helper_id' => $helper->id]);

    $this->actingAs($user)
        ->get("/salary-payments/{$payment->id}")
        ->assertOk();
});

test('helper cannot view another helper salary payment', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($user)
        ->get("/salary-payments/{$payment->id}")
        ->assertForbidden();
});

test('admin can update a salary payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->put("/salary-payments/{$payment->id}", [
            'month' => 4,
            'year' => 2026,
            'base_salary' => 700,
            'total_calendar_days' => 30,
            'sundays_in_period' => 4,
            'pro_rated_amount' => 700,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 26.92,
            'extra_rest_day_pay' => 0,
            'total_amount' => 700,
            'payment_method' => 'paynow',
        ])
        ->assertRedirect("/salary-payments/{$payment->id}");

    expect($payment->fresh()->month)->toBe(4);
    expect($payment->fresh()->payment_method)->toBe('paynow');
});

test('admin can delete a salary payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->delete("/salary-payments/{$payment->id}")
        ->assertRedirect('/salary-payments');

    $this->assertDatabaseMissing('salary_payments', ['id' => $payment->id]);
});

test('admin can create a salary payment with ad-hoc payments', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $adHocPayments = [
        ['description' => 'Groceries', 'amount' => 50.00],
        ['description' => 'Transport', 'amount' => 20.00],
    ];

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'ad_hoc_payments' => $adHocPayments,
            'total_amount' => 670,
            'payment_method' => 'bank_transfer',
        ])
        ->assertRedirect('/salary-payments');

    $payment = SalaryPayment::query()->where('helper_id', $helper->id)->first();
    expect($payment->ad_hoc_payments)->toHaveCount(2);
    expect($payment->ad_hoc_payments[0]['description'])->toBe('Groceries');
    expect((float) $payment->ad_hoc_payments[0]['amount'])->toBe(50.0);
    expect($payment->adHocPaymentsTotal())->toBe(70.0);
});

test('ad-hoc payment validation rejects invalid items', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'ad_hoc_payments' => [
                ['description' => '', 'amount' => 0],
            ],
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['ad_hoc_payments.0.description', 'ad_hoc_payments.0.amount']);
});

test('admin can generate salary slip pdf', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->get("/salary-payments/{$payment->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('duplicate month/year validation prevents creating duplicate salary payment', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'month' => 3,
        'year' => 2026,
    ]);

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['month']);
});

test('duplicate validation allows different month for same helper', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'month' => 3,
        'year' => 2026,
    ]);

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 4,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 30,
            'sundays_in_period' => 4,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertRedirect('/salary-payments');
});

test('duplicate validation on update ignores current record', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $payment = SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'month' => 3,
        'year' => 2026,
    ]);

    $this->actingAs($admin)
        ->put("/salary-payments/{$payment->id}", [
            'month' => 3,
            'year' => 2026,
            'base_salary' => 700,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 700,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 26.92,
            'extra_rest_day_pay' => 0,
            'total_amount' => 700,
            'payment_method' => 'bank_transfer',
        ])
        ->assertRedirect("/salary-payments/{$payment->id}");
});

test('duplicate validation on update prevents duplicate with another record', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'month' => 3,
        'year' => 2026,
    ]);

    $payment = SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'month' => 4,
        'year' => 2026,
    ]);

    $this->actingAs($admin)
        ->put("/salary-payments/{$payment->id}", [
            'month' => 3,
            'year' => 2026,
            'base_salary' => 700,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 700,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 26.92,
            'extra_rest_day_pay' => 0,
            'total_amount' => 700,
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['month']);
});

test('admin can upload screenshot during creation', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $file = UploadedFile::fake()->image('screenshot.png', 400, 300);

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'total_calendar_days' => 31,
            'sundays_in_period' => 5,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
            'screenshot' => $file,
        ])
        ->assertRedirect('/salary-payments');

    $payment = SalaryPayment::query()->where('helper_id', $helper->id)->first();
    expect($payment->payment_screenshot_path)->not->toBeNull();
    expect(Storage::disk('local')->exists($payment->payment_screenshot_path))->toBeTrue();

    // Cleanup
    Storage::disk('local')->delete($payment->payment_screenshot_path);
});

test('admin can serve screenshot file', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $file = UploadedFile::fake()->image('screenshot.png', 400, 300);
    $path = $file->store("screenshots/{$helper->id}", 'local');

    $payment = SalaryPayment::factory()->create([
        'helper_id' => $helper->id,
        'payment_screenshot_path' => $path,
    ]);

    $this->actingAs($admin)
        ->get("/salary-payments/{$payment->id}/screenshot")
        ->assertOk();

    // Cleanup
    Storage::disk('local')->delete($path);
});

test('screenshot route returns 404 when no screenshot', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create(['payment_screenshot_path' => null]);

    $this->actingAs($admin)
        ->get("/salary-payments/{$payment->id}/screenshot")
        ->assertNotFound();
});

test('create page passes existing payments for smart defaults', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    SalaryPayment::factory()->create(['helper_id' => $helper->id, 'month' => 2, 'year' => 2026]);

    $this->actingAs($admin)
        ->get('/salary-payments/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('salary-payments/Create')
            ->has('existingPayments')
        );
});

test('creating salary payment with end date before start date fails validation', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/salary-payments', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'working_days_start' => '2026-03-15',
            'working_days_end' => '2026-03-01',
            'total_calendar_days' => 15,
            'sundays_in_period' => 2,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['working_days_end']);
});

test('updating salary payment with end date before start date fails validation', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->put("/salary-payments/{$payment->id}", [
            'month' => 3,
            'year' => 2026,
            'base_salary' => 600,
            'working_days_start' => '2026-03-15',
            'working_days_end' => '2026-03-01',
            'total_calendar_days' => 15,
            'sundays_in_period' => 2,
            'pro_rated_amount' => 600,
            'extra_rest_days_worked' => 0,
            'rest_day_rate' => 23.08,
            'extra_rest_day_pay' => 0,
            'total_amount' => 600,
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['working_days_end']);
});

test('edit page passes existing payments and screenshot url', function () {
    $admin = User::factory()->admin()->create();
    $payment = SalaryPayment::factory()->create();

    $this->actingAs($admin)
        ->get("/salary-payments/{$payment->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('salary-payments/Edit')
            ->has('existingPayments')
            ->has('screenshotUrl')
        );
});
