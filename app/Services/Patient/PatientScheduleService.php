<?php

declare(strict_types=1);

namespace App\Services\Patient;

use App\DTOs\DueMudra;
use App\DTOs\TodaySummary;
use App\DTOs\TodayTherapy;
use App\Models\User;
use App\Repositories\PracticeSessionRepository;
use App\Repositories\PrescriptionRepository;
use Illuminate\Support\Carbon;

class PatientScheduleService
{
    public function __construct(
        private readonly PrescriptionRepository $prescriptions,
        private readonly PracticeSessionRepository $sessions,
    ) {}

    /**
     * The patient's therapy for today: each due mudra flagged with whether it
     * has been verified today, plus the summary counts.
     */
    public function today(User $patient): TodayTherapy
    {
        $today = Carbon::today();

        $due = $this->prescriptions->dueOn($patient, $today);
        $completedIds = $this->sessions->verifiedPrescriptionIdsOn($patient, $today);

        $mudras = $due->map(
            fn ($prescription) => new DueMudra($prescription, $completedIds->contains($prescription->id))
        );

        $completed = $mudras->filter(fn (DueMudra $m) => $m->completedToday)->count();

        return new TodayTherapy(
            mudras: $mudras,
            summary: new TodaySummary(
                total: $mudras->count(),
                completed: $completed,
                pending: $mudras->count() - $completed,
            ),
        );
    }
}
