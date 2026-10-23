<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Helpers for API messages that a voice assistant reads out loud.
 */
class Speech
{
    /**
     * "A", "A and B", "A, B and C". Long lists end with "and N more".
     */
    public static function list(iterable $items, string $conjunction = 'and', int $limit = 8): string
    {
        $items = collect($items)->values();

        if ($items->count() > $limit) {
            $items = $items->take($limit)->push(($items->count() - $limit).' more');
        }

        return $items->join(', ', " {$conjunction} ");
    }

    /**
     * "1 chore", "3 chores".
     */
    public static function count(int $count, string $noun): string
    {
        return $count.' '.Str::plural($noun, $count);
    }

    /**
     * "Kage's", "Max's", "Bess'".
     */
    public static function possessive(string $name): string
    {
        return str_ends_with(strtolower($name), 's') ? "{$name}'" : "{$name}'s";
    }

    /**
     * "8:00 AM".
     */
    public static function time(Carbon|string $time): string
    {
        return ($time instanceof Carbon ? $time : Carbon::parse($time))->format('g:i A');
    }

    /**
     * "No chores are due." / "1 chore is due: Dishes." / "3 chores are due: A, B and C."
     */
    public static function summarize(iterable $names, string $noun, string $state): string
    {
        $names = collect($names);

        if ($names->isEmpty()) {
            return 'No '.Str::plural($noun)." are {$state}.";
        }

        return static::count($names->count(), $noun).' '.($names->count() === 1 ? 'is' : 'are')." {$state}: ".static::list($names).'.';
    }

    /**
     * "today", "tomorrow", "yesterday", a weekday within the next week, otherwise "October 30".
     */
    public static function day(Carbon $date): string
    {
        $days = (int) round(now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false));

        return match (true) {
            $days === 0 => 'today',
            $days === 1 => 'tomorrow',
            $days === -1 => 'yesterday',
            $days > 1 && $days < 7 => $date->format('l'),
            default => $date->format('F j'),
        };
    }
}
