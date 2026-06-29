<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PrescriptionStatus;
use App\Models\Mudra;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Prescription\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrescriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PrescriptionService;
    }

    public function test_create_persists_an_active_prescription(): void
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        $mudra = Mudra::factory()->create();

        $prescription = $this->service->create($doctor, $patient, [
            'mudra_id' => $mudra->id,
            'scheduled_time' => '07:15',
            'duration_min' => 12,
            'start_date' => now()->toDateString(),
            'notes' => 'Note.',
        ]);

        $this->assertSame(PrescriptionStatus::Active, $prescription->status);
        $this->assertSame($doctor->id, $prescription->doctor_id);
        $this->assertSame($patient->id, $prescription->patient_id);
        $this->assertSame(12, $prescription->duration_min);
    }

    public function test_update_changes_only_editable_fields(): void
    {
        $prescription = Prescription::factory()->create([
            'scheduled_time' => '08:00',
            'duration_min' => 10,
            'notes' => 'Old.',
        ]);
        $originalMudra = $prescription->mudra_id;

        $this->service->update($prescription, [
            'scheduled_time' => '10:45',
            'duration_min' => 25,
            'notes' => 'New.',
        ]);

        $prescription->refresh();
        $this->assertSame(25, $prescription->duration_min);
        $this->assertSame('New.', $prescription->notes);
        $this->assertSame($originalMudra, $prescription->mudra_id);
    }

    public function test_cancel_sets_status_to_cancelled(): void
    {
        $prescription = Prescription::factory()->create();

        $this->service->cancel($prescription);

        $this->assertSame(PrescriptionStatus::Cancelled, $prescription->fresh()->status);
    }
}
