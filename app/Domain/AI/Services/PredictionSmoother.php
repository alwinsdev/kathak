<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DTOs\DetectionResult;
use App\Domain\AI\DTOs\MudraPrediction;
use App\Models\PracticeSession;
use Illuminate\Support\Facades\Cache;

/**
 * Anti-shake temporal smoothing over per-frame predictions.
 *
 * A single camera frame is a noisy sample: a small hand tremor or motion blur
 * can flip one frame to the wrong class even while the patient is holding the
 * mudra correctly. Judged in isolation, that one frame flashes "incorrect"
 * and pauses the hold timer.
 *
 * This service keeps a short sliding window of recent frames per session and
 * rescues a non-matching frame when the recent evidence still clearly shows
 * the target mudra: at least `smoothing_min_agreement` of the window frames
 * detected the target AND their average confidence clears the threshold.
 *
 * It can only rescue isolated flickers — a persistently wrong pose fails the
 * agreement vote within a couple of frames, and a low-confidence hold is
 * never upgraded because the average still has to beat the threshold.
 */
class PredictionSmoother
{
    public function smooth(PracticeSession $session, DetectionResult $frame): DetectionResult
    {
        $session->loadMissing('prescription.mudra');
        $target = (string) ($session->prescription->mudra->ai_class_label ?? '');

        if ($target === '') {
            return $frame;
        }

        $isTarget = $frame->detectedClass !== null
            && mb_strtolower(trim($frame->detectedClass)) === mb_strtolower(trim($target));

        $window = $this->push($session, $isTarget ? $frame->confidence : 0.0);

        // A genuinely matching frame passes through untouched (it already fed
        // the window above, strengthening the vote for its neighbours).
        if ($frame->matched) {
            return $frame;
        }

        $targetConfidences = array_values(array_filter($window, fn (float $c) => $c > 0.0));
        $agreement = count($targetConfidences) / count($window);
        $mean = $targetConfidences === [] ? 0.0 : array_sum($targetConfidences) / count($targetConfidences);
        $threshold = (float) config('practice.confidence_threshold');

        if ($agreement < (float) config('practice.smoothing_min_agreement') || $mean < $threshold) {
            return $frame;
        }

        // Rescue: recent evidence says the patient is holding the target mudra
        // and this frame is an isolated flicker. Report the smoothed view so
        // the UI stays steady and the hold timer keeps crediting.
        $confidence = round($mean, 4);
        $box = $frame->predictions[0] ?? null;

        return new DetectionResult(
            matched: true,
            confidence: $confidence,
            detectedClass: $target,
            topConfidence: $confidence,
            predictions: [new MudraPrediction($target, $confidence, $box?->x, $box?->y, $box?->width, $box?->height)],
            processingMs: $frame->processingMs,
        );
    }

    public function clear(PracticeSession $session): void
    {
        Cache::forget($this->key($session));
    }

    /**
     * Append this frame's target-confidence (0.0 when the frame did not show
     * the target) and return the trimmed window.
     *
     * @return list<float>
     */
    private function push(PracticeSession $session, float $targetConfidence): array
    {
        $window = Cache::get($this->key($session), []);
        $window[] = $targetConfidence;
        $window = array_slice($window, -max(1, (int) config('practice.smoothing_window')));

        Cache::put($this->key($session), $window, (int) config('practice.hold_cache_ttl'));

        return $window;
    }

    private function key(PracticeSession $session): string
    {
        return "practice:smooth:{$session->id}";
    }
}
