<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A doctor may manage a patient only if that patient is in their panel.
        Gate::define('manage-patient', function (User $doctor, User $patient): bool {
            return $doctor->isDoctor()
                && $patient->isPatient()
                && $patient->patientProfile?->doctor_id === $doctor->id;
        });
    }
}
