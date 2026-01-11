<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingAttendee;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookingAttendeeController extends Controller
{
    public function index(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $attendees = $booking->attendees()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $attendees
        ]);
    }

    public function store(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|required_without:user_id',
            'name' => 'nullable|string|max:255|required_without:user_id',
            'is_required' => 'nullable|boolean',
            'send_invitation' => 'nullable|boolean'
        ]);

        // ตรวจสอบว่ามี user_id หรือ email
        if (!$request->user_id && !$request->email) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ user_id หรือ email'
            ], 400);
        }

        // ตรวจสอบว่ามีผู้เข้าร่วมอยู่แล้วหรือไม่
        if ($request->user_id) {
            $existing = BookingAttendee::where('booking_id', $booking->id)
                ->where('user_id', $request->user_id)
                ->exists();
        } else {
            $existing = BookingAttendee::where('booking_id', $booking->id)
                ->where('email', $request->email)
                ->exists();
        }

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้เข้าร่วมนี้ถูกเพิ่มแล้ว'
            ], 400);
        }

        $attendee = BookingAttendee::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user_id,
            'email' => $request->email,
            'name' => $request->name,
            'is_required' => $request->is_required ?? false,
            'status' => 'pending',
            'invited_at' => now()
        ]);

        // ส่งอีเมลเชิญ (ถ้าร้องขอ)
        if ($request->send_invitation && $attendee->email) {
            $this->sendInvitationEmail($attendee);
        }

        return response()->json([
            'success' => true,
            'data' => $attendee->load('user'),
            'message' => 'เพิ่มผู้เข้าร่วมสำเร็จ'
        ], 201);
    }

    public function update(Request $request, Booking $booking, BookingAttendee $attendee): JsonResponse
    {
        $user = Auth::user();
        
        // เจ้าของการจอง, แอดมิน, หรือเจ้าของ attendee เท่านั้น
        if ($user && $user->role === 'user' && 
            $booking->user_id !== $user->id && 
            $attendee->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'sometimes|in:pending,accepted,declined,attended',
            'is_required' => 'sometimes|boolean'
        ]);

        $oldStatus = $attendee->status;
        $attendee->update($request->all());

        // อัปเดต responded_at เมื่อเปลี่ยนสถานะ
        if ($request->has('status') && $oldStatus === 'pending' && $request->status !== 'pending') {
            $attendee->update(['responded_at' => now()]);
        }

        // อัปเดต attended_at เมื่อเข้าร่วม
        if ($request->has('status') && $request->status === 'attended') {
            $attendee->update(['attended_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'data' => $attendee->load('user'),
            'message' => 'อัปเดตผู้เข้าร่วมสำเร็จ'
        ]);
    }

    public function destroy(Booking $booking, BookingAttendee $attendee): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $attendee->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบผู้เข้าร่วมสำเร็จ'
        ]);
    }

    /**
     * ตอบรับ/ปฏิเสธการเชิญ
     */
    public function respond(Request $request, BookingAttendee $attendee): JsonResponse
    {
        $user = Auth::user();
        
        // ตรวจสอบว่าเป็นเจ้าของ attendee หรือไม่
        if ($attendee->user_id && $attendee->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ตรวจสอบ email (สำหรับผู้ที่ไม่ได้เป็นสมาชิก)
        if (!$attendee->user_id && $attendee->email !== $user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,declined'
        ]);

        $attendee->update([
            'status' => $request->status,
            'responded_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $attendee->load(['user', 'booking']),
            'message' => $request->status === 'accepted' ? 'ยอมรับการเชิญสำเร็จ' : 'ปฏิเสธการเชิญสำเร็จ'
        ]);
    }

    /**
     * เช็คอินผู้เข้าร่วม
     */
    public function checkin(BookingAttendee $attendee): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($attendee->status === 'attended') {
            return response()->json([
                'success' => false,
                'message' => 'ผู้เข้าร่วมนี้เช็คอินแล้ว'
            ], 400);
        }

        $attendee->update([
            'status' => 'attended',
            'attended_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $attendee->load(['user', 'booking']),
            'message' => 'เช็คอินผู้เข้าร่วมสำเร็จ'
        ]);
    }

    /**
     * ส่งอีเมลเชิญผู้เข้าร่วม
     */
    public function sendInvitation(BookingAttendee $attendee): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user') {
            $booking = $attendee->booking;
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        if (!$attendee->email) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้เข้าร่วมนี้ไม่มีอีเมล'
            ], 400);
        }

        $this->sendInvitationEmail($attendee);
        $attendee->update(['invited_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'ส่งอีเมลเชิญสำเร็จ'
        ]);
    }

    /**
     * ส่งอีเมลเชิญ (private method)
     */
    private function sendInvitationEmail(BookingAttendee $attendee)
    {
        // TODO: Implement email sending
        // ตัวอย่าง:
        // Mail::to($attendee->email)->send(new BookingInvitation($attendee));
        
        // สำหรับตอนนี้แค่ log ไว้
        \Log::info('Sending invitation email to: ' . $attendee->email);
    }
}

