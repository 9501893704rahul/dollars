<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles + perms + demo users (essential for production)
        $this->call(SetupRolesAndPermissionsSeeder::class);
        $this->call(DemoUsersSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(TaskSeeder::class);
        
        // Default branding settings (Room Ready logo, cyan theme)
        $this->call(DefaultSettingsSeeder::class);
        
        // Skip BulkDemoDataSeeder in production (requires faker)
        // $this->call(BulkDemoDataSeeder::class);
    }
}
