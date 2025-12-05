<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::where('is_active', true)
            ->where('room_type', '!=', 'general')
            ->with(['bookings' => function($query) {
                $query->where('status', 'approved')
                        ->where('start_time', '>=', now());
            }])
            ->get();

        // แก้ไข URL ของรูปภาพ
        $rooms->transform(function($room) {
            if ($room->image) {
                $room->image = 'storage/' . $room->image;
            }
            return $room;
        });

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    public function getRoomTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Room::getRoomTypes()
        ]);
    }

    public function getStatuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Room::getStatuses()
        ]);
    }

    public function getBuildings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Room::getBuildings()
        ]);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['bookings' => function($query) {
            $query  ->where('status', 'approved')
                    ->where('start_time', '>=', now());
        }]);

        // แก้ไข URL ของรูปภาพ
        if ($room->image) {
            $room->image = 'storage/' . $room->image;
        }

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $rooms = Room::with(['bookings' => function($query) {
            $query  ->where('status', 'approved')
                    ->where('start_time', '>=', now());
        }])->get();

        // แก้ไข URL ของรูปภาพ
        $rooms->transform(function($room) {
            if ($room->image) {
                $room->image = 'storage/' . $room->image;
            }
            return $room;
        });

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'amenities' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $roomData = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rooms', 'public');
            $roomData['image'] = $imagePath;
        }

        $room = Room::create($roomData);

        // แก้ไข URL ของรูปภาพ
        if ($room->image) {
            $room->image = 'storage/' . $room->image;
        }

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Room created successfully'
        ], 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'sometimes|string|max:255',
            'amenities' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $roomData = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('rooms', 'public');
            $roomData['image'] = $imagePath;
        }

        $room->update($roomData);

        // แก้ไข URL ของรูปภาพ
        if ($room->image) {
            $room->image = 'storage/' . $room->image;
        }

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Room updated successfully'
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    public function checkAvailability(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time'
        ]);

        $startTime = $request->start_time;
        $endTime = $request->end_time;

        // ตรวจสอบว่ามีการจองที่ทับซ้อนหรือไม่
        $conflictingBookings = $room->bookings()
            ->where('status', 'approved')
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->get();

        $isAvailable = $conflictingBookings->isEmpty();

        return response()->json([
            'success' => true,
            'data' => [
                'is_available' => $isAvailable,
                'conflicting_bookings' => $conflictingBookings->map(function($booking) {
                    return [
                        'id' => $booking->id,
                        'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                        'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                        'purpose' => $booking->purpose,
                        'user' => [
                            'name' => $booking->user->name,
                            'email' => $booking->user->email
                        ]
                    ];
                })
            ]
        ]);
    }

    public function getBookings(Request $request, Room $room): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m'
        ]);

        $query = $room->bookings()->where('status', 'approved');

        if ($request->date) {
            $date = $request->date;
            $query->whereDate('start_time', $date);
        } elseif ($request->month) {
            $month = $request->month;
            $query->whereYear('start_time', substr($month, 0, 4))
                  ->whereMonth('start_time', substr($month, 5, 2));
        } else {
            // ถ้าไม่ระบุ ให้แสดงการจองในเดือนปัจจุบัน
            $query->whereYear('start_time', now()->year)
                  ->whereMonth('start_time', now()->month);
        }

        $bookings = $query->with('user')->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'start_time' => $booking->start_time->format('Y-m-d H:i:s'),
                    'end_time' => $booking->end_time->format('Y-m-d H:i:s'),
                    'purpose' => $booking->purpose,
                    'user' => [
                        'name' => $booking->user->name,
                        'email' => $booking->user->email
                    ]
                ];
            })
        ]);
    }
}
