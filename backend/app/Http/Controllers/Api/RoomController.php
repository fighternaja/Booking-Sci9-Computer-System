<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index()
    {
        try {
            // Query rooms that are active, excluding 'general' room type
            $rooms = Room::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('room_type')
                          ->orWhere('room_type', '!=', 'general');
                })
                ->get();

            // Transform image URLs
            $rooms->transform(function($room) {
                if (!empty($room->image) && strpos($room->image, 'storage/') !== 0) {
                    $room->image = 'storage/' . $room->image;
                }
                return $room;
            });

            return response()->json([
                'success' => true,
                'data' => $rooms->values()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching rooms: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching rooms: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }

    public function getRoomTypes()
    {
        return response()->json([
            'success' => true,
            'data' => Room::getRoomTypes()
        ]);
    }

    public function getStatuses()
    {
        return response()->json([
            'success' => true,
            'data' => Room::getStatuses()
        ]);
    }

    public function getBuildings()
    {
        return response()->json([
            'success' => true,
            'data' => Room::getBuildings()
        ]);
    }

    public function show(Room $room)
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

    public function adminIndex()
    {
        $rooms = Room::with(['bookings' => function($query) {
            $query  ->where('status', 'approved')
                    ->where('start_time', '>=', now());
        }])->get();

        // แก้ไข URL ของรูปภาพ
        $rooms->transform(function($room) {
            if ($room->image && strpos($room->image, 'storage/') !== 0) {
                $room->image = 'storage/' . $room->image;
            }
            return $room;
        });

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'capacity' => 'required|integer|min:1',
                'location' => 'required|string|max:255',
                'amenities' => 'nullable|array',
                'image' => 'nullable|file|max:5120' // 5MB
            ]);

            $roomData = $request->except('image');
            
            if ($request->hasFile('image')) {
                $fileUploadService = new FileUploadService();
                $uploadResult = $fileUploadService->uploadImage(
                    $request->file('image'),
                    'rooms',
                    true // สร้าง thumbnail
                );
                $roomData['image'] = $uploadResult['path'];
            }

            $room = Room::create($roomData);

            // แก้ไข URL ของรูปภาพ
            if ($room->image) {
                $room->image = 'storage/' . $room->image;
            }

            return response()->json([
                'success' => true,
                'data' => $room,
                'message' => 'สร้างห้องสำเร็จ'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, Room $room)
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'capacity' => 'sometimes|integer|min:1',
                'location' => 'sometimes|string|max:255',
                'amenities' => 'nullable|array',
                'image' => 'nullable|file|max:5120', // 5MB
                'is_active' => 'sometimes|boolean'
            ]);

            $roomData = $request->except('image');
            
            if ($request->hasFile('image')) {
                $fileUploadService = new FileUploadService();
                
                // ลบรูปภาพเก่า
                if ($room->image) {
                    $fileUploadService->deleteFile($room->image);
                    // ลบ thumbnail ด้วย
                    $thumbnailPath = dirname($room->image) . '/thumbnails/' . basename($room->image);
                    $fileUploadService->deleteFile($thumbnailPath);
                }
                
                // อัปโหลดรูปภาพใหม่
                $uploadResult = $fileUploadService->uploadImage(
                    $request->file('image'),
                    'rooms',
                    true // สร้าง thumbnail
                );
                $roomData['image'] = $uploadResult['path'];
            }

            $room->update($roomData);

            // แก้ไข URL ของรูปภาพ
            if ($room->image) {
                $room->image = 'storage/' . $room->image;
            }

            return response()->json([
                'success' => true,
                'data' => $room,
                'message' => 'อัปเดตห้องสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Room $room)
    {
        try {
            // ลบรูปภาพถ้ามี
            if ($room->image) {
                $fileUploadService = new FileUploadService();
                $fileUploadService->deleteFile($room->image);
                // ลบ thumbnail ด้วย
                $thumbnailPath = dirname($room->image) . '/thumbnails/' . basename($room->image);
                $fileUploadService->deleteFile($thumbnailPath);
            }
            
            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบห้องสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 400);
        }
    }

    public function checkAvailability(Request $request, Room $room)
    {
        try {
            $request->validate([
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'exclude_booking_id' => 'nullable|exists:bookings,id'
            ]);

            $startTime = $request->start_time;
            $endTime = $request->end_time;
            $excludeBookingId = $request->exclude_booking_id;

            // ตรวจสอบว่าเวลาเริ่มต้นไม่เป็นอดีต (ยืดหยุ่นขึ้น - อนุญาตให้ตรวจสอบได้แม้เวลาจะใกล้เคียงกับปัจจุบัน)
            $startDateTime = new \DateTime($startTime);
            $now = new \DateTime();
            $now->modify('-5 minutes'); // อนุญาตให้ตรวจสอบได้แม้เวลาจะผ่านไป 5 นาที
            
            if ($startDateTime < $now) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_available' => false,
                        'conflicting_bookings' => [],
                        'message' => 'เวลาเริ่มต้นต้องเป็นอนาคต'
                    ]
                ]);
            }

            // ตรวจสอบว่ามีการจองที่ทับซ้อนหรือไม่ (ตรวจสอบทั้ง approved และ pending)
            // ตรวจสอบกรณีที่เวลาทับกันทั้งหมด
            $query = $room->bookings()
                ->whereIn('status', ['approved', 'pending'])
                ->where(function($query) use ($startTime, $endTime) {
                    $query
                        // กรณีที่ 1: การจองใหม่เริ่มก่อนการจองเดิมเริ่ม แต่จบระหว่างการจองเดิม
                        ->where(function($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $startTime)
                              ->where('end_time', '>', $startTime);
                        })
                        // กรณีที่ 2: การจองใหม่เริ่มระหว่างการจองเดิม
                        ->orWhere(function($q) use ($startTime, $endTime) {
                            $q->where('start_time', '>=', $startTime)
                              ->where('start_time', '<', $endTime);
                        })
                        // กรณีที่ 3: การจองเดิมครอบคลุมการจองใหม่ทั้งหมด
                        ->orWhere(function($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                        })
                        // กรณีที่ 4: การจองใหม่ครอบคลุมการจองเดิมทั้งหมด
                        ->orWhere(function($q) use ($startTime, $endTime) {
                            $q->where('start_time', '>', $startTime)
                              ->where('end_time', '<', $endTime);
                        });
                });

            // ไม่นับการจองที่ระบุ (สำหรับกรณี reschedule)
            if ($excludeBookingId) {
                $query->where('id', '!=', $excludeBookingId);
            }

            $conflictingBookings = $query->get();

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง: ' . implode(', ', $e->errors()['start_time'] ?? []) . ' ' . implode(', ', $e->errors()['end_time'] ?? []),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error checking availability: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบความพร้อม: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBookings(Request $request, Room $room)
    {
        $request->validate([
            'date' => 'nullable|date',
            'month' => 'nullable|date_format:Y-m',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        // สำหรับ date parameter ให้ดึงทั้ง approved และ pending
        // สำหรับ month parameter ให้ดึงเฉพาะ approved และ pending
        // ไม่แสดงการจองที่ยกเลิกแล้ว (cancelled) หรือปฏิเสธแล้ว (rejected)
        $query = $room->bookings()
            ->whereIn('status', ['approved', 'pending']);
        
        if ($request->date) {
            $date = $request->date;
            $query->whereDate('start_time', $date);
        } elseif ($request->month) {
            $month = $request->month;
            $query->whereYear('start_time', substr($month, 0, 4))
                  ->whereMonth('start_time', substr($month, 5, 2));
        } elseif ($request->start_date && $request->end_date) {
            // รองรับ start_date และ end_date สำหรับ calendar
            $query->whereBetween('start_time', [$request->start_date, $request->end_date]);
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
                    'notes' => $booking->notes,
                    'status' => $booking->status,
                    'user' => [
                        'name' => $booking->user->name,
                        'email' => $booking->user->email
                    ]
                ];
            })
        ]);
    }
}
