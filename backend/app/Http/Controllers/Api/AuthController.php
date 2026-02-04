<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        // ลงทะเบียนผู้ใช้งานสำเร็จ
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'message' => 'สมัครสมาชิกสำเร็จ'
        ], 201);
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $request->email)->firstOrFail();

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'
                ], 401);
            }

            // Update last login timestamp
            $user = User::where('email', $request->email)->firstOrFail();
            $user->last_login_at = now();
            $user->save();

            // create a sanctum personal access token ตรวจสอบสิทธิ์การเข้าถึง API
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'message' => 'เข้าสู่ระบบสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        // revoke current access token (if using sanctum) ยกเลิกการเข้าถึง API
        $user = Auth::user();
        if ($user && $request->user()) {
            $request->user()->currentAccessToken()?->delete();
        } else {
            Auth::logout();
        }

        return response()->json([
            'success' => true,
            'message' => 'ออกจากระบบสำเร็จ'
        ]);
    }

    public function me(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

}
