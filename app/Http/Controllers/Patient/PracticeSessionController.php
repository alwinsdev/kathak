<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Domain\AI\Services\PracticeSessionService;
use App\Enums\PracticeStatus;
use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PracticeSessionController extends Controller
{
    public function __construct(private readonly PracticeSessionService $sessions) {}

    /**
     * Begin (or resume) today's practice session for a prescription.
     */
    public function start(Request $request, Prescription $prescription): JsonResponse
    {
        Gate::authorize('view', $prescription);

        $session = $this->sessions->start($prescription, $request->user());

        return response()->json([
            'session_id' => $session->id,
            'verified' => $session->status === PracticeStatus::Verified,
            'started_at' => optional($session->started_at)->format('h:i A'),
        ]);
    }
}
