<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * A doctor may update a prescription only if they own it and it is active.
     */
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->isDoctor()
            && $prescription->doctor_id === $user->id
            && $prescription->isActive();
    }

    /**
     * Cancelling reuses the same ownership + active rule as updating.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }
}
