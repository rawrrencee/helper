<?php

use App\Models\Helper;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can view helpers index', function () {
    $admin = User::factory()->admin()->create();
    Helper::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/helpers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('helpers/Index')
            ->has('helpers.data', 3)
        );
});

test('helper cannot view helpers index', function () {
    $helper = User::factory()->create(['role' => 'helper']);

    $this->actingAs($helper)
        ->get('/helpers')
        ->assertForbidden();
});

test('admin can create a helper', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/helpers', [
            'name' => 'Jane Doe',
            'fin' => 'G1234567X',
            'monthly_salary' => 600,
        ])
        ->assertRedirect('/helpers');

    $this->assertDatabaseHas('helpers', ['fin' => 'G1234567X', 'name' => 'Jane Doe']);
    $this->assertDatabaseHas('users', ['username' => 'G1234567X', 'role' => 'helper']);
});

test('admin can create a helper with custom password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/helpers', [
            'name' => 'Jane Doe',
            'fin' => 'G7654321X',
            'monthly_salary' => 600,
            'password' => 'custom-password-123',
        ])
        ->assertRedirect('/helpers');

    $this->assertDatabaseHas('helpers', ['fin' => 'G7654321X']);
    $user = User::where('username', 'G7654321X')->first();
    expect(Hash::check('custom-password-123', $user->password))->toBeTrue();
});

test('helper cannot create a helper', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->post('/helpers', [
            'name' => 'Jane Doe',
            'fin' => 'G1234567X',
            'monthly_salary' => 600,
        ])
        ->assertForbidden();
});

test('FIN validation rejects invalid format', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/helpers', [
            'name' => 'Jane Doe',
            'fin' => 'INVALID',
            'monthly_salary' => 600,
        ])
        ->assertSessionHasErrors('fin');
});

test('FIN must be unique', function () {
    $admin = User::factory()->admin()->create();
    Helper::factory()->create(['fin' => 'G1234567X']);

    $this->actingAs($admin)
        ->post('/helpers', [
            'name' => 'Jane Doe',
            'fin' => 'G1234567X',
            'monthly_salary' => 600,
        ])
        ->assertSessionHasErrors('fin');
});

test('admin can view a helper', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->get("/helpers/{$helper->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('helpers/Show')
            ->has('helper')
        );
});

test('helper can view own profile', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/helpers/{$helper->id}")
        ->assertOk();
});

test('helper cannot view another helper profile', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $otherHelper = Helper::factory()->create();

    $this->actingAs($user)
        ->get("/helpers/{$otherHelper->id}")
        ->assertForbidden();
});

test('admin can update a helper', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->put("/helpers/{$helper->id}", [
            'name' => 'Updated Name',
            'fin' => $helper->fin,
            'monthly_salary' => 700,
        ])
        ->assertRedirect("/helpers/{$helper->id}");

    expect($helper->fresh()->name)->toBe('Updated Name');
    expect($helper->fresh()->monthly_salary)->toBe('700.00');
});

test('admin can delete a helper', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $userId = $helper->user_id;

    $this->actingAs($admin)
        ->delete("/helpers/{$helper->id}")
        ->assertRedirect('/helpers');

    $this->assertDatabaseMissing('helpers', ['id' => $helper->id]);
    $this->assertDatabaseMissing('users', ['id' => $userId]);
});

test('admin can reset helper password', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post("/helpers/{$helper->id}/reset-password", [
            'password' => 'newpassword123',
        ])
        ->assertRedirect();

    expect(Hash::check('newpassword123', $helper->user->fresh()->password))->toBeTrue();
});

test('reset password rejects short password', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post("/helpers/{$helper->id}/reset-password", [
            'password' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

test('reset password rejects empty password', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post("/helpers/{$helper->id}/reset-password", [
            'password' => '',
        ])
        ->assertSessionHasErrors('password');
});

test('admin can search helpers', function () {
    $admin = User::factory()->admin()->create();
    Helper::factory()->create(['name' => 'Maria Santos', 'fin' => 'G1111111A']);
    Helper::factory()->create(['name' => 'Siti Aminah', 'fin' => 'G2222222B']);

    $this->actingAs($admin)
        ->get('/helpers?search=Maria')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('helpers.data', 1)
        );
});
