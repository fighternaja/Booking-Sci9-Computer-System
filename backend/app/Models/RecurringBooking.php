<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RecurringBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'start_time',
        'end_time',
        'purpose',
        'notes',
        'recurrence_type',
        'recurrence_pattern',
        'start_date',
        'end_date',
        'max_occurrences',
        'days_of_week',
        'day_of_month',
        'interval',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
        'recurrence_pattern' => 'array',
        'days_of_week' => 'array',
        'day_of_month' => 'integer',
        'interval' => 'integer',
        'max_occurrences' => 'integer',
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * สร้างการจองตาม pattern
     */
    public function generateBookings($untilDate = null)
    {
        $until = $untilDate ? Carbon::parse($untilDate) : ($this->end_date ? Carbon::parse($this->end_date) : Carbon::now()->addMonths(3));
        $start = Carbon::parse($this->start_date);
        $occurrences = 0;
        $generatedBookings = [];

        while ($start->lte($until) && ($this->max_occurrences === null || $occurrences < $this->max_occurrences)) {
            $shouldCreate = false;

            switch ($this->recurrence_type) {
                case 'daily':
                    $shouldCreate = true;
                    $nextDate = $start->copy()->addDays($this->interval);
                    break;

                case 'weekly':
                    if ($this->days_of_week && in_array($start->dayOfWeek, $this->days_of_week)) {
                        $shouldCreate = true;
                    }
                    $nextDate = $start->copy()->addWeeks($this->interval);
                    break;

                case 'monthly':
                    if ($this->day_of_month && $start->day == $this->day_of_month) {
                        $shouldCreate = true;
                    }
                    $nextDate = $start->copy()->addMonths($this->interval);
                    break;

                case 'custom':
                    $shouldCreate = $this->checkCustomPattern($start);
                    $nextDate = $this->getNextCustomDate($start);
                    break;
            }

            if ($shouldCreate) {
                // ตรวจสอบว่ามีการจองอยู่แล้วหรือไม่
                $existingBooking = Booking::where('recurring_booking_id', $this->id)
                    ->whereDate('start_time', $start->format('Y-m-d'))
                    ->first();

                if (!$existingBooking) {
                    $booking = $this->createBookingForDate($start);
                    if ($booking) {
                        $generatedBookings[] = $booking;
                        $occurrences++;
                    }
                }
            }

            $start = $nextDate;
        }

        return $generatedBookings;
    }

    /**
     * สร้างการจองสำหรับวันที่กำหนด
     */
    private function createBookingForDate(Carbon $date)
    {
        $startTimeStr = is_string($this->start_time) ? $this->start_time : 
            (is_object($this->start_time) ? $this->start_time->format('H:i:s') : $this->start_time);
        $endTimeStr = is_string($this->end_time) ? $this->end_time : 
            (is_object($this->end_time) ? $this->end_time->format('H:i:s') : $this->end_time);
        
        $startDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $startTimeStr);
        $endDateTime = Carbon::parse($date->format('Y-m-d') . ' ' . $endTimeStr);

        // ตรวจสอบความขัดแย้ง
        $conflict = Booking::where('room_id', $this->room_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_time', [$startDateTime, $endDateTime])
                    ->orWhereBetween('end_time', [$startDateTime, $endDateTime])
                    ->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        $q->where('start_time', '<=', $startDateTime)
                            ->where('end_time', '>=', $endDateTime);
                    });
            })
            ->exists();

        if ($conflict) {
            return null;
        }

        $status = $this->user->role === 'admin' ? 'approved' : 'pending';

        return Booking::create([
            'user_id' => $this->user_id,
            'room_id' => $this->room_id,
            'recurring_booking_id' => $this->id,
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'status' => $status
        ]);
    }

    /**
     * ตรวจสอบ custom pattern
     */
    private function checkCustomPattern(Carbon $date)
    {
        if (!$this->recurrence_pattern) {
            return false;
        }

        // ตัวอย่าง: {"days": [1,3,5], "weeks": [1,3]} = สัปดาห์ที่ 1 และ 3 ของเดือน, วันจันทร์ พุธ ศุกร์
        $pattern = $this->recurrence_pattern;
        
        if (isset($pattern['days']) && !in_array($date->dayOfWeek, $pattern['days'])) {
            return false;
        }

        if (isset($pattern['weeks'])) {
            $weekOfMonth = ceil($date->day / 7);
            if (!in_array($weekOfMonth, $pattern['weeks'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * หาวันที่ถัดไปสำหรับ custom pattern
     */
    private function getNextCustomDate(Carbon $date)
    {
        return $date->copy()->addDay();
    }
}

