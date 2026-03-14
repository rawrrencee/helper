<?php

use App\Models\Appointment;
use App\Models\Helper;
use App\Models\Setting;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can visit the dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('dashboardData.role', 'admin')
            ->has('dashboardData.totalHelpers')
        );
});

test('helper can visit the dashboard', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Helper::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('dashboardData.role', 'helper')
            ->has('dashboardData.helper')
        );
});

test('admin dashboard shows helper count', function () {
    $admin = User::factory()->admin()->create();
    Helper::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('dashboardData.totalHelpers', 3)
        );
});

test('admin dashboard includes upcoming appointments and family info', function () {
    $admin = User::factory()->admin()->create();
    Appointment::factory()->count(2)->create();
    Setting::setValue('family_information', '<p>Test family info</p>');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dashboardData.upcomingAppointments')
            ->where('dashboardData.familyInformation', '<p>Test family info</p>')
        );
});

test('helper dashboard includes upcoming appointments and family info', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Helper::factory()->create(['user_id' => $user->id]);
    Appointment::factory()->count(2)->create();
    Setting::setValue('family_information', '<p>Helper family info</p>');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dashboardData.upcomingAppointments')
            ->where('dashboardData.familyInformation', '<p>Helper family info</p>')
        );
});
