<?php

use App\Models\Helper;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;

test('admin can add medication to patient', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->post("/patients/{$patient->id}/medications", [
            'name' => 'Paracetamol',
            'dosage' => '500mg',
            'frequency' => 'After Breakfast',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('medications', [
        'patient_id' => $patient->id,
        'name' => 'Paracetamol',
        'dosage' => '500mg',
        'frequency' => 'After Breakfast',
    ]);
});

test('admin can add medication with custom time frequency', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->post("/patients/{$patient->id}/medications", [
            'name' => 'Metformin',
            'frequency' => '08:00',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('medications', [
        'name' => 'Metformin',
        'frequency' => '08:00',
    ]);
});

test('helper cannot add medication', function () {
    $helper = Helper::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($helper->user)
        ->post("/patients/{$patient->id}/medications", [
            'name' => 'Hacker Drug',
            'frequency' => 'After Breakfast',
        ])
        ->assertForbidden();
});

test('admin can update medication', function () {
    $admin = User::factory()->admin()->create();
    $medication = Medication::factory()->create();

    $this->actingAs($admin)
        ->put("/patients/{$medication->patient_id}/medications/{$medication->id}", [
            'name' => 'Updated Med',
            'frequency' => 'Before Sleep',
        ])
        ->assertRedirect();

    expect($medication->fresh()->name)->toBe('Updated Med');
    expect($medication->fresh()->frequency)->toBe('Before Sleep');
});

test('helper cannot update medication', function () {
    $helper = Helper::factory()->create();
    $medication = Medication::factory()->create();

    $this->actingAs($helper->user)
        ->put("/patients/{$medication->patient_id}/medications/{$medication->id}", [
            'name' => 'Hacked',
            'frequency' => 'After Breakfast',
        ])
        ->assertForbidden();
});

test('admin can delete medication', function () {
    $admin = User::factory()->admin()->create();
    $medication = Medication::factory()->create();

    $this->actingAs($admin)
        ->delete("/patients/{$medication->patient_id}/medications/{$medication->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('medications', ['id' => $medication->id]);
});

test('helper cannot delete medication', function () {
    $helper = Helper::factory()->create();
    $medication = Medication::factory()->create();

    $this->actingAs($helper->user)
        ->delete("/patients/{$medication->patient_id}/medications/{$medication->id}")
        ->assertForbidden();
});

test('medication name and frequency are required', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->post("/patients/{$patient->id}/medications", [])
        ->assertSessionHasErrors(['name', 'frequency']);
});
