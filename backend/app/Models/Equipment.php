<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'available_quantity',
        'image_url',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'available_quantity' => 'integer',
    ];

    /**
     * Get bookings that use this equipment
     */
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_equipment')
            ->withPivot('quantity', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get booking equipment records
     */
    public function bookingEquipment()
    {
        return $this->hasMany(BookingEquipment::class);
    }

    /**
     * Check if equipment is available
     */
    public function isAvailable($quantity = 1)
    {
        return $this->available_quantity >= $quantity;
    }

    /**
     * Reserve equipment
     */
    public function reserve($quantity)
    {
        if (!$this->isAvailable($quantity)) {
            return false;
        }

        $this->available_quantity -= $quantity;
        $this->save();

        return true;
    }

    /**
     * Release equipment
     */
    public function release($quantity)
    {
        $this->available_quantity += $quantity;
        
        // Make sure available quantity doesn't exceed total quantity
        if ($this->available_quantity > $this->quantity) {
            $this->available_quantity = $this->quantity;
        }
        
        $this->save();

        return true;
    }

    /**
     * Get reserved quantity
     */
    public function getReservedQuantityAttribute()
    {
        return $this->quantity - $this->available_quantity;
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute()
    {
        if ($this->quantity == 0) {
            return 0;
        }

        return round(($this->reserved_quantity / $this->quantity) * 100, 1);
    }
}
