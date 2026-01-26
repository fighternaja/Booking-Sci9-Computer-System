<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\WaitlistController;
use App\Http\Controllers\Api\BookingTemplateController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\BookingRestrictionController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\RecurringBookingController;
use App\Http\Controllers\Api\BookingAttendeeController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\BookingEquipmentController;
use App\Http\Controllers\Api\BookingSettingController;

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
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/bookings/{booking}/checkin', [BookingController::class, 'checkin']);
    Route::get('/bookings/{booking}/audit-logs', [BookingController::class, 'getAuditLogs']);
    Route::post('/bookings/bulk-approve', [BookingController::class, 'bulkApprove']);
    Route::post('/bookings/bulk-reject', [BookingController::class, 'bulkReject']);
    Route::post('/bookings/bulk-cancel', [BookingController::class, 'bulkCancel']);
    
    // Waitlist routes รายชื่อรอ
    Route::apiResource('waitlists', WaitlistController::class);
    Route::post('/waitlists/{waitlist}/book', [WaitlistController::class, 'checkAndBook']);
    
    // Booking Template routes เทมเพลตการจอง
    Route::apiResource('booking-templates', BookingTemplateController::class);
    Route::post('/booking-templates/{bookingTemplate}/book', [BookingTemplateController::class, 'bookFromTemplate']);
    
    // Review routes การให้คะแนนและรีวิว
    Route::apiResource('reviews', ReviewController::class);
    Route::get('/rooms/{room}/reviews', [ReviewController::class, 'getRoomReviews']);
    
    // Recurring Booking routes การจองซ้ำ
    Route::apiResource('recurring-bookings', RecurringBookingController::class);
    Route::post('/recurring-bookings/{recurringBooking}/generate', [RecurringBookingController::class, 'generateBookings']);
    
    // Booking Attendee routes ผู้เข้าร่วม
    Route::get('/bookings/{booking}/attendees', [BookingAttendeeController::class, 'index']);
    Route::post('/bookings/{booking}/attendees', [BookingAttendeeController::class, 'store']);
    Route::put('/bookings/{booking}/attendees/{attendee}', [BookingAttendeeController::class, 'update']);
    Route::delete('/bookings/{booking}/attendees/{attendee}', [BookingAttendeeController::class, 'destroy']);
    Route::post('/booking-attendees/{attendee}/respond', [BookingAttendeeController::class, 'respond']);
    Route::post('/booking-attendees/{attendee}/checkin', [BookingAttendeeController::class, 'checkin']);
    Route::post('/booking-attendees/{attendee}/send-invitation', [BookingAttendeeController::class, 'sendInvitation']);
    
    // Equipment routes (User) อุปกรณ์
    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::get('/bookings/{booking}/equipment', [BookingEquipmentController::class, 'index']);
    Route::post('/bookings/{booking}/equipment', [BookingEquipmentController::class, 'store']);
    Route::put('/bookings/{booking}/equipment/{equipment}', [BookingEquipmentController::class, 'update']);
    Route::delete('/bookings/{booking}/equipment/{equipment}', [BookingEquipmentController::class, 'destroy']);
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    
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
                ->limit(10)
                ->get();

            // Calculate additional stats (previously done on frontend)
            $today = now()->startOfDay();
            $weekAgo = now()->subDays(7);
            $monthAgo = now()->subMonth();

            $todayBookings = \App\Models\Booking::whereDate('start_time', today())->count();
            $weekBookings = \App\Models\Booking::where('start_time', '>=', $weekAgo)->count();
            $monthBookings = \App\Models\Booking::where('start_time', '>=', $monthAgo)->count();

            // Most used rooms
            $mostUsedRooms = \App\Models\Booking::select('room_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
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
            $topUsers = \App\Models\Booking::select('user_id', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
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
        });
        
        // Admin room management จัดการห้อง
        Route::get('/admin/rooms', [RoomController::class, 'adminIndex']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
        
        // Settings management จัดการตั้งค่าระบบ
        Route::get('/admin/settings', [SettingController::class, 'index']);
        Route::get('/admin/settings/{key}', [SettingController::class, 'show']);
        Route::post('/admin/settings', [SettingController::class, 'store']);
        Route::put('/admin/settings/{key}', [SettingController::class, 'update']);
        Route::delete('/admin/settings/{key}', [SettingController::class, 'destroy']);
        Route::get('/admin/settings/group/{group}', [SettingController::class, 'getByGroup']);
        
        // Audit logs ประวัติการเปลี่ยนแปลง
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/admin/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        
        // Booking Restrictions จัดการข้อจำกัดการจอง
        Route::get('/admin/booking-restrictions', [BookingRestrictionController::class, 'index']);
        Route::post('/admin/booking-restrictions/time', [BookingRestrictionController::class, 'updateTimeRestrictions']);
        Route::post('/admin/booking-restrictions/limits', [BookingRestrictionController::class, 'updateBookingLimits']);
        Route::post('/admin/booking-restrictions/roles', [BookingRestrictionController::class, 'updateRoleRestrictions']);

        // Booking Import
        Route::post('/admin/bookings/import', [BookingController::class, 'import']);
        
        // Equipment Management (Admin) จัดการอุปกรณ์
        Route::get('/admin/equipment', [EquipmentController::class, 'index']);
        Route::post('/admin/equipment', [EquipmentController::class, 'store']);
        Route::get('/admin/equipment/stats', [EquipmentController::class, 'stats']);
        Route::get('/admin/equipment/{equipment}', [EquipmentController::class, 'show']);
        Route::put('/admin/equipment/{equipment}', [EquipmentController::class, 'update']);
        Route::delete('/admin/equipment/{equipment}', [EquipmentController::class, 'destroy']);
        
        // Booking Settings จัดการการตั้งค่าการจอง
        Route::get('/admin/settings/booking-restrictions', [BookingSettingController::class, 'index']);
        Route::put('/admin/settings/booking-restrictions', [BookingSettingController::class, 'update']);
        Route::post('/admin/settings/booking-restrictions/reset', [BookingSettingController::class, 'reset']);
        
        // Export routes ส่งออกข้อมูล
        Route::get('/admin/exports/bookings/csv', [ExportController::class, 'exportBookingsCsv']);
        Route::get('/admin/exports/bookings/excel', [ExportController::class, 'exportBookingsExcel']);
        Route::get('/admin/exports/bookings/report', [ExportController::class, 'exportBookingReport']);
    });
    
    // Test restrictions (สำหรับผู้ใช้ทั่วไป)
    Route::post('/bookings/test-restrictions', [BookingRestrictionController::class, 'testRestrictions']);
});
