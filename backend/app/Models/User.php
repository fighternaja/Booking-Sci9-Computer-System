<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AuditLog;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'profile_picture',
        'is_active',
        'last_login_at',
        'suspended_until',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'suspended_until' => 'datetime',
        'is_active' => 'boolean',
        // Note: password is intentionally not auto-hashed here since
        // the controller/seeder already hashes it explicitly.
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function waitlists()
    {
        return $this->hasMany(Waitlist::class);
    }

    public function bookingTemplates()
    {
        return $this->hasMany(BookingTemplate::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isActive()
    {
        return $this->is_active && ($this->suspended_until === null || $this->suspended_until->isPast());
    }

    public function isSuspended()
    {
        return $this->suspended_until !== null && $this->suspended_until->isFuture();
    }
}
