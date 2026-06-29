<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Seed demo doctors. Doctors are not self-registered; they are provisioned
     * here (a future module may add an admin-driven flow).
     */
    public function run(): void
    {
        $doctors = [
            ['name' => 'Dr. Anjali Sharma', 'email' => 'anjali@kathak.test'],
            ['name' => 'Dr. Ravi Menon', 'email' => 'ravi@kathak.test'],
        ];

        foreach ($doctors as $doctor) {
            User::updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => Role::Doctor,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
