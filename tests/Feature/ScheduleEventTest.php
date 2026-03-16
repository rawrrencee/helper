<?php

use App\Models\Helper;
use App\Models\Patient;
use App\Models\ScheduleEvent;
use App\Models\User;

test('admin can view schedule for a patient', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $patient->helpers()->attach(Helper::factory()->create());

    $this->actingAs($admin)
        ->get("/patients/{$patient->id}/schedule")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Schedule')
            ->has('patient')
            ->has('scheduleEvents')
            ->has('medicationEvents')
            ->has('appointments')
        );
});

test('admin can create a schedule event', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $patient->helpers()->attach(Helper::factory()->create());

    $this->actingAs($admin)
        ->post("/patients/{$patient->id}/schedule-events", [
            'title' => 'DDC',
            'event_type' => 'activity',
            'recurrence_type' => 'weekly',
            'recurrence_days' => [1, 3, 5],
            'time_of_day' => '09:00',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('schedule_events', [
        'patient_id' => $patient->id,
        'title' => 'DDC',
        'recurrence_type' => 'weekly',
    ]);
});

test('admin can update a schedule event', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $patient->helpers()->attach(Helper::factory()->create());
    $event = ScheduleEvent::create([
        'patient_id' => $patient->id,
        'title' => 'DDC',
        'event_type' => 'activity',
        'recurrence_type' => 'weekly',
        'recurrence_days' => [1, 3, 5],
        'time_of_day' => '09:00',
    ]);

    $this->actingAs($admin)
        ->put("/schedule-events/{$event->id}", [
            'title' => 'Active Aging Center',
            'event_type' => 'activity',
            'recurrence_type' => 'daily',
            'time_of_day' => '10:00',
        ])
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('Active Aging Center');
    expect($event->fresh()->recurrence_type)->toBe('daily');
});

test('admin can delete a schedule event', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();
    $patient->helpers()->attach(Helper::factory()->create());
    $event = ScheduleEvent::create([
        'patient_id' => $patient->id,
        'title' => 'DDC',
        'event_type' => 'activity',
        'recurrence_type' => 'weekly',
        'recurrence_days' => [1, 3, 5],
        'time_of_day' => '09:00',
    ]);

    $this->actingAs($admin)
        ->delete("/schedule-events/{$event->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('schedule_events', ['id' => $event->id]);
});

test('helper cannot create a schedule event', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $patient = Patient::factory()->create();
    $patient->helpers()->attach($helper);

    $this->actingAs($user)
        ->post("/patients/{$patient->id}/schedule-events", [
            'title' => 'DDC',
            'event_type' => 'activity',
            'recurrence_type' => 'weekly',
            'recurrence_days' => [1, 3, 5],
            'time_of_day' => '09:00',
        ])
        ->assertForbidden();
});

test('helper can view schedule for assigned patient', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $patient = Patient::factory()->create();
    $patient->helpers()->attach($helper);

    $this->actingAs($user)
        ->get("/patients/{$patient->id}/schedule")
        ->assertOk();
});
