<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PatientRegistered;
use Illuminate\Support\Facades\Log;

class LogPatientRegistered
{
    public function handle(PatientRegistered $event): void
    {
        Log::channel('business')->info('Patient registered', [
            'patient_id' => $event->patient->id,
            'doctor_id' => $event->patient->patientProfile?->doctor_id,
        ]);
    }
}
