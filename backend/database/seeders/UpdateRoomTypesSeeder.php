<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class UpdateRoomTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // อัพเดทประเภทห้องตาม description ที่มีอยู่
        $roomTypeMappings = [
            'ห้องคอมพิวเตอร์' => 'computer',
            'ห้องประชุม' => 'meeting',
            'ห้องเรียน' => 'classroom',
        ];

        foreach ($roomTypeMappings as $description => $roomType) {
            Room::where('description', $description)
                ->update(['room_type' => $roomType]);
        }

        // สำหรับห้องที่ไม่มี description หรือ description ว่าง ให้เป็น general
        Room::whereNull('description')
            ->orWhere('description', '')
            ->update(['room_type' => 'general']);

        echo "อัพเดทประเภทห้องเรียบร้อยแล้ว\n";
    }
}
