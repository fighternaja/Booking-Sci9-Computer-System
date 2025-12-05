<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $notifications = [];

        if ($user->role === 'admin') {
            // สำหรับ Admin: แสดงการจองที่รออนุมัติ
            $pendingBookings = Booking::with(['user', 'room'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($pendingBookings as $booking) {
                $notifications[] = [
                    'id' => $booking->id,
                    'type' => 'booking_pending',
                    'message' => "{$booking->user->name} ขอจองห้อง {$booking->room->name}",
                    'data' => [
                        'booking_id' => $booking->id,
                        'room_name' => $booking->room->name,
                        'user_name' => $booking->user->name,
                        'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                    ],
                    'created_at' => $booking->created_at->toISOString(),
                    'read_at' => null, // Admin notifications are always unread
                ];
            }
        } else {
            // สำหรับ User: แสดงสถานะการจองของตัวเอง
            // แสดงเฉพาะการจองที่ status เปลี่ยนไปแล้ว (approved/rejected)
            // และ updated_at ใหม่กว่า created_at (แสดงว่ามีการอัปเดต status)
            $userBookings = Booking::with(['room'])
                ->where('user_id', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->whereColumn('updated_at', '>', 'created_at') // status เปลี่ยนไปแล้ว
                ->where('updated_at', '>=', now()->subDays(7)) // แสดงแค่ 7 วันล่าสุด
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            // ดึง read status จาก cache/session
            $readBookings = cache()->get("user_{$user->id}_read_notifications", []);

            foreach ($userBookings as $booking) {
                $type = $booking->status === 'approved' ? 'booking_approved' : 'booking_rejected';
                $message = $booking->status === 'approved'
                    ? "การจองห้อง {$booking->room->name} ถูกอนุมัติแล้ว"
                    : "การจองห้อง {$booking->room->name} ถูกปฏิเสธ";

                $readAt = in_array($booking->id, $readBookings) ? $booking->updated_at->toISOString() : null;

                $notifications[] = [
                    'id' => $booking->id,
                    'type' => $type,
                    'message' => $message,
                    'data' => [
                        'booking_id' => $booking->id,
                        'room_name' => $booking->room->name,
                        'status' => $booking->status,
                        'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                    ],
                    'created_at' => $booking->updated_at->toISOString(),
                    'read_at' => $readAt,
                ];
            }
        }

        // นับจำนวน unread
        $unreadCount = 0;
        if ($user->role === 'user') {
            // นับการจองที่ status เปลี่ยนไปแล้วแต่ยังไม่ได้อ่าน
            $readBookings = cache()->get("user_{$user->id}_read_notifications", []);
            $unreadBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->whereColumn('updated_at', '>', 'created_at')
                ->where('updated_at', '>=', now()->subDays(7))
                ->whereNotIn('id', $readBookings)
                ->count();
            $unreadCount = $unreadBookings;
        } else {
            // สำหรับ admin นับ pending bookings
            $unreadCount = Booking::where('status', 'pending')->count();
        }

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // ตรวจสอบว่า booking นี้เป็นของ user นี้หรือไม่
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        if ($user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // เก็บ read status ใน cache (เก็บไว้ 30 วัน)
        $cacheKey = "user_{$user->id}_read_notifications";
        $readBookings = cache()->get($cacheKey, []);
        
        if (!in_array($id, $readBookings)) {
            $readBookings[] = $id;
            cache()->put($cacheKey, $readBookings, now()->addDays(30));
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
}

