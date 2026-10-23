<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use App\Services\Api\NameResolver;
use App\Support\Speech;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Works out which pill and which dose slot a "gave the meds" request means, then logs it.
 */
class MedicationGiver
{
    /**
     * Pick the pill from an optional pet name and an optional pill name.
     * A pet alone must have exactly one active pill; a pill alone implies its pet.
     */
    public static function choosePill(?string $petName, ?string $pillName): Pill
    {
        if (!$petName && !$pillName) {
            throw new ApiException('Which pet or medication? Name at least one.', 'pet_or_pill_required', 422);
        }

        $pet = $petName ? NameResolver::pet($petName) : null;

        if ($pillName) {
            return NameResolver::pill($pillName, $pet);
        }

        $pills = $pet->activePills()->with('pet')->orderBy('name')->get();

        if ($pills->isEmpty()) {
            throw new ApiException("{$pet->name} doesn't have any active medications.", 'no_active_pills', 422);
        }
        if ($pills->count() > 1) {
            throw new ApiException(
                "{$pet->name} has more than one medication: ".Speech::list($pills->pluck('name'), 'or').'. Which one?',
                'ambiguous_pill',
                409,
                ['candidates' => $pills->map(fn (Pill $pill) => NameResolver::describe($pill))->values()]
            );
        }

        return $pills->first();
    }

    /**
     * Pick the dose slot to fill at the given moment.
     * Without a time that's the earliest slot already due. A slot that isn't due yet needs $early.
     */
    public static function chooseSlot(Pill $pill, Carbon $at, ?string $time, bool $early): array
    {
        $slots = $pill->doseSlots($at);
        $name = Speech::possessive($pill->pet->name).' '.$pill->name;

        if ($time !== null) {
            $slot = $slots->firstWhere('time', static::normalizeTime($time));

            if (!$slot) {
                throw new ApiException(
                    "{$name} isn't scheduled at {$time}. Its times are ".Speech::list($slots->pluck('label')).'.',
                    'invalid_slot',
                    422,
                    ['scheduled_times' => $slots->pluck('time')]
                );
            }
            if ($slot['status'] === 'given') {
                $givenBy = $slot['log']->user?->first_name ?? 'someone';

                throw new ApiException(
                    "The {$slot['label']} dose of {$name} was already given at ".Speech::time($slot['log']->administered_at)." by {$givenBy}.",
                    'slot_already_given',
                    422
                );
            }
        } else {
            $slot = $slots->firstWhere('status', 'due') ?? $slots->firstWhere('status', 'upcoming');

            if (!$slot) {
                throw new ApiException("All of {$name} doses have already been given today.", 'all_doses_given', 422);
            }
        }

        if ($slot['status'] === 'upcoming' && !$early) {
            throw new ApiException(
                "{$name} isn't due until {$slot['label']}. Nothing was logged.",
                'dose_not_due',
                422,
                ['next_time' => $slot['time'], 'next_label' => $slot['label']]
            );
        }

        return $slot;
    }

    public static function give(Pill $pill, array $slot, User $user, Carbon $at, ?string $notes): PillLog
    {
        return PillLog::create([
            'pill_id' => $pill->id,
            'user_id' => $user->id,
            'administered_at' => $at,
            'scheduled_time' => $slot['time'],
            'notes' => $notes,
        ]);
    }

    /**
     * "8am", "8:00 PM" or "20:00" as "HH:MM", or null if it isn't a time.
     */
    protected static function normalizeTime(string $time): ?string
    {
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
