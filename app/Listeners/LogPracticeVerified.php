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

        Log::channel('business')->info('Practice verified', [
            'practice_session_id' => $session->id,
            'prescription_id' => $session->prescription_id,
            'patient_id' => $session->patient_id,
            'best_confidence' => $session->best_confidence,
        ]);
    }
}
