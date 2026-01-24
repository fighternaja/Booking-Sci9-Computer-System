<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EquipmentController extends Controller
{
    /**
     * Display a listing of equipment
     */
    public function index()
    {
        $equipment = Equipment::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Store a newly created equipment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $equipment = Equipment::create([
            'name' => $request->name,
            'description' => $request->description,
            'quantity' => $request->quantity,
            'available_quantity' => $request->quantity, // Initially all available
            'image_url' => $request->image_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipment created successfully',
            'data' => $equipment
        ], 201);
    }

    /**
     * Display the specified equipment
     */
    public function show($id)
    {
        $equipment = Equipment::with('bookingEquipment.booking')->find($id);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    /**
     * Update the specified equipment
     */
    public function update(Request $request, $id)
    {
        $equipment = Equipment::find($id);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'sometimes|required|integer|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if new quantity is less than reserved quantity
        if ($request->has('quantity')) {
            $reservedQuantity = $equipment->reserved_quantity;
            if ($request->quantity < $reservedQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot reduce quantity below reserved amount ({$reservedQuantity})"
                ], 422);
            }

            // Update available quantity proportionally
            $diff = $request->quantity - $equipment->quantity;
            $equipment->available_quantity += $diff;
        }

        $equipment->update($request->only(['name', 'description', 'quantity', 'image_url']));

        return response()->json([
            'success' => true,
            'message' => 'Equipment updated successfully',
            'data' => $equipment
        ]);
    }

    /**
     * Remove the specified equipment
     */
    public function destroy($id)
    {
        $equipment = Equipment::find($id);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        // Check if equipment is currently in use
        if ($equipment->reserved_quantity > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete equipment that is currently in use'
            ], 422);
        }

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment deleted successfully'
        ]);
    }

    /**
     * Get equipment usage statistics
     */
    public function stats()
    {
        $equipment = Equipment::all();

        $stats = [
            'total_equipment' => $equipment->count(),
            'total_quantity' => $equipment->sum('quantity'),
            'total_available' => $equipment->sum('available_quantity'),
            'total_reserved' => $equipment->sum('quantity') - $equipment->sum('available_quantity'),
            'equipment_list' => $equipment->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'available' => $item->available_quantity,
                    'reserved' => $item->reserved_quantity,
                    'usage_percentage' => $item->usage_percentage,
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
