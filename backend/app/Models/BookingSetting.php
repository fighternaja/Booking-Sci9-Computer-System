<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? json_decode($setting->value, true) : $default;
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value),
                'description' => $description
            ]
        );
    }

    /**
     * Get all settings as array
     */
    public static function getAll()
    {
        $settings = self::all();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->key] = json_decode($setting->value, true);
        }
        
        return $result;
    }

    /**
     * Get default booking restrictions
     */
    public static function getDefaults()
    {
        return [
            'max_hours_per_booking' => 4,
            'min_hours_per_booking' => 1,
            'allowed_time_start' => '08:00',
            'allowed_time_end' => '20:00',
            'max_bookings_per_day' => 3,
            'max_bookings_per_week' => 10,
            'max_advance_days' => 30,
            'min_advance_hours' => 1,
            'allowed_weekdays' => [1, 2, 3, 4, 5], // Mon-Fri
            'require_approval' => true
        ];
    }
}
