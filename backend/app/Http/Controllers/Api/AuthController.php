<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
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

    public function login(Request $request): JsonResponse
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

    public function logout(Request $request): JsonResponse
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

    public function me(Request $request): JsonResponse
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

    public function googleLogin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string'
            ]);

            // Get user info from Google using access token
            $accessToken = $request->token;
            
            try {
                // ใช้ Google OAuth2 v2 API เพื่อดึงข้อมูลผู้ใช้
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken
                ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

                // ถ้า v2 ไม่สำเร็จ ลองใช้ v3
                if (!$response->successful()) {
                    $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
                        'access_token' => $accessToken
                    ]);
                }

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่สามารถตรวจสอบข้อมูลจาก Google ได้: ' . $response->body()
                    ], 401);
                }

                $userInfo = $response->json();

                if (isset($userInfo['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token ไม่ถูกต้องหรือหมดอายุ: ' . ($userInfo['error_description'] ?? $userInfo['error'])
                    ], 401);
                }

                // Extract user info from Google
                // รองรับทั้ง v2 และ v3 API
                $googleId = $userInfo['id'] ?? $userInfo['sub'] ?? null;
                $email = $userInfo['email'] ?? null;
                $name = $userInfo['name'] ?? ($email ?? 'User');
                // ดึงรูปภาพโปรไฟล์จาก Google (URL เต็ม)
                $picture = $userInfo['picture'] ?? null;

                if (!$email || !$googleId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่ได้รับข้อมูลอีเมลจาก Google'
                    ], 401);
                }
            } catch (\Exception $httpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อกับ Google API: ' . $httpException->getMessage()
                ], 500);
            }

            // Check if user exists
            $user = User::where('google_id', $googleId)
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->google_id = $googleId;
                }

                // Update profile picture from Google every time (to get latest picture)
                // เก็บ URL เต็มจาก Google โดยตรง
                if ($picture) {
                    $user->profile_picture = $picture;
                }

                $user->save();
            } else {
                // Create new user
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => null,
                    'role' => 'user',
                    'profile_picture' => $picture, // เก็บ URL เต็มจาก Google
                    'email_verified_at' => now(),
                ]);
            }

            // Create token
            $token = $user->createToken('api-token')->plainTextToken;

            // ส่งข้อมูล user กลับไปพร้อมกับ profile_picture (URL เต็มจาก Google)
            $userData = $user->toArray();
            // ตรวจสอบว่า profile_picture เป็น URL เต็มหรือไม่
            if ($userData['profile_picture'] && !filter_var($userData['profile_picture'], FILTER_VALIDATE_URL)) {
                // ถ้าไม่ใช่ URL เต็ม ให้เพิ่ม prefix
                $userData['profile_picture'] = 'storage/' . $userData['profile_picture'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $userData,
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
}
