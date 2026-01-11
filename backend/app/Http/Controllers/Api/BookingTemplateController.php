<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingTemplate;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BookingTemplate::with(['user', 'room'])
            ->where('user_id', Auth::id());

        $templates = $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'nullable|date_format:H:i',
            'duration' => 'nullable|integer|min:15|max:480', // 15 นาที ถึง 8 ชั่วโมง
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean'
        ]);

        // ถ้าเลือกเป็น default ให้ยกเลิก default ของเทมเพลตอื่น
        if ($request->is_default) {
            BookingTemplate::where('user_id', Auth::id())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $template = BookingTemplate::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'duration' => $request->duration,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'is_default' => $request->is_default ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $template->load(['user', 'room']),
            'message' => 'สร้างเทมเพลตสำเร็จ'
        ], 201);
    }

    public function show(BookingTemplate $bookingTemplate): JsonResponse
    {
        if ($bookingTemplate->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $bookingTemplate->load(['user', 'room'])
        ]);
    }

    public function update(Request $request, BookingTemplate $bookingTemplate): JsonResponse
    {
        if ($bookingTemplate->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'room_id' => 'nullable|exists:rooms,id',
            'start_time' => 'nullable|date_format:H:i',
            'duration' => 'nullable|integer|min:15|max:480',
            'purpose' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'is_default' => 'nullable|boolean'
        ]);

        // ถ้าเลือกเป็น default ให้ยกเลิก default ของเทมเพลตอื่น
        if ($request->has('is_default') && $request->is_default) {
            BookingTemplate::where('user_id', Auth::id())
                ->where('id', '!=', $bookingTemplate->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $bookingTemplate->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $bookingTemplate->load(['user', 'room']),
            'message' => 'อัปเดตเทมเพลตสำเร็จ'
        ]);
    }

    public function destroy(BookingTemplate $bookingTemplate): JsonResponse
    {
        if ($bookingTemplate->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $bookingTemplate->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบเทมเพลตสำเร็จ'
        ]);
    }

    /**
     * จองห้องด้วยเทมเพลต
     */
    public function bookFromTemplate(Request $request, BookingTemplate $bookingTemplate): JsonResponse
    {
        if ($bookingTemplate->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $date = $request->date;
        $roomId = $request->room_id ?? $bookingTemplate->room_id;

        if (!$roomId) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกห้อง'
            ], 400);
        }

        // สร้างการจองจากเทมเพลต
        $start = $request->start_time ?? ($bookingTemplate->start_time ? 
            \Carbon\Carbon::parse($date . ' ' . $bookingTemplate->start_time) : 
            \Carbon\Carbon::parse($date . ' 09:00'));
        
        $end = $request->end_time ?? ($bookingTemplate->duration ? 
            $start->copy()->addMinutes($bookingTemplate->duration) : 
            $start->copy()->addHour());

        // ตรวจสอบความขัดแย้ง
        $conflict = Booking::where('room_id', $roomId)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function($q) use ($start, $end) {
                            $q->where('start_time', '<=', $start)
                            ->where('end_time', '>=', $end);
                        });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'ห้องไม่ว่างในช่วงเวลาที่เลือก'
            ], 400);
        }

        $user = Auth::user();
        $status = ($user && $user->role === 'admin') ? 'approved' : 'pending';

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $roomId,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => $bookingTemplate->purpose,
            'notes' => $bookingTemplate->notes,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => $status === 'approved' ? 'จองห้องสำเร็จ' : 'ส่งคำขอจองห้องแล้ว'
        ], 201);
    }
}

