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
 * Talks to the self-hosted MediaPipe AI service (POST /classify). The service
 * returns a generic hand-shape label (e.g. open_palm, closed_fist); we translate
 * it to a mudra class via the configurable label map (config/services.php), so
 * the existing verification workflow is unchanged. The API key and URL live in
 * config/.env and are never sent to the browser.
 */
class MediapipeInferenceClient implements InferenceClient
{
    public function detect(string $imageBinary): InferenceResult
    {
        $url = config('services.mediapipe.url');

        if (empty($url)) {
            throw new InferenceException('Inference service is not configured.');
        }

        try {
            $response = Http::timeout((int) config('practice.inference_timeout'))
                ->withHeaders(['X-API-Key' => (string) config('services.mediapipe.key')])
                ->attach('image', $imageBinary, 'frame.jpg')
                ->post(rtrim((string) $url, '/').'/classify');
        } catch (Throwable $e) {
            throw new InferenceException('Inference request failed: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new InferenceException('Inference service returned HTTP '.$response->status().'.');
        }

        $prediction = $response->json('prediction');
        if (! is_array($prediction) || ! isset($prediction['label'])) {
            return new InferenceResult([]); // no hand detected
        }

        $class = $this->mapLabel((string) $prediction['label']);
        if ($class === null) {
            return new InferenceResult([]); // unmapped/unknown label -> no match
        }

        return new InferenceResult([
            new MudraPrediction($class, (float) ($prediction['confidence'] ?? 0)),
        ]);
    }

    /** Translate an AI-service label to a mudra class via the configured map. */
    private function mapLabel(string $label): ?string
    {
        $map = (array) config('services.mediapipe.label_map', []);

        return $map[$label] ?? null;
    }
}
