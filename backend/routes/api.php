<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\StatsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{room}', [RoomController::class, 'show']);
Route::get('/stats', [StatsController::class, 'index']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Booking routes
    Route::apiResource('bookings', BookingController::class);
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve']);
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        // Admin dashboard
        Route::get('/admin/dashboard', function () {
            $totalRooms = \App\Models\Room::count();
            $totalBookings = \App\Models\Booking::count();
            $pendingBookings = \App\Models\Booking::where('status', 'pending')->count();
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
                    'recent_bookings' => $recentBookings
                ]
            ]);
        });
        
        // Admin room management
        Route::get('/admin/rooms', [RoomController::class, 'adminIndex']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
    });
});
