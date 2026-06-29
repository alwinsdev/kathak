<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mudra;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mudra>
 */
class MudraFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'benefits' => fake()->sentence(),
            'ai_class_label' => ucfirst($name),
            'reference_image_path' => null,
            'is_active' => true,
        ];
    }
}
