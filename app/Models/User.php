<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ntfy_topic',
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
     * Limit to users who have set an ntfy topic to receive reminders on.
     */
    public function scopeWithNtfyTopic($query)
    {
        return $query->whereNotNull('ntfy_topic')->where('ntfy_topic', '!=', '');
    }

    /**
     * The ntfy topic reminders are published to (used by NtfyChannel).
     */
    public function routeNotificationForNtfy(): ?string
    {
        return $this->ntfy_topic ?: null;
    }
}
