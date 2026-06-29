<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrescriptionStatus;
use App\Models\Mudra;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => User::factory()->doctor(),
            'mudra_id' => Mudra::factory(),
            'scheduled_time' => fake()->time('H:i'),
            'duration_min' => fake()->numberBetween(5, 30),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'notes' => fake()->optional()->sentence(),
            'status' => PrescriptionStatus::Active,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PrescriptionStatus::Cancelled,
        ]);
    }
}
