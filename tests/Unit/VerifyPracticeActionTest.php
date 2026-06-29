<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\AI\Actions\VerifyPracticeAction;
use App\Domain\AI\Clients\FakeInferenceClient;
use App\Models\Mudra;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyPracticeActionTest extends TestCase
{
    use RefreshDatabase;

    private function sessionForMudra(string $aiClassLabel): PracticeSession
    {
        $mudra = Mudra::factory()->create(['ai_class_label' => $aiClassLabel]);
        $patient = User::factory()->create();
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'mudra_id' => $mudra->id,
        ]);

        return PracticeSession::factory()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
        ]);
    }

    public function test_matches_when_target_detected_above_threshold(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $session = $this->sessionForMudra('Pataka');
        $fake = (new FakeInferenceClient)->withDetection('Pataka', 0.91);

        $result = (new VerifyPracticeAction($fake))->handle($session, 'binary', 'corr-test');

        $this->assertTrue($result->matched);
        $this->assertSame(0.91, $result->confidence);
        $this->assertSame('Pataka', $result->detectedClass);
    }

    public function test_does_not_match_below_threshold(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $session = $this->sessionForMudra('Pataka');
        $fake = (new FakeInferenceClient)->withDetection('Pataka', 0.50);

        $result = (new VerifyPracticeAction($fake))->handle($session, 'binary', 'corr-test');

        $this->assertFalse($result->matched);
        $this->assertSame(0.50, $result->confidence);
    }

    public function test_does_not_match_a_different_mudra(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $session = $this->sessionForMudra('Pataka');
        $fake = (new FakeInferenceClient)->withDetection('Mushti', 0.95);

        $result = (new VerifyPracticeAction($fake))->handle($session, 'binary', 'corr-test');

        $this->assertFalse($result->matched);
        $this->assertSame(0.0, $result->confidence);
        $this->assertSame('Mushti', $result->detectedClass); // reported, but not the target
    }

    public function test_no_detection_is_not_a_match(): void
    {
        $session = $this->sessionForMudra('Pataka');
        $fake = (new FakeInferenceClient)->withNothing();

        $result = (new VerifyPracticeAction($fake))->handle($session, 'binary', 'corr-test');

        $this->assertFalse($result->matched);
        $this->assertNull($result->detectedClass);
        $this->assertSame([], $result->predictions);
    }
}
