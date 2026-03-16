<?php

use App\Models\Claim;
use App\Models\Helper;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view claims index', function () {
    $admin = User::factory()->admin()->create();
    Claim::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/claims')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('claims/Index')
            ->has('claims.data', 3)
        );
});

test('helper can view own claims only', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    Claim::factory()->create(['helper_id' => $helper->id]);
    Claim::factory()->create(); // another helper's claim

    $this->actingAs($user)
        ->get('/claims')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('claims.data', 1)
        );
});

test('admin can create a claim', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/claims', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'title' => 'Groceries',
            'amount' => 50.00,
        ])
        ->assertRedirect('/claims');

    $this->assertDatabaseHas('claims', [
        'helper_id' => $helper->id,
        'title' => 'Groceries',
        'status' => 'pending',
    ]);
});

test('helper can create a claim', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post('/claims', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'title' => 'Transport',
            'amount' => 20.00,
        ])
        ->assertRedirect('/claims');

    $this->assertDatabaseHas('claims', [
        'helper_id' => $helper->id,
        'title' => 'Transport',
    ]);
});

test('admin can approve a claim', function () {
    $admin = User::factory()->admin()->create();
    $claim = Claim::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->put("/claims/{$claim->id}", ['status' => 'approved'])
        ->assertRedirect();

    expect($claim->fresh()->status)->toBe('approved');
});

test('admin can reject a claim', function () {
    $admin = User::factory()->admin()->create();
    $claim = Claim::factory()->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->put("/claims/{$claim->id}", ['status' => 'rejected'])
        ->assertRedirect();

    expect($claim->fresh()->status)->toBe('rejected');
});

test('helper cannot update a claim', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $claim = Claim::factory()->create(['helper_id' => $helper->id]);

    $this->actingAs($user)
        ->put("/claims/{$claim->id}", ['status' => 'approved'])
        ->assertForbidden();
});

test('admin can view a claim', function () {
    $admin = User::factory()->admin()->create();
    $claim = Claim::factory()->create();

    $this->actingAs($admin)
        ->get("/claims/{$claim->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('claims/Show')
        );
});

test('admin can delete a claim', function () {
    $admin = User::factory()->admin()->create();
    $claim = Claim::factory()->create();

    $this->actingAs($admin)
        ->delete("/claims/{$claim->id}")
        ->assertRedirect('/claims');

    $this->assertDatabaseMissing('claims', ['id' => $claim->id]);
});

test('claim can be linked to salary payment via pivot', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $claim = Claim::factory()->create(['helper_id' => $helper->id, 'status' => 'approved']);

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
            'total_amount' => 650,
            'payment_method' => 'bank_transfer',
            'claim_ids' => [$claim->id],
        ])
        ->assertRedirect('/salary-payments');

    $payment = SalaryPayment::query()->where('helper_id', $helper->id)->first();
    expect($payment->claims)->toHaveCount(1);
    expect($payment->claims->first()->id)->toBe($claim->id);
});

test('claim with screenshot stores file', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $file = UploadedFile::fake()->image('receipt.jpg', 400, 300);

    $this->actingAs($admin)
        ->post('/claims', [
            'helper_id' => $helper->id,
            'month' => 3,
            'year' => 2026,
            'title' => 'Medical',
            'amount' => 30.00,
            'screenshot' => $file,
        ])
        ->assertRedirect('/claims');

    $claim = Claim::query()->where('helper_id', $helper->id)->first();
    expect($claim->screenshot_path)->not->toBeNull();
    expect(Storage::disk('local')->exists($claim->screenshot_path))->toBeTrue();

    Storage::disk('local')->delete($claim->screenshot_path);
});
