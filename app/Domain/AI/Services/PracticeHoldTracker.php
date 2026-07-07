<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\DTOs\HoldProgress;
use App\Models\PracticeSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Owns the verification hold timer. Server-authoritative and cache-backed.
 *
 * The target is the doctor-prescribed duration, so progress is treated as
 * TOTAL practised time, not one unbroken streak:
 *
 * - A matched frame credits the time since the previous frame, capped by the
 *   grace step, so slow inference or a dropped frame contributes a bounded
 *   amount instead of stalling or inflating the hold.
 * - A frame that does NOT match (wrong pose, confidence dip, hand briefly out
 *   of view) PAUSES the hold: progress is kept, nothing is credited, and the
 *   next matched frame resumes from where it stopped. The classifier flickers
 *   below the threshold even during a good hold, so resetting would make long
 *   prescriptions impossible to complete.
 * - The session completes only on a matched frame that reaches the target.
 */
class PracticeHoldTracker
{
    public function record(PracticeSession $session, bool $matched, float $confidence): HoldProgress
    {
        $holdSeconds = $session->prescription?->holdSeconds() ?? (int) config('practice.hold_seconds');
        $now = $this->nowMs();
        $ttl = (int) config('practice.hold_cache_ttl');
        $state = Cache::get($this->key($session));

        if (! $matched) {
            if (! is_array($state)) {
                return new HoldProgress(0.0, $holdSeconds, false, 0.0);
            }

            // Pause: keep the accumulated time, refresh the TTL, and advance
            // the frame marker so the mismatch period itself is never credited.
            Cache::put(
                $this->key($session),
                ['accumulated' => $state['accumulated'], 'last' => $now, 'best' => $state['best'] ?? 0.0],
                $ttl,
            );

            return new HoldProgress(
                heldSeconds: round($state['accumulated'] / 1000, 2),
                holdSeconds: $holdSeconds,
                ready: false,
                bestConfidence: (float) ($state['best'] ?? 0.0),
            );
        }

        // Cap a single credit so a long gap (slow inference / one dropped frame)
        // contributes a bounded amount rather than inflating progress.
        $maxStepMs = (int) round(((int) config('practice.detection_interval_ms')) * ((float) config('practice.hold_grace_factor')));

        if (is_array($state)) {
            $accumulatedMs = $state['accumulated'] + min($now - $state['last'], $maxStepMs);
        } else {
            $accumulatedMs = 0; // first matched frame starts the hold
        }

        $bestConfidence = max($state['best'] ?? 0.0, $confidence);

        Cache::put(
            $this->key($session),
            ['accumulated' => $accumulatedMs, 'last' => $now, 'best' => $bestConfidence],
            $ttl,
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
