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

class PracticeSessionStartTest extends TestCase
{
    use RefreshDatabase;

    private function patient(): User
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        return $patient;
    }

    public function test_starting_creates_an_in_progress_session_for_own_prescription(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($patient)
            ->postJson(route('patient.practice.start', $prescription));

        $response->assertOk()
            ->assertJson(['verified' => false])
            ->assertJsonStructure(['session_id', 'verified']);

        $this->assertDatabaseHas('practice_sessions', [
            'prescription_id' => $prescription->id,
            'patient_id' => $patient->id,
            'status' => PracticeStatus::InProgress->value,
        ]);
    }

    public function test_starting_is_idempotent_and_resumes_an_in_progress_session(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        $first = $this->actingAs($patient)->postJson(route('patient.practice.start', $prescription))->json('session_id');
        $second = $this->actingAs($patient)->postJson(route('patient.practice.start', $prescription))->json('session_id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('practice_sessions', 1);
    }

    public function test_starting_returns_verified_when_already_done_today(): void
    {
        $patient = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);
        $verified = PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'practiced_on' => now()->toDateString(),
        ]);

        $response = $this->actingAs($patient)->postJson(route('patient.practice.start', $prescription));

        $response->assertOk()->assertjson(['session_id' => $verified->id, 'verified' => true]);
        $this->assertDatabaseCount('practice_sessions', 1);
    }

    public function test_cannot_start_practice_for_another_patients_prescription(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $prescription = Prescription::factory()->create(['patient_id' => $other->id]);

        $this->actingAs($patient)
            ->postJson(route('patient.practice.start', $prescription))
            ->assertForbidden();
    }
}
