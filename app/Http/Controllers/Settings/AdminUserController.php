<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display the admin users list.
     */
    public function index(): Response
    {
        return Inertia::render('settings/AdminUsers', [
            'adminUsers' => User::query()
                ->where('role', 'admin')
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'email', 'created_at']),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => 'admin',
        ]);

        return back()->with('success', 'Admin user created.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if (! request()->user()->isAdmin()) {
            abort(403);
        }

        if ($user->id === request()->user()->id) {
            return back()->withErrors(['general' => 'You cannot delete your own account.']);
        }

        if (User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['general' => 'Cannot delete the last admin account.']);
        }

        $user->delete();

        return back()->with('success', 'Admin user deleted.');
    }
}
