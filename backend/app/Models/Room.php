<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'room_type',
        'description',
        'capacity',
        'location',
        'building',
        'floor',
        'amenities',
        'image',
        'is_active',
        'status'
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('images/default-room.jpg');
    }

    public static function getRoomTypes()
    {
        return [
            'computer' => 'ห้องคอมพิวเตอร์',
            'meeting' => 'ห้องประชุม',
            'classroom' => 'ห้องเรียน',
            'general' => 'ห้องทั่วไป'
        ];
    }

    public function getRoomTypeNameAttribute()
    {
        $types = self::getRoomTypes();
        return $types[$this->room_type] ?? $this->room_type;
    }

    public static function getStatuses()
    {
        return [
            'available' => 'ว่าง',
            'maintenance' => 'ซ่อมบำรุง',
            'occupied' => 'ถูกใช้งาน',
            'reserved' => 'จองแล้ว'
        ];
    }

    public function getStatusNameAttribute()
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    public static function getBuildings()
    {
        return [
            'building_a' => 'อาคาร A',
            'building_b' => 'อาคาร B', 
            'building_c' => 'อาคาร C',
            'building_d' => 'อาคาร D',
            'main_building' => 'อาคารหลัก',
            'library' => 'อาคารหอสมุด',
            'science' => 'อาคารวิทยาศาสตร์',
            'engineering' => 'อาคารวิศวกรรม'
        ];
    }

    public function getBuildingNameAttribute()
    {
        $buildings = self::getBuildings();
        return $buildings[$this->building] ?? $this->building;
    }

    public function getFullLocationAttribute()
    {
        $parts = [];
        if ($this->building) {
            $parts[] = $this->building_name;
        }
        if ($this->floor) {
            $parts[] = "ชั้น {$this->floor}";
        }
        if ($this->location) {
            $parts[] = $this->location;
        }
        return implode(' ', $parts);
    }
}
