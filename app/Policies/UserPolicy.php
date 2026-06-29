<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * A doctor may manage a patient only if that patient is in their panel.
     * Used to authorise viewing a patient and prescribing for them.
     */
    public function manage(User $doctor, User $patient): bool
    {
        return $doctor->isDoctor()
            && $patient->isPatient()
            && $patient->patientProfile?->doctor_id === $doctor->id;
    }
}
