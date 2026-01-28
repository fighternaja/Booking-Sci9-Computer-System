<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\BookingRestrictionService;
use App\Mail\BookingCreated;
use App\Mail\BookingApproved;
use App\Mail\BookingRejected;
use App\Mail\BookingCancelled;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Eager load equipment to solve N+1 problem on frontend
        $query = Booking::with(['user', 'room', 'equipment']);

        $user = Auth::user();
        if ($user && $user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            // Filter by date range (overlapping logic or simple range)
            // For general listing, start_time within range is usually sufficient
            // But for schedule, we might want overlapping.
            // Let's use simple start_time range for now as it's standard for lists.
            $query->where(function($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_time', [$request->start_date, $request->end_date]);
            });
        }

        // Support limit for pagination-like behavior without full pagination UI yet
        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'purpose' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'requires_checkin' => 'nullable|boolean',
                'auto_cancel_minutes' => 'nullable|integer|min:5|max:60'
            ]);

            // ตรวจสอบว่าเวลาเริ่มต้นต้องเป็นอนาคต (ใช้ Carbon เพื่อจัดการ timezone ได้ดีกว่า)
            try {
                $startTime = \Carbon\Carbon::parse($request->start_time);
                $endTime = \Carbon\Carbon::parse($request->end_time);
            } catch (\Exception $e) {
                \Log::error('Error parsing datetime in booking store: ' . $e->getMessage(), [
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'รูปแบบวันที่และเวลาไม่ถูกต้อง: ' . $e->getMessage()
                ], 400);
            }
            
            $now = \Carbon\Carbon::now();
            
            if ($startTime->lte($now)) {
                return response()->json([
                    'success' => false,
                    'message' => 'เวลาเริ่มต้นต้องเป็นอนาคต กรุณาเลือกเวลาที่ยังไม่ผ่านไป'
                ], 400);
            }

            if ($endTime->lte($startTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น'
                ], 400);
            }

            $room = Room::findOrFail($request->room_id);
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อน'
                ], 401);
            }
        
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
        // ตรวจสอบกรณีที่เวลาทับกันทั้งหมด:
        // 1. การจองใหม่เริ่มก่อนการจองเดิมเริ่ม แต่จบหลังการจองเดิมเริ่ม (ทับซ้อนด้านหน้า)
        // 2. การจองใหม่เริ่มระหว่างการจองเดิม (ทับซ้อนตรงกลาง)
        // 3. การจองใหม่เริ่มก่อนการจองเดิมจบ และจบหลังการจองเดิมจบ (ครอบคลุมทั้งหมด)
        // 4. การจองใหม่เริ่มหลังการจองเดิมเริ่ม แต่จบก่อนการจองเดิมจบ (อยู่ภายใน)
        $conflict = Booking::where('room_id', $request->room_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($startTime, $endTime) {
                $query
                    // กรณีที่ 1: การจองใหม่เริ่มก่อนการจองเดิมเริ่ม แต่จบระหว่างการจองเดิม
                    ->where(function($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $startTime)
                          ->where('end_time', '>', $startTime);
                    })
                    // กรณีที่ 2: การจองใหม่เริ่มระหว่างการจองเดิม
                    ->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_time', '>=', $startTime)
                          ->where('start_time', '<', $endTime);
                    })
                    // กรณีที่ 3: การจองเดิมครอบคลุมการจองใหม่ทั้งหมด
                    ->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    })
                    // กรณีที่ 4: การจองใหม่ครอบคลุมการจองเดิมทั้งหมด
                    ->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_time', '>', $startTime)
                          ->where('end_time', '<', $endTime);
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
        $status = ($user && $user->role === 'admin') ? 'approved' : 'pending';

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $request->room_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
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

        // ส่งอีเมลแจ้งเตือน
        if (Setting::get('email_notifications_enabled', false)) {
            try {
                $booking->load(['user', 'room']);
                Mail::to($booking->user->email)->send(new BookingCreated($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send booking created email: ' . $e->getMessage());
            }
        }

        $message = $status === 'approved' 
            ? 'จองห้องสำเร็จ' 
            : 'ส่งคำขอจองห้องแล้ว กรุณารอการอนุมัติจากผู้ดูแลระบบ';

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => $message
        ], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลที่ต้องการ'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error creating booking: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการจองห้อง: ' . $e->getMessage()
            ], 500);
        }
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

        // ส่งอีเมลแจ้งเตือน
        if (Setting::get('email_notifications_enabled', false) && Setting::get('notification_on_approval', true)) {
            try {
                $booking->load(['user', 'room']);
                Mail::to($booking->user->email)->send(new BookingApproved($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send booking approved email: ' . $e->getMessage());
            }
        }

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

        // ส่งอีเมลแจ้งเตือน
        if (Setting::get('email_notifications_enabled', false) && Setting::get('notification_on_rejection', true)) {
            try {
                $booking->load(['user', 'room']);
                Mail::to($booking->user->email)->send(new BookingRejected($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send booking rejected email: ' . $e->getMessage());
            }
        }

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
        // ตรวจสอบกรณีที่เวลาทับกันทั้งหมด
        $conflict = Booking::where('room_id', $roomId)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($request) {
                $query
                    // กรณีที่ 1: การจองใหม่เริ่มก่อนการจองเดิมเริ่ม แต่จบระหว่างการจองเดิม
                    ->where(function($q) use ($request) {
                        $q->where('start_time', '<', $request->start_time)
                          ->where('end_time', '>', $request->start_time);
                    })
                    // กรณีที่ 2: การจองใหม่เริ่มระหว่างการจองเดิม
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '>=', $request->start_time)
                          ->where('start_time', '<', $request->end_time);
                    })
                    // กรณีที่ 3: การจองเดิมครอบคลุมการจองใหม่ทั้งหมด
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    })
                    // กรณีที่ 4: การจองใหม่ครอบคลุมการจองเดิมทั้งหมด
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '>', $request->start_time)
                          ->where('end_time', '<', $request->end_time);
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
        
        // Parse datetime string ที่มี timezone offset และแปลงเป็น Carbon instance
        // Carbon::parse() จะ parse ISO 8601 string ที่มี timezone offset ได้ถูกต้อง
        try {
            // Parse datetime string
            // Use defaults or input timezone, do not force convert to UTC to avoid double shifting
            $startTime = \Carbon\Carbon::parse($request->start_time);
            $endTime = \Carbon\Carbon::parse($request->end_time);
            
            \Log::info('Parsed datetime for reschedule', [
                'original_start' => $request->start_time,
                'original_end' => $request->end_time,
                'parsed_start' => $startTime->toIso8601String(),
                'parsed_end' => $endTime->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error parsing datetime in reschedule', [
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'รูปแบบวันที่และเวลาไม่ถูกต้อง: ' . $e->getMessage()
            ], 400);
        }
        
        // อัปเดตการจอง
        $booking->update([
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'approved' // อนุมัติอัตโนมัติเมื่อเลื่อนจอง
        ]);
        
        // Refresh booking เพื่อให้ได้ข้อมูลล่าสุดพร้อม relationships
        $booking->refresh();
        $booking->load(['user', 'room']);
        $newValues = $booking->toArray();

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

        // ส่งอีเมลแจ้งเตือน
        if (Setting::get('email_notifications_enabled', false)) {
            try {
                $booking->load(['user', 'room']);
                Mail::to($booking->user->email)->send(new BookingCancelled($booking));
            } catch (\Exception $e) {
                \Log::error('Failed to send booking cancelled email: ' . $e->getMessage());
            }
        }

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

            // ส่งอีเมลแจ้งเตือน
            if (Setting::get('email_notifications_enabled', false) && Setting::get('notification_on_approval', true)) {
                try {
                    $booking->load(['user', 'room']);
                    Mail::to($booking->user->email)->send(new BookingApproved($booking));
                } catch (\Exception $e) {
                    \Log::error('Failed to send booking approved email: ' . $e->getMessage());
                }
            }
            
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

            // ส่งอีเมลแจ้งเตือน
            if (Setting::get('email_notifications_enabled', false) && Setting::get('notification_on_rejection', true)) {
                try {
                    $booking->load(['user', 'room']);
                    Mail::to($booking->user->email)->send(new BookingRejected($booking));
                } catch (\Exception $e) {
                    \Log::error('Failed to send booking rejected email: ' . $e->getMessage());
                }
            }
            
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

            // ส่งอีเมลแจ้งเตือน
            if (Setting::get('email_notifications_enabled', false)) {
                try {
                    $booking->load(['user', 'room']);
                    Mail::to($booking->user->email)->send(new BookingCancelled($booking));
                } catch (\Exception $e) {
                    \Log::error('Failed to send booking cancelled email: ' . $e->getMessage());
                }
            }
            
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

    /**
     * นำเข้าการจองจาก Excel
     */
    public function import(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        /*$request->validate([
            'bookings' => 'required|array',
            // 'bookings.*.room_id' => 'required|exists:rooms,id', // Removed strict check to allow name lookup
            'bookings.*.start_time' => 'required|date',
            // 'bookings.*.end_time' => 'required|date|after:bookings.*.start_time', // Disable strict time check
            'bookings.*.end_time' => 'required|date',
            'bookings.*.purpose' => 'required|string',
            'bookings.*.user_name' => 'nullable|string',
            'bookings.*.room_name' => 'nullable|string' // Added
        ]);*/

        $importedCount = 0;
        $errors = [];

        foreach ($request->bookings as $index => $bookingData) {
            try {
                // Resolved Room Logic
                $roomId = $bookingData['room_id'] ?? null;
                if (!$roomId && !empty($bookingData['room_name'])) {
                    // Try to find room by name (insensitive search roughly)
                    // Removing spaces and lowercase for better match
                    $normalizedName = strtolower(str_replace(' ', '', $bookingData['room_name']));
                    $allRooms = \App\Models\Room::all();
                    foreach ($allRooms as $r) {
                        if (strtolower(str_replace(' ', '', $r->name)) === $normalizedName) {
                            $roomId = $r->id;
                            break;
                        }
                    }
                }

                if (!$roomId) {
                    // Auto-create room if not found
                    $roomName = $bookingData['room_name'] ?? 'Unknown Room';
                    if (empty($bookingData['room_name'])) {
                        $roomName = 'ห้องระบุไม่ได้'; // Default name
                    }
                    
                    $newRoom = \App\Models\Room::create([
                        'name' => $roomName,
                        'description' => 'Imported Auto-created',
                        'capacity' => 0, // Default
                        'location' => 'Unknown'
                    ]);
                    $roomId = $newRoom->id;
                }
                
                // Verify room exists (if ID was passed)
                if (!\App\Models\Room::find($roomId)) {
                     // Should not happen if auto-created, but safety check
                     $errors[] = "แถวที่ " . ($index + 1) . ": ไม่พบห้อง ID: $roomId";
                     continue;
                }

                // Find user by name or use current admin
                $bookingUser = $user;
                if (!empty($bookingData['user_name'])) {
                    $foundUser = \App\Models\User::where('name', $bookingData['user_name'])->first();
                    if ($foundUser) {
                        $bookingUser = $foundUser;
                    }
                }

                $startTime = \Carbon\Carbon::parse($bookingData['start_time']);
                $endTime = \Carbon\Carbon::parse($bookingData['end_time']);

                // Auto-fix start_time == end_time or end < start
                if ($endTime->lte($startTime)) {
                    $endTime = $startTime->copy()->addHour(); // Default to 1 hour duration
                }

                // Check conflict
                $conflict = Booking::where('room_id', $roomId)
                    ->whereIn('status', ['approved', 'pending'])
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->exists();

                if ($conflict) {
                    $errors[] = "แถวที่ " . ($index + 1) . ": ห้องไม่ว่างช่วงเวลา " . $startTime->format('H:i') . " - " . $endTime->format('H:i');
                    continue;
                }

                $note = $bookingData['notes'] ?? '';
                // Append original user name if we are booking as admin on behalf of someone else
                if ($bookingUser->id === $user->id && !empty($bookingData['user_name']) && $bookingData['user_name'] !== $user->name) {
                    $note .= ($note ? " | " : "") . "ผู้จอง: " . $bookingData['user_name'];
                }

                $booking = Booking::create([
                    'user_id' => $bookingUser->id,
                    'room_id' => $roomId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'purpose' => $bookingData['purpose'],
                    'notes' => $note,
                    'status' => 'approved',
                ]);

                AuditLog::log(
                    'created',
                    $booking,
                    null,
                    $booking->toArray(),
                    "นำเข้าการจองห้อง #{$booking->id}"
                );

                $importedCount++;

            } catch (\Exception $e) {
                $errors[] = "แถวที่ " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "นำเข้าสำเร็จ {$importedCount} รายการ",
            'data' => [
                'imported_count' => $importedCount,
                'errors' => $errors
            ]
        ]);
    }
    /**
     * ล้างข้อมูลการจองตามช่วงเวลา
     */
    public function clear(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date', // Removed after_or_equal for simplicity in clearing
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $query = Booking::whereBetween('start_time', [
            $request->start_date . ' 00:00:00',
            $request->end_date . ' 23:59:59'
        ]);

        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }

        $count = $query->count();
        $query->delete();

        // Audit Log
        AuditLog::log(
            'deleted',
            $user, // Log against the admin user performed the action
            null,
            null,
            "ล้างข้อมูลการจองช่วงวันที่ {$request->start_date} ถึง {$request->end_date}" . ($request->room_id ? " (ห้อง ID: {$request->room_id})" : "") . " จำนวน $count รายการ"
        );

        return response()->json([
            'success' => true,
            'message' => "ลบข้อมูลการจองจำนวน $count รายการเรียบร้อยแล้ว",
            'deleted_count' => $count
        ]);
    }
}
