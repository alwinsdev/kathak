<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Clients\RoboflowInferenceClient;
use App\Domain\AI\Exceptions\InferenceException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoboflowInferenceClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.roboflow.key' => 'test-key',
            'services.roboflow.model_url' => 'https://example.test/model/1',
        ]);
    }

    public function test_parses_predictions_into_inference_result(): void
    {
        Http::fake([
            '*' => Http::response([
                'predictions' => [
                    ['class' => 'Pataka', 'confidence' => 0.91, 'x' => 100, 'y' => 120, 'width' => 80, 'height' => 90],
                    ['class' => 'Mushti', 'confidence' => 0.20],
                ],
            ]),
        ]);

        $result = (new RoboflowInferenceClient)->detect('binary-image');

        $this->assertCount(2, $result->predictions);
        $this->assertSame('Pataka', $result->topClass());
        $this->assertSame(0.91, $result->confidenceFor('Pataka'));
        $this->assertSame(80.0, $result->predictions[0]->width);
    }

    public function test_sends_api_key_and_never_requires_it_client_side(): void
    {
        Http::fake(['*' => Http::response(['predictions' => []])]);

        (new RoboflowInferenceClient)->detect('binary-image');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api_key=test-key'));
    }

    public function test_throws_inference_exception_on_http_error(): void
    {
        Http::fake(['*' => Http::response('upstream down', 500)]);

        $this->expectException(InferenceException::class);

        (new RoboflowInferenceClient)->detect('binary-image');
    }

    public function test_throws_when_not_configured(): void
    {
        config(['services.roboflow.key' => null]);

        $this->expectException(InferenceException::class);

        (new RoboflowInferenceClient)->detect('binary-image');
    }
}
