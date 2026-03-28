<?php

use App\Models\Helper;
use App\Models\User;

test('login page shows demo credentials when demo mode is enabled', function () {
    config(['demo.enabled' => true, 'demo.accounts' => [
        ['label' => 'Admin', 'username' => 'admin', 'password' => 'password'],
        ['label' => 'Helper', 'username' => 'helper', 'password' => 'password'],
    ]]);

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('demo.0.label', 'Admin')
            ->where('demo.1.label', 'Helper')
        );
});

test('login page hides demo credentials when demo mode is disabled', function () {
    config(['demo.enabled' => false]);

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('demo', false)
        );
});

test('demo security blocks password changes', function () {
    config(['demo.enabled' => true]);

    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertForbidden();
});

test('demo security blocks account deletion', function () {
    config(['demo.enabled' => true]);

    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertForbidden();
});

test('demo security blocks admin user creation', function () {
    config(['demo.enabled' => true]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/settings/admin-users', [
            'name' => 'New Admin',
            'username' => 'newadmin',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertForbidden();
});

test('demo security blocks admin user deletion', function () {
    config(['demo.enabled' => true]);

    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/settings/admin-users/{$otherAdmin->id}")
        ->assertForbidden();
});

test('demo security blocks helper password reset', function () {
    config(['demo.enabled' => true]);

    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post("/helpers/{$helper->id}/reset-password")
        ->assertForbidden();
});

test('demo security allows normal routes when demo mode is enabled', function () {
    config(['demo.enabled' => true]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk();
});

test('demo security does not restrict when demo mode is disabled', function () {
    config(['demo.enabled' => false]);

    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasNoErrors();
});
