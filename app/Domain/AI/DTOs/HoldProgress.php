<?php

declare(strict_types=1);

namespace App\Domain\AI\DTOs;

/**
 * Current hold progress for a session, as decided by PracticeHoldTracker.
 */
readonly class HoldProgress
{
    public function __construct(
        public float $heldSeconds,
        public int $holdSeconds,
        public bool $ready,
        public float $bestConfidence,
    ) {}
}
