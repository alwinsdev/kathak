<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\PracticeStatus;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PracticeSessionRepository
{
    /**
     * The patient's in-progress session for a prescription today, if any.
     */
    public function currentInProgressToday(Prescription $prescription): ?PracticeSession
    {
        return PracticeSession::query()
            ->where('prescription_id', $prescription->id)
            ->where('status', PracticeStatus::InProgress)
            ->whereDate('practiced_on', Carbon::today())
            ->latest('id')
            ->first();
    }

    /**
     * A verified session for a prescription today, if one already exists.
     */
    public function verifiedToday(Prescription $prescription): ?PracticeSession
    {
        return PracticeSession::query()
            ->where('prescription_id', $prescription->id)
            ->where('status', PracticeStatus::Verified)
            ->whereDate('practiced_on', Carbon::today())
            ->first();
    }

    /**
     * Prescription ids the patient has a verified session for on the given date.
     *
     * @return Collection<int, int>
     */
    public function verifiedPrescriptionIdsOn(User $patient, Carbon $date): Collection
    {
        return PracticeSession::query()
            ->where('patient_id', $patient->id)
            ->verified()
            ->whereDate('practiced_on', $date)
            ->pluck('prescription_id')
            ->unique()
            ->values();
    }

    /**
     * All of the patient's verified sessions (used for history statistics).
     *
     * @return Collection<int, PracticeSession>
     */
    public function verifiedFor(User $patient): Collection
    {
        return PracticeSession::query()
            ->where('patient_id', $patient->id)
            ->verified()
            ->get();
    }

    /**
     * Distinct dates (Y-m-d) the patient practised on within the last $days
     * days (including today). Used for adherence displays.
     *
     * @return Collection<int, string>
     */
    public function verifiedDatesInLastDays(User $patient, int $days): Collection
    {
        return PracticeSession::query()
            ->where('patient_id', $patient->id)
            ->verified()
            ->whereDate('practiced_on', '>=', Carbon::today()->subDays($days - 1))
            ->pluck('practiced_on')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();
    }

    /**
     * The most recent date the patient had a verified session, if any.
     */
    public function lastVerifiedDate(User $patient): ?Carbon
    {
        $date = PracticeSession::query()
            ->where('patient_id', $patient->id)
            ->verified()
            ->max('practiced_on');

        return $date ? Carbon::parse($date) : null;
    }

    /**
     * The patient's most recent verified sessions, newest first.
     *
     * @return Collection<int, PracticeSession>
     */
    public function recentVerified(User $patient, int $limit): Collection
    {
        return PracticeSession::query()
            ->where('patient_id', $patient->id)
            ->verified()
            ->with('prescription.mudra')
            ->orderByDesc('practiced_on')
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get();
    }
}
