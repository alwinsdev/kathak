<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Placeholder doctor home. The real dashboard ships in module L2.
     */
    public function index(): View
    {
        return view('doctor.dashboard');
    }
}
