<?php

namespace App\Models\Traits;

use App\Support\Timezone;
use Carbon\Carbon;

/**
 * The "effective day" runs from DAY_CUTOFF_HOUR to DAY_CUTOFF_HOUR the next day,
 * in the household's timezone.
 */
trait HasEffectiveDate
{
    /**
     * The hour at which a new "effective day" begins.
     * Pills given between midnight and this hour count for the previous day.
     */
    const DAY_CUTOFF_HOUR = 3;

    /**
     * Get the "effective date" for a given timestamp.
     * If the household's time is before the cutoff hour, returns the previous day.
     *
     * @param  Carbon|null  $timestamp  Defaults to now()
     * @return Carbon Midnight of the effective date, in the household's timezone
     */
    public static function getEffectiveDate(?Carbon $timestamp = null): Carbon
    {
        $timestamp = ($timestamp ?? now())->copy()->setTimezone(Timezone::household());

        if ($timestamp->hour < static::DAY_CUTOFF_HOUR) {
            return $timestamp->subDay()->startOfDay();
        }

        return $timestamp->startOfDay();
    }

    /**
     * Get the start of the "effective day" for a given date.
     * The effective day starts at DAY_CUTOFF_HOUR on the calendar date.
     *
     * @param  Carbon|null  $date  The effective date (not timestamp)
     * @return Carbon Start of effective day in UTC, ready for queries (e.g. 2024-01-15 03:00 household time)
     */
    public static function getEffectiveDayStart(?Carbon $date = null): Carbon
    {
        return static::householdDate($date)->addHours(static::DAY_CUTOFF_HOUR)->utc();
    }

    /**
     * Get the end of the "effective day" for a given date.
     * The effective day ends at DAY_CUTOFF_HOUR on the next calendar date.
     *
     * @param  Carbon|null  $date  The effective date (not timestamp)
     * @return Carbon End of effective day in UTC, ready for queries (e.g. 2024-01-16 02:59:59 household time)
     */
    public static function getEffectiveDayEnd(?Carbon $date = null): Carbon
    {
        return static::householdDate($date)->addDay()->addHours(static::DAY_CUTOFF_HOUR)->subSecond()->utc();
    }

    /**
     * Get today's effective date string (Y-m-d format).
     *
     * @return string e.g., "2024-01-15"
     */
    public static function getEffectiveDateString(): string
    {
        return static::getEffectiveDate()->toDateString();
    }

    /**
     * Midnight of an effective date in the household's timezone. Defaults to today's.
     * Only the calendar date of $date is used, whatever its timezone.
     */
    protected static function householdDate(?Carbon $date): Carbon
    {
        $date = $date ?? static::getEffectiveDate();

        return Carbon::parse($date->toDateString(), Timezone::household());
    }
}
