<?php

declare(strict_types=1);

namespace App\Domain\AI\Clients;

use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\DTOs\InferenceResult;
use App\Domain\AI\DTOs\MudraPrediction;
use App\Domain\AI\Exceptions\InferenceException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Talks to the Roboflow serverless model. The API key lives in config/.env and
 * is never sent to the browser — the browser only ever calls our detect route.
 */
class RoboflowInferenceClient implements InferenceClient
{
    public function detect(string $imageBinary): InferenceResult
    {
        $key = config('services.roboflow.key');
        $url = config('services.roboflow.model_url');

        if (empty($key) || empty($url)) {
            throw new InferenceException('Inference service is not configured.');
        }

        try {
            $response = Http::timeout((int) config('practice.inference_timeout'))
                ->withBody(base64_encode($imageBinary), 'application/x-www-form-urlencoded')
                ->post($url.'?api_key='.urlencode((string) $key));
        } catch (Throwable $e) {
            throw new InferenceException('Inference request failed: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new InferenceException('Inference service returned HTTP '.$response->status().'.');
        }

        $predictions = array_map(
            static fn (array $p) => new MudraPrediction(
                class: (string) ($p['class'] ?? ''),
                confidence: (float) ($p['confidence'] ?? 0),
                x: isset($p['x']) ? (float) $p['x'] : null,
                y: isset($p['y']) ? (float) $p['y'] : null,
                width: isset($p['width']) ? (float) $p['width'] : null,
                height: isset($p['height']) ? (float) $p['height'] : null,
            ),
            $response->json('predictions', []),
        );

        return new InferenceResult(array_values($predictions));
    }
}
