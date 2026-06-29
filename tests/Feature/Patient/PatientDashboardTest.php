<?php

declare(strict_types=1);

namespace Tests\Feature\Patient;

use App\Enums\PrescriptionStatus;
use App\Models\PatientProfile;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): User
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        return $patient;
    }

    public function test_dashboard_shows_only_prescriptions_active_today(): void
    {
        $patient = $this->patient();

        $active = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'status' => PrescriptionStatus::Active,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => null,
        ]);
        $future = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->addWeek()->toDateString(),
        ]);
        $expired = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $cancelled = Prescription::factory()->cancelled()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($patient)->get(route('patient.dashboard'));

        $response->assertOk()
            ->assertSee($active->mudra->name)
            ->assertDontSee($future->mudra->name)
            ->assertDontSee($expired->mudra->name)
            ->assertDontSee($cancelled->mudra->name);
    }

    public function test_end_date_today_is_still_active(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->actingAs($patient)->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee($prescription->mudra->name);
    }

    public function test_completed_today_is_reflected_in_summary(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);
        PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'practiced_on' => now()->toDateString(),
        ]);

        $this->actingAs($patient)->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Done');
    }

    public function test_dashboard_does_not_show_other_patients_prescriptions(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $otherPrescription = Prescription::factory()->create([
            'patient_id' => $other->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($patient)->get(route('patient.dashboard'))
            ->assertOk()
            ->assertDontSee($otherPrescription->mudra->name);
    }

    public function test_doctor_cannot_access_patient_dashboard(): void
    {
        $doctor = User::factory()->doctor()->create();

        $this->actingAs($doctor)->get(route('patient.dashboard'))->assertForbidden();
    }
}
