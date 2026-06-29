<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\AI\Clients\RoboflowInferenceClient;
use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\Contracts\MetricsRecorder;
use App\Domain\AI\Services\CacheMetricsRecorder;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Bind the AI domain's contracts to their concrete implementations.
     * Tests rebind InferenceClient to a FakeInferenceClient.
     */
    public function register(): void
    {
        $this->app->bind(InferenceClient::class, RoboflowInferenceClient::class);
        $this->app->bind(MetricsRecorder::class, CacheMetricsRecorder::class);
    }
}
