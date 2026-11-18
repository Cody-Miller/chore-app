<?php

namespace App\Services\Api;

/**
 * Scores how well words heard by speech-to-text match a name, allowing for
 * extra words ("took out the trash"), word endings ("vacuumed") and
 * mishearings ("kitty layer", "kiddie liter", "Kodi").
 *
 * Scores run from 0 to 1. A name scores by how much of it the input covers:
 * each word of the name takes its best match among the input words, weighted
 * so filler ("the", "my") counts for nothing and common chore verbs ("clean",
 * "take") count for less than the words that tell chores apart.
 */
class FuzzyMatcher
{
    /**
     * Words that never tell one name from another.
     */
    const STOPWORDS = [
        'a', 'an', 'the', 'my', 'your', 'our', 'their', 'his', 'her', 'its', 'this', 'that', 'these', 'those',
        'of', 'to', 'and', 'in', 'on', 'for', 'with', 'out', 'up', 'off', 'some', 'all',
    ];

    /**
     * Command and filler words people say around a name ("Mark, Cody, completed ...", "okay so I did ...").
     */
    const FILLER = [
        'mark', 'marked', 'okay', 'ok', 'so', 'hey', 'hi', 'hello', 'um', 'uh', 'er', 'erm', 'hmm', 'well',
        'yeah', 'yes', 'alright', 'please', 'now', 'just', 'also', 'then', 'again', 'already',
        'has', 'have', 'had', 'did', 'does', 'do', 'done', 'was', 'were', 'is', 'been', 'i', 'we', 'it', 'me',
        'completed', 'complete', 'finished', 'finish', 'by', 'as', 'when', 'last', 'who',
    ];

    /**
     * Verbs that start many chore names, so they say little about which chore is meant.
     */
    const CHORE_VERBS = [
        'do', 'run', 'take', 'clean', 'wash', 'change', 'empty', 'scoop', 'make', 'feed', 'wipe', 'mop',
        'sweep', 'dust', 'put', 'load', 'unload', 'fold', 'start', 'water', 'replace', 'fix', 'check', 'give',
        'vacuum', 'tidy', 'clear',
    ];

    /**
     * Weight of a CHORE_VERBS word in a name, relative to 1 for other words.
     */
    const VERB_WEIGHT = 0.35;

    /**
     * Irregular forms the suffix rules in stem() can't reach.
     */
    const LEMMAS = [
        'took' => 'take', 'taken' => 'take', 'did' => 'do', 'done' => 'do', 'does' => 'do', 'ran' => 'run',
        'swept' => 'sweep', 'fed' => 'feed', 'made' => 'make', 'gave' => 'give', 'given' => 'give',
        'hung' => 'hang', 'threw' => 'throw', 'thrown' => 'throw', 'brought' => 'bring', 'bought' => 'buy',
        'went' => 'go', 'got' => 'get', 'emptied' => 'empty',
    ];

    /**
     * Lowercase words with punctuation and possessive "'s" removed: "Mark, Cody's" → ["mark", "cody"].
     *
     * @return string[]
     */
    public static function words(string $input): array
    {
        $input = mb_strtolower($input);
        $input = preg_replace("/['’]s\b/u", '', $input);
        $input = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $input);

