<?php

use App\Enums\PrescriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A doctor's prescription of a mudra to a patient, with a simple schedule.
     *
     * Verification tuning (hold seconds, confidence threshold) is intentionally
     * NOT stored here for the POC — it comes from config/practice.php. Per-
     * prescription overrides can be added later without reshaping this table.
     */
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mudra_id')->constrained('mudras')->restrictOnDelete();
            $table->time('scheduled_time');
            $table->unsignedSmallInteger('duration_min');
            $table->date('start_date');
            $table->text('notes')->nullable();
            // Lifecycle status. Only "active" is used in this POC; the rest
            // (completed/expired/cancelled) are reserved for future expansion.
            $table->string('status')->default(PrescriptionStatus::Active->value);
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
