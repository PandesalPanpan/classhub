<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('key_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_id')->constrained('keys')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->string('status'); // USED, STORED
            $table->timestamp('occurred_at');
            $table->string('source')->default('iot'); // 'iot' or 'synthetic'
            $table->timestamps();

            $table->index(['key_id', 'schedule_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_events');
    }
};