        return array_values(array_filter(explode(' ', $input), fn ($word) => $word !== ''));
    }

    /**
     * The input with leading filler, articles and possessives removed: "the kitty litter" → "kitty litter".
     */
    public static function stripLeading(string $input): string
    {
        $words = static::words($input);

        while ($words && (in_array($words[0], static::FILLER) || in_array($words[0], static::STOPWORDS))) {
            array_shift($words);
        }

        return implode(' ', $words);
    }

    /**
     * The words that could name something: no filler or stopwords, except any listed in $keep
     * (so a person really called Mark isn't stripped).
     *
     * @return string[]
     */
    public static function contentWords(string $input, array $keep = []): array
    {
        return array_values(array_filter(
            static::words($input),
            fn ($word) => in_array($word, $keep) || (!in_array($word, static::FILLER) && !in_array($word, static::STOPWORDS))
        ));
    }

    /**
     * A rough stem so "vacuumed", "vacuuming" and "vacuum" compare equal.
     */
    public static function stem(string $word): string
    {
        if (isset(static::LEMMAS[$word])) {
            return static::LEMMAS[$word];
        }

        $length = strlen($word);
        $word = match (true) {
            $length > 4 && str_ends_with($word, 'ies') => substr($word, 0, -3).'y',
            $length > 4 && str_ends_with($word, 'ied') => substr($word, 0, -3).'y',
            $length > 5 && str_ends_with($word, 'ing') => static::undouble(substr($word, 0, -3)),
            $length > 4 && str_ends_with($word, 'ed') => static::undouble(substr($word, 0, -2)),
            $length > 4 && (bool) preg_match('/(s|x|z|ch|sh)es$/', $word) => substr($word, 0, -2),
            $length > 3 && str_ends_with($word, 's') && !str_ends_with($word, 'ss') => substr($word, 0, -1),
            default => $word,
        };

        // "change" and "changed" both become "chang"
        return strlen($word) > 4 && str_ends_with($word, 'e') ? substr($word, 0, -1) : $word;
    }

    /**
     * How alike two words are, by spelling or by sound. Below 0.5 counts as no match.
     */
    public static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $spelling = 1 - levenshtein($a, $b) / max(strlen($a), strlen($b));
        $sound = 0.0;
        $codeA = metaphone($a);
        $codeB = metaphone($b);

        if ($codeA !== '' && $codeB !== '') {
            $sound = $codeA === $codeB
                // Short codes like "KT" (cat, kitty, kid) are weaker evidence
                ? (strlen($codeA) >= 3 ? 0.85 : 0.75)
                : 0.7 * (1 - levenshtein($codeA, $codeB) / max(strlen($codeA), strlen($codeB)));
        }

        $similarity = max($spelling, $sound);

        return $similarity >= 0.5 ? $similarity : 0.0;
    }

    /**
     * How well the input covers a name, from 0 to 1. Extra input words don't count against it.
     */
    public static function score(string $name, string $input): float
    {
        $inputWords = array_map([static::class, 'stem'], static::contentWords($input));

        // Also try neighbouring words joined, so "dish washer" can match "dishwasher"
        $single = $inputWords;
        for ($i = 0; $i < count($single) - 1; $i++) {
            $inputWords[] = $single[$i].$single[$i + 1];
        }

        if (!$inputWords) {
            return 0.0;
        }

        $nameWords = static::words($name);
        $weights = array_map(fn ($word) => match (true) {
            in_array($word, static::STOPWORDS) || in_array($word, static::FILLER) => 0.0,
            in_array(static::stem($word), static::CHORE_VERBS) => static::VERB_WEIGHT,
            default => 1.0,
        }, $nameWords);

        // A name made only of small words ("Do It") still has to match on them
        if (array_sum($weights) == 0) {
            $weights = array_fill(0, count($nameWords), 1.0);
        }

        $total = 0.0;
        foreach ($nameWords as $i => $word) {
            if ($weights[$i] > 0) {
                $stem = static::stem($word);
                $total += $weights[$i] * max(array_map(fn ($inputWord) => static::similarity($stem, $inputWord), $inputWords));
            }
        }

        return array_sum($weights) > 0 ? $total / array_sum($weights) : 0.0;
    }

    /**
     * Score each item by its best-matching name, highest first.
     *
     * @param  iterable  $items
     * @param  callable  $names  item => string[] (a name and any aliases)
     * @return array<int, array{item: mixed, score: float}>
     */
    public static function rank(iterable $items, callable $names, string $input): array
    {
        $ranked = [];

        foreach ($items as $item) {
            $scores = array_map(fn ($name) => static::score($name, $input), $names($item));
            $ranked[] = ['item' => $item, 'score' => round($scores ? max($scores) : 0.0, 4)];
        }

        // Stable sort keeps the caller's order (alphabetical) among equal scores
        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $ranked;
    }

    private static function undouble(string $word): string
    {
        return preg_match('/([b-df-hj-km-np-rtv-z])\1$/', $word) ? substr($word, 0, -1) : $word;
    }
}
