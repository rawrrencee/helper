<?php

use App\Http\Controllers\Settings\AdminUserController;
use App\Http\Controllers\Settings\EmployerController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::middleware('admin')->group(function () {
        Route::get('settings/employer', [EmployerController::class, 'edit'])->name('employer.edit');
        Route::put('settings/employer', [EmployerController::class, 'update'])->name('employer.update');

        Route::get('settings/admin-users', [AdminUserController::class, 'index'])->name('admin-users.index');
        Route::post('settings/admin-users', [AdminUserController::class, 'store'])->name('admin-users.store');
        Route::delete('settings/admin-users/{user}', [AdminUserController::class, 'destroy'])->name('admin-users.destroy');
    });
});
