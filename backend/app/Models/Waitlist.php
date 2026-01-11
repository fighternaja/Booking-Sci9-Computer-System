<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'start_time',
        'end_time',
        'purpose',
        'notes',
        'status',
        'auto_book',
        'notified_at'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'notified_at' => 'datetime',
        'auto_book' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'waiting' => 'warning',
            'notified' => 'info',
            'booked' => 'success',
            'cancelled' => 'secondary'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getStatusNameAttribute()
    {
        $statuses = [
            'waiting' => 'รอ',
            'notified' => 'แจ้งเตือนแล้ว',
            'booked' => 'จองแล้ว',
            'cancelled' => 'ยกเลิก'
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}

