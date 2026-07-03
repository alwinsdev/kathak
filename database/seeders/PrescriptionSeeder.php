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

        // The self-hosted YOLO classifier is trained on these six mudras.
        $plan = [
            ['mudra' => 'shikhara', 'time' => '08:00', 'duration' => 10, 'notes' => 'Make a closed fist facing the camera and hold it steady.'],
            ['mudra' => 'pataka', 'time' => '09:00', 'duration' => 10, 'notes' => 'Open flat palm, fingers together, held upright to the camera.'],
            ['mudra' => 'soochi', 'time' => '10:00', 'duration' => 10, 'notes' => 'Point the index finger straight up, fold the other fingers.'],
            ['mudra' => 'trishool', 'time' => '11:00', 'duration' => 10, 'notes' => 'Raise index, middle and ring fingers like a trident.'],
            ['mudra' => 'mayura', 'time' => '12:00', 'duration' => 10, 'notes' => 'Touch the ring-finger tip to the thumb tip, other fingers extended.'],
            ['mudra' => 'shukatunda', 'time' => '13:00', 'duration' => 10, 'notes' => 'Open your hand and spread all fingers to the camera.'],
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
