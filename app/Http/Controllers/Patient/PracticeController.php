<?php

declare(strict_types=1);

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Repositories\PracticeSessionRepository;
use App\Services\Patient\PatientScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function __construct(
        private readonly PracticeSessionRepository $sessions,
        private readonly PatientScheduleService $schedule,
    ) {}

    /**
     * The live AI practice screen for one of the patient's own prescriptions.
     * The camera + detection behaviour is driven by config (no magic numbers).
     */
    public function show(Request $request, Prescription $prescription): View
    {
        Gate::authorize('view', $prescription);

        $prescription->load('mudra');

        $guide = config('mudra_guides.'.$prescription->mudra->ai_class_label)
            ?? config('mudra_guides.default');

        // If it's already verified today, show the completed state up front
        // (no camera) unless the patient explicitly chose to practise again.
        $completedToday = $request->boolean('again')
            ? null
            : $this->sessions->verifiedToday($prescription);

        // The next still-pending mudra today (other than this one) — used to
        // chain sessions after a successful verification.
        $next = $this->schedule->today($request->user())->mudras
            ->first(fn ($m) => ! $m->completedToday && $m->prescription->id !== $prescription->id);

        return view('patient.practice.show', [
            'prescription' => $prescription,
            'guide' => $guide,
            'completedToday' => $completedToday,
            'nextPractice' => $next?->prescription,
            'practiceConfig' => [
                'holdSeconds' => $prescription->holdSeconds(),
                'durationMin' => $prescription->duration_min,
                'detectionIntervalMs' => (int) config('practice.detection_interval_ms'),
                'jpegQuality' => (float) config('practice.jpeg_quality'),
                'confidenceThreshold' => (float) config('practice.confidence_threshold'),
            ],
        ]);
    }
}
