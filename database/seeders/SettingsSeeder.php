<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the application's settings row.
     */
    public function run(): void
    {
        Setting::query()->updateOrCreate(
            ['id' => 1],
            Setting::defaults(),
        );

        Setting::refreshCache();
    }
}
