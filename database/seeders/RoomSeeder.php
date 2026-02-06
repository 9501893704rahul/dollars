<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultRooms = ['Bedroom', 'Kitchen', 'Bathroom', 'Living Room', 'Dining'];
        
        foreach ($defaultRooms as $roomName) {
            Room::firstOrCreate(
                ['name' => $roomName],
                ['is_default' => true]
            );
        }
    }
}
