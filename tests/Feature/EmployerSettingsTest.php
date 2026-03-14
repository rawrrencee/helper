<?php

use App\Models\Setting;
use App\Models\User;

test('admin can view employer settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/settings/employer')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Employer')
            ->has('employer_name')
            ->has('employer_address')
        );
});

test('admin can update employer settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/settings/employer', [
            'employer_name' => 'John Doe',
            'employer_address' => '123 Main St, Singapore',
        ])
        ->assertRedirect();

    expect(Setting::getValue('employer_name'))->toBe('John Doe');
    expect(Setting::getValue('employer_address'))->toBe('123 Main St, Singapore');
});

test('helper cannot view employer settings', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->get('/settings/employer')
        ->assertForbidden();
});

test('helper cannot update employer settings', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->put('/settings/employer', [
            'employer_name' => 'John Doe',
        ])
        ->assertForbidden();
});
