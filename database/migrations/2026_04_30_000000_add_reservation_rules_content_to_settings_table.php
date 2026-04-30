<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('reservation_rules_content')->nullable()->after('policy_updated_at');
        });

        $defaultRules = (string) config('classhub.schedule.reservation_rules_content', '');

        if ($defaultRules === '') {
            return;
        }

        DB::table('settings')
            ->whereNull('reservation_rules_content')
            ->update([
                'reservation_rules_content' => $defaultRules,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('reservation_rules_content');
        });
    }
};
