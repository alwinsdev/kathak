<?php

declare(strict_types=1);

namespace App\Services\Prescription;

use App\Enums\PrescriptionStatus;
use App\Models\Prescription;
use App\Models\User;

class PrescriptionService
{
    /**
     * Create a new active prescription for a patient.
     *
     * @param  array{mudra_id: int|string, scheduled_time: string, duration_min: int|string, start_date: string, notes?: string|null}  $data
     */
    public function create(User $doctor, User $patient, array $data): Prescription
    {
        return Prescription::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'mudra_id' => $data['mudra_id'],
            'scheduled_time' => $data['scheduled_time'],
            'duration_min' => $data['duration_min'],
            'start_date' => $data['start_date'],
            'notes' => $data['notes'] ?? null,
            'status' => PrescriptionStatus::Active,
        ]);
    }

    /**
     * Update the editable fields (time, duration, notes) of an active prescription.
     *
     * @param  array{scheduled_time: string, duration_min: int|string, notes?: string|null}  $data
     */
    public function update(Prescription $prescription, array $data): Prescription
    {
        $prescription->update([
            'scheduled_time' => $data['scheduled_time'],
            'duration_min' => $data['duration_min'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $prescription;
    }

    /**
     * Cancel a prescription (soft lifecycle change; the record is retained).
     */
    public function cancel(Prescription $prescription): void
    {
        $prescription->update(['status' => PrescriptionStatus::Cancelled]);
    }
}
