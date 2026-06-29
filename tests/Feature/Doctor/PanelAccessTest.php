<?php

declare(strict_types=1);

namespace Tests\Feature\Doctor;

use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function patientFor(User $doctor): User
    {
        $patient = User::factory()->create();
        PatientProfile::factory()->create([
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        return $patient;
    }

    public function test_dashboard_lists_only_the_doctors_own_patients(): void
    {
        $doctorA = User::factory()->doctor()->create();
        $doctorB = User::factory()->doctor()->create();
        $mine = $this->patientFor($doctorA);
        $theirs = $this->patientFor($doctorB);

        $this->actingAs($doctorA)->get(route('doctor.dashboard'))
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);
    }

    public function test_doctor_cannot_view_another_doctors_patient(): void
    {
        $doctorA = User::factory()->doctor()->create();
        $doctorB = User::factory()->doctor()->create();
        $patientOfB = $this->patientFor($doctorB);

        $this->actingAs($doctorA)->get(route('doctor.patients.show', $patientOfB))
            ->assertForbidden();
    }

    public function test_doctor_cannot_prescribe_for_another_doctors_patient(): void
    {
        $doctorA = User::factory()->doctor()->create();
        $doctorB = User::factory()->doctor()->create();
        $patientOfB = $this->patientFor($doctorB);

        $this->actingAs($doctorA)->post(route('doctor.prescriptions.store', $patientOfB), [])
            ->assertForbidden();
    }

    public function test_doctor_cannot_modify_another_doctors_prescription(): void
    {
        $doctorA = User::factory()->doctor()->create();
        $doctorB = User::factory()->doctor()->create();
        $patientOfB = $this->patientFor($doctorB);
        $prescription = Prescription::factory()->create([
            'patient_id' => $patientOfB->id,
            'doctor_id' => $doctorB->id,
        ]);

        $this->actingAs($doctorA)->put(route('doctor.prescriptions.update', $prescription), [
            'scheduled_time' => '09:30',
            'duration_min' => 20,
        ])->assertForbidden();

        $this->actingAs($doctorA)->delete(route('doctor.prescriptions.destroy', $prescription))
            ->assertForbidden();
    }

    public function test_patient_role_cannot_access_doctor_panel_routes(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient)->get(route('doctor.dashboard'))->assertForbidden();
        $this->actingAs($patient)->get(route('doctor.patients.show', $patient))->assertForbidden();
    }
}
