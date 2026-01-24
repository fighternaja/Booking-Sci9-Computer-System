<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BookingSettingController extends Controller
{
    /**
     * Get all booking restrictions
     */
    public function index(): JsonResponse
    {
        try {
            $settings = BookingSetting::getAll();
            
            // If no settings exist, return defaults
            if (empty($settings)) {
                $settings = BookingSetting::getDefaults();
            }

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update booking restrictions
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'max_hours_per_booking' => 'required|integer|min:1|max:24',
                'min_hours_per_booking' => 'required|integer|min:1|max:24',
                'allowed_time_start' => 'required|date_format:H:i',
                'allowed_time_end' => 'required|date_format:H:i',
                'max_bookings_per_day' => 'required|integer|min:1',
                'max_bookings_per_week' => 'required|integer|min:1',
                'max_advance_days' => 'required|integer|min:1',
                'min_advance_hours' => 'required|integer|min:0',
                'allowed_weekdays' => 'required|array',
                'allowed_weekdays.*' => 'integer|min:0|max:6',
                'require_approval' => 'required|boolean'
            ]);

            // Validate that min < max
            if ($validated['min_hours_per_booking'] > $validated['max_hours_per_booking']) {
                return response()->json([
                    'success' => false,
                    'message' => 'จำนวนชั่วโมงขั้นต่ำต้องน้อยกว่าหรือเท่ากับจำนวนชั่วโมงสูงสุด'
                ], 422);
            }

            // Update each setting
            foreach ($validated as $key => $value) {
                BookingSetting::set($key, $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทการตั้งค่าเรียบร้อย',
                'data' => $validated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset to default settings
     */
    public function reset(): JsonResponse
    {
        try {
            $defaults = BookingSetting::getDefaults();

            foreach ($defaults as $key => $value) {
                BookingSetting::set($key, $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'รีเซ็ตการตั้งค่าเป็นค่าเริ่มต้นเรียบร้อย',
                'data' => $defaults
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resetting settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
