<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PrescriptionCreated;
use Illuminate\Support\Facades\Log;

class LogPrescriptionCreated
{
    public function handle(PrescriptionCreated $event): void
    {
        $prescription = $event->prescription;

        Log::channel('business')->info('Prescription created', [
            'prescription_id' => $prescription->id,
            'doctor_id' => $prescription->doctor_id,
            'patient_id' => $prescription->patient_id,
            'mudra_id' => $prescription->mudra_id,
        ]);
    }
}
