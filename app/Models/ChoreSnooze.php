<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChoreSnooze extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chore_id',
        'user_id',
        'snoozed_until',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snoozed_until' => 'datetime',
        ];
    }

    /**
     * Get the chore that is snoozed.
     */
    public function chore(): BelongsTo
    {
        return $this->belongsTo(Chore::class);
    }

    /**
     * Get the user who snoozed the chore.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this snooze is still active.
     */
    public function isActive(): bool
    {
        return $this->snoozed_until->isFuture();
    }

    /**
     * Scope to get only active snoozes.
     */
    public function scopeActive($query)
    {
        return $query->where('snoozed_until', '>', now());
    }

    /**
     * Get available snooze duration options.
     *
     * @return array Array of snooze options with hours as keys and labels as values
     */
    public static function getSnoozeOptions(): array
    {
        return [
            24 => '1 day',
            72 => '3 days',
            168 => '1 week',
            336 => '2 weeks',
            672 => '1 month',
            4032 => '6 months',
            8064 => '1 year',
        ];
    }
}
