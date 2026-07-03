<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\PracticeSessionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly PracticeSessionRepository $sessions) {}

    /**
     * The doctor's panel: only patients assigned to this doctor, with
     * read-only adherence context for display.
     */
    public function index(Request $request): View
    {
        $doctor = $request->user();

        $patients = User::patients()
            ->whereHas('patientProfile', fn ($query) => $query->where('doctor_id', $doctor->id))
            ->with('patientProfile')
            ->withCount(['prescriptions as active_prescriptions_count' => fn ($query) => $query->where('status', PrescriptionStatus::Active->value)])
            ->orderBy('name')
            ->get()
            ->each(function (User $patient) {
                $patient->setAttribute('last_practice_date', $this->sessions->lastVerifiedDate($patient));

                $practised = $this->sessions->verifiedDatesInLastDays($patient, 7)->flip();
                $patient->setAttribute('adherence_days', collect(range(6, 0))->map(function (int $daysBack) use ($practised) {
                    $date = Carbon::today()->subDays($daysBack);

                    return [
                        'title' => $date->format('D d M'),
                        'done' => $practised->has($date->toDateString()),
                        'isToday' => $daysBack === 0,
                    ];
                })->values()->all());
            });

        return view('doctor.dashboard', [
            'patients' => $patients,
            'totalPatients' => $patients->count(),
            'totalActivePrescriptions' => $patients->sum('active_prescriptions_count'),
            'practisedToday' => $patients->filter(fn (User $patient) => $patient->last_practice_date?->isToday())->count(),
        ]);
    }
}
