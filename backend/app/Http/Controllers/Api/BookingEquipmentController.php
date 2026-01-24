<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\BookingEquipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingEquipmentController extends Controller
{
    /**
     * Get equipment for a booking
     */
    public function index($bookingId)
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $equipment = $booking->equipment()->withPivot('quantity', 'status', 'notes')->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Add equipment to a booking
     */
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'equipment_id' => 'required|exists:equipment,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $equipment = Equipment::find($request->equipment_id);

        // Check if equipment is available
        if (!$equipment->isAvailable($request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => "Only {$equipment->available_quantity} units available"
            ], 422);
        }

        // Check if equipment already added to this booking
        $existing = BookingEquipment::where('booking_id', $bookingId)
            ->where('equipment_id', $request->equipment_id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment already added to this booking'
            ], 422);
        }

        // Create booking equipment record
        $bookingEquipment = BookingEquipment::create([
            'booking_id' => $bookingId,
            'equipment_id' => $request->equipment_id,
            'quantity' => $request->quantity,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // If booking is already approved, auto-approve equipment
        if ($booking->status === 'approved') {
            $bookingEquipment->approve();
        }

        return response()->json([
            'success' => true,
            'message' => 'Equipment added to booking',
            'data' => $bookingEquipment->load('equipment')
        ], 201);
    }

    /**
     * Update equipment quantity in a booking
     */
    public function update(Request $request, $bookingId, $equipmentId)
    {
        $bookingEquipment = BookingEquipment::where('booking_id', $bookingId)
            ->where('equipment_id', $equipmentId)
            ->first();

        if (!$bookingEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found in this booking'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $equipment = $bookingEquipment->equipment;
        $oldQuantity = $bookingEquipment->quantity;
        $newQuantity = $request->quantity;
        $diff = $newQuantity - $oldQuantity;

        // If increasing quantity, check availability
        if ($diff > 0) {
            if (!$equipment->isAvailable($diff)) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$equipment->available_quantity} additional units available"
                ], 422);
            }

            // If already approved, reserve additional quantity
            if ($bookingEquipment->status === 'approved') {
                $equipment->reserve($diff);
            }
        } elseif ($diff < 0) {
            // If decreasing quantity and already approved, release equipment
            if ($bookingEquipment->status === 'approved') {
                $equipment->release(abs($diff));
            }
        }

        $bookingEquipment->quantity = $newQuantity;
        if ($request->has('notes')) {
            $bookingEquipment->notes = $request->notes;
        }
        $bookingEquipment->save();

        return response()->json([
            'success' => true,
            'message' => 'Equipment quantity updated',
            'data' => $bookingEquipment->load('equipment')
        ]);
    }

    /**
     * Remove equipment from a booking
     */
    public function destroy($bookingId, $equipmentId)
    {
        $bookingEquipment = BookingEquipment::where('booking_id', $bookingId)
            ->where('equipment_id', $equipmentId)
            ->first();

        if (!$bookingEquipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found in this booking'
            ], 404);
        }

        // Release equipment if it was approved
        $bookingEquipment->releaseEquipment();

        $bookingEquipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment removed from booking'
        ]);
    }
}
