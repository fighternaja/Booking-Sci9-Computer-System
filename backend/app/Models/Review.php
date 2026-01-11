<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'booking_id',
        'rating',
        'comment',
        'is_visible'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * คำนวณคะแนนเฉลี่ยของห้อง
     */
    public static function getAverageRating($roomId)
    {
        return self::where('room_id', $roomId)
            ->where('is_visible', true)
            ->avg('rating') ?? 0;
    }

    /**
     * นับจำนวนรีวิวของห้อง
     */
    public static function getReviewCount($roomId)
    {
        return self::where('room_id', $roomId)
            ->where('is_visible', true)
            ->count();
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถรีวิวได้หรือไม่ (ต้องมีการจองที่ผ่านไปแล้ว)
     */
    public static function canReview($userId, $roomId, $bookingId = null)
    {
        $query = Booking::where('user_id', $userId)
            ->where('room_id', $roomId)
            ->where('status', 'approved')
            ->where('end_time', '<', now());

        if ($bookingId) {
            $query->where('id', $bookingId);
        }

        return $query->exists();
    }
}

