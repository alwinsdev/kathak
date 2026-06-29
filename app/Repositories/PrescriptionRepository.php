<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PrescriptionRepository
{
    /**
     * The patient's prescriptions in effect on the given date, with their
     * mudra, ordered by scheduled time.
     *
     * @return Collection<int, Prescription>
     */
    public function dueOn(User $patient, Carbon $date): Collection
    {
        return Prescription::query()
            ->where('patient_id', $patient->id)
            ->activeOn($date)
            ->with('mudra')
            ->orderBy('scheduled_time')
            ->get();
    }
}
