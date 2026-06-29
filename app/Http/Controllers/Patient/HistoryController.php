<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\PracticeHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __construct(private readonly PracticeHistoryService $history) {}

    /**
     * The patient's practice history and summary stats.
     */
    public function index(Request $request): View
    {
        $patient = $request->user();

        return view('patient.history', [
            'sessions' => $this->history->recent($patient),
            'stats' => $this->history->stats($patient),
        ]);
    }
}
