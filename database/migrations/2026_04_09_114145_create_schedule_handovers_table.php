<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('schedule_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('previous_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('next_schedule_id')->constrained('schedules')->cascadeOnDelete();

            // Dual-confirmation timestamps.
            $table->timestamp('previous_confirmed_at')->nullable();
            $table->timestamp('next_confirmed_at')->nullable();

            // Per-party dispute timestamps.
            $table->timestamp('previous_disputed_at')->nullable();
            $table->timestamp('next_disputed_at')->nullable();

            // Resolution tracking.
            $table->timestamp('resolution_deadline_at')->nullable();
            $table->timestamp('resolution_finalized_at')->nullable();

            $table->timestamps();

            // One handover per outgoing schedule.
            $table->unique('previous_schedule_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_handovers');
    }
};
