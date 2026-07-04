<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PrescriptionStatus;
use App\Models\Mudra;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    /**
     * Give the demo patient a couple of active prescriptions from their doctor.
     */
    public function run(): void
    {
        $patient = User::where('email', 'patient@kathak.test')->first();

        if ($patient === null || $patient->patientProfile?->doctor_id === null) {
            return;
        }

        $doctorId = $patient->patientProfile->doctor_id;

        // The POC recognizes a single Siddha mudra.
        $plan = [
            ['mudra' => 'aakash-mudra', 'time' => '08:00', 'duration' => 10, 'notes' => 'Touch the middle fingertip to the thumb tip; keep the other fingers straight. Hold steady facing the camera.'],
        ];

        foreach ($plan as $item) {
            $mudra = Mudra::where('slug', $item['mudra'])->first();

            if ($mudra === null) {
                continue;
            }

            Prescription::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'mudra_id' => $mudra->id,
                    'status' => PrescriptionStatus::Active,
                ],
                [
                    'doctor_id' => $doctorId,
                    'scheduled_time' => $item['time'],
                    'duration_min' => $item['duration'],
                    'start_date' => now()->toDateString(),
                    'notes' => $item['notes'],
                ],
            );
        }
    }
}
