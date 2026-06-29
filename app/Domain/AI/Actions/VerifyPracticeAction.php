<?php

declare(strict_types=1);

namespace App\Domain\AI\Actions;

use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\DTOs\DetectionResult;
use App\Domain\AI\Exceptions\InferenceException;
use App\Models\PracticeSession;
use Illuminate\Support\Facades\Log;

/**
 * Core per-frame use case: run inference and decide whether the frame shows the
 * prescribed mudra above the confidence threshold.
 *
 * STATELESS by contract: input -> inference -> evaluation -> DetectionResult.
 * It never writes to the database, cache, or session state. Hold tracking and
 * completion are owned by PracticeHoldTracker / PracticeSessionService.
 */
class VerifyPracticeAction
{
    public function __construct(private readonly InferenceClient $inference) {}

    public function handle(PracticeSession $session, string $imageBinary, string $correlationId): DetectionResult
    {
        $session->loadMissing('prescription.mudra');

        $target = (string) ($session->prescription->mudra->ai_class_label ?? '');
        $threshold = (float) config('practice.confidence_threshold');

        $context = [
            'correlation_id' => $correlationId,
            'patient_id' => $session->patient_id,
            'prescription_id' => $session->prescription_id,
            'practice_session_id' => $session->id,
        ];

        Log::channel('business')->info('inference_start', $context);
        $startedAt = microtime(true);

        try {
            $result = $this->inference->detect($imageBinary);
        } catch (InferenceException $e) {
            Log::channel('business')->warning('inference_failed', $context + [
                'processing_time_ms' => $this->elapsedMs($startedAt),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $processingMs = $this->elapsedMs($startedAt);
        $confidence = $target !== '' ? $result->confidenceFor($target) : 0.0;
        $matched = $confidence >= $threshold;
        $top = $result->topPrediction();

        Log::channel('business')->info('inference_success', $context + [
            'processing_time_ms' => $processingMs,
            'detected_class' => $top?->class,
            'confidence' => $confidence,
            'matched' => $matched,
        ]);

        return new DetectionResult(
            matched: $matched,
            confidence: $confidence,
            detectedClass: $top?->class,
            topConfidence: $top?->confidence ?? 0.0,
            predictions: $result->predictions,
            processingMs: $processingMs,
        );
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
