<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mudra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MudraSeeder extends Seeder
{
    /**
     * Seed the Siddha mudra library. The POC recognizes exactly one mudra:
     * Aakash Mudra (ஆகாய முத்திரை). `ai_class_label` is the internal Siddha
     * label the AI mapping layer emits — never a raw model class name.
     */
    public function run(): void
    {
        $image = 'images/mudras/aakash.jpg';

        Mudra::updateOrCreate(
            ['slug' => Str::slug('Aakash Mudra')],
            [
                'name' => 'Aakash Mudra',
                'description' => 'ஆகாய முத்திரை — touch the tip of the middle finger to the tip of the thumb; keep the other three fingers straight and relaxed.',
                'benefits' => 'Traditional Siddha mudra associated with the space (aakash) element.',
                'ai_class_label' => 'aakash',
                'reference_image_path' => file_exists(public_path($image)) ? $image : null,
                'is_active' => true,
            ],
        );

        // Earlier experimental entries are retired, never shown, but kept for
        // referential integrity with any historical prescriptions.
        Mudra::where('ai_class_label', '!=', 'aakash')->update(['is_active' => false]);
    }
}
