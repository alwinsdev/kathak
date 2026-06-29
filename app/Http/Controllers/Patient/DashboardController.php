<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Placeholder patient home. The real dashboard ships in module L3.
     */
    public function index(): View
    {
        return view('patient.dashboard');
    }
}
