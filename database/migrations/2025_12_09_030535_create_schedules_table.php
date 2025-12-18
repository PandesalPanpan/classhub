<?php

use App\ScheduleStatus;
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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms', 'id');
            $table->foreignId('requester_id')->nullable()->constrained('users', 'id');
            $table->foreignId('approver_id')->nullable()->constrained('users', 'id');
            $table->string('subject');
            $table->string('program_year_section')->nullable();
            $table->string('instructor')->nullable();
            $table->string('status')->default(ScheduleStatus::Pending->value);
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
