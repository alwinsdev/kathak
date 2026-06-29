<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The doctor's panel: only patients assigned to this doctor.
     */
    public function index(Request $request): View
    {
        $doctor = $request->user();

        $patients = User::patients()
            ->whereHas('patientProfile', fn ($query) => $query->where('doctor_id', $doctor->id))
            ->with('patientProfile')
            ->withCount(['prescriptions as active_prescriptions_count' => fn ($query) => $query->where('status', PrescriptionStatus::Active->value)])
            ->orderBy('name')
            ->get();

        return view('doctor.dashboard', [
            'patients' => $patients,
            'totalPatients' => $patients->count(),
            'totalActivePrescriptions' => $patients->sum('active_prescriptions_count'),
        ]);
    }
}
