<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['num' => 204, 'nb_lits' => 4, 'building_id' => 1, 'floor' => 2, 'surface' => 32, 'view' => 'mountains', 'balcony' => true],
            ['num' => 118, 'nb_lits' => 2, 'building_id' => 1, 'floor' => 1, 'surface' => 22, 'view' => 'parking', 'balcony' => false],
            ['num' => 306, 'nb_lits' => 6, 'building_id' => 2, 'floor' => 3, 'surface' => 44, 'view' => 'mountains', 'balcony' => true],
        ];

        foreach ($rooms as $roomData) {
            $room = Room::query()->updateOrCreate(['num' => $roomData['num']], $roomData);

            Image::query()->updateOrCreate(
                ['room_id' => $room->id, 'name' => 'placeholder.jpg'],
                [
                    'path' => "rooms/{$room->id}/placeholder.jpg",
                    'url' => "https://example.com/rooms/{$room->id}/placeholder.jpg",
                ],
            );
        }
    }
}
