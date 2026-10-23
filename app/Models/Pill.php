<?php

namespace App\Models;

use App\Models\Traits\HasEffectiveDate;
use App\Models\Traits\HasTags;
use App\Support\Timezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Pill extends Model
{
    use HasEffectiveDate, HasFactory, HasTags, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'name',
        'slug',
        'description',
        'dosage',
        'scheduled_times',
        'is_active',
    ];

    protected $casts = [
        'scheduled_times' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to only include active pills.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Get the most recent administration log.
     */
    public function lastPillLog()
    {
        return $this->hasOne(PillLog::class)->latestOfMany('administered_at');
    }

    /**
     * Get pills that are due now (scheduled time has passed but not given today).
     */
    public static function getDueNow()
    {
        return static::whereNull('deleted_at')
            ->active()
            ->whereHas('pet')
            ->with(['pet', 'tags'])
            ->get()
            ->filter(fn ($pill) => $pill->doseSlots()->contains('status', 'due'));
    }

    /**
     * Get pills scheduled for later today (upcoming).
     */
    public static function getUpcoming()
    {
        return static::whereNull('deleted_at')
            ->active()
            ->whereHas('pet')
            ->with(['pet', 'tags'])
            ->get()
            ->filter(fn ($pill) => $pill->doseSlots()->contains('status', 'upcoming'));
    }

    /**
     * Get pills that have been given today.
     */
    public static function getCompletedToday()
    {
        $effectiveDayStart = static::getEffectiveDayStart();
        $effectiveDayEnd = static::getEffectiveDayEnd();

        return static::whereNull('deleted_at')
            ->active()
            ->whereHas('pet')
            ->with(['pet', 'tags'])
            ->whereHas('pillLogs', function ($query) use ($effectiveDayStart, $effectiveDayEnd) {
                $query->where('administered_at', '>=', $effectiveDayStart)
                    ->where('administered_at', '<=', $effectiveDayEnd);
            })
            ->get();
    }

    /**
     * The effective day's dose slots in schedule order, judged at the given moment (default now).
     * Each slot is ['time', 'label', 'at', 'status' => given|due|upcoming, 'log'].
     */
    public function doseSlots(?Carbon $at = null): Collection
    {
        $at = $at ?? now();
        $date = static::getEffectiveDate($at);

        $logs = $this->pillLogs()
            ->with('user')
            ->where('administered_at', '>=', static::getEffectiveDayStart($date))
            ->where('administered_at', '<=', static::getEffectiveDayEnd($date))
            ->get()
            ->keyBy('scheduled_time');

        return collect($this->scheduled_times ?? [])
            ->unique()
            ->map(function ($time) use ($date, $at, $logs) {
                $slotAt = static::slotDateTime($date, $time);
                $log = $logs->get($time);

                return [
                    'time' => $time,
                    'label' => $slotAt->format('g:i A'),
                    'at' => $slotAt,
                    'status' => $log ? 'given' : ($slotAt->lte($at) ? 'due' : 'upcoming'),
                    'log' => $log,
                ];
            })
            ->sortBy('at')
            ->values();
    }

    /**
     * The slot that should be filled next: the earliest due slot, otherwise the earliest upcoming one.
     */
    public function nextDoseSlot(?Carbon $at = null): ?array
    {
        $slots = $this->doseSlots($at);

        return $slots->firstWhere('status', 'due') ?? $slots->firstWhere('status', 'upcoming');
    }

    /**
     * When a scheduled time falls on a given effective date, in the household's timezone.
     * Times before the cutoff belong to the next calendar day.
     */
    public static function slotDateTime(Carbon $date, string $time): Carbon
    {
        $slotAt = Carbon::parse($date->toDateString().' '.$time, Timezone::household());
        if ($slotAt->hour < static::DAY_CUTOFF_HOUR) {
            $slotAt->addDay();
        }

        return $slotAt;
    }

    /**
     * Get the next scheduled time for this pill.
     */
    public function getNextScheduledTime()
    {
        $currentTime = now(Timezone::household())->format('H:i');
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
