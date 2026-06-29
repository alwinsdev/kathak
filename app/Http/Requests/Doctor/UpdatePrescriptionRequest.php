<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctor;

use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    /**
     * Ownership + active check is delegated to the PrescriptionPolicy.
     */
    public function authorize(): bool
    {
        $prescription = $this->route('prescription');

        return $prescription instanceof Prescription
            && $this->user()->can('update', $prescription);
    }

    /**
     * Only the schedule time, duration and notes are editable; the mudra and
     * start date are immutable for an existing prescription.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_time' => ['required', 'date_format:H:i'],
            'duration_min' => ['required', 'integer', 'min:1', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
