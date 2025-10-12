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
            ->with(['bookings' => function($query) {
                $query->where('status', 'approved')
                      ->where('start_time', '>=', now());
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    public function show(Room $room): JsonResponse
    {
        $room->load(['bookings' => function($query) {
            $query->where('status', 'approved')
                  ->where('start_time', '>=', now());
        }]);

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $rooms = Room::with(['bookings' => function($query) {
            $query->where('status', 'approved')
                  ->where('start_time', '>=', now());
        }])->get();

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
}
