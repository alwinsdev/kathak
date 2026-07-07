<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Services\PracticeHoldTracker;
use App\Models\PracticeSession;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PracticeHoldTrackerTest extends TestCase
{
    use RefreshDatabase;

    private PracticeHoldTracker $tracker;

    private PracticeSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'practice.hold_seconds' => 3, // fallback only — the prescription wins
            'practice.detection_interval_ms' => 1000,
            'practice.hold_grace_factor' => 2.5, // maxStep = 2500ms
            'practice.hold_cache_ttl' => 300,
        ]);
        $this->tracker = new PracticeHoldTracker;

        // 1 prescribed minute -> a 60s hold target.
        $prescription = Prescription::factory()->create(['duration_min' => 1]);
        $this->session = PracticeSession::factory()->create([
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
        ]);
    }

    public function test_hold_target_is_the_prescribed_duration(): void
    {
        $progress = $this->tracker->record($this->session, true, 0.9);

        $this->assertSame(60, $progress->holdSeconds);
    }

    public function test_hold_accumulates_across_consecutive_matched_frames(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->assertSame(0.0, $this->tracker->record($this->session, true, 0.9)->heldSeconds);

        Carbon::setTestNow(now()->addSecond());
        $this->assertSame(1.0, $this->tracker->record($this->session, true, 0.9)->heldSeconds);

        Carbon::setTestNow(now()->addSecond());
        $progress = $this->tracker->record($this->session, true, 0.95);
        $this->assertSame(2.0, $progress->heldSeconds);
        $this->assertFalse($progress->ready);
        $this->assertSame(0.95, $progress->bestConfidence);
    }

    public function test_a_frame_that_does_not_match_pauses_instead_of_resetting(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->tracker->record($this->session, true, 0.9);
        Carbon::setTestNow(now()->addSecond());
        $this->tracker->record($this->session, true, 0.9); // held 1.0

        // Confidence dip / wrong pose: progress is kept, nothing credited.
        Carbon::setTestNow(now()->addSecond());
        $paused = $this->tracker->record($this->session, false, 0.4);
        $this->assertSame(1.0, $paused->heldSeconds);
        $this->assertFalse($paused->ready);

        // The mismatch period itself is never credited: the next match resumes
        // from the pause, crediting only the time since the paused frame.
        Carbon::setTestNow(now()->addSecond());
        $this->assertSame(2.0, $this->tracker->record($this->session, true, 0.9)->heldSeconds);
    }

    public function test_a_mismatch_before_any_progress_reports_zero(): void
    {
        $progress = $this->tracker->record($this->session, false, 0.2);

        $this->assertSame(0.0, $progress->heldSeconds);
        $this->assertFalse($progress->ready);
    }

    public function test_a_long_gap_credits_a_capped_step_rather_than_resetting(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->tracker->record($this->session, true, 0.9);
        Carbon::setTestNow(now()->addSecond());
        $this->assertSame(1.0, $this->tracker->record($this->session, true, 0.9)->heldSeconds);

        // A long gap (slow inference / dropped frame) credits at most the grace
        // step (interval 1000ms × 2.5 = 2500ms), and never resets the hold.
        Carbon::setTestNow(now()->addSeconds(10));
        $this->assertSame(3.5, $this->tracker->record($this->session, true, 0.9)->heldSeconds);
    }

    public function test_hold_becomes_ready_at_the_prescribed_duration(): void
    {
        // Wider sampling so each frame credits a full 30s step (cap = 75s).
        config(['practice.detection_interval_ms' => 30000]);

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->assertFalse($this->tracker->record($this->session, true, 0.9)->ready);

        Carbon::setTestNow(now()->addSeconds(30));
        $progress = $this->tracker->record($this->session, true, 0.9);
        $this->assertSame(30.0, $progress->heldSeconds);
        $this->assertFalse($progress->ready);

        Carbon::setTestNow(now()->addSeconds(30));
        $progress = $this->tracker->record($this->session, true, 0.9);
        $this->assertSame(60.0, $progress->heldSeconds);
        $this->assertTrue($progress->ready);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
