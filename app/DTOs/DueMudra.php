<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Prescription;

/**
 * A prescription due today together with whether it has been completed today.
 */
readonly class DueMudra
{
    public function __construct(
        public Prescription $prescription,
        public bool $completedToday,
    ) {}
}
