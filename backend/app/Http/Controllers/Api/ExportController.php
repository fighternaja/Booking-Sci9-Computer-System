<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExportController extends Controller
{
    /**
     * ส่งออกข้อมูลการจองเป็น CSV
     */
    public function exportBookingsCsv(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Booking::with(['user', 'room']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('start_time', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('end_time', '<=', $request->to_date);
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $bookings = $query->orderBy('start_time', 'desc')->get();

        $filename = 'bookings_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'ผู้จอง',
                'อีเมล',
                'ห้อง',
                'วันที่เริ่ม',
                'เวลาที่เริ่ม',
                'วันที่สิ้นสุด',
                'เวลาที่สิ้นสุด',
                'วัตถุประสงค์',
                'หมายเหตุ',
                'สถานะ',
                'เหตุผลการยกเลิก',
                'เหตุผลการปฏิเสธ',
                'เหตุผลการอนุมัติ',
                'วันที่สร้าง',
                'วันที่อัปเดต'
            ]);

            // Data
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->id,
                    $booking->user->name ?? '',
                    $booking->user->email ?? '',
                    $booking->room->name ?? '',
                    $booking->start_time ? $booking->start_time->format('Y-m-d') : '',
                    $booking->start_time ? $booking->start_time->format('H:i') : '',
                    $booking->end_time ? $booking->end_time->format('Y-m-d') : '',
                    $booking->end_time ? $booking->end_time->format('H:i') : '',
                    $booking->purpose,
                    $booking->notes ?? '',
                    $this->getStatusThai($booking->status),
                    $booking->cancellation_reason ?? '',
                    $booking->rejection_reason ?? '',
                    $booking->approval_reason ?? '',
                    $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : '',
                    $booking->updated_at ? $booking->updated_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * ส่งออกข้อมูลการจองเป็น Excel (JSON format - ต้องใช้ library เช่น PhpSpreadsheet)
     */
    public function exportBookingsExcel(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Booking::with(['user', 'room']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('start_time', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('end_time', '<=', $request->to_date);
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $bookings = $query->orderBy('start_time', 'desc')->get();

        // Format data for Excel
        $data = $bookings->map(function($booking) {
            return [
                'ID' => $booking->id,
                'ผู้จอง' => $booking->user->name ?? '',
                'อีเมล' => $booking->user->email ?? '',
                'ห้อง' => $booking->room->name ?? '',
                'วันที่เริ่ม' => $booking->start_time ? $booking->start_time->format('Y-m-d') : '',
                'เวลาที่เริ่ม' => $booking->start_time ? $booking->start_time->format('H:i') : '',
                'วันที่สิ้นสุด' => $booking->end_time ? $booking->end_time->format('Y-m-d') : '',
                'เวลาที่สิ้นสุด' => $booking->end_time ? $booking->end_time->format('H:i') : '',
                'วัตถุประสงค์' => $booking->purpose,
                'หมายเหตุ' => $booking->notes ?? '',
                'สถานะ' => $this->getStatusThai($booking->status),
                'เหตุผลการยกเลิก' => $booking->cancellation_reason ?? '',
                'เหตุผลการปฏิเสธ' => $booking->rejection_reason ?? '',
                'เหตุผลการอนุมัติ' => $booking->approval_reason ?? '',
                'วันที่สร้าง' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : '',
                'วันที่อัปเดต' => $booking->updated_at ? $booking->updated_at->format('Y-m-d H:i:s') : ''
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'ข้อมูลพร้อมส่งออก (JSON format - ใช้ library เช่น PhpSpreadsheet สำหรับ Excel)'
        ]);
    }

    /**
     * ส่งออกข้อมูลการจองเป็น PDF (JSON format - ต้องใช้ library เช่น DomPDF)
     */
    public function exportBookingsPdf(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Booking::with(['user', 'room']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('start_time', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('end_time', '<=', $request->to_date);
        }

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $bookings = $query->orderBy('start_time', 'desc')->get();

        // Format data for PDF
        $data = [
            'title' => 'รายงานการจองห้อง',
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'total' => $bookings->count(),
            'bookings' => $bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'user' => $booking->user->name ?? '',
                    'email' => $booking->user->email ?? '',
                    'room' => $booking->room->name ?? '',
                    'start_time' => $booking->start_time ? $booking->start_time->format('d/m/Y H:i') : '',
                    'end_time' => $booking->end_time ? $booking->end_time->format('d/m/Y H:i') : '',
                    'purpose' => $booking->purpose,
                    'status' => $this->getStatusThai($booking->status),
                    'cancellation_reason' => $booking->cancellation_reason ?? '',
                    'rejection_reason' => $booking->rejection_reason ?? '',
                    'approval_reason' => $booking->approval_reason ?? ''
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'ข้อมูลพร้อมส่งออก (JSON format - ใช้ library เช่น DomPDF สำหรับ PDF)'
        ]);
    }

    /**
     * ส่งออกรายงานการจอง (สรุปข้อมูล)
     */
    public function exportBookingReport(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $bookings = Booking::with(['user', 'room'])
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        $report = [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate
            ],
            'summary' => [
                'total_bookings' => $bookings->count(),
                'approved' => $bookings->where('status', 'approved')->count(),
                'pending' => $bookings->where('status', 'pending')->count(),
                'rejected' => $bookings->where('status', 'rejected')->count(),
                'cancelled' => $bookings->where('status', 'cancelled')->count(),
            ],
            'by_room' => $bookings->groupBy('room_id')->map(function($roomBookings) {
                $room = $roomBookings->first()->room;
                return [
                    'room_name' => $room->name ?? '',
                    'total' => $roomBookings->count(),
                    'approved' => $roomBookings->where('status', 'approved')->count(),
                    'pending' => $roomBookings->where('status', 'pending')->count(),
                    'rejected' => $roomBookings->where('status', 'rejected')->count(),
                    'cancelled' => $roomBookings->where('status', 'cancelled')->count(),
                ];
            })->values(),
            'by_user' => $bookings->groupBy('user_id')->map(function($userBookings) {
                $user = $userBookings->first()->user;
                return [
                    'user_name' => $user->name ?? '',
                    'user_email' => $user->email ?? '',
                    'total' => $userBookings->count(),
                    'approved' => $userBookings->where('status', 'approved')->count(),
                    'pending' => $userBookings->where('status', 'pending')->count(),
                    'rejected' => $userBookings->where('status', 'rejected')->count(),
                    'cancelled' => $userBookings->where('status', 'cancelled')->count(),
                ];
            })->values(),
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
        ];

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * แปลงสถานะเป็นภาษาไทย
     */
    private function getStatusThai($status)
    {
        $statuses = [
            'pending' => 'รออนุมัติ',
            'approved' => 'อนุมัติ',
            'rejected' => 'ปฏิเสธ',
            'cancelled' => 'ยกเลิก'
        ];

        return $statuses[$status] ?? $status;
    }
}

