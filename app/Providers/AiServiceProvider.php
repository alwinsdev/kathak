<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\AI\Clients\MediapipeInferenceClient;
use App\Domain\AI\Clients\RoboflowInferenceClient;
use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\Contracts\MetricsRecorder;
use App\Domain\AI\Services\CacheMetricsRecorder;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Bind the AI domain's contracts to their concrete implementations. The
     * inference driver is config-selected ('roboflow' default, 'mediapipe' for
     * the self-hosted AI service). Tests rebind InferenceClient to a fake.
     */
    public function register(): void
    {
        $this->app->bind(InferenceClient::class, function ($app) {
            return match (config('services.inference.driver')) {
                'mediapipe' => $app->make(MediapipeInferenceClient::class),
                default => $app->make(RoboflowInferenceClient::class),
            };
        });
        $this->app->bind(MetricsRecorder::class, CacheMetricsRecorder::class);
    }
}
