<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DoctorSeeder::class,
            MudraSeeder::class,
        ]);

        // A demo patient assigned to the first seeded doctor for convenience.
        $doctor = User::doctors()->orderBy('id')->first();

        $patient = User::updateOrCreate(
            ['email' => 'patient@kathak.test'],
            [
                'name' => 'Demo Patient',
                'password' => Hash::make('password'),
                'role' => Role::Patient,
                'email_verified_at' => now(),
            ],
        );

        $patient->patientProfile()->updateOrCreate(
            ['user_id' => $patient->id],
            [
                'doctor_id' => $doctor?->id,
                'age' => 62,
                'gender' => Gender::Female,
                'phone' => '9876543210',
                'condition_notes' => 'Post-stroke finger stiffness.',
            ],
        );
    }
}
