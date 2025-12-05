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
        
        // Check for conflicts
        $conflict = Booking::where('room_id', $request->room_id)
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
                'message' => 'Room is not available for the selected time period'
            ], 400);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['user', 'room']),
            'message' => 'Booking created successfully'
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
}
