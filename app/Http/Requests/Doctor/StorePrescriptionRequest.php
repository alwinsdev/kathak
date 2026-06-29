<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctor;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    /**
     * The doctor may prescribe only for a patient in their own panel.
     */
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof User && $this->user()->can('manage-patient', $patient);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mudra_id' => [
                'required',
                Rule::exists('mudras', 'id')->where('is_active', true),
            ],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'duration_min' => ['required', 'integer', 'min:1', 'max:120'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
