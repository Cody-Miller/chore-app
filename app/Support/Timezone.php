<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeZone;

/**
 * The timezones in play for one request.
 *
 * Everything is stored and computed in UTC. The household timezone is where the
 * house is: pill schedules, when "today" starts and chore due dates follow it.
 * The viewer timezone belongs to whoever is looking (the logged-in user on the
 * website, the token on the API) and only changes how timestamps are shown and read.
 */
class Timezone
{
    const DEFAULT = 'America/New_York';

    private ?string $viewer = null;

    public static function household(): string
    {
        return static::valid(config('app.household_timezone')) ?? self::DEFAULT;
    }

    /**
     * The viewer's timezone, falling back to the household's.
     */
    public static function viewer(): string
    {
        return app(self::class)->viewer ?? static::household();
    }

    public static function setViewer(?string $timezone): void
    {
        app(self::class)->viewer = static::valid($timezone);
    }

    /**
     * A date range as UTC copies, ready for a query. Laravel would otherwise bind
     * each date in its own timezone without converting it.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function utcRange(Carbon $start, Carbon $end): array
    {
        return [$start->copy()->utc(), $end->copy()->utc()];
    }

    /**
     * The timezone if it's a real one, otherwise null.
     */
    public static function valid(mixed $timezone): ?string
    {
        return is_string($timezone) && in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : null;
    }
}
