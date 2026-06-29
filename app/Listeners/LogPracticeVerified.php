<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PracticeVerified;
use Illuminate\Support\Facades\Log;

class LogPracticeVerified
{
    public function handle(PracticeVerified $event): void
    {
        $session = $event->session;

        Log::channel('business')->info('practice_verified', [
            'correlation_id' => $event->correlationId,
            'patient_id' => $session->patient_id,
            'prescription_id' => $session->prescription_id,
            'practice_session_id' => $session->id,
            'detected_class' => $session->detected_class,
            'best_confidence' => $session->best_confidence,
        ]);
    }
}
