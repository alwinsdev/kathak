<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Clients\MediapipeInferenceClient;
use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\Exceptions\InferenceException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediapipeInferenceClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mediapipe.url' => 'http://ai.test',
            'services.mediapipe.key' => 'test-key',
            'services.mediapipe.label_map' => [
                'open_palm' => 'shuktund',
                'closed_fist' => 'shikhar',
            ],
        ]);
    }

    private function fakeClassify(array|string $body, int $status = 200): void
    {
        Http::fake(['ai.test/classify' => Http::response($body, $status)]);
    }

    public function test_maps_label_to_configured_mudra_class(): void
    {
        $this->fakeClassify([
            'success' => true,
            'prediction' => ['label' => 'closed_fist', 'confidence' => 1.0],
            'hands_detected' => 1,
            'processing_time_ms' => 12,
        ]);

        $result = (new MediapipeInferenceClient)->detect('binary-image');

        $this->assertSame('shikhar', $result->topClass());
        $this->assertSame(1.0, $result->confidenceFor('shikhar'));
    }

    public function test_sends_api_key_header_to_classify_endpoint(): void
    {
        $this->fakeClassify([
            'success' => true,
            'prediction' => ['label' => 'open_palm', 'confidence' => 1.0],
            'hands_detected' => 1,
            'processing_time_ms' => 9,
        ]);

        (new MediapipeInferenceClient)->detect('binary-image');

        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'test-key')
            && str_contains($request->url(), '/classify'));
    }

    public function test_unmapped_label_yields_no_prediction(): void
    {
        $this->fakeClassify([
            'success' => true,
            'prediction' => ['label' => 'unknown', 'confidence' => 0.0],
            'hands_detected' => 1,
            'processing_time_ms' => 5,
        ]);

        $result = (new MediapipeInferenceClient)->detect('binary-image');

        $this->assertNull($result->topPrediction());
    }

    public function test_no_hand_yields_no_prediction(): void
    {
        $this->fakeClassify([
            'success' => true,
            'prediction' => null,
            'hands_detected' => 0,
            'processing_time_ms' => 4,
        ]);

        $result = (new MediapipeInferenceClient)->detect('binary-image');

        $this->assertNull($result->topPrediction());
    }

    public function test_throws_inference_exception_on_http_error(): void
    {
        $this->fakeClassify('upstream down', 500);

        $this->expectException(InferenceException::class);

        (new MediapipeInferenceClient)->detect('binary-image');
    }

    public function test_throws_when_not_configured(): void
    {
        config(['services.mediapipe.url' => null]);

        $this->expectException(InferenceException::class);

        (new MediapipeInferenceClient)->detect('binary-image');
    }

    public function test_provider_binds_mediapipe_driver_from_config(): void
    {
        config(['services.inference.driver' => 'mediapipe']);

        $this->assertInstanceOf(
            MediapipeInferenceClient::class,
            $this->app->make(InferenceClient::class),
        );
    }
}
