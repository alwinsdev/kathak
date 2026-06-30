<?php

declare(strict_types=1);

namespace Tests\Feature\Patient;

use App\Enums\PracticeStatus;
use App\Models\Mudra;
use App\Models\PatientProfile;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end practice flow driven by the MediaPipe provider. Only the AI
 * service's HTTP boundary is faked; the real MediapipeInferenceClient, label
 * mapping, VerifyPracticeAction, hold tracking, and completion all run.
 */
class MediapipePracticeFlowTest extends TestCase
{
    use RefreshDatabase;

    private function setup_scenario(string $aiClassLabel = 'shuktund'): array
    {
        config([
            'practice.confidence_threshold' => 0.75,
            'practice.hold_seconds' => 2,
            'practice.detection_interval_ms' => 1000,
            'practice.hold_grace_factor' => 2.5,
            // Use the self-hosted MediaPipe driver end-to-end.
            'services.inference.driver' => 'mediapipe',
            'services.mediapipe.url' => 'http://ai.test',
            'services.mediapipe.key' => 'test-key',
            'services.mediapipe.label_map' => [
                'open_palm' => 'shuktund',
                'closed_fist' => 'shikhar',
            ],
        ]);

        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        $mudra = Mudra::factory()->create(['ai_class_label' => $aiClassLabel]);
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'mudra_id' => $mudra->id,
            'start_date' => now()->subDay()->toDateString(),
        ]);
        $session = PracticeSession::factory()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'status' => PracticeStatus::InProgress,
        ]);

        return [$patient, $session];
    }

    private function fakeAiLabel(?string $label, float $confidence = 1.0): void
    {
        Http::fake(['ai.test/classify' => Http::response([
            'success' => true,
            'prediction' => $label === null ? null : ['label' => $label, 'confidence' => $confidence],
            'hands_detected' => $label === null ? 0 : 1,
            'processing_time_ms' => 11,
        ])]);
    }

    private function detect(User $patient, PracticeSession $session)
    {
        return $this->actingAs($patient)->postJson(
            route('patient.practice.detect', $session),
            ['image' => UploadedFile::fake()->create('frame.jpg', 50, 'image/jpeg')],
        );
    }

    public function test_open_palm_holds_and_auto_completes_via_mediapipe(): void
    {
        [$patient, $session] = $this->setup_scenario('shuktund');
        $this->fakeAiLabel('open_palm'); // maps to shuktund

        Carbon::setTestNow(Carbon::create(2026, 6, 30, 8, 0, 0));
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => false]);

        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => false]);

        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => true]);

        $this->assertSame(PracticeStatus::Verified, $session->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_unknown_gesture_never_completes(): void
    {
        [$patient, $session] = $this->setup_scenario('shuktund');
        $this->fakeAiLabel('unknown', 0.0); // unmapped -> no prediction

        Carbon::setTestNow(Carbon::create(2026, 6, 30, 8, 0, 0));
        foreach (range(0, 4) as $i) {
            Carbon::setTestNow(now()->addSecond());
            $this->detect($patient, $session)->assertJson(['verified' => false]);
        }

        $this->assertSame(PracticeStatus::InProgress, $session->fresh()->status);
        Carbon::setTestNow();
    }
}
