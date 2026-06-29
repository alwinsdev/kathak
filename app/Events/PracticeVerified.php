<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PracticeSession;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired exactly once when a practice session becomes AI-verified.
 */
class PracticeVerified
{
    use Dispatchable;

    public function __construct(
        public readonly PracticeSession $session,
        public readonly ?string $correlationId = null,
    ) {}
}
