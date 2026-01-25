<?php

namespace App\Models;

use App\Models\Traits\HasEffectiveDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pill extends Model
{
    use HasEffectiveDate, HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'name',
        'slug',
        'description',
        'dosage',
        'scheduled_times',
    ];

    protected $casts = [
        'scheduled_times' => 'array',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the pet this pill belongs to.
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * Get all pill logs for this pill.
     */
    public function pillLogs()
    {
        return $this->hasMany(PillLog::class)->orderBy('administered_at', 'desc');
    }

    /**
     * Get pills that are due now (scheduled time has passed but not given today).
     */
    public static function getDueNow()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->get()
            ->filter(function ($pill) use ($currentTime, $effectiveDayStart, $effectiveDayEnd) {
                // Check if any scheduled time has passed today
                foreach ($pill->scheduled_times as $scheduledTime) {
                    if ($scheduledTime <= $currentTime) {
                        // Check if this dose was already given during the effective day
                        $givenToday = $pill->pillLogs()
                            ->where('administered_at', '>=', $effectiveDayStart)
                            ->where('administered_at', '<=', $effectiveDayEnd)
                            ->where('scheduled_time', $scheduledTime)
                            ->exists();

                        if (! $givenToday) {
                            return true; // Due now
                        }
                    }
                }

                return false;
            });
    }

    /**
     * Get pills scheduled for later today (upcoming).
     */
    public static function getUpcoming()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->get()
            ->filter(function ($pill) use ($currentTime, $effectiveDayStart, $effectiveDayEnd) {
                foreach ($pill->scheduled_times as $scheduledTime) {
                    if ($scheduledTime > $currentTime) {
                        $givenToday = $pill->pillLogs()
                            ->where('administered_at', '>=', $effectiveDayStart)
                            ->where('administered_at', '<=', $effectiveDayEnd)
                            ->where('scheduled_time', $scheduledTime)
                            ->exists();

                        if (! $givenToday) {
                            return true; // Upcoming
                        }
                    }
                }

                return false;
            });
    }

    /**
     * Get pills that have been given today.
     */
    public static function getCompletedToday()
    {
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->whereHas('pillLogs', function ($query) use ($effectiveDayStart, $effectiveDayEnd) {
                $query->where('administered_at', '>=', $effectiveDayStart)
                    ->where('administered_at', '<=', $effectiveDayEnd);
            })
            ->get();
    }

    /**
     * Get the next scheduled time for this pill.
     */
    public function getNextScheduledTime()
    {
        $currentTime = now()->format('H:i');
        foreach ($this->scheduled_times as $time) {
            if ($time > $currentTime) {
                return $time;
            }
        }

        return $this->scheduled_times[0] ?? null; // Tomorrow's first dose
    }

    /**
     * Check if this pill has been given today.
     */
    public function hasBeenGivenToday()
    {
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return $this->pillLogs()
            ->where('administered_at', '>=', $effectiveDayStart)
            ->where('administered_at', '<=', $effectiveDayEnd)
            ->exists();
    }

    /**
     * Get which scheduled doses have been completed today.
     */
    public function getTodaysCompletedDoses()
    {
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return $this->pillLogs()
            ->where('administered_at', '>=', $effectiveDayStart)
            ->where('administered_at', '<=', $effectiveDayEnd)
            ->pluck('scheduled_time')
            ->toArray();
    }

    /**
     * Check if a specific scheduled time has been given today.
     */
    public function hasBeenGivenAt($scheduledTime)
    {
        return in_array($scheduledTime, $this->getTodaysCompletedDoses());
    }

    /**
     * Get the last administration log.
     */
    public function getLastLog()
    {
        return $this->pillLogs()->first();
    }

    /**
     * Get who last gave this pill.
     */
    public function getLastAdministeredUser()
    {
        return $this->getLastLog()?->user?->name;
    }

    /**
     * Get when this pill was last given.
     */
    public function getLastAdministeredDate()
    {
        return $this->getLastLog()?->administered_at;
    }
}
