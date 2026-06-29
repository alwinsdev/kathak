<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PracticeController extends Controller
{
    /**
     * Practice entry point. Placeholder until L4 adds the live camera + AI
     * verification screen. Ownership is enforced here so it is ready for L4.
     */
    public function show(Prescription $prescription): View
    {
        Gate::authorize('view', $prescription);

        $prescription->load('mudra');

        return view('patient.practice.show', compact('prescription'));
    }
}
