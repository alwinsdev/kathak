<?php

declare(strict_types=1);

namespace Tests\Feature\Patient;

use App\Enums\PracticeStatus;
use App\Models\PatientProfile;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPagesTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): User
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        return $patient;
    }

    public function test_patient_can_view_own_prescription_detail(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($patient)->get(route('patient.prescriptions.show', $prescription))
            ->assertOk()
            ->assertSee($prescription->mudra->name);
    }

    public function test_patient_cannot_view_another_patients_prescription(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $other->id]);

        $this->actingAs($patient)->get(route('patient.prescriptions.show', $prescription))
            ->assertForbidden();
    }

    public function test_patient_can_open_practice_screen_for_own_prescription(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($patient)->get(route('patient.practice.show', $prescription))
            ->assertOk()
            ->assertSee($prescription->mudra->name)
            ->assertSee('Detection Status')
            ->assertSee('Start Practice');
    }

    public function test_practice_screen_shows_completed_state_when_already_verified_today(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);
        PracticeSession::factory()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'status' => PracticeStatus::Verified,
            'practiced_on' => now()->toDateString(),
            'best_confidence' => 0.95,
            'completed_at' => now(),
        ]);

        $this->actingAs($patient)->get(route('patient.practice.show', $prescription))
            ->assertOk()
            ->assertSee('Completed for today')
            ->assertDontSee('Start Practice'); // no camera/practice flow when already done
    }

    public function test_practice_entry_is_forbidden_for_another_patient(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $other->id]);

        $this->actingAs($patient)->get(route('patient.practice.show', $prescription))
            ->assertForbidden();
    }

    public function test_history_page_lists_verified_sessions_and_last_practice_date(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);
        PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'practiced_on' => now()->toDateString(),
        ]);

        $this->actingAs($patient)->get(route('patient.history'))
            ->assertOk()
            ->assertSee('Last Practice')
            ->assertSee($prescription->mudra->name);
    }

    public function test_history_shows_empty_state_when_no_sessions(): void
    {
        $patient = $this->patient();

        $this->actingAs($patient)->get(route('patient.history'))
            ->assertOk()
            ->assertSee('Practice Calendar')
            ->assertSee('No practice on this day.');
    }

    public function test_doctor_cannot_access_patient_pages(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor)->get(route('patient.history'))->assertForbidden();
    }
}
