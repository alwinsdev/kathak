<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Domain\AI\Contracts\MetricsRecorder;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight, dependency-free metrics: counters and running averages kept in
 * the cache. Enough to observe operational health now; a dashboard or proper
 * metrics backend can read/replace these later without touching call sites.
 */
class CacheMetricsRecorder implements MetricsRecorder
{
    private const PREFIX = 'metrics:ai:';

    public function increment(string $metric, int $by = 1): void
    {
        $this->bump(self::PREFIX.$metric, $by);
    }

    public function observe(string $metric, float $value): void
    {
        // Store sum + count so an average can be derived on read.
        $this->bump(self::PREFIX.$metric.':sum', (int) round($value));
        $this->bump(self::PREFIX.$metric.':count', 1);
    }

    private function bump(string $key, int $by): void
    {
        Cache::add($key, 0);
        Cache::increment($key, $by);
    }
}
