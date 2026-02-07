<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Seed the default application settings.
     * 
     * This ensures the correct branding (Room Ready) is set up.
     */
    public function run(): void
    {
        // Copy logo files from public assets to storage if they don't exist
        $this->setupLogoFiles();

        // Set default settings if they don't exist
        $defaults = [
            'site_name' => 'Room Ready',
            'theme_color' => '#06b6d4',
            'button_primary_color' => '#06b6d4',
            'button_success_color' => '#10b981',
            'button_danger_color' => '#ef4444',
            'button_warning_color' => '#f59e0b',
            'button_info_color' => '#06b6d4',
            'logo_alignment' => 'center',
            'date_format' => 'M d, Y',
            'time_format' => '12',
            'items_per_page' => 15,
            'timezone' => 'America/New_York',
            'auto_save_enabled' => true,
            'auto_save_delay' => 400,
            'notify_session_started' => true,
            'notify_session_completed' => true,
            'notify_assignments' => true,
        ];

        foreach ($defaults as $key => $value) {
            // Only set if not already exists
            if (Setting::where('key', $key)->doesntExist()) {
                Setting::set($key, $value);
                $this->command->info("Set default setting: {$key}");
            }
        }

        // Set logo paths if files exist and settings not already set
        if (Setting::where('key', 'application_logo_path')->doesntExist()) {
            if (Storage::disk('public')->exists('logos/clean-logo.png')) {
                Setting::set('application_logo_path', 'logos/clean-logo.png');
                $this->command->info('Set default logo path');
            }
        }

        if (Setting::where('key', 'favicon_path')->doesntExist()) {
            if (Storage::disk('public')->exists('favicons/clean-logo-small.png')) {
                Setting::set('favicon_path', 'favicons/clean-logo-small.png');
                $this->command->info('Set default favicon path');
            }
        }

        // Clear settings cache
        Setting::clearCache();

        $this->command->info('Default settings seeded successfully!');
    }

    /**
     * Copy logo files from public assets to storage if they don't exist.
     */
    private function setupLogoFiles(): void
    {
        $publicPath = public_path('images/assets');
        
        // Ensure storage directories exist
        Storage::disk('public')->makeDirectory('logos');
        Storage::disk('public')->makeDirectory('favicons');

        // Copy main logo
        $sourceLogo = $publicPath . '/clean-logo.png';
        if (file_exists($sourceLogo) && !Storage::disk('public')->exists('logos/clean-logo.png')) {
            Storage::disk('public')->put(
                'logos/clean-logo.png',
                file_get_contents($sourceLogo)
            );
            $this->command->info('Copied logo to storage');
        }

        // Copy favicon
        $sourceFavicon = $publicPath . '/clean-logo-small.png';
        if (file_exists($sourceFavicon) && !Storage::disk('public')->exists('favicons/clean-logo-small.png')) {
            Storage::disk('public')->put(
                'favicons/clean-logo-small.png',
                file_get_contents($sourceFavicon)
            );
            $this->command->info('Copied favicon to storage');
        }
    }
}
