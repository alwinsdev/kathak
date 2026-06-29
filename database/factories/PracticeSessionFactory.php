<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PracticeStatus;
use App\Models\PracticeSession;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeSession>
 */
class PracticeSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $practicedOn = now()->toDateString();

        return [
            'prescription_id' => Prescription::factory(),
            'patient_id' => User::factory(),
            'practiced_on' => $practicedOn,
            'started_at' => now(),
            'completed_at' => now(),
            'status' => PracticeStatus::Verified,
            'best_confidence' => fake()->randomFloat(3, 0.75, 0.99),
            'detected_class' => fake()->word(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PracticeStatus::Verified,
            'completed_at' => now(),
        ]);
    }

    public function on(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'practiced_on' => $date,
        ]);
    }
}
