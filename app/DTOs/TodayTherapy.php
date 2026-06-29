<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * The patient's therapy for today: the due mudras plus the summary counts.
 */
readonly class TodayTherapy
{
    /**
     * @param  Collection<int, DueMudra>  $mudras
     */
    public function __construct(
        public Collection $mudras,
        public TodaySummary $summary,
    ) {}
}
