<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount('bookings');
        $user->load(['bookings' => function($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:user,admin',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldValues = $user->toArray();

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('role')) {
            $user->role = $request->role;
        }

        if ($request->has('is_active')) {
            $user->is_active = $request->is_active;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Log the change
        AuditLog::log('updated', $user, $oldValues, $user->toArray(), 'User updated by admin');

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }

    public function suspend(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'suspended_until' => 'required|date|after:now',
            'suspension_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldValues = $user->toArray();

        $user->suspended_until = $request->suspended_until;
        $user->suspension_reason = $request->suspension_reason;
        $user->is_active = false;
        $user->save();

        AuditLog::log('suspended', $user, $oldValues, $user->toArray(), $request->suspension_reason);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User suspended successfully'
        ]);
    }

    public function unsuspend(User $user): JsonResponse
    {
        $oldValues = $user->toArray();

        $user->suspended_until = null;
        $user->suspension_reason = null;
        $user->is_active = true;
        $user->save();

        AuditLog::log('unsuspended', $user, $oldValues, $user->toArray(), 'User unsuspended by admin');

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User unsuspended successfully'
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account'
            ], 400);
        }

        $oldValues = $user->toArray();
        
        AuditLog::log('deleted', $user, $oldValues, null, 'User deleted by admin');
        
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function getActivityLog(User $user): JsonResponse
    {
        $logs = AuditLog::where('user_id', $user->id)
            ->orWhere(function($query) use ($user) {
                $query->where('model_type', User::class)
                      ->where('model_id', $user->id);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}

