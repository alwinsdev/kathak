<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Patient\PracticeHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PracticeHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private PracticeHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PracticeHistoryService::class);
    }

    public function test_stats_compute_totals_streak_and_last_practice_date(): void
    {
        $patient = User::factory()->create();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        // Verified on today, yesterday, and the day before → streak of 3.
        foreach ([0, 1, 2] as $offset) {
            PracticeSession::factory()->verified()->create([
                'patient_id' => $patient->id,
                'prescription_id' => $prescription->id,
                'practiced_on' => Carbon::today()->subDays($offset)->toDateString(),
            ]);
        }

        $stats = $this->service->stats($patient);

        $this->assertSame(3, $stats->total);
        $this->assertSame(3, $stats->streak);
        $this->assertSame(Carbon::today()->toDateString(), $stats->lastPracticeDate->toDateString());
    }

    public function test_streak_breaks_when_a_day_is_missed(): void
    {
        $patient = User::factory()->create();
        $prescription = Prescription::factory()->create(['patient_id' => $patient->id]);

        // Today and 3 days ago only (gap) → streak counts just today.
        PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'practiced_on' => Carbon::today()->toDateString(),
        ]);
        PracticeSession::factory()->verified()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'practiced_on' => Carbon::today()->subDays(3)->toDateString(),
        ]);

        $this->assertSame(1, $this->service->stats($patient)->streak);
    }

    public function test_no_sessions_returns_zeroed_stats(): void
    {
        $patient = User::factory()->create();

        $stats = $this->service->stats($patient);

        $this->assertSame(0, $stats->total);
        $this->assertSame(0, $stats->streak);
        $this->assertNull($stats->lastPracticeDate);
    }
}
