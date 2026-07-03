<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientScheduleService;
use App\Services\Patient\PracticeHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PatientScheduleService $schedule,
        private readonly PracticeHistoryService $history,
    ) {}

    /**
     * The patient's therapy for today.
     */
    public function index(Request $request): View
    {
        $patient = $request->user();

        return view('patient.dashboard', [
            'today' => $this->schedule->today($patient),
            'streak' => $this->history->stats($patient)->streak,
        ]);
    }
}
