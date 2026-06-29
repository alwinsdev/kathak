<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DTOs\HoldProgress;
use App\Models\PracticeSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Owns the verification hold timer. Server-authoritative and cache-backed:
 *
 *  - accumulates held time across consecutive matched frames (server clock),
 *  - applies a grace window so brief jitter doesn't break the hold,
 *  - restarts when a frame doesn't match or a long gap occurs,
 *  - reports when the required hold has been reached (readiness).
 *
 * It stores only transient progress in the cache; it never touches the DB.
 */
class PracticeHoldTracker
{
    public function record(PracticeSession $session, bool $matched, float $confidence): HoldProgress
    {
        $holdSeconds = (int) config('practice.hold_seconds');

        if (! $matched) {
            $this->clear($session);

            return new HoldProgress(0.0, $holdSeconds, false, 0.0);
        }

        $now = $this->nowMs();
        $state = Cache::get($this->key($session));
        $maxGapMs = (int) round(((int) config('practice.detection_interval_ms')) * ((float) config('practice.hold_grace_factor')));

        if (is_array($state) && ($now - $state['last']) <= $maxGapMs) {
            $accumulatedMs = $state['accumulated'] + ($now - $state['last']);
        } else {
            // First matched frame, or the gap exceeded the grace window: restart.
            $accumulatedMs = 0;
        }

        $bestConfidence = max($state['best'] ?? 0.0, $confidence);

        Cache::put(
            $this->key($session),
            ['accumulated' => $accumulatedMs, 'last' => $now, 'best' => $bestConfidence],
            (int) config('practice.hold_cache_ttl'),
        );

        return new HoldProgress(
            heldSeconds: round($accumulatedMs / 1000, 2),
            holdSeconds: $holdSeconds,
            ready: $accumulatedMs >= $holdSeconds * 1000,
            bestConfidence: $bestConfidence,
        );
    }

    public function clear(PracticeSession $session): void
    {
        Cache::forget($this->key($session));
    }

    private function key(PracticeSession $session): string
    {
        return "practice:hold:{$session->id}";
    }

    private function nowMs(): int
    {
        return (int) Carbon::now()->valueOf();
    }
}
