<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ProfileController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes การจัดการข้อมูลสำหรับผู้ใช้งานทั่วไป
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/types', [RoomController::class, 'getRoomTypes']);
Route::get('/rooms/statuses', [RoomController::class, 'getStatuses']);
Route::get('/rooms/buildings', [RoomController::class, 'getBuildings']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/rooms/{room}/bookings', [RoomController::class, 'getBookings']);
Route::post('/rooms/{room}/check-availability', [RoomController::class, 'checkAvailability']);
Route::get('/stats', [StatsController::class, 'index']);

// Auth routes การจัดการข้อมูลสำหรับการลงทะเบียนและเข้าสู่ระบบ
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes การจัดการข้อมูลสำหรับผู้ใช้งาน
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [ProfileController::class, 'index']);
    Route::post('/user/update', [ProfileController::class, 'update']);
    
    // Booking routes การจัดการข้อมูลสำหรับการจองห้อง
    Route::apiResource('bookings', BookingController::class);
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve']);
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject']);
    
    // Admin routes การจัดการข้อมูลสำหรับผู้ดูแลระบบ
    Route::middleware('admin')->group(function () {
        // Admin dashboard กรอบข้อมูลสำหรับผู้ดูแลระบบ
        Route::get('/admin/dashboard', function () {
            $totalRooms = \App\Models\Room::count();
            $totalBookings = \App\Models\Booking::count();
            $pendingBookings = \App\Models\Booking::where('status', 'pending')->count();
            $approvedBookings = \App\Models\Booking::where('status', 'approved')->count();
            $rejectedBookings = \App\Models\Booking::where('status', 'rejected')->count();
            $totalUsers = \App\Models\User::count();
            $recentBookings = \App\Models\Booking::with(['user', 'room'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_rooms' => $totalRooms,
                    'total_bookings' => $totalBookings,
                    'pending_bookings' => $pendingBookings,
                    'approved_bookings' => $approvedBookings,
                    'rejected_bookings' => $rejectedBookings,
                    'total_users' => $totalUsers,
                    'recent_bookings' => $recentBookings
                ]
            ]);
        });
        
        // Admin room management จัดการห้อง
        Route::get('/admin/rooms', [RoomController::class, 'adminIndex']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
    });
});
