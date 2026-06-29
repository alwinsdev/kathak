<?php

declare(strict_types=1);

namespace Tests\Feature\Doctor;

use App\Enums\PrescriptionStatus;
use App\Models\Mudra;
use App\Models\PatientProfile;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function doctorWithPatient(): array
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create([
            'user_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        return [$doctor, $patient];
    }

    public function test_doctor_can_create_a_prescription_for_own_patient(): void
    {
        [$doctor, $patient] = $this->doctorWithPatient();
        $mudra = Mudra::factory()->create(['is_active' => true]);

        $response = $this->actingAs($doctor)->post(route('doctor.prescriptions.store', $patient), [
            'mudra_id' => $mudra->id,
            'scheduled_time' => '08:00',
            'duration_min' => 10,
            'start_date' => now()->toDateString(),
            'notes' => 'Morning practice.',
        ]);

        $response->assertRedirect(route('doctor.patients.show', $patient));
        $this->assertDatabaseHas('prescriptions', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'mudra_id' => $mudra->id,
            'duration_min' => 10,
            'status' => PrescriptionStatus::Active->value,
        ]);
    }

    public function test_prescription_validation_rejects_bad_input(): void
    {
        [$doctor, $patient] = $this->doctorWithPatient();
        $inactiveMudra = Mudra::factory()->create(['is_active' => false]);

        $response = $this->actingAs($doctor)->from(route('doctor.patients.show', $patient))
            ->post(route('doctor.prescriptions.store', $patient), [
                'mudra_id' => $inactiveMudra->id,
                'scheduled_time' => 'not-a-time',
                'duration_min' => 0,
                'start_date' => now()->subDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['mudra_id', 'scheduled_time', 'duration_min', 'start_date']);
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_doctor_can_update_editable_fields_only(): void
    {
        [$doctor, $patient] = $this->doctorWithPatient();
        $mudra = Mudra::factory()->create();
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'mudra_id' => $mudra->id,
            'scheduled_time' => '08:00',
            'duration_min' => 10,
        ]);

        $response = $this->actingAs($doctor)->put(route('doctor.prescriptions.update', $prescription), [
            'scheduled_time' => '09:30',
            'duration_min' => 20,
            'notes' => 'Updated note.',
            'mudra_id' => 999, // must be ignored
        ]);

        $response->assertRedirect(route('doctor.patients.show', $patient->id));
        $prescription->refresh();
        $this->assertSame('09:30', Str::substr($prescription->scheduled_time, 0, 5));
        $this->assertSame(20, $prescription->duration_min);
        $this->assertSame('Updated note.', $prescription->notes);
        $this->assertSame($mudra->id, $prescription->mudra_id); // unchanged
    }

    public function test_doctor_can_cancel_a_prescription(): void
    {
        [$doctor, $patient] = $this->doctorWithPatient();
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->actingAs($doctor)->delete(route('doctor.prescriptions.destroy', $prescription));

        $response->assertRedirect(route('doctor.patients.show', $patient->id));
        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescription->id,
            'status' => PrescriptionStatus::Cancelled->value,
        ]);
    }

    public function test_cancelled_prescription_cannot_be_edited(): void
    {
        [$doctor, $patient] = $this->doctorWithPatient();
        $prescription = Prescription::factory()->cancelled()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        $this->actingAs($doctor)->put(route('doctor.prescriptions.update', $prescription), [
            'scheduled_time' => '09:30',
            'duration_min' => 20,
        ])->assertForbidden();
    }
}
