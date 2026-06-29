<?php

use App\Enums\PracticeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A patient's practice attempt against a prescription. L3 owns this read
     * model and renders history from it; L4 (AI verification) writes the rows
     * (target match + hold → verified). It may add columns additively later.
     */
    public function up(): void
    {
        Schema::create('practice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->date('practiced_on');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default(PracticeStatus::InProgress->value);
            $table->decimal('best_confidence', 4, 3)->nullable();
            $table->string('detected_class')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'practiced_on']);
            $table->index('prescription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_sessions');
    }
};
