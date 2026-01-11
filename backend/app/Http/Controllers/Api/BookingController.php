<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\AuditLog;
use App\Services\BookingRestrictionService;
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
            'notes' => 'nullable|string',
            'requires_checkin' => 'nullable|boolean',
            'auto_cancel_minutes' => 'nullable|integer|min:5|max:60'
        ]);

        $room = Room::findOrFail($request->room_id);
        $user = Auth::user();
        
        // ตรวจสอบข้อจำกัดและกฎเกณฑ์
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

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ], 400);
        }
        
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
            'status' => $status,
            'requires_checkin' => $request->requires_checkin ?? false,
            'auto_cancel_minutes' => $request->auto_cancel_minutes
        ]);

        // บันทึก audit log
        AuditLog::log(
            'created',
            $booking,
            null,
            $booking->toArray(),
            "สร้างการจองห้อง #{$booking->id} - ห้อง: {$room->name}"
        );

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

        $booking->load(['user', 'room', 'auditLogs.user']);

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * ดึงประวัติการเปลี่ยนแปลงของ booking
     */
    public function getAuditLogs(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $logs = $booking->auditLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
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

        $oldValues = $booking->toArray();
        $booking->update($request->all());
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'updated',
            $booking,
            $oldValues,
            $newValues,
            "อัปเดตการจองห้อง #{$booking->id}"
        );

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

        $oldValues = $booking->toArray();
        
        // บันทึก audit log
        AuditLog::log(
            'deleted',
            $booking,
            $oldValues,
            null,
            "ลบการจองห้อง #{$booking->id}"
        );

        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'approval_reason' => 'nullable|string|max:500'
        ]);

        $oldValues = $booking->toArray();
        $booking->update([
            'status' => 'approved',
            'approval_reason' => $request->approval_reason
        ]);
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'approved',
            $booking,
            $oldValues,
            $newValues,
            "อนุมัติการจองห้อง #{$booking->id}" . ($request->approval_reason ? " - เหตุผล: {$request->approval_reason}" : '')
        );

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking approved successfully'
        ]);
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $oldValues = $booking->toArray();
        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'rejected',
            $booking,
            $oldValues,
            $newValues,
            "ปฏิเสธการจองห้อง #{$booking->id} - เหตุผล: {$request->rejection_reason}"
        );

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
        $room = Room::findOrFail($roomId);
        
        // ตรวจสอบข้อจำกัดและกฎเกณฑ์
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

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ], 400);
        }
        
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

        $oldValues = $booking->toArray();
        
        // อัปเดตการจอง
        $booking->update([
            'room_id' => $roomId,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'approved' // อนุมัติอัตโนมัติเมื่อเลื่อนจอง
        ]);
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'rescheduled',
            $booking,
            $oldValues,
            $newValues,
            "เลื่อนการจองห้อง #{$booking->id} - ห้อง: {$room->name}"
        );

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking rescheduled successfully'
        ]);
    }

    /**
     * เช็คอินการจอง
     */
    public function checkin(Booking $booking): JsonResponse
    {
        $user = Auth::user();
        
        // เจ้าของการจองหรือแอดมินเท่านั้นที่เช็คอินได้
        if (!$user || ($user->role !== 'admin' && $booking->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ตรวจสอบว่าการจองต้องเช็คอิน
        if (!$booking->requires_checkin) {
            return response()->json([
                'success' => false,
                'message' => 'การจองนี้ไม่ต้องเช็คอิน'
            ], 400);
        }

        // ตรวจสอบว่ายังไม่เช็คอิน
        if ($booking->checked_in_at) {
            return response()->json([
                'success' => false,
                'message' => 'เช็คอินแล้ว'
            ], 400);
        }

        // ตรวจสอบว่าการจองยังไม่ผ่านไปแล้ว
        if ($booking->end_time < now()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเช็คอินการจองที่ผ่านไปแล้วได้'
            ], 400);
        }

        $oldValues = $booking->toArray();
        $booking->update([
            'checked_in_at' => now()
        ]);
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'checked_in',
            $booking,
            $oldValues,
            $newValues,
            "เช็คอินการจองห้อง #{$booking->id}"
        );

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'เช็คอินสำเร็จ'
        ]);
    }

    /**
     * ยกเลิกการจอง
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $user = Auth::user();
        
        // เจ้าของการจองหรือแอดมินเท่านั้นที่ยกเลิกได้
        if (!$user || ($user->role !== 'admin' && $booking->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ตรวจสอบว่าการจองยังไม่ผ่านไปแล้ว
        if ($booking->end_time < now()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกการจองที่ผ่านไปแล้วได้'
            ], 400);
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        $oldValues = $booking->toArray();
        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason
        ]);
        $newValues = $booking->fresh()->toArray();

        // บันทึก audit log
        AuditLog::log(
            'cancelled',
            $booking,
            $oldValues,
            $newValues,
            "ยกเลิกการจองห้อง #{$booking->id}" . ($request->cancellation_reason ? " - เหตุผล: {$request->cancellation_reason}" : '')
        );

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'ยกเลิกการจองสำเร็จ'
        ]);
    }

    /**
     * อนุมัติหลายการจองพร้อมกัน
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id'
        ]);

        $bookings = Booking::whereIn('id', $request->booking_ids)
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;
        foreach ($bookings as $booking) {
            $oldValues = $booking->toArray();
            $booking->update(['status' => 'approved']);
            $newValues = $booking->fresh()->toArray();
            
            // บันทึก audit log
            AuditLog::log(
                'approved',
                $booking,
                $oldValues,
                $newValues,
                "อนุมัติการจองห้อง #{$booking->id} (Bulk operation)"
            );
            
            $approvedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "อนุมัติ {$approvedCount} การจองสำเร็จ",
            'data' => [
                'approved_count' => $approvedCount,
                'total_requested' => count($request->booking_ids)
            ]
        ]);
    }

    /**
     * ปฏิเสธหลายการจองพร้อมกัน
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id'
        ]);

        $bookings = Booking::whereIn('id', $request->booking_ids)
            ->where('status', 'pending')
            ->get();

        $rejectedCount = 0;
        foreach ($bookings as $booking) {
            $oldValues = $booking->toArray();
            $booking->update(['status' => 'rejected']);
            $newValues = $booking->fresh()->toArray();
            
            // บันทึก audit log
            AuditLog::log(
                'rejected',
                $booking,
                $oldValues,
                $newValues,
                "ปฏิเสธการจองห้อง #{$booking->id} (Bulk operation)"
            );
            
            $rejectedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "ปฏิเสธ {$rejectedCount} การจองสำเร็จ",
            'data' => [
                'rejected_count' => $rejectedCount,
                'total_requested' => count($request->booking_ids)
            ]
        ]);
    }

    /**
     * ยกเลิกหลายการจองพร้อมกัน
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_ids' => 'required|array',
            'booking_ids.*' => 'exists:bookings,id'
        ]);

        $query = Booking::whereIn('id', $request->booking_ids);
        
        // ถ้าไม่ใช่แอดมิน ตรวจสอบว่าเป็นเจ้าของการจอง
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $bookings = $query->whereIn('status', ['pending', 'approved'])->get();

        $cancelledCount = 0;
        foreach ($bookings as $booking) {
            $oldValues = $booking->toArray();
            $booking->update(['status' => 'cancelled']);
            $newValues = $booking->fresh()->toArray();
            
            // บันทึก audit log
            AuditLog::log(
                'cancelled',
                $booking,
                $oldValues,
                $newValues,
                "ยกเลิกการจองห้อง #{$booking->id} (Bulk operation)"
            );
            
            $cancelledCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "ยกเลิก {$cancelledCount} การจองสำเร็จ",
            'data' => [
                'cancelled_count' => $cancelledCount,
                'total_requested' => count($request->booking_ids)
            ]
        ]);
    }
}
