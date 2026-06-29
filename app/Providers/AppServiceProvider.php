<?php

declare(strict_types=1);

namespace App\Providers;

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
     *
     * Authorisation is handled by Policies (PrescriptionPolicy, UserPolicy),
     * discovered by convention. Domain-event listeners in app/Listeners are
     * auto-discovered by Laravel, so no manual wiring is needed here.
     */
    public function boot(): void
    {
        //
    }
}
