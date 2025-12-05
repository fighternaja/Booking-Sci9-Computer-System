<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user', 'room']);

        $user = Auth::user();
        if ($user && $user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $room = Room::findOrFail($request->room_id);
        
        // Check for conflicts (ตรวจสอบทั้ง approved และ pending bookings)
        $conflict = Booking::where('room_id', $request->room_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                        ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                        ->orWhere(function($q) use ($request) {
                            $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                        });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'ห้องไม่ว่างในช่วงเวลาที่เลือก กรุณาเลือกช่วงเวลาอื่น'
            ], 400);
        }

        // ตรวจสอบ role ของ user
        $user = Auth::user();
        $status = ($user && $user->role === 'admin') ? 'approved' : 'pending';

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'status' => $status
        ]);

        $message = $status === 'approved' 
            ? 'จองห้องสำเร็จ' 
            : 'ส่งคำขอจองห้องแล้ว กรุณารอการอนุมัติจากผู้ดูแลระบบ';

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => $message
        ], 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room'])
        ]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'start_time' => 'sometimes|date|after:now',
            'end_time' => 'sometimes|date|after:start_time',
            'purpose' => 'sometimes|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $booking->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking updated successfully'
        ]);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }

    public function approve(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking approved successfully'
        ]);
    }

    public function reject(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking rejected successfully'
        ]);
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        
        // ตรวจสอบสิทธิ์: เจ้าของการจองหรือแอดมินเท่านั้น
        if (!$user || ($user->role !== 'admin' && $booking->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ตรวจสอบว่าการจองยังไม่ผ่านไปแล้ว
        if (new \DateTime($booking->start_time) < new \DateTime()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reschedule past bookings'
            ], 400);
        }

        // ตรวจสอบว่าการจองยังไม่ถูกยกเลิกหรือปฏิเสธ
        if (in_array($booking->status, ['cancelled', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reschedule cancelled or rejected bookings'
            ], 400);
        }

        $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'room_id' => 'sometimes|exists:rooms,id'
        ]);

        $roomId = $request->room_id ?? $booking->room_id;
        
        // ตรวจสอบความขัดแย้ง (ไม่นับการจองปัจจุบัน)
        $conflict = Booking::where('room_id', $roomId)
            ->where('id', '!=', $booking->id)
            ->where('status', 'approved')
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                        ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                        ->orWhere(function($q) use ($request) {
                            $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                        });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'ห้องไม่ว่างในช่วงเวลาที่เลือก กรุณาเลือกช่วงเวลาอื่น'
            ], 400);
        }

        // อัปเดตการจอง
        $booking->update([
            'room_id' => $roomId,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'approved' // อนุมัติอัตโนมัติเมื่อเลื่อนจอง
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking rescheduled successfully'
        ]);
    }
}
