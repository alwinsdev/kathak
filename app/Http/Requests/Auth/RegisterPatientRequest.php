<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterPatientRequest extends FormRequest
{
    /**
     * Registration is a public action.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:150', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'doctor_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', Role::Doctor->value),
            ],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'condition_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'doctor_id' => 'doctor',
            'condition_notes' => 'condition',
        ];
    }
}
