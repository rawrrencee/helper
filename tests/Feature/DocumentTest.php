<?php

use App\Models\Document;
use App\Models\Helper;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view documents for a helper', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    Document::factory()->count(3)->create(['helper_id' => $helper->id]);

    $this->actingAs($admin)
        ->get("/helpers/{$helper->id}/documents")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('documents/Index')
            ->has('documents.data', 3)
        );
});

test('helper can view own documents', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    Document::factory()->create(['helper_id' => $helper->id]);

    $this->actingAs($user)
        ->get("/helpers/{$helper->id}/documents")
        ->assertOk();
});

test('helper cannot view another helper documents', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $otherHelper = Helper::factory()->create();

    $this->actingAs($user)
        ->get("/helpers/{$otherHelper->id}/documents")
        ->assertForbidden();
});

test('admin can upload a document', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();

    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post("/helpers/{$helper->id}/documents", ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('documents', [
        'helper_id' => $helper->id,
        'name' => 'contract.pdf',
    ]);
});

test('helper cannot upload a document', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);

    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post("/helpers/{$helper->id}/documents", ['file' => $file])
        ->assertForbidden();
});

test('admin can delete a document', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $document = Document::factory()->create(['helper_id' => $helper->id]);

    Storage::disk('local')->put($document->file_path, 'content');

    $this->actingAs($admin)
        ->delete("/documents/{$document->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('documents', ['id' => $document->id]);
});

test('helper cannot delete a document', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $document = Document::factory()->create(['helper_id' => $helper->id]);

    $this->actingAs($user)
        ->delete("/documents/{$document->id}")
        ->assertForbidden();
});

test('hidden documents are not shown to helper in index', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    Document::factory()->create(['helper_id' => $helper->id, 'hidden_from_helper' => false]);
    Document::factory()->create(['helper_id' => $helper->id, 'hidden_from_helper' => true]);

    $this->actingAs($user)
        ->get("/helpers/{$helper->id}/documents")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('documents/Index')
            ->has('documents.data', 1)
        );
});

test('hidden documents are shown to admin in index', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    Document::factory()->create(['helper_id' => $helper->id, 'hidden_from_helper' => false]);
    Document::factory()->create(['helper_id' => $helper->id, 'hidden_from_helper' => true]);

    $this->actingAs($admin)
        ->get("/helpers/{$helper->id}/documents")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('documents/Index')
            ->has('documents.data', 2)
        );
});

test('helper cannot download hidden document', function () {
    Storage::fake('local');

    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $document = Document::factory()->create([
        'helper_id' => $helper->id,
        'hidden_from_helper' => true,
    ]);

    Storage::disk('local')->put($document->file_path, 'content');

    $this->actingAs($user)
        ->get("/documents/{$document->id}/download")
        ->assertForbidden();
});

test('admin can download hidden document', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $document = Document::factory()->create([
        'helper_id' => $helper->id,
        'hidden_from_helper' => true,
    ]);

    Storage::disk('local')->put($document->file_path, 'content');

    $this->actingAs($admin)
        ->get("/documents/{$document->id}/download")
        ->assertOk();
});

test('admin can toggle document visibility', function () {
    $admin = User::factory()->admin()->create();
    $helper = Helper::factory()->create();
    $document = Document::factory()->create([
        'helper_id' => $helper->id,
        'hidden_from_helper' => false,
    ]);

    $this->actingAs($admin)
        ->patch("/documents/{$document->id}/toggle-visibility")
        ->assertRedirect();

    expect($document->fresh()->hidden_from_helper)->toBeTrue();

    $this->actingAs($admin)
        ->patch("/documents/{$document->id}/toggle-visibility")
        ->assertRedirect();

    expect($document->fresh()->hidden_from_helper)->toBeFalse();
});

test('helper cannot toggle document visibility', function () {
    $user = User::factory()->create(['role' => 'helper']);
    $helper = Helper::factory()->create(['user_id' => $user->id]);
    $document = Document::factory()->create(['helper_id' => $helper->id]);

    $this->actingAs($user)
        ->patch("/documents/{$document->id}/toggle-visibility")
        ->assertForbidden();
});
