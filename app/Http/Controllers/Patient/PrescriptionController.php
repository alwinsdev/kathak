<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    /**
     * Read-only detail of one of the patient's own prescriptions.
     */
    public function show(Prescription $prescription): View
    {
        Gate::authorize('view', $prescription);

        $prescription->load('mudra');

        return view('patient.prescriptions.show', compact('prescription'));
    }
}
