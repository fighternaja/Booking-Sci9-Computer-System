<?php

namespace Database\Seeders;

use App\Models\BookingSetting;
use Illuminate\Database\Seeder;

class BookingSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = BookingSetting::getDefaults();

        foreach ($defaults as $key => $value) {
            BookingSetting::set($key, $value, $this->getDescription($key));
        }
    }

    private function getDescription($key)
    {
        $descriptions = [
            'max_hours_per_booking' => 'จำนวนชั่วโมงสูงสุดต่อการจอง',
            'min_hours_per_booking' => 'จำนวนชั่วโมงขั้นต่ำต่อการจอง',
            'allowed_time_start' => 'เวลาเริ่มต้นที่อนุญาตให้จอง',
            'allowed_time_end' => 'เวลาสิ้นสุดที่อนุญาตให้จอง',
            'max_bookings_per_day' => 'จำนวนการจองสูงสุดต่อวัน',
            'max_bookings_per_week' => 'จำนวนการจองสูงสุดต่อสัปดาห์',
            'max_advance_days' => 'จำนวนวันสูงสุดที่สามารถจองล่วงหน้า',
            'min_advance_hours' => 'จำนวนชั่วโมงขั้นต่ำที่ต้องจองล่วงหน้า',
            'allowed_weekdays' => 'วันในสัปดาห์ที่อนุญาตให้จอง (0=อาทิตย์, 6=เสาร์)',
            'require_approval' => 'ต้องการการอนุมัติก่อนใช้งาน'
        ];

        return $descriptions[$key] ?? null;
    }
}
