<?php

use App\Models\User;

test('admin can view admin users page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/settings/admin-users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/AdminUsers')
            ->has('adminUsers')
        );
});

test('helper cannot view admin users page', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->get('/settings/admin-users')
        ->assertForbidden();
});

test('admin can create another admin user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/settings/admin-users', [
            'name' => 'New Admin',
            'username' => 'newadmin',
            'email' => 'new@admin.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'name' => 'New Admin',
        'username' => 'newadmin',
        'role' => 'admin',
    ]);
});

test('admin can create admin without email', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/settings/admin-users', [
            'name' => 'No Email Admin',
            'username' => 'noemailadmin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'username' => 'noemailadmin',
        'email' => null,
    ]);
});

test('helper cannot create admin user', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->post('/settings/admin-users', [
            'name' => 'Hacker',
            'username' => 'hacker',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();
});

test('admin cannot delete self', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/settings/admin-users/{$admin->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin cannot delete last admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/settings/admin-users/{$admin->id}")
        ->assertRedirect();

    expect(User::where('role', 'admin')->count())->toBe(1);
});

test('admin can delete another admin', function () {
    $admin1 = User::factory()->admin()->create();
    $admin2 = User::factory()->admin()->create();

    $this->actingAs($admin1)
        ->delete("/settings/admin-users/{$admin2->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
});

test('helper cannot delete admin user', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($user)
        ->delete("/settings/admin-users/{$admin->id}")
        ->assertForbidden();
});

test('username must be unique when creating admin', function () {
    $admin = User::factory()->admin()->create(['username' => 'taken']);

    $this->actingAs($admin)
        ->post('/settings/admin-users', [
            'name' => 'Duplicate',
            'username' => 'taken',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['username']);
});

test('password must be confirmed', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/settings/admin-users', [
            'name' => 'Test',
            'username' => 'testuser',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ])
        ->assertSessionHasErrors(['password']);
});
