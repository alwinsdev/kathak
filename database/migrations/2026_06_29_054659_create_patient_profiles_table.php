<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Patient demographics + the doctor who owns their care.
     */
    public function up(): void
    {
        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            // One profile per patient user; remove the profile if the user goes.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // The patient's chosen/assigned doctor. Keep the patient if the
            // doctor is removed (clinical record stays); just unset the link.
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->text('condition_notes')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
    }
};
