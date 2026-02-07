<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Force update branding to Room Ready.
     */
    public function up(): void
    {
        // Force update branding settings with correct logo paths
        $settings = [
            'site_name' => 'Room Ready',
            'theme_color' => '#06b6d4',
            'button_primary_color' => '#06b6d4',
            'application_logo_path' => 'logos/MobzCQHlNWpcaRgoNOCIgUGS42zdZBPaA3jSZblA.png',
            'favicon_path' => 'favicons/Ino9XN7ltM93IZNToNrt88hGTiQDYfbnBuHx2fZu.png',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['key' => $key, 'value' => $value, 'updated_at' => now()]
            );
        }

        // Clear cache if possible
        if (function_exists('cache')) {
            cache()->flush();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
