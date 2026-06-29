<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
     * Authorisation is handled by Policies (discovered by convention). Domain-
     * event listeners in app/Listeners are auto-discovered by Laravel.
     */
    public function boot(): void
    {
        // Per-user rate limit for the AI detection endpoint (cost/abuse guard).
        RateLimiter::for('practice-detect', function (Request $request) {
            return Limit::perMinute((int) config('practice.detect_rate_limit_per_minute'))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
