<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FamilyInfoController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SalaryPaymentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::put('helpers/bank-details', [HelperController::class, 'updateBankDetails'])->name('helpers.update-bank-details');
    Route::resource('helpers', HelperController::class);
    Route::post('helpers/{helper}/reset-password', [HelperController::class, 'resetPassword'])->name('helpers.reset-password');

    Route::get('helpers/{helper}/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('helpers/{helper}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::patch('documents/{document}/toggle-visibility', [DocumentController::class, 'toggleVisibility'])->name('documents.toggle-visibility');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::resource('salary-payments', SalaryPaymentController::class);
    Route::post('salary-payments/{salary_payment}/screenshot', [SalaryPaymentController::class, 'uploadScreenshot'])->name('salary-payments.upload-screenshot');
    Route::get('salary-payments/{salary_payment}/screenshot', [SalaryPaymentController::class, 'screenshot'])->name('salary-payments.screenshot');
    Route::get('salary-payments/{salary_payment}/pdf', [SalaryPaymentController::class, 'generatePdf'])->name('salary-payments.pdf');

    Route::get('family-info', [FamilyInfoController::class, 'show'])->name('family-info.show');
    Route::put('family-info', [FamilyInfoController::class, 'update'])->name('family-info.update');

    Route::resource('patients', PatientController::class);
    Route::post('patients/{patient}/medications', [MedicationController::class, 'store'])->name('patients.medications.store');
    Route::put('patients/{patient}/medications/{medication}', [MedicationController::class, 'update'])->name('patients.medications.update');
    Route::delete('patients/{patient}/medications/{medication}', [MedicationController::class, 'destroy'])->name('patients.medications.destroy');

    Route::resource('appointments', AppointmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('appointments/{appointment}/notes', [AppointmentController::class, 'updateNotes'])->name('appointments.update-notes');
});

require __DIR__.'/settings.php';
