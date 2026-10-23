<?php

namespace App\Services\Api;

use App\Exceptions\ApiException;
use App\Models\Chore;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\User;
use App\Support\Speech;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds pets, pills, chores and users from the names people actually say.
 *
 * Matching runs in order and stops at the first rule with any hits: exact slug,
 * case-insensitive name, slugified name, then whole words inside the slug
 * ("litter box" finds "Clean Litter Box"). One hit wins; several is ambiguous.
 */
class NameResolver
{
    /**
     * Not-found errors list the options when there are this many or fewer.
     */
    const MAX_LISTED_OPTIONS = 10;

    public static function pet(string $input): Pet
    {
        return static::resolve(Pet::orderBy('name')->get(), $input, 'pet', 'pet');
    }

    /**
     * An active pill, optionally limited to one pet's pills.
     */
    public static function pill(string $input, ?Pet $pet = null): Pill
    {
        $pills = Pill::active()
            ->whereHas('pet')
            ->with('pet')
            ->when($pet, fn ($q) => $q->where('pet_id', $pet->id))
            ->orderBy('name')
            ->get();

        return static::resolve($pills, $input, 'pill', 'medication', $pet ? " for {$pet->name}" : '');
    }

    public static function chore(string $input): Chore
    {
        return static::resolve(Chore::orderBy('name')->get(), $input, 'chore', 'chore');
    }

    /**
     * A household member by id, full name or first name. Defaults to the shared API User.
     */
    public static function user(?string $input): User
    {
        if ($input === null || trim($input) === '') {
            return User::apiUser();
        }

        $users = User::humans()->orderBy('name')->get();
        $needle = mb_strtolower(trim($input));

        foreach ([
            fn (User $user) => (string) $user->id === trim($input),
            fn (User $user) => mb_strtolower($user->name) === $needle,
            fn (User $user) => mb_strtolower($user->first_name) === $needle,
        ] as $rule) {
            $found = $users->filter($rule);

            if ($found->count() === 1) {
                return $found->first();
            }
            if ($found->count() > 1) {
                throw new ApiException(
                    "More than one person is named {$input}. Use their id instead.",
                    'ambiguous_user',
                    409,
                    ['candidates' => $found->map(fn (User $user) => ['id' => $user->id, 'first_name' => $user->first_name])->values()]
                );
            }
        }

        throw new ApiException(
            "I couldn't find anyone named {$input}. Try ".Speech::list($users->pluck('first_name'), 'or').'.',
            'user_not_found',
            404,
            ['candidates' => $users->map(fn (User $user) => ['id' => $user->id, 'first_name' => $user->first_name])->values()]
        );
    }

    /**
     * How a pet, pill or chore is listed in "candidates".
     */
    public static function describe(Model $model): array
    {
        $description = ['name' => $model->name, 'slug' => $model->slug];

        if ($model instanceof Pill) {
            $description['pet'] = $model->pet?->name;
        }

        return $description;
    }

    /**
     * Pick the one model matching the input, or throw a not-found / ambiguous error.
     */
    protected static function resolve(Collection $models, string $input, string $code, string $label, string $context = ''): Model
    {
        $input = trim($input);
        $name = mb_strtolower($input);
        $slug = Str::slug($input);
        // Within one pet's pills there is no need to repeat the pet's name
        $spoken = fn (Model $model) => $context === '' ? static::spokenName($model) : $model->name;

        foreach ([
            fn (Model $model) => $model->slug === $input,
            fn (Model $model) => mb_strtolower($model->name) === $name,
            fn (Model $model) => $slug !== '' && $model->slug === $slug,
            fn (Model $model) => $slug !== '' && str_contains("-{$model->slug}-", "-{$slug}-"),
        ] as $rule) {
            $found = $models->filter($rule);

            if ($found->count() === 1) {
                return $found->first();
            }
            if ($found->count() > 1) {
                throw new ApiException(
                    "More than one {$label}{$context} matches {$input}: ".Speech::list($found->map($spoken), 'or').'. Which one?',
                    "ambiguous_{$code}",
                    409,
                    ['candidates' => $found->map(fn ($model) => static::describe($model))->values()]
                );
            }
        }

        $message = "I couldn't find a {$label} called {$input}{$context}.";
        $extra = [];

        if ($models->isNotEmpty() && $models->count() <= self::MAX_LISTED_OPTIONS) {
            $message .= ' Try '.Speech::list($models->map($spoken), 'or').'.';
            $extra['candidates'] = $models->map(fn ($model) => static::describe($model))->values();
        }

        throw new ApiException($message, "{$code}_not_found", 404, $extra);
    }

    /**
     * Pills are read out with their pet ("Kage's Metacam") so similar names stay distinguishable.
     */
    protected static function spokenName(Model $model): string
    {
        return $model instanceof Pill && $model->pet
            ? Speech::possessive($model->pet->name).' '.$model->name
            : $model->name;
    }
}
