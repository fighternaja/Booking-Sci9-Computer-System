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
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ชั้น 2 ห้อง 1',
                'amenities' => ['โปรเจคเตอร์', 'ระบบเสียง', 'Wi-Fi', 'เครื่องปรับอากาศ','กระดานไวท์บอร์ด','คอมพิวเตอร์'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 203(HardWare)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ชั้น 2 ห้อง 3',
                'amenities' => ['กระดานไวท์บอร์ด', 'Wi-Fi', 'เครื่องปรับอากาศ', 'โต๊ะเรียน','คอมพิวเตอร์','ระบบเสียง'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 204(COM)',
                'description' => '',
                'capacity' => 10,
                'location' => 'ชั้น 2 ห้อง 4',
                'amenities' => ['Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','ระบบเสียง','กระดานไวท์บอร์ด'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 205(COM)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 24,
                'location' => 'ชั้น 4 อาคาร B',
                'amenities' => ['คอมพิวเตอร์', 'โปรเจคเตอร์', 'Wi-Fi', 'เครื่องปรับอากาศ'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 301(COM)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 49,
                'location' => 'ชั้น 3 ห้อง 1',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 302(SmB)',
                'description' => 'ห้องเรียน',
                'capacity' => 50,
                'location' => 'ชั้น 3 ห้อง 2',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด','โต๊ะเรียน'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 303(Com)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 48,
                'location' => 'ชั้น 3 ห้อง 3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด',],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 304(Com)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 40,
                'location' => 'ชั้น 3 ห้อง 4',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด',],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 306(Com)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 50,
                'location' => 'ชั้น 3 ห้อง 5',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด',],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 402',
                'description' => 'ห้องประชุม',
                'capacity' => 40,
                'location' => 'ชั้น 4 ห้อง 2',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ','กระดานไวท์บอร์ด','โต๊ะ','เก้าอี้'],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 403(Com)',
                'description' => 'ห้องคอมพิวเตอร์',
                'capacity' => 24,
                'location' => 'ชั้น 4 ห้อง 3',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด',],
                'is_active' => true,
            ],
            [
                'name' => 'Sci9 405',
                'description' => 'ห้องเรียน',
                'capacity' => 48,
                'location' => 'ชั้น 4 ห้อง 5',
                'amenities' => ['ระบบเสียง', 'โปรเจคเตอร์ ', 'Wi-Fi', 'เครื่องปรับอากาศ', 'คอมพิวเตอร์','กระดานไวท์บอร์ด',],
                'is_active' => true,
            ],

        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
