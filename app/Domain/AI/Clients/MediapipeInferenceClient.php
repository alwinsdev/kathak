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
    /**
     * Generic class for detections that map to no prescribed mudra. Keeps the
     * model's internal vocabulary out of the application while still letting
     * the UI distinguish "incorrect mudra" from "no hand detected".
     */
    public const INCORRECT = 'other';

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

        // Internal mapping layer: raw model class -> Siddha mudra label. An
        // unmapped class is reported generically so the raw token never leaves
        // this boundary.
        $class = $this->mapLabel((string) $prediction['label']) ?? self::INCORRECT;

        return new InferenceResult([
            new MudraPrediction($class, (float) ($prediction['confidence'] ?? 0)),
        ]);
    }

    /**
     * TEMPORARY POC MAPPING: translate a raw model class to a Siddha mudra
     * label. The current YOLO model was trained on Bharatanatyam classes as a
     * stop-gap; the application exposes Siddha names only, so this is the one
     * boundary where the model's vocabulary is allowed to appear. Remove once
     * a dedicated Siddha Mudra model (with Siddha class names) is trained —
     * see services.mediapipe.temporary_poc_model_mapping.
     */
    private function mapLabel(string $label): ?string
    {
        $map = (array) config('services.mediapipe.temporary_poc_model_mapping', []);

        return $map[$label] ?? null;
    }
}
