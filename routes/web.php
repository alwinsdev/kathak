<?php

declare(strict_types=1);

use App\Http\Controllers\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\PrescriptionController as DoctorPrescriptionController;
use App\Http\Controllers\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Single entry point after login: route the user to their role's home.
 */
Route::get('/dashboard', function () {
    return redirect()->route(Auth::user()->isDoctor() ? 'doctor.dashboard' : 'patient.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Doctor area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:doctor'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/patients/{patient}', [DoctorPatientController::class, 'show'])->name('patients.show');

        Route::post('/patients/{patient}/prescriptions', [DoctorPrescriptionController::class, 'store'])->name('prescriptions.store');
        Route::put('/prescriptions/{prescription}', [DoctorPrescriptionController::class, 'update'])->name('prescriptions.update');
        Route::delete('/prescriptions/{prescription}', [DoctorPrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
    });

/*
|--------------------------------------------------------------------------
| Patient area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('/dashboard', [PatientDashboardController::class, 'index'])->name('dashboard');
    });

require __DIR__.'/auth.php';
