<?php

namespace Database\Seeders;

use App\Enums\ResourceTypes;
use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        // Rooms
        Resource::create([
            'name' => 'Room A101',
            'type' => ResourceTypes::ROOM,
            'capacity' => 2,
            'is_active' => true,
            'meta' => [
                'floor' => 1,
                'building' => 'A',
            ],
        ]);

        Resource::create([
            'name' => 'Room A102',
            'type' => ResourceTypes::ROOM,
            'capacity' => 4,
            'is_active' => true,
            'meta' => [
                'floor' => 1,
                'building' => 'A',
            ],
        ]);

        Resource::create([
            'name' => 'Room B201',
            'type' => ResourceTypes::ROOM,
            'capacity' => 6,
            'is_active' => true,
            'meta' => [
                'floor' => 2,
                'building' => 'B',
            ],
        ]);

        // Seats
        Resource::create([
            'name' => 'Seat A1',
            'type' => ResourceTypes::SEAT,
            'capacity' => 1,
            'is_active' => true,
        ]);

        Resource::create([
            'name' => 'Seat A2',
            'type' => ResourceTypes::SEAT,
            'capacity' => 1,
            'is_active' => true,
        ]);

        Resource::create([
            'name' => 'Seat B1',
            'type' => ResourceTypes::SEAT,
            'capacity' => 1,
            'is_active' => true,
        ]);

        // Flights
        Resource::create([
            'name' => 'Flight MS101',
            'type' => ResourceTypes::FLIGHT,
            'capacity' => 180,
            'is_active' => true,
            'meta' => [
                'from' => 'Cairo',
                'to' => 'Dubai',
            ],
        ]);

        Resource::create([
            'name' => 'Flight MS202',
            'type' => ResourceTypes::FLIGHT,
            'capacity' => 220,
            'is_active' => true,
            'meta' => [
                'from' => 'Cairo',
                'to' => 'Riyadh',
            ],
        ]);
    }
}
