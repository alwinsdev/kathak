<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'doctor_id' => User::factory()->doctor(),
            'age' => fake()->numberBetween(18, 85),
            'gender' => fake()->randomElement(Gender::cases()),
            'phone' => fake()->numerify('##########'),
            'condition_notes' => fake()->sentence(),
        ];
    }
}
