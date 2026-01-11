<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WaitlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Waitlist::with(['user', 'room']);

        $user = Auth::user();
        if ($user && $user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $waitlists = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $waitlists
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
            'auto_book' => 'nullable|boolean'
        ]);

        $room = Room::findOrFail($request->room_id);
        
        // ตรวจสอบว่าห้องว่างหรือไม่
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

        // ถ้าห้องว่าง ให้จองเลย ไม่ต้องเข้าลิสต์รอ
        if (!$conflict) {
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

            return response()->json([
                'success' => true,
                'data' => $booking->load(['user', 'room']),
                'message' => $status === 'approved' ? 'จองห้องสำเร็จ' : 'ส่งคำขอจองห้องแล้ว'
            ], 201);
        }

        // ถ้าห้องไม่ว่าง ให้เพิ่มในรายชื่อรอ
        $waitlist = Waitlist::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'auto_book' => $request->auto_book ?? false,
            'status' => 'waiting'
        ]);

        return response()->json([
            'success' => true,
            'data' => $waitlist->load(['user', 'room']),
            'message' => 'เพิ่มในรายชื่อรอแล้ว จะแจ้งเตือนเมื่อห้องว่าง'
        ], 201);
    }

    public function show(Waitlist $waitlist): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $waitlist->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $waitlist->load(['user', 'room'])
        ]);
    }

    public function destroy(Waitlist $waitlist): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $waitlist->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $waitlist->update(['status' => 'cancelled']);
        $waitlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกรายชื่อรอสำเร็จ'
        ]);
    }

    /**
     * ตรวจสอบและจองอัตโนมัติสำหรับรายชื่อรอ
     */
    public function checkAndBook(Waitlist $waitlist): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->role === 'user' && $waitlist->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($waitlist->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'รายชื่อรอนี้ไม่สามารถจองได้'
            ], 400);
        }

        // ตรวจสอบว่าห้องว่างหรือไม่
        $conflict = Booking::where('room_id', $waitlist->room_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($waitlist) {
                $query->whereBetween('start_time', [$waitlist->start_time, $waitlist->end_time])
                        ->orWhereBetween('end_time', [$waitlist->start_time, $waitlist->end_time])
                        ->orWhere(function($q) use ($waitlist) {
                            $q->where('start_time', '<=', $waitlist->start_time)
                            ->where('end_time', '>=', $waitlist->end_time);
                        });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'ห้องยังไม่ว่าง'
            ], 400);
        }

        // จองห้อง
        $user = Auth::user();
        $status = ($user && $user->role === 'admin') ? 'approved' : 'pending';

        $booking = Booking::create([
            'user_id' => $waitlist->user_id,
            'room_id' => $waitlist->room_id,
            'start_time' => $waitlist->start_time,
            'end_time' => $waitlist->end_time,
            'purpose' => $waitlist->purpose,
            'notes' => $waitlist->notes,
            'status' => $status
        ]);

        $waitlist->update([
            'status' => 'booked'
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'จองห้องสำเร็จ'
        ], 201);
    }

    /**
     * ตรวจสอบรายชื่อรอทั้งหมดและจองอัตโนมัติ (สำหรับ cron job)
     */
    public static function processWaitlists()
    {
        $waitlists = Waitlist::where('status', 'waiting')
            ->where('start_time', '>', now())
            ->with(['user', 'room'])
            ->get();

        foreach ($waitlists as $waitlist) {
            // ตรวจสอบว่าห้องว่างหรือไม่
            $conflict = Booking::where('room_id', $waitlist->room_id)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function($query) use ($waitlist) {
                    $query->whereBetween('start_time', [$waitlist->start_time, $waitlist->end_time])
                            ->orWhereBetween('end_time', [$waitlist->start_time, $waitlist->end_time])
                            ->orWhere(function($q) use ($waitlist) {
                                $q->where('start_time', '<=', $waitlist->start_time)
                                ->where('end_time', '>=', $waitlist->end_time);
                            });
                })
                ->exists();

            if (!$conflict) {
                // ถ้าเปิด auto_book ให้จองอัตโนมัติ
                if ($waitlist->auto_book) {
                    $status = $waitlist->user->role === 'admin' ? 'approved' : 'pending';
                    
                    $booking = Booking::create([
                        'user_id' => $waitlist->user_id,
                        'room_id' => $waitlist->room_id,
                        'start_time' => $waitlist->start_time,
                        'end_time' => $waitlist->end_time,
                        'purpose' => $waitlist->purpose,
                        'notes' => $waitlist->notes,
                        'status' => $status
                    ]);

                    $waitlist->update([
                        'status' => 'booked'
                    ]);

                    // ส่งการแจ้งเตือน (สามารถเพิ่มได้ภายหลัง)
                } else {
                    // แจ้งเตือนว่าห้องว่างแล้ว
                    $waitlist->update([
                        'status' => 'notified',
                        'notified_at' => now()
                    ]);

                    // ส่งการแจ้งเตือน (สามารถเพิ่มได้ภายหลัง)
                }
            }
        }
    }
}

