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
 * Exact rules run first, in order, and stop at the first rule with any hits: exact slug,
 * case-insensitive name, slugified name, then whole words inside the slug
 * ("litter box" finds "Clean Litter Box"). One hit wins; several is ambiguous.
 *
 * Chores (and pets, where the caller asks) then fall back to fuzzy matching for
 * speech-to-text mishearings and extra words (see FuzzyMatcher). People always do.
 */
class NameResolver
{
    /**
     * Not-found errors list the options when there are this many or fewer.
     */
    const MAX_LISTED_OPTIONS = 10;

    /**
     * A fuzzy match needs at least this score (0 to 1) to be used.
     */
    const FUZZY_MIN = 0.6;

    /**
     * Matches within this much of the best score are a close call, so the user is asked.
     */
    const FUZZY_CLOSE = 0.1;

    /**
     * A close call names at most this many options.
     */
    const FUZZY_MAX_OPTIONS = 3;

    /**
     * Below FUZZY_MIN but at least this, a not-found error suggests the match ("Did you mean ...?").
     */
    const FUZZY_SUGGEST = 0.35;

    /**
     * Matching a whole spoken sentence ("text") needs a higher score, since it has more words to match by chance.
     */
    const TEXT_MIN = 0.7;

    /**
     * People are a short list of single names, so a fuzzy match needs to be closer.
     */
    const USER_MIN = 0.7;

