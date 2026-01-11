<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use Carbon\Carbon;

class BookingRestrictionService
{
    /**
     * ตรวจสอบข้อจำกัดทั้งหมดก่อนจอง
     */
    public static function validateBooking($user, $room, $startTime, $endTime): array
    {
        $errors = [];

        // 1. ตรวจสอบข้อจำกัดเวลา
        $timeRestriction = self::checkTimeRestrictions($startTime, $endTime);
        if (!$timeRestriction['valid']) {
            $errors[] = $timeRestriction['message'];
        }

        // 2. ตรวจสอบข้อจำกัดการจอง
        $bookingLimits = self::checkBookingLimits($user, $startTime, $endTime);
        if (!$bookingLimits['valid']) {
            $errors[] = $bookingLimits['message'];
        }

        // 3. ตรวจสอบข้อจำกัดตามบทบาท
        $roleRestriction = self::checkRoleRestrictions($user, $room);
        if (!$roleRestriction['valid']) {
            $errors[] = $roleRestriction['message'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * ตรวจสอบข้อจำกัดเวลา
     */
    public static function checkTimeRestrictions($startTime, $endTime): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // ตรวจสอบช่วงเวลาที่จองได้
        $bookingStartTime = Setting::get('booking_start_time', '08:00');
        $bookingEndTime = Setting::get('booking_end_time', '18:00');
        
        if ($bookingStartTime && $bookingEndTime) {
            $allowedStart = Carbon::parse($start->format('Y-m-d') . ' ' . $bookingStartTime);
            $allowedEnd = Carbon::parse($start->format('Y-m-d') . ' ' . $bookingEndTime);
            
            if ($start->format('H:i') < $allowedStart->format('H:i') || 
                $end->format('H:i') > $allowedEnd->format('H:i')) {
                return [
                    'valid' => false,
                    'message' => "สามารถจองได้ในช่วงเวลา {$bookingStartTime} - {$bookingEndTime} เท่านั้น"
                ];
            }
        }

        // ตรวจสอบวันหยุด
        $holidays = Setting::get('booking_holidays', []);
        if (is_string($holidays)) {
            $holidays = json_decode($holidays, true) ?? [];
        }
        
        if (is_array($holidays) && !empty($holidays)) {
            $bookingDate = $start->format('Y-m-d');
            if (in_array($bookingDate, $holidays)) {
                return [
                    'valid' => false,
                    'message' => 'ไม่สามารถจองได้ในวันหยุด'
                ];
            }
        }

        // ตรวจสอบวันในสัปดาห์ที่จองได้
        $allowedDays = Setting::get('booking_allowed_days', []);
        if (is_string($allowedDays)) {
            $allowedDays = json_decode($allowedDays, true) ?? [];
        }
        
        if (is_array($allowedDays) && !empty($allowedDays)) {
            $dayOfWeek = $start->dayOfWeek; // 0 = Sunday, 6 = Saturday
            if (!in_array($dayOfWeek, $allowedDays)) {
                $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
                return [
                    'valid' => false,
                    'message' => "ไม่สามารถจองได้ในวัน{$dayNames[$dayOfWeek]}"
                ];
            }
        }

        // ตรวจสอบเวลาขั้นต่ำ/สูงสุดของการจอง
        $minDuration = Setting::get('booking_min_duration_minutes', 15);
        $maxDuration = Setting::get('booking_max_duration_minutes', 480); // 8 hours
        
        $duration = $start->diffInMinutes($end);
        
        if ($duration < $minDuration) {
            return [
                'valid' => false,
                'message' => "ระยะเวลาการจองขั้นต่ำคือ {$minDuration} นาที"
            ];
        }
        
        if ($duration > $maxDuration) {
            return [
                'valid' => false,
                'message' => "ระยะเวลาการจองสูงสุดคือ {$maxDuration} นาที"
            ];
        }

        return ['valid' => true];
    }

    /**
     * ตรวจสอบข้อจำกัดการจอง
     */
    public static function checkBookingLimits($user, $startTime, $endTime): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // ตรวจสอบจำนวนการจองต่อสัปดาห์
        $weeklyLimit = Setting::get('booking_weekly_limit', null);
        if ($weeklyLimit !== null) {
            $weekStart = $start->copy()->startOfWeek();
            $weekEnd = $start->copy()->endOfWeek();
            
            $weeklyBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'pending'])
                ->whereBetween('start_time', [$weekStart, $weekEnd])
                ->count();
            
            if ($weeklyBookings >= $weeklyLimit) {
                return [
                    'valid' => false,
                    'message' => "คุณได้จองครบ {$weeklyLimit} ครั้งต่อสัปดาห์แล้ว"
                ];
            }
        }

