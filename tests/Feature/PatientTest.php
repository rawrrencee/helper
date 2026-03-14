<?php

use App\Models\Helper;
use App\Models\Patient;
use App\Models\User;

test('admin can view patients index', function () {
    $admin = User::factory()->admin()->create();
    Patient::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/patients')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Index')
            ->has('patients', 2)
        );
});

test('helper can only see assigned patients', function () {
    $helper = Helper::factory()->create();
    $assignedPatient = Patient::factory()->create();
    $unassignedPatient = Patient::factory()->create();
    $assignedPatient->helpers()->attach($helper);

    $this->actingAs($helper->user)
        ->get('/patients')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Index')
            ->has('patients', 1)
        );
});

test('admin can view create patient form', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/patients/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Create')
            ->has('helpers')
        );
});

test('helper cannot view create patient form', function () {
    $helper = Helper::factory()->create();

    $this->actingAs($helper->user)
        ->get('/patients/create')
        ->assertForbidden();
});

test('admin can create a patient', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/patients', [
            'name' => 'John Doe',
            'nric' => 'S1234567A',
            'phone' => '91234567',
            'helper_ids' => [$helper->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('patients', [
        'name' => 'John Doe',
        'nric' => 'S1234567A',
    ]);

    $patient = Patient::where('nric', 'S1234567A')->first();
    expect($patient->helpers)->toHaveCount(1);
});

test('helper cannot create a patient', function () {
    $helper = Helper::factory()->create();

    $this->actingAs($helper->user)
        ->post('/patients', [
            'name' => 'Hacker',
            'nric' => 'S1234567A',
            'helper_ids' => [$helper->id],
        ])
        ->assertForbidden();
});

test('nric validation requires correct format', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->post('/patients', [
            'name' => 'Test',
            'nric' => 'INVALID',
            'helper_ids' => [$helper->id],
        ])
        ->assertSessionHasErrors(['nric']);
});

test('nric must be unique', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    Patient::factory()->create(['nric' => 'S1234567A']);

    $this->actingAs($admin)
        ->post('/patients', [
            'name' => 'Test',
            'nric' => 'S1234567A',
            'helper_ids' => [$helper->id],
        ])
        ->assertSessionHasErrors(['nric']);
});

test('admin can view patient show page with full nric', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create(['nric' => 'S1234567A']);

    $this->actingAs($admin)
        ->get("/patients/{$patient->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('patient.nric', 'S1234567A')
            ->where('isAdmin', true)
        );
});

test('helper can view assigned patient with masked nric', function () {
    $helper = Helper::factory()->create();
    $patient = Patient::factory()->create(['nric' => 'S1234567A']);
    $patient->helpers()->attach($helper);

    $response = $this->actingAs($helper->user)
        ->get("/patients/{$patient->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('patients/Show')
            ->where('patient.masked_nric', '***567A')
            ->where('isAdmin', false)
            ->missing('patient.nric')
        );
});

test('helper cannot view unassigned patient', function () {
    $helper = Helper::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($helper->user)
        ->get("/patients/{$patient->id}")
        ->assertForbidden();
});

test('admin can update a patient', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create(['nric' => 'S1234567A']);
    $helper = Helper::factory()->create();

    $this->actingAs($admin)
        ->put("/patients/{$patient->id}", [
            'name' => 'Updated Name',
            'nric' => 'S1234567A',
            'helper_ids' => [$helper->id],
        ])
        ->assertRedirect();

    expect($patient->fresh()->name)->toBe('Updated Name');
});

test('admin can delete a patient', function () {
    $admin = User::factory()->admin()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->delete("/patients/{$patient->id}")
        ->assertRedirect('/patients');

    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});

test('helper cannot delete a patient', function () {
    $helper = Helper::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($helper->user)
        ->delete("/patients/{$patient->id}")
        ->assertForbidden();
});

test('patient nric is masked in serialization', function () {
    $patient = Patient::factory()->create(['nric' => 'S1234567A']);
    $array = $patient->toArray();

    expect($array)->not->toHaveKey('nric');
    expect($array['masked_nric'])->toBe('***567A');
});
