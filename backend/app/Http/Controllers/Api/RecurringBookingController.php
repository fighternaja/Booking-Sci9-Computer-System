<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringBooking;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingRestrictionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RecurringBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RecurringBooking::with(['user:id,name,email', 'room:id,name,image,location']);

        $user = Auth::user();
        if ($user && $user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $recurringBookings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $recurringBookings
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = RecurringBooking::query();
        
        if ($user && $user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        $total = $query->count();
        $active = $query->clone()->where('is_active', true)->count();
        $inactive = $query->clone()->where('is_active', false)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'recurrence_type' => 'required|in:daily,weekly,monthly,custom',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'max_occurrences' => 'nullable|integer|min:1|max:365',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'interval' => 'nullable|integer|min:1|max:12',
            'recurrence_pattern' => 'nullable|array'
        ]);

        $room = Room::findOrFail($request->room_id);
        $user = Auth::user();

        // ตรวจสอบข้อจำกัดสำหรับวันที่แรก
        $firstDate = Carbon::parse($request->start_date);
        $startDateTime = Carbon::parse($request->start_date . ' ' . $request->start_time);
        $endDateTime = Carbon::parse($request->start_date . ' ' . $request->end_time);

        if ($user->role === 'admin') {
            $validation = BookingRestrictionService::validateBookingForAdmin(
                $user,
                $room,
                $startDateTime,
                $endDateTime
            );
        } else {
            $validation = BookingRestrictionService::validateBooking(
                $user,
                $room,
                $startDateTime,
                $endDateTime
            );
        }

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => implode(', ', $validation['errors'])
            ], 400);
        }

        $recurringBooking = RecurringBooking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'recurrence_type' => $request->recurrence_type,
            'recurrence_pattern' => $request->recurrence_pattern,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'max_occurrences' => $request->max_occurrences,
            'days_of_week' => $request->days_of_week,
            'day_of_month' => $request->day_of_month,
            'interval' => $request->interval ?? 1,
            'is_active' => true
        ]);

        // สร้างการจองตาม pattern
        $generatedBookings = $recurringBooking->generateBookings();

        return response()->json([
            'success' => true,
            'data' => $recurringBooking->load(['user', 'room']),
            'generated_bookings' => count($generatedBookings),
            'message' => 'สร้างการจองซ้ำสำเร็จ และสร้างการจอง ' . count($generatedBookings) . ' ครั้ง'
        ], 201);
    }

    public function show(RecurringBooking $recurringBooking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $recurringBooking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $recurringBooking->load(['user', 'room', 'bookings']);

        return response()->json([
            'success' => true,
            'data' => $recurringBooking
        ]);
    }

    public function update(Request $request, RecurringBooking $recurringBooking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $recurringBooking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'purpose' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $recurringBooking->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $recurringBooking->load(['user', 'room']),
            'message' => 'อัปเดตการจองซ้ำสำเร็จ'
        ]);
    }

    public function destroy(RecurringBooking $recurringBooking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $recurringBooking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ยกเลิกการจองที่ยังไม่ผ่านไปแล้ว
        $recurringBooking->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '>', now())
            ->update(['status' => 'cancelled']);

        $recurringBooking->update(['is_active' => false]);
        $recurringBooking->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการจองซ้ำสำเร็จ'
        ]);
    }

    /**
     * สร้างการจองเพิ่มเติมจาก recurring booking
     */
    public function generateBookings(Request $request, RecurringBooking $recurringBooking): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $recurringBooking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $untilDate = $request->input('until_date');

        $generatedBookings = $recurringBooking->generateBookings($untilDate);

        return response()->json([
            'success' => true,
            'data' => [
                'generated_count' => count($generatedBookings),
                'bookings' => $generatedBookings
            ],
            'message' => 'สร้างการจอง ' . count($generatedBookings) . ' ครั้งสำเร็จ'
        ]);
    }


    /**
     * ตรวจสอบความขัดแย้งสำหรับการจองซ้ำ (Preview)
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'recurrence_type' => 'required|in:daily,weekly,monthly,custom',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'days_of_week' => 'nullable|array',
            'day_of_month' => 'nullable|integer',
            'interval' => 'nullable|integer',
        ]);

        // สร้าง instance ชั่วคราว (ยังไม่ save ลง DB)
        $recurringBooking = new RecurringBooking($request->all());
        $recurringBooking->user_id = Auth::id(); // สมมติUserปัจจุบัน

        // ดึงข้อมูล Preview
        $previewResults = $recurringBooking->previewBookings($request->end_date);

        // สรุปผล
        $total = count($previewResults);
        $available = count(array_filter($previewResults, fn($r) => $r['is_available']));
        $conflicts = $total - $available;

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total' => $total,
                    'available' => $available,
                    'conflicts' => $conflicts
                ],
                'dates' => $previewResults
            ]
        ]);
    }
}

