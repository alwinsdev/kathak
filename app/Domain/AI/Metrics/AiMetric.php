<?php

declare(strict_types=1);

namespace App\Domain\AI\Metrics;

/**
 * Canonical AI metric names (no magic strings at call sites).
 */
final class AiMetric
{
    public const VERIFICATION_ATTEMPTS = 'verification_attempts';

    public const VERIFICATION_SUCCESS = 'verification_success';

    public const VERIFICATION_TIMEOUT = 'verification_timeout';

    public const INFERENCE_FAILURES = 'inference_failures';

    public const AVERAGE_PROCESSING_TIME_MS = 'average_processing_time_ms';
}
