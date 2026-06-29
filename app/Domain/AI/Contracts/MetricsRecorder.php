<?php

declare(strict_types=1);

namespace App\Domain\AI\Contracts;

interface MetricsRecorder
{
    /** Increment a counter metric. */
    public function increment(string $metric, int $by = 1): void;

    /** Record an observation (e.g. a duration) for averaging. */
    public function observe(string $metric, float $value): void;
}
