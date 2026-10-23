<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Email of the shared system account that API actions are credited to by default.
     */
    const API_USER_EMAIL = 'api@system.local';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the chore logs for the user.
     */
    public function choreLogs()
    {
        return $this->hasMany(ChoreLog::class);
    }

    /**
     * Get the chore snoozes for the user.
     */
    public function snoozes()
    {
        return $this->hasMany(ChoreSnooze::class);
    }

    /**
     * Get the chores assigned to the user.
     */
    public function assignedChores()
    {
        return $this->hasMany(Chore::class, 'assigned_user_id');
    }

    /**
     * The shared "API User" account, created on first use.
     */
    public static function apiUser(): self
    {
        return static::firstOrCreate(
            ['email' => self::API_USER_EMAIL],
            [
                'name' => 'API User',
                'password' => Hash::make('api-system-user-' . bin2hex(random_bytes(16))),
                'email_verified_at' => now(),
            ]
        );
    }

    public function isApiUser(): bool
    {
        return $this->email === self::API_USER_EMAIL;
    }

    /**
     * Limit to real household members (excludes the API system user).
     */
    public function scopeHumans($query)
    {
        return $query->where('email', '!=', self::API_USER_EMAIL);
    }

    /**
     * First word of the user's name.
     */
    public function getFirstNameAttribute(): string
    {
        return Str::before(trim($this->name), ' ');
    }
}
