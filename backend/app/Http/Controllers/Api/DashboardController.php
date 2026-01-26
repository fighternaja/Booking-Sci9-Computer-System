<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalRooms = Room::count();
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $approvedBookings = Booking::where('status', 'approved')->count();
        $rejectedBookings = Booking::where('status', 'rejected')->count();
        $totalUsers = User::count();
        
        $recentBookings = Booking::with(['user:id,name,email', 'room:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Previous Logic
        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7);
        $monthAgo = now()->subMonth();

        $todayBookings = Booking::whereDate('start_time', today())->count();
        $weekBookings = Booking::where('start_time', '>=', $weekAgo)->count();
        $monthBookings = Booking::where('start_time', '>=', $monthAgo)->count();

        // Most used rooms (Top 5)
        $mostUsedRooms = Booking::select('room_id', DB::raw('count(*) as count'))
            ->with('room:id,name')
            ->groupBy('room_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->room ? $item->room->name : 'Unknown',
                    'count' => $item->count
                ];
            });

        // Top users
        $topUsers = Booking::select('user_id', DB::raw('count(*) as count'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->user ? $item->user->name : 'Unknown',
                    'count' => $item->count
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_rooms' => $totalRooms,
                'total_bookings' => $totalBookings,
                'pending_bookings' => $pendingBookings,
                'approved_bookings' => $approvedBookings,
                'rejected_bookings' => $rejectedBookings,
                'total_users' => $totalUsers,
                'recent_bookings' => $recentBookings,
                'today_bookings' => $todayBookings,
                'week_bookings' => $weekBookings,
                'month_bookings' => $monthBookings,
                'most_used_rooms' => $mostUsedRooms,
                'top_users' => $topUsers
            ]
        ]);
    }

    public function getChartsData()
    {
        // 1. Booking Trends (Last 30 Days)
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(29);
        
        $bookings = Booking::select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count'))
            ->whereBetween('start_time', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendData = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $record = $bookings->firstWhere('date', $dateStr);
            $trendData[] = [
                'date' => $currentDate->format('d/m'),
                'bookings' => $record ? $record->count : 0
            ];
            $currentDate->addDay();
        }

        // 2. Status Distribution
        $statusData = Booking::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->count
                ];
            });

        // 3. Peak Usage Hours
        $hourData = Booking::select(DB::raw('HOUR(start_time) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function($item) {
                return [
                    'hour' => sprintf('%02d:00', $item->hour),
                    'count' => $item->count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'trends' => $trendData,
                'status_distribution' => $statusData,
                'peak_hours' => $hourData
            ]
        ]);
    }
}
