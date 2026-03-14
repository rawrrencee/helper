<?php

use App\Models\Helper;
use App\Models\User;

test('helper can update bank details with bank transfer', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put('/helpers/bank-details', [
            'payment_method' => 'bank_transfer',
            'bank_name' => 'DBS',
            'bank_account_no' => '1234567890',
        ])
        ->assertRedirect();

    expect($helper->fresh()->bank_name)->toBe('DBS');
    expect($helper->fresh()->bank_account_no)->toBe('1234567890');
    expect($helper->fresh()->paynow_enabled)->toBeFalse();
});

test('helper can update bank details with paynow', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put('/helpers/bank-details', [
            'payment_method' => 'paynow',
            'paynow_identifier' => '91234567',
        ])
        ->assertRedirect();

    expect($helper->fresh()->paynow_enabled)->toBeTrue();
    expect($helper->fresh()->paynow_identifier)->toBe('91234567');
});

test('admin cannot access bank details update endpoint', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/helpers/bank-details', [
            'payment_method' => 'bank_transfer',
            'bank_name' => 'DBS',
            'bank_account_no' => '1234567890',
        ])
        ->assertForbidden();
});

test('validation rejects missing required fields for bank transfer', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put('/helpers/bank-details', [
            'payment_method' => 'bank_transfer',
        ])
        ->assertSessionHasErrors(['bank_name', 'bank_account_no']);
});

test('validation rejects missing paynow identifier', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put('/helpers/bank-details', [
            'payment_method' => 'paynow',
        ])
        ->assertSessionHasErrors(['paynow_identifier']);
});

test('helper sees bank details prop on salary payments index', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create([
        'user_id' => $user->id,
        'bank_name' => 'OCBC',
        'bank_account_no' => '9876543210',
    ]);

    $this->actingAs($user)
        ->get('/salary-payments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bankDetails')
            ->where('bankDetails.bank_name', 'OCBC')
            ->where('bankDetails.bank_account_no', '9876543210')
        );
});
