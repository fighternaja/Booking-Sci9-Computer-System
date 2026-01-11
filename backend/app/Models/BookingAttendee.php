<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'email',
        'name',
        'status',
        'invited_at',
        'responded_at',
        'attended_at',
        'is_required'
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
        'attended_at' => 'datetime',
        'is_required' => 'boolean'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'pending' => 'รอตอบรับ',
            'accepted' => 'ยอมรับ',
            'declined' => 'ปฏิเสธ',
            'attended' => 'เข้าร่วมแล้ว'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * ตรวจสอบว่าผู้เข้าร่วมเป็นสมาชิกหรือไม่
     */
    public function isMember()
    {
        return $this->user_id !== null;
    }

    /**
     * ดึงอีเมลของผู้เข้าร่วม
     */
    public function getEmailAddressAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->email;
        }
        return $this->email;
    }

    /**
     * ดึงชื่อของผู้เข้าร่วม
     */
    public function getDisplayNameAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        return $this->name;
    }
}

