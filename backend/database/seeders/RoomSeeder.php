<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds. 
     */
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Sci9 201(COM)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ห้อง 1',
                'building' => 'อาคาร Sci9',
                'floor' => '2',
                'amenities' => ['โปรเจคเตอร์', 'ระบบเสียง', 'Wi-Fi', 'เครื่องปรับอากาศ','กระดานไวท์บอร์ด','คอมพิวเตอร์'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 203(HardWare)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ห้อง 3',
                'building' => 'อาคาร Sci9',
                'floor' => '2',
                'amenities' => ['กระดานไวท์บอร์ด', 'Wi-Fi', 'เครื่องปรับอากาศ', 'โต๊ะเรียน','คอมพิวเตอร์','ระบบเสียง'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 204(COM)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 10,
                'location' => 'ห้อง 4',
                'building' => 'อาคาร Sci9',
                'floor' => '2',
                'amenities' => ['Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','ระบบเสียง','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 205(COM)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 24,
                'location' => 'ห้อง 5',
                'building' => 'อาคาร B',
                'floor' => '4',
                'amenities' => ['คอมพิวเตอร์', 'โปรเจคเตอร์', 'Wi-Fi', 'เครื่องปรับอากาศ'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 301(COM)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 49,
                'location' => 'ห้อง 1',
                'building' => 'อาคาร Sci9',
                'floor' => '3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 302(SmB)',
                'room_type' => 'classroom',
                'description' => 'ห้องเรียน',
                'capacity' => 50,
                'location' => 'ห้อง 2',
                'building' => 'อาคาร Sci9',
                'floor' => '3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด','โต๊ะเรียน'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 303(Com)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 48,
                'location' => 'ห้อง 3',
                'building' => 'อาคาร Sci9',
                'floor' => '3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 304(Com)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 40,
                'location' => 'ห้อง 4',
                'building' => 'อาคาร Sci9',
                'floor' => '3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 306(Com)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ห้อง 5',
                'building' => 'อาคาร Sci9',
                'floor' => '3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 402',
                'room_type' => 'meeting',
                'description' => 'ห้องประชุม',
                'capacity' => 40,
                'location' => 'ห้อง 2',
                'building' => 'อาคาร Sci9',
                'floor' => '4',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ','กระดานไวท์บอร์ด','โต๊ะ','เก้าอี้'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 403(Com)',
                'room_type' => 'computer',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 24,
                'location' => 'ห้อง 3',
                'building' => 'อาคาร Sci9',
                'floor' => '4',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],
            [
                'name' => 'Sci9 405',
                'room_type' => 'classroom',
                'description' => 'ห้องเรียน',
                'capacity' => 48,
                'location' => 'ห้อง 5',
                'building' => 'อาคาร Sci9',
                'floor' => '4',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
                'status' => 'available',
            ],

        ];

        // ลบข้อมูลเก่าทั้งหมดก่อน (ถ้ามี) - ใช้ delete แทน truncate เพราะมี foreign key
        Room::query()->delete();

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
