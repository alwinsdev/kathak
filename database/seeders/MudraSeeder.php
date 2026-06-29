<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mudra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MudraSeeder extends Seeder
{
    /**
     * Seed the Siddha mudra reference library. The `ai_class_label` must match
     * the class names emitted by the Roboflow model so AI verification can map
     * a detection back to a prescribed mudra.
     */
    public function run(): void
    {
        $mudras = [
            ['name' => 'Pataka', 'description' => 'Open palm, fingers extended and held together, thumb bent.', 'benefits' => 'Improves wrist flexibility & finger coordination.'],
            ['name' => 'Tripataka', 'description' => 'Like Pataka but ring finger bent.', 'benefits' => 'Strengthens fine motor control.'],
            ['name' => 'Ardhapataka', 'description' => 'Like Tripataka but little finger also bent.', 'benefits' => 'Improves finger isolation & dexterity.'],
            ['name' => 'Kartarimukha', 'description' => 'Index and middle finger stretched apart, others folded.', 'benefits' => 'Relieves tension in palm muscles.'],
            ['name' => 'Mayura', 'description' => 'Ring finger touches thumb tip, others extended.', 'benefits' => 'Calming, improves focus.'],
            ['name' => 'Ardhachandra', 'description' => 'Hand in crescent shape, thumb stretched out.', 'benefits' => 'Stretches palm & opens chest posture.'],
            ['name' => 'Arala', 'description' => 'Index curved, others slightly bent.', 'benefits' => 'Loosens stiff fingers.'],
            ['name' => 'Shukatunda', 'description' => 'Like Arala but ring finger also curved.', 'benefits' => 'Targets joint stiffness.'],
            ['name' => 'Mushti', 'description' => 'Closed fist with thumb on top.', 'benefits' => 'Builds grip strength.'],
            ['name' => 'Shikhara', 'description' => 'Closed fist, thumb pointing up.', 'benefits' => 'Stabilizes wrist, builds strength.'],
        ];

        foreach ($mudras as $mudra) {
            Mudra::updateOrCreate(
                ['slug' => Str::slug($mudra['name'])],
                [
                    'name' => $mudra['name'],
                    'description' => $mudra['description'],
                    'benefits' => $mudra['benefits'],
                    'ai_class_label' => $mudra['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
