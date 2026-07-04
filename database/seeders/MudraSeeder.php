<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mudra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MudraSeeder extends Seeder
{
    /**
     * Seed the Siddha mudra reference library.
     *
     * `ai_class_label` MUST match the class token emitted by the Roboflow
     * model (workspace prabanjan17-gmail-com / kathak-trainer/8). The model
     * uses lowercase, sometimes-shortened tokens (e.g. "shikhar" for
     * Shikhara), so the display name and the AI label differ.
     */
    public function run(): void
    {
        $mudras = [
            ['name' => 'Pataka', 'ai' => 'pataka', 'description' => 'Open palm, fingers extended and held together, thumb bent.', 'benefits' => 'Improves wrist flexibility & finger coordination.'],
            ['name' => 'Tripataka', 'ai' => 'tripataka', 'description' => 'Like Pataka but ring finger bent.', 'benefits' => 'Strengthens fine motor control.'],
            ['name' => 'Ardhapataka', 'ai' => 'ardhpataka', 'description' => 'Like Tripataka but little finger also bent.', 'benefits' => 'Improves finger isolation & dexterity.'],
            ['name' => 'Kartarimukha', 'ai' => 'kartarimukh', 'description' => 'Index and middle finger stretched apart, others folded.', 'benefits' => 'Relieves tension in palm muscles.'],
            ['name' => 'Mayura', 'ai' => 'mayur', 'description' => 'Ring finger touches thumb tip, others extended.', 'benefits' => 'Calming, improves focus.'],
            ['name' => 'Ardhachandra', 'ai' => 'ardhachandra', 'description' => 'Hand in crescent shape, thumb stretched out.', 'benefits' => 'Stretches palm & opens chest posture.'],
            ['name' => 'Arala', 'ai' => 'aral', 'description' => 'Index curved, others slightly bent.', 'benefits' => 'Loosens stiff fingers.'],
            ['name' => 'Shukatunda', 'ai' => 'shuktund', 'description' => 'Open hand with fingers spread apart, facing the camera.', 'benefits' => 'Targets joint stiffness & finger spread.'],
            ['name' => 'Mushti', 'ai' => 'mushti', 'description' => 'Closed fist with thumb on top.', 'benefits' => 'Builds grip strength.'],
            ['name' => 'Shikhara', 'ai' => 'shikhar', 'description' => 'Closed fist held up, facing the camera.', 'benefits' => 'Stabilizes wrist, builds strength.'],
            ['name' => 'Soochi', 'ai' => 'soochi', 'description' => 'Index finger pointing straight up, other fingers folded.', 'benefits' => 'Improves index-finger control & focus.'],
            ['name' => 'Trishool', 'ai' => 'trishool', 'description' => 'Index, middle and ring fingers raised like a trident, thumb and little finger folded.', 'benefits' => 'Strengthens three-finger extension.'],
        ];

        // Real reference photos exist for the mudras the model is trained on.
        $withPhoto = ['shikhar', 'pataka', 'soochi', 'trishool', 'mayur', 'shuktund', 'ardhpataka', 'mushti', 'aral', 'ardhachandra', 'kartarimukh', 'tripataka'];

        foreach ($mudras as $mudra) {
            $image = in_array($mudra['ai'], $withPhoto, true)
                ? "images/mudras/{$mudra['ai']}.jpg"
                : null;

            Mudra::updateOrCreate(
                ['slug' => Str::slug($mudra['name'])],
                [
                    'name' => $mudra['name'],
                    'description' => $mudra['description'],
                    'benefits' => $mudra['benefits'],
                    'ai_class_label' => $mudra['ai'],
                    'reference_image_path' => $image,
                    'is_active' => true,
                ],
            );
        }
    }
}
