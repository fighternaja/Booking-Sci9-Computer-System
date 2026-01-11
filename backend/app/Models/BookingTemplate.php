<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'room_id',
        'start_time',
        'duration',
        'purpose',
        'notes',
        'is_default'
    ];

    protected $casts = [
        'duration' => 'integer',
        'is_default' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * สร้างการจองจากเทมเพลต
     */
    public function createBooking($date, $startTime = null, $endTime = null)
    {
        $start = $startTime ?? ($this->start_time ? 
            \Carbon\Carbon::parse($date . ' ' . $this->start_time) : 
            \Carbon\Carbon::parse($date . ' 09:00'));
        
        $end = $endTime ?? ($this->duration ? 
            $start->copy()->addMinutes($this->duration) : 
            $start->copy()->addHour());

        return Booking::create([
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'start_time' => $start,
            'end_time' => $end,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'status' => auth()->user()->role === 'admin' ? 'approved' : 'pending'
        ]);
    }
}

