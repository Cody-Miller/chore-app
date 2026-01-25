<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait HasEffectiveDate
{
    /**
     * The hour at which a new "effective day" begins.
     * Pills given between midnight and this hour count for the previous day.
     */
    const DAY_CUTOFF_HOUR = 3;

    /**
     * Get the "effective date" for a given timestamp.
     * If the time is before the cutoff hour, returns the previous day.
     *
     * @param  Carbon|null  $timestamp  Defaults to now()
     * @return Carbon Date-only Carbon instance (time set to 00:00:00)
     */
    public static function getEffectiveDate(?Carbon $timestamp = null): Carbon
    {
        $timestamp = $timestamp ?? now();

        if ($timestamp->hour < static::DAY_CUTOFF_HOUR) {
            return $timestamp->copy()->subDay()->startOfDay();
        }

        return $timestamp->copy()->startOfDay();
    }

    /**
     * Get the start of the "effective day" for a given date.
     * The effective day starts at DAY_CUTOFF_HOUR on the calendar date.
     *
     * @param  Carbon|null  $date  The effective date (not timestamp)
     * @return Carbon Start of effective day (e.g., 2024-01-15 03:00:00)
     */
    public static function getEffectiveDayStart(?Carbon $date = null): Carbon
    {
        $date = $date ?? static::getEffectiveDate();

        return $date->copy()->startOfDay()->addHours(static::DAY_CUTOFF_HOUR);
    }

    /**
     * Get the end of the "effective day" for a given date.
     * The effective day ends at DAY_CUTOFF_HOUR on the next calendar date.
     *
     * @param  Carbon|null  $date  The effective date (not timestamp)
     * @return Carbon End of effective day (e.g., 2024-01-16 02:59:59)
     */
    public static function getEffectiveDayEnd(?Carbon $date = null): Carbon
    {
        $date = $date ?? static::getEffectiveDate();

        return $date->copy()->addDay()->startOfDay()
            ->addHours(static::DAY_CUTOFF_HOUR)
            ->subSecond();
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
}
