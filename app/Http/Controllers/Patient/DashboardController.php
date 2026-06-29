<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientScheduleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly PatientScheduleService $schedule) {}

    /**
     * The patient's therapy for today.
     */
    public function index(Request $request): View
    {
        return view('patient.dashboard', [
            'today' => $this->schedule->today($request->user()),
        ]);
    }
}
