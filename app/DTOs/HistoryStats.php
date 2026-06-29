<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Carbon;

readonly class HistoryStats
{
    public function __construct(
        public int $total,
        public int $thisWeek,
        public int $streak,
        public ?Carbon $lastPracticeDate,
    ) {}
}
