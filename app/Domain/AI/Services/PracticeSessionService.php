<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use App\Enums\PracticeStatus;
use App\Events\PracticeVerified;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use App\Repositories\PracticeSessionRepository;
use Illuminate\Support\Carbon;

class PracticeSessionService
{
    public function __construct(
        private readonly PracticeSessionRepository $sessions,
    ) {}

    /**
     * Begin (or resume) today's practice session for a prescription.
     *
     * Idempotent: if the prescription is already verified today the verified
     * session is returned; an existing in-progress session is resumed rather
     * than duplicated.
     */
    public function start(Prescription $prescription, User $patient): PracticeSession
    {
        if ($verified = $this->sessions->verifiedToday($prescription)) {
            return $verified;
        }

        if ($inProgress = $this->sessions->currentInProgressToday($prescription)) {
            return $inProgress;
        }

        return PracticeSession::create([
            'prescription_id' => $prescription->id,
            'patient_id' => $patient->id,
            'practiced_on' => Carbon::today(),
            'started_at' => Carbon::now(),
            'status' => PracticeStatus::InProgress,
        ]);
    }

    /**
     * Mark a session verified — exactly once.
     *
     * The status flip is an atomic, conditional UPDATE (in_progress -> verified):
     * only the first caller to win that update returns true and dispatches the
     * PracticeVerified event. Duplicate/retried/concurrent requests see 0 rows
     * affected and become safe no-ops, so the event fires exactly once.
     */
    public function complete(
        PracticeSession $session,
        ?string $detectedClass,
        float $bestConfidence,
        ?string $correlationId = null,
    ): bool {
        $affected = PracticeSession::query()
            ->whereKey($session->getKey())
            ->where('status', PracticeStatus::InProgress)
            ->update([
                'status' => PracticeStatus::Verified,
                'completed_at' => Carbon::now(),
                'best_confidence' => $bestConfidence,
                'detected_class' => $detectedClass,
            ]);

        if ($affected === 0) {
            return false;
        }

        PracticeVerified::dispatch($session->fresh(), $correlationId);

        return true;
    }
}
