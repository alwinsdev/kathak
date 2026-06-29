<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StorePrescriptionRequest;
use App\Http\Requests\Doctor\UpdatePrescriptionRequest;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Prescription\PrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptions) {}

    /**
     * Prescribe a mudra to one of the doctor's patients.
     */
    public function store(StorePrescriptionRequest $request, User $patient): RedirectResponse
    {
        $this->prescriptions->create($request->user(), $patient, $request->validated());

        return redirect()
            ->route('doctor.patients.show', $patient)
            ->with('status', 'Prescription added.');
    }

    /**
     * Update the schedule time, duration or notes of an active prescription.
     */
    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): RedirectResponse
    {
        $this->prescriptions->update($prescription, $request->validated());

        return redirect()
            ->route('doctor.patients.show', $prescription->patient_id)
            ->with('status', 'Prescription updated.');
    }

    /**
     * Cancel an active prescription.
     */
    public function destroy(Prescription $prescription): RedirectResponse
    {
        Gate::authorize('delete', $prescription);

        $patientId = $prescription->patient_id;
        $this->prescriptions->cancel($prescription);

        return redirect()
            ->route('doctor.patients.show', $patientId)
            ->with('status', 'Prescription cancelled.');
    }
}
