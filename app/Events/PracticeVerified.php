<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PracticeSession;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a practice session is AI-verified. Dispatched by L4; defined now
 * so listeners (logging, future notifications/streaks) can be wired ahead.
 */
class PracticeVerified
{
    use Dispatchable;

    public function __construct(public readonly PracticeSession $session) {}
}
