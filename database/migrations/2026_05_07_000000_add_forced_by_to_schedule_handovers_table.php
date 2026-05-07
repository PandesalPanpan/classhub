<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_handovers', function (Blueprint $table) {
            $table->foreignId('forced_by')->nullable()->after('resolution_finalized_at')->constrained('users', 'id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_handovers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forced_by');
        });
    }
};
