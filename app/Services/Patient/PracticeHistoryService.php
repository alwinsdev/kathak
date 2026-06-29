<?php

declare(strict_types=1);

namespace App\Services\Patient;

use App\DTOs\HistoryStats;
use App\Models\PracticeSession;
use App\Models\User;
use App\Repositories\PracticeSessionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PracticeHistoryService
{
    public function __construct(
        private readonly PracticeSessionRepository $sessions,
    ) {}

    /**
     * The patient's most recent verified practice sessions.
     *
     * @return Collection<int, PracticeSession>
     */
    public function recent(User $patient): Collection
    {
        return $this->sessions->recentVerified($patient, (int) config('practice.history_limit'));
    }

    /**
     * Summary statistics for the history page.
     */
    public function stats(User $patient): HistoryStats
    {
        $sessions = $this->sessions->verifiedFor($patient);

        $dates = $sessions
            ->map(fn ($session) => $session->practiced_on->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        return new HistoryStats(
            total: $sessions->count(),
            thisWeek: $sessions->filter(
                fn ($session) => $session->practiced_on->greaterThanOrEqualTo(Carbon::now()->startOfWeek())
            )->count(),
            streak: $this->currentStreak($dates),
            lastPracticeDate: $dates->isNotEmpty() ? Carbon::parse($dates->first()) : null,
        );
    }

    /**
     * Consecutive practice days ending today or (if today is not yet done)
     * yesterday. Returns 0 if the most recent practice is older than that.
     *
     * @param  Collection<int, string>  $dates  Distinct practice dates, newest first.
     */
    private function currentStreak(Collection $dates): int
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $set = $dates->flip();
        $today = Carbon::today();

        if ($set->has($today->toDateString())) {
            $cursor = $today->copy();
        } elseif ($set->has($today->copy()->subDay()->toDateString())) {
            $cursor = $today->copy()->subDay();
        } else {
            return 0;
        }

        $streak = 0;
        while ($set->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }
}