        // ตรวจสอบจำนวนการจองต่อเดือน
        $monthlyLimit = Setting::get('booking_monthly_limit', null);
        if ($monthlyLimit !== null) {
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();
            
            $monthlyBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'pending'])
                ->whereBetween('start_time', [$monthStart, $monthEnd])
                ->count();
            
            if ($monthlyBookings >= $monthlyLimit) {
                return [
                    'valid' => false,
                    'message' => "คุณได้จองครบ {$monthlyLimit} ครั้งต่อเดือนแล้ว"
                ];
            }
        }

        // ตรวจสอบระยะเวลาการจองล่วงหน้า
        $advanceBookingDays = Setting::get('booking_advance_days', null);
        if ($advanceBookingDays !== null) {
            $maxAdvanceDate = Carbon::now()->addDays($advanceBookingDays);
            if ($start->gt($maxAdvanceDate)) {
                return [
                    'valid' => false,
                    'message' => "สามารถจองล่วงหน้าได้ไม่เกิน {$advanceBookingDays} วัน"
                ];
            }
        }

        // ตรวจสอบจำนวนการจองพร้อมกัน
        $concurrentLimit = Setting::get('booking_concurrent_limit', null);
        if ($concurrentLimit !== null) {
            $concurrentBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function($q) use ($start, $end) {
                            $q->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                        });
                })
                ->count();
            
            if ($concurrentBookings >= $concurrentLimit) {
                return [
                    'valid' => false,
                    'message' => "คุณมี {$concurrentLimit} การจองที่ทับซ้อนกันอยู่แล้ว"
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * ตรวจสอบข้อจำกัดตามบทบาท
     */
    public static function checkRoleRestrictions($user, $room): array
    {
        // ตรวจสอบว่าบทบาทนี้สามารถจองได้หรือไม่
        $allowedRoles = Setting::get('booking_allowed_roles', []);
        if (is_string($allowedRoles)) {
            $allowedRoles = json_decode($allowedRoles, true) ?? [];
        }
        
        if (is_array($allowedRoles) && !empty($allowedRoles)) {
            if (!in_array($user->role, $allowedRoles)) {
                return [
                    'valid' => false,
                    'message' => 'บทบาทของคุณไม่มีสิทธิ์จองห้อง'
                ];
            }
        }

        // ตรวจสอบห้องที่จองได้ตามบทบาท
        $roleRoomRestrictions = Setting::get('role_room_restrictions', []);
        if (is_string($roleRoomRestrictions)) {
            $roleRoomRestrictions = json_decode($roleRoomRestrictions, true) ?? [];
        }
        
        if (is_array($roleRoomRestrictions) && !empty($roleRoomRestrictions)) {
            // ตรวจสอบว่ามีข้อจำกัดสำหรับบทบาทนี้หรือไม่
            if (isset($roleRoomRestrictions[$user->role])) {
                $allowedRoomIds = $roleRoomRestrictions[$user->role];
                if (!in_array($room->id, $allowedRoomIds)) {
                    return [
                        'valid' => false,
                        'message' => 'บทบาทของคุณไม่สามารถจองห้องนี้ได้'
                    ];
                }
            }
        }

        // ตรวจสอบประเภทห้องที่จองได้ตามบทบาท
        $roleRoomTypeRestrictions = Setting::get('role_room_type_restrictions', []);
        if (is_string($roleRoomTypeRestrictions)) {
            $roleRoomTypeRestrictions = json_decode($roleRoomTypeRestrictions, true) ?? [];
        }
        
        if (is_array($roleRoomTypeRestrictions) && !empty($roleRoomTypeRestrictions)) {
            if (isset($roleRoomTypeRestrictions[$user->role])) {
                $allowedRoomTypes = $roleRoomTypeRestrictions[$user->role];
                if (!in_array($room->room_type, $allowedRoomTypes)) {
                    return [
                        'valid' => false,
                        'message' => 'บทบาทของคุณไม่สามารถจองประเภทห้องนี้ได้'
                    ];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * ตรวจสอบข้อจำกัดเฉพาะสำหรับแอดมิน (ข้ามข้อจำกัดบางอย่าง)
     */
    public static function validateBookingForAdmin($user, $room, $startTime, $endTime): array
    {
        // แอดมินข้ามข้อจำกัดบางอย่าง แต่ยังตรวจสอบข้อจำกัดเวลาและวันหยุด
        $errors = [];

        // ตรวจสอบข้อจำกัดเวลา (แอดมินยังต้องทำตาม)
        $timeRestriction = self::checkTimeRestrictions($startTime, $endTime);
        if (!$timeRestriction['valid']) {
            $errors[] = $timeRestriction['message'];
        }

        // ตรวจสอบข้อจำกัดตามบทบาท (ถ้ามี)
        $roleRestriction = self::checkRoleRestrictions($user, $room);
        if (!$roleRestriction['valid']) {
            $errors[] = $roleRestriction['message'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

