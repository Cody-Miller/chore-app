<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pill extends Model
{
    use HasFactory, SoftDeletes;

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
        $currentDate = $now->toDateString();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->get()
            ->filter(function ($pill) use ($currentTime, $currentDate) {
                // Check if any scheduled time has passed today
                foreach ($pill->scheduled_times as $scheduledTime) {
                    if ($scheduledTime <= $currentTime) {
                        // Check if this dose was already given today
                        $givenToday = $pill->pillLogs()
                            ->whereDate('administered_at', $currentDate)
                            ->where('scheduled_time', $scheduledTime)
                            ->exists();

                        if (!$givenToday) {
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
        $currentDate = $now->toDateString();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->get()
            ->filter(function ($pill) use ($currentTime, $currentDate) {
                foreach ($pill->scheduled_times as $scheduledTime) {
                    if ($scheduledTime > $currentTime) {
                        $givenToday = $pill->pillLogs()
                            ->whereDate('administered_at', $currentDate)
                            ->where('scheduled_time', $scheduledTime)
                            ->exists();

                        if (!$givenToday) {
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
        $currentDate = now()->toDateString();

        return static::whereNull('deleted_at')
            ->with('pet')
            ->whereHas('pillLogs', function ($query) use ($currentDate) {
                $query->whereDate('administered_at', $currentDate);
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
        return $this->pillLogs()
            ->whereDate('administered_at', now())
            ->exists();
    }

    /**
     * Get which scheduled doses have been completed today.
     */
    public function getTodaysCompletedDoses()
    {
        return $this->pillLogs()
            ->whereDate('administered_at', now())
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
