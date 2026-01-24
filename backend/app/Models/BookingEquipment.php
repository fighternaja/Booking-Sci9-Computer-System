<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingEquipment extends Model
{
    use HasFactory;

    protected $table = 'booking_equipment';

    protected $fillable = [
        'booking_id',
        'equipment_id',
        'quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the booking
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the equipment
     */
    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Approve equipment request
     */
    public function approve()
    {
        $this->status = 'approved';
        $this->save();

        // Reserve equipment
        $this->equipment->reserve($this->quantity);
    }

    /**
     * Reject equipment request
     */
    public function reject($notes = null)
    {
        $this->status = 'rejected';
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }

    /**
     * Release equipment (when booking is cancelled)
     */
    public function releaseEquipment()
    {
        if ($this->status === 'approved') {
            $this->equipment->release($this->quantity);
        }
    }
}
