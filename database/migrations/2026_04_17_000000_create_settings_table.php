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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->float('key_usage_check_percent')->default(0.4);
            $table->unsignedSmallInteger('handover_eligibility_window_minutes')->default(30);
            $table->unsignedSmallInteger('grace_period_minutes')->default(15);
            $table->boolean('handover_enabled')->default(true);
            $table->boolean('allow_past_schedule_requests')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
