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
     * The live AI practice screen for one of the patient's own prescriptions.
     * The camera + detection behaviour is driven by config (no magic numbers).
     */
    public function show(Prescription $prescription): View
    {
        Gate::authorize('view', $prescription);

        $prescription->load('mudra');

        return view('patient.practice.show', [
            'prescription' => $prescription,
            'practiceConfig' => [
                'holdSeconds' => (int) config('practice.hold_seconds'),
                'detectionIntervalMs' => (int) config('practice.detection_interval_ms'),
                'jpegQuality' => (float) config('practice.jpeg_quality'),
                'confidenceThreshold' => (float) config('practice.confidence_threshold'),
            ],
        ]);
    }
}
