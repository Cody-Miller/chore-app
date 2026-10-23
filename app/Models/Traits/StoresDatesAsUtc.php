<?php

namespace App\Models\Traits;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Laravel writes a date in whatever timezone its Carbon instance is in. Convert
 * to the app's timezone (UTC) first, so a time in someone's local zone is stored correctly.
 */
trait StoresDatesAsUtc
{
    public function fromDateTime($value)
    {
        if ($value instanceof DateTimeInterface) {
            $value = Carbon::instance($value)->setTimezone(config('app.timezone'));
        }

        return parent::fromDateTime($value);
    }
}
