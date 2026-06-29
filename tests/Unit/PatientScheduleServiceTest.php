<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Patient\PatientScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PatientScheduleService::class);
    }

    public function test_today_respects_status_and_date_window(): void
    {
        $patient = User::factory()->create();

        $active = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
        Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->addWeek()->toDateString(),
        ]);
        Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        Prescription::factory()->cancelled()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);

        $today = $this->service->today($patient);

        $this->assertCount(1, $today->mudras);
        $this->assertSame($active->id, $today->mudras->first()->prescription->id);
        $this->assertFalse($today->mudras->first()->completedToday);
    }

    public function test_summary_counts_completed_and_pending(): void
    {
        $patient = User::factory()->create();
        $done = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);
        Prescription::factory()->create([
            'patient_id' => $patient->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);
        PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $done->id,
            'practiced_on' => now()->toDateString(),
        ]);

        $summary = $this->service->today($patient)->summary;

        $this->assertSame(2, $summary->total);
        $this->assertSame(1, $summary->completed);
        $this->assertSame(1, $summary->pending);
    }
}
