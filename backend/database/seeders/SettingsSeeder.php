<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'ระบบจองห้อง',
                'type' => 'string',
                'group' => 'general',
                'description' => 'ชื่อเว็บไซต์'
            ],
            [
                'key' => 'site_description',
                'value' => 'ระบบจัดการการจองห้องประชุม',
                'type' => 'string',
                'group' => 'general',
                'description' => 'คำอธิบายเว็บไซต์'
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'เปิดใช้งานโหมดบำรุงรักษา'
            ],

            // Booking Settings
            [
                'key' => 'booking_advance_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'จำนวนวันที่สามารถจองล่วงหน้าได้'
            ],
            [
                'key' => 'booking_min_duration_minutes',
                'value' => '15',
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'ระยะเวลาการจองขั้นต่ำ (นาที)'
            ],
            [
                'key' => 'booking_max_duration_minutes',
                'value' => '480',
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'ระยะเวลาการจองสูงสุด (นาที)'
            ],
            [
                'key' => 'booking_start_time',
                'value' => '08:00',
                'type' => 'string',
                'group' => 'booking',
                'description' => 'เวลาเริ่มต้นที่สามารถจองได้'
            ],
            [
                'key' => 'booking_end_time',
                'value' => '18:00',
                'type' => 'string',
                'group' => 'booking',
                'description' => 'เวลาสิ้นสุดที่สามารถจองได้'
            ],
            [
                'key' => 'auto_approve_admin',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'booking',
                'description' => 'อนุมัติอัตโนมัติสำหรับ Admin'
            ],
            // Time Restrictions
            [
                'key' => 'booking_allowed_days',
                'value' => json_encode([1, 2, 3, 4, 5]), // จันทร์-ศุกร์
                'type' => 'json',
                'group' => 'booking',
                'description' => 'วันที่สามารถจองได้ (0=อาทิตย์, 1=จันทร์, ..., 6=เสาร์)'
            ],
            [
                'key' => 'booking_holidays',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'booking',
                'description' => 'วันหยุดที่จองไม่ได้ (รูปแบบ: ["2024-01-01", "2024-12-25"])'
            ],
            // Booking Limits
            [
                'key' => 'booking_weekly_limit',
                'value' => null,
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'จำนวนการจองสูงสุดต่อสัปดาห์ (null = ไม่จำกัด)'
            ],
            [
                'key' => 'booking_monthly_limit',
                'value' => null,
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'จำนวนการจองสูงสุดต่อเดือน (null = ไม่จำกัด)'
            ],
            [
                'key' => 'booking_concurrent_limit',
                'value' => null,
                'type' => 'integer',
                'group' => 'booking',
                'description' => 'จำนวนการจองที่ทับซ้อนกันได้สูงสุด (null = ไม่จำกัด)'
            ],
            // Role-based Restrictions
            [
                'key' => 'booking_allowed_roles',
                'value' => json_encode(['admin', 'user']),
                'type' => 'json',
                'group' => 'booking',
                'description' => 'บทบาทที่สามารถจองได้'
            ],
            [
                'key' => 'role_room_restrictions',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'booking',
                'description' => 'ข้อจำกัดห้องตามบทบาท (รูปแบบ: {"user": [1, 2, 3]})'
            ],
            [
                'key' => 'role_room_type_restrictions',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'booking',
                'description' => 'ข้อจำกัดประเภทห้องตามบทบาท (รูปแบบ: {"user": ["meeting", "classroom"]})'
            ],

            // Notification Settings
            [
                'key' => 'email_notifications_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'เปิดใช้งานการแจ้งเตือนทางอีเมล'
            ],
            [
                'key' => 'reminder_before_hours',
                'value' => '1',
                'type' => 'integer',
                'group' => 'notification',
                'description' => 'จำนวนชั่วโมงที่เตือนก่อนการจองเริ่มต้น'
            ],
            [
                'key' => 'notification_on_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'แจ้งเตือนเมื่อการจองถูกอนุมัติ'
            ],
            [
                'key' => 'notification_on_rejection',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'แจ้งเตือนเมื่อการจองถูกปฏิเสธ'
            ],

            // System Settings
            [
                'key' => 'max_bookings_per_user',
                'value' => '10',
                'type' => 'integer',
                'group' => 'system',
                'description' => 'จำนวนการจองสูงสุดต่อผู้ใช้ (ต่อสัปดาห์)'
            ],
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'group' => 'system',
                'description' => 'เวลาหมดอายุของ Session (นาที)'
            ],
            [
                'key' => 'enable_audit_log',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'เปิดใช้งาน Audit Log'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

