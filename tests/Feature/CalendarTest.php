<?php

use App\Models\Helper;
use App\Models\Patient;
use App\Models\User;

test('admin can view calendars page', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->get('/calendars')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/Index')
            ->has('patientSchedules')
        );
});

test('helper can view calendars page with assigned patients', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $patient = Patient::factory()->create();
    $patient->helpers()->attach($helper);

    $this->actingAs($user)
        ->get('/calendars')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('calendars/Index')
            ->has('patientSchedules', 1)
            ->where('patientSchedules.0.patient.id', $patient->id)
        );
});

test('guest cannot view calendars page', function () {
    $this->get('/calendars')
        ->assertRedirect('/login');
});
