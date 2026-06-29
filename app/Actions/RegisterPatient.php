<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterPatient
{
    /**
     * Create a patient user together with their demographic profile and the
     * doctor they selected at registration.
     *
     * @param  array<string, mixed>  $data  Validated registration data.
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => Role::Patient,
            ]);

            $user->patientProfile()->create([
                'doctor_id' => $data['doctor_id'],
                'age' => $data['age'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'condition_notes' => $data['condition_notes'] ?? null,
            ]);

            return $user;
        });
    }
}
