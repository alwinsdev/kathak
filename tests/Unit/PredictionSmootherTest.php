<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\DTOs\DetectionResult;
use App\Domain\AI\DTOs\MudraPrediction;
use App\Domain\AI\Services\PredictionSmoother;
use App\Models\Mudra;
use App\Models\PracticeSession;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionSmootherTest extends TestCase
{
    use RefreshDatabase;

    private PredictionSmoother $smoother;

    private PracticeSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'practice.confidence_threshold' => 0.75,
            'practice.smoothing_window' => 5,
            'practice.smoothing_min_agreement' => 0.6,
            'practice.hold_cache_ttl' => 300,
        ]);
        $this->smoother = new PredictionSmoother;

        $mudra = Mudra::factory()->create(['ai_class_label' => 'aakash']);
        $prescription = Prescription::factory()->create(['mudra_id' => $mudra->id]);
        $this->session = PracticeSession::factory()->create([
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
        ]);
    }

    private function frame(bool $matched, float $confidence, ?string $detected): DetectionResult
    {
        return new DetectionResult(
            matched: $matched,
            confidence: $confidence,
            detectedClass: $detected,
            topConfidence: $confidence,
            predictions: $detected === null ? [] : [new MudraPrediction($detected, $confidence, 100, 100, 80, 80)],
            processingMs: 10,
        );
    }

    public function test_a_matching_frame_passes_through_untouched(): void
    {
        $frame = $this->frame(true, 0.9, 'aakash');

        $this->assertSame($frame, $this->smoother->smooth($this->session, $frame));
    }

    public function test_an_isolated_flicker_is_rescued_by_recent_evidence(): void
    {
        $this->smoother->smooth($this->session, $this->frame(true, 0.9, 'aakash'));
        $this->smoother->smooth($this->session, $this->frame(true, 0.8, 'aakash'));

        // One shaky frame misclassifies with high confidence.
        $smoothed = $this->smoother->smooth($this->session, $this->frame(false, 0.0, 'other'));

        $this->assertTrue($smoothed->matched);
        $this->assertSame('aakash', $smoothed->detectedClass);
        $this->assertEqualsWithDelta(0.85, $smoothed->confidence, 0.001);
    }

    public function test_a_persistently_wrong_pose_is_never_rescued(): void
    {
        foreach (range(1, 5) as $i) {
            $smoothed = $this->smoother->smooth($this->session, $this->frame(false, 0.0, 'other'));
            $this->assertFalse($smoothed->matched, "frame {$i} must not match");
        }
    }

    public function test_a_low_confidence_hold_is_not_upgraded(): void
    {
        // Right mudra, but consistently below the 75% bar: the average cannot
        // clear the threshold, so smoothing must not fake a match.
        $this->smoother->smooth($this->session, $this->frame(false, 0.6, 'aakash'));
        $this->smoother->smooth($this->session, $this->frame(false, 0.62, 'aakash'));
        $smoothed = $this->smoother->smooth($this->session, $this->frame(false, 0.61, 'aakash'));

        $this->assertFalse($smoothed->matched);
    }

    public function test_the_vote_decays_after_the_pose_is_dropped(): void
    {
        $this->smoother->smooth($this->session, $this->frame(true, 0.9, 'aakash'));
        $this->smoother->smooth($this->session, $this->frame(true, 0.9, 'aakash'));

        // First wrong frame: rescued (2 of 3 recent frames show the target).
        $this->assertTrue($this->smoother->smooth($this->session, $this->frame(false, 0.0, 'other'))->matched);

        // Second consecutive wrong frame: agreement falls to 2/4 < 0.6 — no rescue.
        $this->assertFalse($this->smoother->smooth($this->session, $this->frame(false, 0.0, 'other'))->matched);
    }
}
