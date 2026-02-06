<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles + perms + demo users
        $this->call(SetupRolesAndPermissionsSeeder::class);
        $this->call(DemoUsersSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(TaskSeeder::class);
        
        // Bulk graph data (100+ props, sessions, etc.)
        $this->call(BulkDemoDataSeeder::class);
    }
}
