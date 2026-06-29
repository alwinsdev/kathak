<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PracticeStatus;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PracticeSessionSeeder extends Seeder
{
    /**
     * Seed a few verified practice sessions for the demo patient so the history
     * page and streak have data. (In the running app these are produced by AI
     * verification in L4; here they are demo data only.)
     */
    public function run(): void
    {
        $patient = User::where('email', 'patient@kathak.test')->first();

        if ($patient === null) {
            return;
        }

        $prescriptions = $patient->prescriptions()->active()->with('mudra')->get()->keyBy(fn ($p) => $p->mudra->slug);

        // Shikhara: verified on the previous 5 days (NOT today) so the patient
        // can verify it live today, while history/streak still have data.
        $this->seedDays($patient, $prescriptions->get('shikhara'), range(1, 5));

        // Mushti: verified on a few earlier days, but not today.
        $this->seedDays($patient, $prescriptions->get('mushti'), [1, 2, 4]);
    }

    /**
     * @param  array<int, int>  $daysAgo
     */
    private function seedDays(User $patient, ?Prescription $prescription, array $daysAgo): void
    {
        if ($prescription === null) {
            return;
        }

        foreach ($daysAgo as $offset) {
            $date = Carbon::today()->subDays($offset);

            $prescription->practiceSessions()->firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'practiced_on' => $date->toDateString(),
                ],
                [
                    'started_at' => $date->copy()->setTime(8, 0),
                    'completed_at' => $date->copy()->setTime(8, 5),
                    'status' => PracticeStatus::Verified,
                    'best_confidence' => 0.92,
                    'detected_class' => $prescription->mudra->ai_class_label,
                ],
            );
        }
    }
}
