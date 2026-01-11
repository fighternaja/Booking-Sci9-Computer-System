<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'recurring_booking_id',
        'start_time',
        'end_time',
        'purpose',
        'notes',
        'status',
        'requires_checkin',
        'checked_in_at',
        'auto_cancel_minutes',
        'auto_cancelled_at',
        'cancellation_reason',
        'rejection_reason',
        'approval_reason'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'requires_checkin' => 'boolean',
        'checked_in_at' => 'datetime',
        'auto_cancel_minutes' => 'integer',
        'auto_cancelled_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model', 'model_type', 'model_id');
    }

    public function recurringBooking()
    {
        return $this->belongsTo(RecurringBooking::class);
    }

    public function attendees()
    {
        return $this->hasMany(BookingAttendee::class);
    }

    /**
     * นับจำนวนผู้เข้าร่วมที่ยอมรับ
     */
    public function getAcceptedAttendeesCountAttribute()
    {
        return $this->attendees()->where('status', 'accepted')->count();
    }

    /**
     * นับจำนวนผู้เข้าร่วมจริง
     */
    public function getAttendedCountAttribute()
    {
        return $this->attendees()->where('status', 'attended')->count();
    }

    public function getDurationAttribute()
    {
        return $this->start_time->diffInHours($this->end_time);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'secondary'
        ];

        return $badges[$this->status] ?? 'secondary';
    }
}
