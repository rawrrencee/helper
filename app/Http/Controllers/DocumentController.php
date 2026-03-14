<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * List documents for a helper.
     */
    public function index(Helper $helper): Response
    {
        $this->authorize('viewAny', [Document::class, $helper]);

        $query = $helper->documents()->latest();

        if (! auth()->user()->isAdmin()) {
            $query->where('hidden_from_helper', false);
        }

        $documents = $query->paginate(10);

        return Inertia::render('documents/Index', [
            'helper' => $helper,
            'documents' => $documents,
        ]);
    }

    /**
     * Upload a document for a helper.
     */
    public function store(Request $request, Helper $helper): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'name' => ['nullable', 'string', 'max:255'],
            'hidden_from_helper' => ['sometimes', 'boolean'],
        ]);

        $file = $request->file('file');
        $path = $file->store("documents/{$helper->id}", 'local');

        $helper->documents()->create([
            'name' => $request->input('name', $file->getClientOriginalName()),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'hidden_from_helper' => $request->boolean('hidden_from_helper'),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    /**
     * Download a document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk('local')->download($document->file_path, $document->name);
    }

    /**
     * Toggle document visibility for helpers.
     */
    public function toggleVisibility(Document $document): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $document->update(['hidden_from_helper' => ! $document->hidden_from_helper]);

        return back()->with('success', 'Document visibility updated.');
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }
}
