<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Services\PracticeHoldTracker;
use App\Models\PracticeSession;
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
            'practice.hold_seconds' => 3,
            'practice.detection_interval_ms' => 1000,
            'practice.hold_grace_factor' => 2.5, // maxGap = 2500ms
            'practice.hold_cache_ttl' => 300,
        ]);
        $this->tracker = new PracticeHoldTracker;
        $this->session = PracticeSession::factory()->create();
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

        Carbon::setTestNow(now()->addSecond());
        $progress = $this->tracker->record($this->session, true, 0.92);
        $this->assertSame(3.0, $progress->heldSeconds);
        $this->assertTrue($progress->ready);
        $this->assertSame(0.95, $progress->bestConfidence);
    }

    public function test_hold_resets_when_a_frame_does_not_match(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->tracker->record($this->session, true, 0.9);
        Carbon::setTestNow(now()->addSecond());
        $this->tracker->record($this->session, true, 0.9); // held 1.0

        $reset = $this->tracker->record($this->session, false, 0.0);
        $this->assertSame(0.0, $reset->heldSeconds);

        // Next match restarts from zero.
        Carbon::setTestNow(now()->addSecond());
        $this->assertSame(0.0, $this->tracker->record($this->session, true, 0.9)->heldSeconds);
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