    /**
     * @param  bool  $fuzzy  also try fuzzy matching (read-only callers like pills/today?pet=)
     */
    public static function pet(string $input, bool $fuzzy = false): Pet
    {
        return static::resolve(Pet::orderBy('name')->get(), $input, 'pet', 'pet', fuzzy: $fuzzy);
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

    /**
     * A chore by name, slug or alias, allowing for mishearings and extra words.
     *
     * @param  string|null  $text  the whole sentence as spoken, used to break ties or as a last resort
     */
    public static function chore(string $input, ?string $text = null): Chore
    {
        return static::resolve(Chore::orderBy('name')->get(), $input, 'chore', 'chore', fuzzy: true, text: $text);
    }

    /**
     * A household member by id, full name or first name. Defaults to the shared API User.
     * Filler words ("mark", "okay", "did") are ignored and close-sounding names match ("Kodi" finds Cody).
     */
    public static function user(?string $input): User
    {
        if ($input === null || trim($input) === '') {
            return User::apiUser();
        }

        $users = User::humans()->orderBy('name')->get();
        $firstNames = $users->map(fn (User $user) => mb_strtolower($user->first_name))->all();
        // Keep a filler word that is someone's name, so a person called Mark can still be found
        $cleaned = implode(' ', FuzzyMatcher::contentWords($input, $firstNames));

        foreach (array_unique([trim($input), $cleaned]) as $attempt) {
            if ($attempt === '') {
                continue;
            }

            $needle = mb_strtolower($attempt);

            foreach ([
                fn (User $user) => (string) $user->id === $attempt,
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
                        ['candidates' => static::describeUsers($found)]
                    );
                }
            }
        }

        // Each word said is compared with each first name; the best word counts
        $ranked = collect(FuzzyMatcher::rank($users, fn (User $user) => [$user->first_name], $cleaned))
            ->filter(fn ($match) => $match['score'] >= self::USER_MIN);

        if ($ranked->isNotEmpty()) {
            $close = static::closeCalls($ranked);

            if ($close->count() === 1) {
                return $close->first();
            }

            throw new ApiException(
                'Did you mean '.Speech::list($close->map(fn (User $user) => $user->first_name), 'or').'?',
                'ambiguous_user',
                409,
                ['candidates' => static::describeUsers($close)]
            );
        }

        throw new ApiException(
            "I couldn't find anyone named {$input}. Try ".Speech::list($users->pluck('first_name'), 'or').'.',
            'user_not_found',
            404,
            ['candidates' => static::describeUsers($users)]
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
    protected static function resolve(
        Collection $models,
        string $input,
        string $code,
        string $label,
        string $context = '',
        bool $fuzzy = false,
        ?string $text = null,
    ): Model {
        $input = trim($input);
        $text = $text !== null && trim($text) !== '' ? trim($text) : null;
        // Within one pet's pills there is no need to repeat the pet's name
        $spoken = fn (Model $model) => $context === '' ? static::spokenName($model) : $model->name;
        $ambiguous = fn (Collection $found, string $message) => new ApiException(
            $message,
            "ambiguous_{$code}",
            409,
            ['candidates' => $found->map(fn ($model) => static::describe($model))->values()]
        );

        // Exact rules on the input as given, then without leading filler and articles ("the kitty litter")
        $attempts = [$input];
        if ($fuzzy && ($stripped = FuzzyMatcher::stripLeading($input)) !== '' && $stripped !== mb_strtolower($input)) {
            $attempts[] = $stripped;
        }

        foreach ($attempts as $attempt) {
            $found = static::exactMatches($models, $attempt);

            if ($found->count() === 1) {
                return $found->first();
            }
            if ($found->count() > 1) {
                // "litter" is two chores, but "I scooped the litter box" says which
                if ($text && ($picked = static::fuzzyPick($found, $text, 0.0))) {
                    return $picked;
                }

                throw $ambiguous($found, "More than one {$label}{$context} matches {$input}: ".Speech::list($found->map($spoken), 'or').'. Which one?');
            }
        }

        $suggestions = collect();

        if ($fuzzy) {
            $ranked = collect(FuzzyMatcher::rank($models, fn (Model $model) => static::names($model), $input));
            $matches = $ranked->filter(fn ($match) => $match['score'] >= self::FUZZY_MIN);

            if ($matches->isNotEmpty()) {
                $close = static::closeCalls($matches);

                if ($close->count() === 1) {
                    return $close->first();
                }
                if ($text && ($picked = static::fuzzyPick($close, $text, 0.0))) {
                    return $picked;
                }

                throw $ambiguous($close, 'Did you mean '.Speech::list($close->map($spoken), 'or').'?');
            }

            // Nothing in the short phrase; try the whole sentence
            if ($text && ($picked = static::fuzzyPick($models, $text, self::TEXT_MIN))) {
                return $picked;
            }

            $suggestions = static::closeCalls($ranked->filter(fn ($match) => $match['score'] >= self::FUZZY_SUGGEST), 0.15);
        }

        $message = "I couldn't find a {$label} called {$input}{$context}.";
        $extra = [];

        if ($suggestions->isNotEmpty()) {
            $message .= ' Did you mean '.Speech::list($suggestions->map($spoken), 'or').'?';
            $extra['candidates'] = $suggestions->map(fn ($model) => static::describe($model))->values();
        } elseif ($models->isNotEmpty() && $models->count() <= self::MAX_LISTED_OPTIONS) {
            $message .= ' Try '.Speech::list($models->map($spoken), 'or').'.';
            $extra['candidates'] = $models->map(fn ($model) => static::describe($model))->values();
        }

        throw new ApiException($message, "{$code}_not_found", 404, $extra);
    }

    /**
     * Models hit by the first exact rule with any hits: slug, name, slugified name, whole words.
     * A chore's aliases count as names.
     */
    protected static function exactMatches(Collection $models, string $input): Collection
    {
        $name = mb_strtolower($input);
        $slug = Str::slug($input);
        $slugs = fn (Model $model) => [$model->slug, ...array_map(fn ($alias) => Str::slug($alias), static::aliases($model))];

        foreach ([
            fn (Model $model) => $model->slug === $input,
            fn (Model $model) => in_array($name, array_map('mb_strtolower', static::names($model)), true),
            fn (Model $model) => $slug !== '' && in_array($slug, $slugs($model), true),
            fn (Model $model) => $slug !== '' && collect($slugs($model))->contains(fn ($candidate) => str_contains("-{$candidate}-", "-{$slug}-")),
        ] as $rule) {
            $found = $models->filter($rule);

            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        return collect();
    }

    /**
     * The one model the text clearly points to: at least $min and ahead of the rest by FUZZY_CLOSE.
     */
    protected static function fuzzyPick(Collection $models, string $text, float $min): ?Model
    {
        $ranked = FuzzyMatcher::rank($models, fn (Model $model) => static::names($model), $text);
        $best = $ranked[0] ?? null;
        $second = $ranked[1]['score'] ?? 0.0;

        return $best && $best['score'] > 0 && $best['score'] >= $min && $best['score'] - $second >= self::FUZZY_CLOSE
            ? $best['item']
            : null;
    }

    /**
     * From matches ranked best first, the ones within $margin of the best, up to FUZZY_MAX_OPTIONS.
     */
    protected static function closeCalls(Collection $ranked, float $margin = self::FUZZY_CLOSE): Collection
    {
        $best = $ranked->first()['score'] ?? 0;

        return $ranked
            ->filter(fn ($match) => $match['score'] >= $best - $margin)
            ->take(self::FUZZY_MAX_OPTIONS)
            ->pluck('item')
            ->values();
    }

    /**
     * The names something can be called: its name, plus a chore's aliases.
     */
    protected static function names(Model $model): array
    {
        return [$model->name, ...static::aliases($model)];
    }

    protected static function aliases(Model $model): array
    {
        return $model instanceof Chore ? $model->aliases ?? [] : [];
    }

    protected static function describeUsers(Collection $users): Collection
    {
        return $users->map(fn (User $user) => ['id' => $user->id, 'first_name' => $user->first_name])->values();
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
