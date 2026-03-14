<?php

use App\Models\Setting;
use App\Models\User;

test('admin can view family info page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/family-info')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('FamilyInfo')
            ->has('family_information')
        );
});

test('helper can view family info page', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->get('/family-info')
        ->assertOk();
});

test('admin can update family info', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/family-info', [
            'family_information' => '<p>Mother: Maria, Father: Jose</p>',
        ])
        ->assertRedirect();

    expect(Setting::getValue('family_information'))->toBe('<p>Mother: Maria, Father: Jose</p>');
});

test('helper cannot update family info', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->put('/family-info', [
            'family_information' => '<p>Hacked</p>',
        ])
        ->assertForbidden();
});

test('family info sanitizes dangerous html', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put('/family-info', [
            'family_information' => '<p>Safe</p><script>alert("xss")</script><img onerror="alert(1)" src="x">',
        ])
        ->assertRedirect();

    $info = Setting::getValue('family_information');
    expect($info)->not->toContain('<script>');
    expect($info)->not->toContain('<img');
    expect($info)->toContain('<p>Safe</p>');
});

test('guest cannot access family info', function () {
    $this->get('/family-info')
        ->assertRedirect('/login');
});
