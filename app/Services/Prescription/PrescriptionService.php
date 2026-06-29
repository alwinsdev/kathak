<?php

declare(strict_types=1);

namespace App\Services\Prescription;

use App\Enums\PrescriptionStatus;
use App\Events\PrescriptionCreated;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    /**
     * Create a new active prescription for a patient.
     *
     * @param  array{mudra_id: int|string, scheduled_time: string, duration_min: int|string, start_date: string, notes?: string|null}  $data
     */
    public function create(User $doctor, User $patient, array $data): Prescription
    {
        $prescription = Prescription::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'mudra_id' => $data['mudra_id'],
            'scheduled_time' => $data['scheduled_time'],
            'duration_min' => $data['duration_min'],
            'start_date' => $data['start_date'],
            'notes' => $data['notes'] ?? null,
            'status' => PrescriptionStatus::Active,
        ]);

        PrescriptionCreated::dispatch($prescription);

        return $prescription;
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

        Log::channel('business')->info('Prescription updated', [
            'prescription_id' => $prescription->id,
            'doctor_id' => $prescription->doctor_id,
            'patient_id' => $prescription->patient_id,
        ]);

        return $prescription;
    }

    /**
     * Cancel a prescription (soft lifecycle change; the record is retained).
     */
    public function cancel(Prescription $prescription): void
    {
        $prescription->update(['status' => PrescriptionStatus::Cancelled]);

        Log::channel('business')->info('Prescription cancelled', [
            'prescription_id' => $prescription->id,
            'doctor_id' => $prescription->doctor_id,
            'patient_id' => $prescription->patient_id,
        ]);
    }
}
