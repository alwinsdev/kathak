<?php

declare(strict_types=1);

namespace Tests\Feature\Patient;

use App\Domain\AI\Clients\FakeInferenceClient;
use App\Domain\AI\Contracts\InferenceClient;
use App\Enums\PracticeStatus;
use App\Events\PracticeVerified;
use App\Models\Mudra;
use App\Models\PatientProfile;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PracticeVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function setup_scenario(string $target = 'Pataka'): array
    {
        config([
            'practice.confidence_threshold' => 0.75,
            'practice.hold_seconds' => 2,
            'practice.detection_interval_ms' => 1000,
            'practice.hold_grace_factor' => 2.5,
        ]);

        $doctor = User::factory()->doctor()->create();
        $patient = User::factory()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id, 'doctor_id' => $doctor->id]);

        $mudra = Mudra::factory()->create(['ai_class_label' => $target]);
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

        return [$patient, $prescription, $session];
    }

    private function frame(): UploadedFile
    {
        return UploadedFile::fake()->create('frame.jpg', 50, 'image/jpeg');
    }

    private function detect(User $patient, PracticeSession $session)
    {
        return $this->actingAs($patient)
            ->postJson(route('patient.practice.detect', $session), ['image' => $this->frame()]);
    }

    public function test_holding_the_mudra_verifies_after_the_required_seconds(): void
    {
        [$patient, $prescription, $session] = $this->setup_scenario();
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => false]);

        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => false]);

        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertOk()->assertJson(['verified' => true]);

        $this->assertSame(PracticeStatus::Verified, $session->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_wrong_mudra_never_verifies(): void
    {
        [$patient, , $session] = $this->setup_scenario();
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Mushti', 0.99));

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        foreach (range(0, 4) as $i) {
            Carbon::setTestNow(now()->addSecond());
            $this->detect($patient, $session)->assertJson(['verified' => false]);
        }

        $this->assertSame(PracticeStatus::InProgress, $session->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_event_and_completion_happen_exactly_once_under_duplicates(): void
    {
        Event::fake([PracticeVerified::class]);
        [$patient, , $session] = $this->setup_scenario();
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertJson(['verified' => true]);

        // Duplicate / refresh / extra frames after verification.
        $this->detect($patient, $session)->assertJson(['verified' => true]);
        $this->detect($patient, $session)->assertJson(['verified' => true]);

        Event::assertDispatchedTimes(PracticeVerified::class, 1);
        $this->assertDatabaseCount('practice_sessions', 1);
        Carbon::setTestNow();
    }

    public function test_metrics_are_recorded(): void
    {
        [$patient, , $session] = $this->setup_scenario();
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertJson(['verified' => true]);
        Carbon::setTestNow();

        $this->assertSame(3, Cache::get('metrics:ai:verification_attempts'));
        $this->assertSame(1, Cache::get('metrics:ai:verification_success'));
        $this->assertSame(3, Cache::get('metrics:ai:average_processing_time_ms:count'));
    }

    public function test_dashboard_reflects_verified_completion(): void
    {
        [$patient, $prescription, $session] = $this->setup_scenario();
        $this->app->instance(InferenceClient::class, (new FakeInferenceClient)->withDetection('Pataka', 0.9));

        Carbon::setTestNow(Carbon::create(2026, 6, 29, 8, 0, 0));
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session);
        Carbon::setTestNow(now()->addSecond());
        $this->detect($patient, $session)->assertJson(['verified' => true]);
        Carbon::setTestNow();

        $this->actingAs($patient)->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Done');
    }
}
