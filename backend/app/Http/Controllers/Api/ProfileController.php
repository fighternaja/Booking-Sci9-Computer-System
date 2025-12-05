<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // ดึงข้อมูลของผู้ใช้งานที่ login อยู่
    public function index(Request $request)
    {
        $user = $request->user();
        // แก้ไข URL ของรูปภาพให้เป็น URL เต็ม (เฉพาะไฟล์ที่อัปโหลด ไม่ใช่ URL จาก Google)
        if ($user->profile_picture && !filter_var($user->profile_picture, FILTER_VALIDATE_URL)) {
            $user->profile_picture = 'storage/' . $user->profile_picture;
        }
        return response()->json($user);
    }

    //แก้ไขข้อมูลของผู้ใช้งาน
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',//เพิ่มรูปภาพประจำตัว
        ]);

        if($request->has('name')) {
            $user->name = $request->name;
        }
        if($request->has('email')) {
            $user->email = $request->email;
        }
        if($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        if($request->hasFile('profile_picture')) {
            // ถ้าผู้ใช้อัปโหลดรูปใหม่ ให้แทนที่รูปเดิม (ไม่ว่าจะเป็น URL จาก Google หรือไฟล์ที่อัปโหลดมาก่อน)
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');
            $user->profile_picture = $path;
        }
        // ถ้าไม่มีการอัปโหลดรูปใหม่ ให้คงรูปเดิมไว้ (อาจเป็น URL จาก Google หรือไฟล์ที่อัปโหลดมาก่อน)
        $user->save();

        // แก้ไข URL ของรูปภาพให้เป็น URL เต็ม (เฉพาะไฟล์ที่อัปโหลด ไม่ใช่ URL จาก Google)
        if ($user->profile_picture && !filter_var($user->profile_picture, FILTER_VALIDATE_URL)) {
            $user->profile_picture = 'storage/' . $user->profile_picture;
        }

        return response()->json([
            'message' => 'อัปเดตโปรไฟล์สำเร็จ',
            'user' => $user,
            'success' => true
        ]);
    }
}
