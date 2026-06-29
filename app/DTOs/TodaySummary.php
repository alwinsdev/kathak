<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class TodaySummary
{
    public function __construct(
        public int $total,
        public int $completed,
        public int $pending,
    ) {}
}
