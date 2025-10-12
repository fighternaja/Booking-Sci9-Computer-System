<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalRooms = Room::where('is_active', true)->count();
        $totalUsers = User::count();
        $totalBookings = \App\Models\Booking::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_rooms' => $totalRooms,
                'total_users' => $totalUsers,
                'total_bookings' => $totalBookings
            ]
        ]);
    }
}
