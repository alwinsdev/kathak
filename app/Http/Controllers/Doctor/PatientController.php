<?php

declare(strict_types=1);

namespace App\Http\Controllers\Doctor;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Mudra;
use App\Models\User;
use App\Repositories\PracticeSessionRepository;
use App\Services\Patient\PracticeHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly PracticeSessionRepository $sessions,
        private readonly PracticeHistoryService $history,
    ) {}

    /**
     * The doctor's clinical workspace for one patient: active prescriptions
     * plus read-only practice context (adherence, stats, recent activity).
     */
    public function show(User $patient): View
    {
        Gate::authorize('manage', $patient);

        $patient->load('patientProfile.doctor');

        $prescriptions = $patient->prescriptions()
            ->active()
            ->with('mudra')
            ->orderBy('scheduled_time')
            ->get();

        $previousPrescriptions = $patient->prescriptions()
            ->whereIn('status', [PrescriptionStatus::Cancelled->value, PrescriptionStatus::Completed->value])
            ->with('mudra')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $mudras = Mudra::active()->orderBy('name')->get();

        // Adherence: which prescriptions are done today + the last-7-days strip.
        $doneTodayIds = $this->sessions->verifiedPrescriptionIdsOn($patient, Carbon::today());
        $practisedDates = $this->sessions->verifiedDatesInLastDays($patient, 7)->flip();

        $adherence = collect(range(6, 0))->map(function (int $daysBack) use ($practisedDates) {
            $date = Carbon::today()->subDays($daysBack);

            return [
                'label' => $date->format('D'),
                'title' => $date->format('d M'),
                'done' => $practisedDates->has($date->toDateString()),
                'isToday' => $daysBack === 0,
            ];
        });

        return view('doctor.patients.show', [
            'patient' => $patient,
            'prescriptions' => $prescriptions,
            'previousPrescriptions' => $previousPrescriptions,
            'mudras' => $mudras,
            'doneTodayIds' => $doneTodayIds,
            'adherence' => $adherence,
            'lastPractice' => $this->sessions->lastVerifiedDate($patient),
            'stats' => $this->history->stats($patient),
            'recentActivity' => $this->sessions->recentVerified($patient, 6),
        ]);
    }
}
