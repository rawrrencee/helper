<?php

use App\Models\Appointment;
use App\Models\Helper;
use App\Models\Patient;
use App\Models\User;

test('admin can create an appointment', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/appointments', [
            'title' => 'Doctor Checkup',
            'appointment_date' => '2026-04-01',
            'appointment_time' => '10:00',
            'location' => 'General Hospital',
            'notes' => 'Bring medical records',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('appointments', [
        'title' => 'Doctor Checkup',
        'appointment_date' => '2026-04-01 00:00:00',
        'location' => 'General Hospital',
    ]);
});

test('admin can create appointment with doctor', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/appointments', [
            'title' => 'Dental Checkup',
            'doctor' => 'Jane Smith',
            'appointment_date' => '2026-04-15',
            'appointment_time' => '14:00',
            'location' => 'Dental Clinic',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('appointments', [
        'title' => 'Dental Checkup',
        'doctor' => 'Jane Smith',
    ]);
});

test('helper cannot create an appointment', function () {
    $user = User::factory()->create(['role' => 'helper']);

    $this->actingAs($user)
        ->post('/appointments', [
            'title' => 'Doctor Checkup',
            'appointment_date' => '2026-04-01',
        ])
        ->assertForbidden();
});

test('admin can update an appointment', function () {
    $admin = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($admin)
        ->put("/appointments/{$appointment->id}", [
            'title' => 'Updated Title',
            'doctor' => 'Dr. Updated',
            'appointment_date' => '2026-05-01',
            'status' => 'completed',
        ])
        ->assertRedirect();

    expect($appointment->fresh()->title)->toBe('Updated Title');
    expect($appointment->fresh()->doctor)->toBe('Dr. Updated');
    expect($appointment->fresh()->status)->toBe('completed');
});

test('helper cannot update an appointment', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $appointment = Appointment::factory()->create();

    $this->actingAs($user)
        ->put("/appointments/{$appointment->id}", [
            'title' => 'Hacked',
            'appointment_date' => '2026-05-01',
        ])
        ->assertForbidden();
});

test('admin can delete an appointment', function () {
    $admin = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($admin)
        ->delete("/appointments/{$appointment->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
});

test('helper cannot delete an appointment', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $appointment = Appointment::factory()->create();

    $this->actingAs($user)
        ->delete("/appointments/{$appointment->id}")
        ->assertForbidden();
});

test('validation requires title and date', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/appointments', [])
        ->assertSessionHasErrors(['title', 'appointment_date']);
});

test('family info page does not include appointments', function () {
    $admin = User::factory()->admin()->create();
    Appointment::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/family-info')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('FamilyInfo')
            ->missing('appointments')
        );
});

test('helper can view family info without appointments', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Appointment::factory()->count(2)->create();

    $this->actingAs($user)
        ->get('/family-info')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('appointments')
        );
});

test('admin can view appointments index', function () {
    $admin = User::factory()->admin()->create();
    Appointment::factory()->count(2)->create();
    Appointment::factory()->completed()->create();

    $this->actingAs($admin)
        ->get('/appointments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('appointments/Index')
            ->has('upcomingAppointments')
            ->has('completedAppointments')
        );
});

test('helper can view appointments index', function () {
    $user = User::factory()->create(['role' => 'helper']);
    Appointment::factory()->count(2)->create();

    $this->actingAs($user)
        ->get('/appointments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('appointments/Index')
            ->has('upcomingAppointments')
            ->has('completedAppointments')
        );
});

test('helper can update appointment notes', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $appointment = Appointment::factory()->create(['notes' => 'Old notes']);

    $this->actingAs($user)
        ->patch("/appointments/{$appointment->id}/notes", [
            'notes' => 'Updated by helper',
        ])
        ->assertRedirect();

    expect($appointment->fresh()->notes)->toBe('Updated by helper');
});

test('helper cannot update other fields via notes endpoint', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $appointment = Appointment::factory()->create(['title' => 'Original Title', 'notes' => 'Old notes']);

    $this->actingAs($user)
        ->patch("/appointments/{$appointment->id}/notes", [
            'notes' => 'New notes',
            'title' => 'Hacked Title',
        ])
        ->assertRedirect();

    $fresh = $appointment->fresh();
    expect($fresh->notes)->toBe('New notes');
    expect($fresh->title)->toBe('Original Title');
});

test('admin can create appointment linked to patient', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->post('/appointments', [
            'title' => 'Patient Checkup',
            'patient_id' => $patient->id,
            'appointment_date' => '2026-04-01',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('appointments', [
        'title' => 'Patient Checkup',
        'patient_id' => $patient->id,
    ]);
});

test('admin can create appointment without patient', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/appointments', [
            'title' => 'General Appointment',
            'appointment_date' => '2026-04-01',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('appointments', [
        'title' => 'General Appointment',
        'patient_id' => null,
    ]);
});

test('helper sees appointments for assigned patients and unlinked appointments', function () {
    $helper = Helper::factory()->create();
    $assignedPatient = Patient::factory()->create();
    $unassignedPatient = Patient::factory()->create();
    $assignedPatient->helpers()->attach($helper);

    Appointment::factory()->forPatient($assignedPatient)->create();
    Appointment::factory()->forPatient($unassignedPatient)->create();
    Appointment::factory()->create(); // no patient

    $this->actingAs($helper->user)
        ->get('/appointments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('appointments/Index')
            ->has('upcomingAppointments', 2)
        );
});

test('admin index includes patients list for dropdown', function () {
    $admin = User::factory()->admin()->create();
    Patient::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/appointments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('patients', 2)
        );
});
