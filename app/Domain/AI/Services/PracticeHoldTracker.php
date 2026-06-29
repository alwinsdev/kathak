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
 * Each matched frame credits the time since the previous matched frame, but a
 * single credit is capped (the "grace step") so that slow inference or a dropped
 * frame can never reset the hold — only a frame that does NOT match the target
 * resets it. This keeps the hold intuitive ("keep showing the mudra and the bar
 * fills") even though inference latency makes frames arrive a few seconds apart.
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

        // Cap a single credit so a long gap (slow inference / one dropped frame)
        // contributes a bounded amount rather than resetting progress.
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
