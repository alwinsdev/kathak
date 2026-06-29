<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Mudra;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Show one of the doctor's own patients with their active prescriptions.
     */
    public function show(User $patient): View
    {
        Gate::authorize('manage-patient', $patient);

        $patient->load('patientProfile.doctor');

        $prescriptions = $patient->prescriptions()
            ->active()
            ->with('mudra')
            ->orderBy('scheduled_time')
            ->get();

        $mudras = Mudra::active()->orderBy('name')->get();

        return view('doctor.patients.show', compact('patient', 'prescriptions', 'mudras'));
    }
}
