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
     * Give the demo patient active prescriptions from their doctor.
     *
     * The POC's self-hosted MediaPipe classifier verifies two distinct hand
     * shapes — an open spread hand (Shukatunda) and a closed fist (Shikhara) —
     * so the demo prescribes exactly those, keeping every card completable.
     */
    public function run(): void
    {
        $patient = User::where('email', 'patient@kathak.test')->first();

        if ($patient === null || $patient->patientProfile?->doctor_id === null) {
            return;
        }

        $doctorId = $patient->patientProfile->doctor_id;

        $plan = [
            ['mudra' => 'shukatunda', 'time' => '08:00', 'duration' => 10, 'notes' => 'Open your hand and spread all fingers, palm facing the camera.'],
            ['mudra' => 'shikhara', 'time' => '12:00', 'duration' => 10, 'notes' => 'Make a closed fist facing the camera and hold it steady.'],
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
