<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The mudra reference library. Seeded data; doctors prescribe from it.
     */
    public function up(): void
    {
        Schema::create('mudras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('benefits')->nullable();
            // Maps this mudra to the class label the Roboflow model returns.
            // Used by AI verification to compare detection against prescription.
            $table->string('ai_class_label')->nullable()->index();
            $table->string('reference_image_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mudras');
    }
};
