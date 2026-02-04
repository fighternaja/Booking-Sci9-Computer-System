<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // ตรวจสอบว่าผู้ใช้เป็น admin
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:rooms,name',
            'description' => 'nullable|string|max:1000',
            'capacity' => 'required|integer|min:1|max:1000',
            'location' => 'required|string|max:255',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|integer|min:1|max:50',
            'room_type' => 'nullable|in:classroom,meeting,lab,conference,general',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB
            'is_active' => 'sometimes|boolean',
            'status' => 'nullable|in:available,maintenance,unavailable'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'กรุณาระบุชื่อห้อง',
            'name.unique' => 'ชื่อห้องนี้มีอยู่ในระบบแล้ว',
            'name.max' => 'ชื่อห้องต้องไม่เกิน 255 ตัวอักษร',
            'description.max' => 'คำอธิบายต้องไม่เกิน 1000 ตัวอักษร',
            'capacity.required' => 'กรุณาระบุความจุของห้อง',
            'capacity.integer' => 'ความจุต้องเป็นตัวเลขเท่านั้น',
            'capacity.min' => 'ความจุต้องมากกว่า 0',
            'capacity.max' => 'ความจุต้องไม่เกิน 1000 คน',
            'location.required' => 'กรุณาระบุสถานที่',
            'location.max' => 'สถานที่ต้องไม่เกิน 255 ตัวอักษร',
            'building.max' => 'ชื่ออาคารต้องไม่เกิน 100 ตัวอักษร',
            'floor.integer' => 'ชั้นต้องเป็นตัวเลขเท่านั้น',
            'floor.min' => 'ชั้นต้องมากกว่า 0',
            'floor.max' => 'ชั้นต้องไม่เกิน 50',
            'room_type.in' => 'ประเภทห้องไม่ถูกต้อง',
            'amenities.array' => 'สิ่งอำนวยความสะดวกต้องเป็น array',
            'amenities.*.max' => 'สิ่งอำนวยความสะดวกแต่ละรายการต้องไม่เกิน 100 ตัวอักษร',
            'image.file' => 'ไฟล์รูปภาพไม่ถูกต้อง',
            'image.mimes' => 'รูปภาพต้องเป็นไฟล์ประเภท: jpg, jpeg, png, gif, webp',
            'image.max' => 'ขนาดรูปภาพต้องไม่เกิน 5MB',
            'is_active.boolean' => 'สถานะการใช้งานต้องเป็น true หรือ false',
            'status.in' => 'สถานะห้องไม่ถูกต้อง'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'ชื่อห้อง',
            'description' => 'คำอธิบาย',
            'capacity' => 'ความจุ',
            'location' => 'สถานที่',
            'building' => 'อาคาร',
            'floor' => 'ชั้น',
            'room_type' => 'ประเภทห้อง',
            'amenities' => 'สิ่งอำนวยความสะดวก',
            'image' => 'รูปภาพ',
            'is_active' => 'สถานะการใช้งาน',
            'status' => 'สถานะห้อง'
        ];
    }
}
