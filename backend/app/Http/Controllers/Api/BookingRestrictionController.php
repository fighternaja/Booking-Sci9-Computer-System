<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BookingRestrictionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingRestrictionController extends Controller
{
    /**
     * ดึงข้อมูลข้อจำกัดทั้งหมด
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $restrictions = [
            // Time Restrictions
            'time_restrictions' => [
                'booking_start_time' => Setting::get('booking_start_time', '08:00'),
                'booking_end_time' => Setting::get('booking_end_time', '18:00'),
                'booking_allowed_days' => Setting::get('booking_allowed_days', [1, 2, 3, 4, 5]),
                'booking_holidays' => Setting::get('booking_holidays', []),
                'booking_min_duration_minutes' => Setting::get('booking_min_duration_minutes', 15),
                'booking_max_duration_minutes' => Setting::get('booking_max_duration_minutes', 480),
            ],
            // Booking Limits
            'booking_limits' => [
                'booking_weekly_limit' => Setting::get('booking_weekly_limit'),
                'booking_monthly_limit' => Setting::get('booking_monthly_limit'),
                'booking_advance_days' => Setting::get('booking_advance_days', 30),
                'booking_concurrent_limit' => Setting::get('booking_concurrent_limit'),
            ],
            // Role-based Restrictions
            'role_restrictions' => [
                'booking_allowed_roles' => Setting::get('booking_allowed_roles', ['admin', 'user']),
                'role_room_restrictions' => Setting::get('role_room_restrictions', []),
                'role_room_type_restrictions' => Setting::get('role_room_type_restrictions', []),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $restrictions
        ]);
    }

    /**
     * อัปเดตข้อจำกัดเวลา
     */
    public function updateTimeRestrictions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_start_time' => 'sometimes|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'booking_end_time' => 'sometimes|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'booking_allowed_days' => 'sometimes|array',
            'booking_allowed_days.*' => 'integer|min:0|max:6',
            'booking_holidays' => 'sometimes|array',
            'booking_holidays.*' => 'date',
            'booking_min_duration_minutes' => 'sometimes|integer|min:1|max:1440',
            'booking_max_duration_minutes' => 'sometimes|integer|min:1|max:1440',
        ]);

        if ($request->has('booking_start_time')) {
            Setting::set('booking_start_time', $request->booking_start_time, 'string', 'booking', 'เวลาเริ่มต้นที่สามารถจองได้');
        }

        if ($request->has('booking_end_time')) {
            Setting::set('booking_end_time', $request->booking_end_time, 'string', 'booking', 'เวลาสิ้นสุดที่สามารถจองได้');
        }

        if ($request->has('booking_allowed_days')) {
            Setting::set('booking_allowed_days', json_encode($request->booking_allowed_days), 'json', 'booking', 'วันที่สามารถจองได้');
        }

        if ($request->has('booking_holidays')) {
            Setting::set('booking_holidays', json_encode($request->booking_holidays), 'json', 'booking', 'วันหยุดที่จองไม่ได้');
        }

        if ($request->has('booking_min_duration_minutes')) {
            Setting::set('booking_min_duration_minutes', $request->booking_min_duration_minutes, 'integer', 'booking', 'ระยะเวลาการจองขั้นต่ำ (นาที)');
        }

        if ($request->has('booking_max_duration_minutes')) {
            Setting::set('booking_max_duration_minutes', $request->booking_max_duration_minutes, 'integer', 'booking', 'ระยะเวลาการจองสูงสุด (นาที)');
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตข้อจำกัดเวลาสำเร็จ'
        ]);
    }

    /**
     * อัปเดตข้อจำกัดการจอง
     */
    public function updateBookingLimits(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_weekly_limit' => 'nullable|integer|min:1',
            'booking_monthly_limit' => 'nullable|integer|min:1',
            'booking_advance_days' => 'sometimes|integer|min:1|max:365',
            'booking_concurrent_limit' => 'nullable|integer|min:1',
        ]);

        if ($request->has('booking_weekly_limit')) {
            Setting::set('booking_weekly_limit', $request->booking_weekly_limit, 'integer', 'booking', 'จำนวนการจองสูงสุดต่อสัปดาห์');
        }

        if ($request->has('booking_monthly_limit')) {
            Setting::set('booking_monthly_limit', $request->booking_monthly_limit, 'integer', 'booking', 'จำนวนการจองสูงสุดต่อเดือน');
        }

        if ($request->has('booking_advance_days')) {
            Setting::set('booking_advance_days', $request->booking_advance_days, 'integer', 'booking', 'จำนวนวันที่สามารถจองล่วงหน้าได้');
        }

        if ($request->has('booking_concurrent_limit')) {
            Setting::set('booking_concurrent_limit', $request->booking_concurrent_limit, 'integer', 'booking', 'จำนวนการจองที่ทับซ้อนกันได้สูงสุด');
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตข้อจำกัดการจองสำเร็จ'
        ]);
    }

    /**
     * อัปเดตข้อจำกัดตามบทบาท
     */
    public function updateRoleRestrictions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_allowed_roles' => 'sometimes|array',
            'booking_allowed_roles.*' => 'string|in:admin,user',
            'role_room_restrictions' => 'sometimes|array',
            'role_room_type_restrictions' => 'sometimes|array',
        ]);

        if ($request->has('booking_allowed_roles')) {
            Setting::set('booking_allowed_roles', json_encode($request->booking_allowed_roles), 'json', 'booking', 'บทบาทที่สามารถจองได้');
        }

        if ($request->has('role_room_restrictions')) {
            Setting::set('role_room_restrictions', json_encode($request->role_room_restrictions), 'json', 'booking', 'ข้อจำกัดห้องตามบทบาท');
        }

        if ($request->has('role_room_type_restrictions')) {
            Setting::set('role_room_type_restrictions', json_encode($request->role_room_type_restrictions), 'json', 'booking', 'ข้อจำกัดประเภทห้องตามบทบาท');
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตข้อจำกัดตามบทบาทสำเร็จ'
        ]);
    }

    /**
     * ทดสอบข้อจำกัดสำหรับการจอง
     */
    public function testRestrictions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
        ]);

        $room = \App\Models\Room::findOrFail($request->room_id);

        if ($user->role === 'admin') {
            $validation = BookingRestrictionService::validateBookingForAdmin(
                $user,
                $room,
                $request->start_time,
                $request->end_time
            );
        } else {
            $validation = BookingRestrictionService::validateBooking(
                $user,
                $room,
                $request->start_time,
                $request->end_time
            );
        }

        return response()->json([
            'success' => true,
            'data' => $validation
        ]);
    }
}

