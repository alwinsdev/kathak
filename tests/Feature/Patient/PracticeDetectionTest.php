<?php

declare(strict_types=1);

namespace Tests\Feature\Patient;

use App\Domain\AI\Clients\FakeInferenceClient;
use App\Domain\AI\Contracts\InferenceClient;
use App\Domain\AI\DTOs\InferenceResult;
use App\Domain\AI\Exceptions\InferenceException;
use App\Enums\PracticeStatus;
use App\Models\Mudra;
use App\Models\PatientProfile;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PracticeDetectionTest extends TestCase
{
    use RefreshDatabase;

    private function sessionFor(User $patient, string $aiClassLabel = 'Pataka'): PracticeSession
    {
        $mudra = Mudra::factory()->create(['ai_class_label' => $aiClassLabel]);
        $prescription = Prescription::factory()->create([
            'patient_id' => $patient->id,
            'mudra_id' => $mudra->id,
        ]);

        return PracticeSession::factory()->create([
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'status' => PracticeStatus::InProgress,
        ]);
    }

    private function patient(): User
    {
        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        return $patient;
    }

    private function frame(): UploadedFile
    {
        return UploadedFile::fake()->create('frame.jpg', 50, 'image/jpeg');
    }

    public function test_detect_returns_matched_when_fake_inference_detects_target(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $patient = $this->patient();
        $session = $this->sessionFor($patient);
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        $response = $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()]);

        $response->assertOk()->assertJson([
            'matched' => true,
            'detected_class' => 'Pataka',
        ]);
    }

    public function test_detect_returns_not_matched_for_wrong_mudra(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $patient = $this->patient();
        $session = $this->sessionFor($patient);
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Mushti', 0.95));

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()])
            ->assertOk()
            ->assertJson(['matched' => false]);
    }

    public function test_detect_requires_a_valid_image(): void
    {
        $patient = $this->patient();
        $session = $this->sessionFor($patient);

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), [])
            ->assertStatus(422);

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), [
                'image' => UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
            ])
            ->assertStatus(422);
    }

    public function test_detect_is_forbidden_for_another_patients_session(): void
    {
        $patient = $this->patient();
        $other = $this->patient();
        $session = $this->sessionFor($other);
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()])
            ->assertForbidden();
    }

    public function test_detect_returns_502_when_inference_unavailable(): void
    {
        // No fake bound + no Roboflow key configured → RoboflowInferenceClient throws.
        config(['services.roboflow.key' => null]);
        $patient = $this->patient();
        $session = $this->sessionFor($patient);

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()])
            ->assertStatus(502)
            ->assertJson(['error' => true]);
    }

    public function test_inference_failure_records_metric_and_structured_log(): void
    {
        $patient = $this->patient();
        $session = $this->sessionFor($patient);

        // An inference client that always fails.
        $this->app->instance(InferenceClient::class, new class implements InferenceClient
        {
            public function detect(string $imageBinary): InferenceResult
            {
                throw new InferenceException('upstream down');
            }
        });

        // Capture business-channel warnings to assert the structured failure log.
        $warnings = [];
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info');
        Log::shouldReceive('warning')->andReturnUsing(function (string $message, array $context = []) use (&$warnings) {
            $warnings[] = ['message' => $message, 'context' => $context];
        });

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()])
            ->assertStatus(502)
            ->assertJson(['error' => true]);

        $this->assertSame(1, Cache::get('metrics:ai:inference_failures'));

        $failureLog = collect($warnings)->firstWhere('message', 'inference_failed');
        $this->assertNotNull($failureLog, 'Expected an inference_failed structured log.');
        $this->assertArrayHasKey('correlation_id', $failureLog['context']);
        $this->assertArrayHasKey('processing_time_ms', $failureLog['context']);
    }

    public function test_a_single_frame_does_not_complete_the_session(): void
    {
        config(['practice.confidence_threshold' => 0.75]);
        $patient = $this->patient();
        $session = $this->sessionFor($patient);
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.99));

        $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()])
            ->assertOk();

        // A single matched frame accrues zero hold time, so it never verifies.
        $this->assertSame('in_progress', $session->fresh()->status->value);
        $this->assertDatabaseMissing('practice_sessions', ['id' => $session->id, 'status' => 'verified']);
    }
}
