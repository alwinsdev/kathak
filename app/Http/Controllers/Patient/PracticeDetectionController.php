<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Domain\AI\Actions\VerifyPracticeAction;
use App\Domain\AI\Contracts\MetricsRecorder;
use App\Domain\AI\Exceptions\InferenceException;
use App\Domain\AI\Metrics\AiMetric;
use App\Domain\AI\Services\PracticeHoldTracker;
use App\Domain\AI\Services\PracticeSessionService;
use App\Domain\AI\Services\PredictionSmoother;
use App\Enums\PracticeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\DetectFrameRequest;
use App\Models\PracticeSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PracticeDetectionController extends Controller
{
    public function __construct(
        private readonly VerifyPracticeAction $verify,
        private readonly PredictionSmoother $smoother,
        private readonly PracticeHoldTracker $holdTracker,
        private readonly PracticeSessionService $sessions,
        private readonly MetricsRecorder $metrics,
    ) {}

    /**
     * Evaluate one camera frame, advance the (server-authoritative) hold timer,
     * and complete the session when the hold is satisfied. Coordination only —
     * the AI evaluation, hold logic and completion live in the AI domain.
     */
    public function detect(DetectFrameRequest $request, PracticeSession $session): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?: (string) Str::uuid();

        // Idempotent short-circuit for refresh / duplicate / multi-tab requests.
        if ($session->status === PracticeStatus::Verified) {
            return response()->json($this->verifiedPayload($session));
        }

        $this->metrics->increment(AiMetric::VERIFICATION_ATTEMPTS);

        $imageBinary = file_get_contents($request->file('image')->getRealPath());

        try {
            $detection = $this->verify->handle($session, $imageBinary, $correlationId);
        } catch (InferenceException) {
            $this->metrics->increment(AiMetric::INFERENCE_FAILURES);

            return response()->json([
                'error' => true,
                'message' => 'Detection is temporarily unavailable. Please keep practising.',
            ], 502);
        }

        $this->metrics->observe(AiMetric::AVERAGE_PROCESSING_TIME_MS, $detection->processingMs);

        // Anti-shake: judge the frame against recent evidence so one tremor /
        // motion-blur frame cannot flash "incorrect" or pause the hold.
        $detection = $this->smoother->smooth($session, $detection);

        $progress = $this->holdTracker->record($session, $detection->matched, $detection->confidence);

        if ($progress->ready) {
            $justCompleted = $this->sessions->complete(
                $session,
                $detection->detectedClass,
                $progress->bestConfidence,
                $correlationId,
            );

            if ($justCompleted) {
                $this->metrics->increment(AiMetric::VERIFICATION_SUCCESS);
                $this->holdTracker->clear($session);
                $this->smoother->clear($session);
            }
        }

        $verified = $session->fresh()->status === PracticeStatus::Verified;

        return response()->json($detection->toArray() + [
            'held_seconds' => $progress->heldSeconds,
            'hold_seconds' => $progress->holdSeconds,
            'verified' => $verified,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function verifiedPayload(PracticeSession $session): array
    {
        $holdSeconds = $session->prescription?->holdSeconds() ?? (int) config('practice.hold_seconds');

        return [
            'matched' => true,
            'confidence' => (float) ($session->best_confidence ?? 1.0),
            'detected_class' => $session->detected_class,
            'top_confidence' => (float) ($session->best_confidence ?? 1.0),
            'processing_time_ms' => 0,
            'predictions' => [],
            'held_seconds' => (float) $holdSeconds,
            'hold_seconds' => $holdSeconds,
            'verified' => true,
        ];
    }
}
