<?php

namespace Tests\Unit;

use App\Services\Api\FuzzyMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FuzzyMatcherTest extends TestCase
{
    const CHORES = ['Kitty Litter', 'Cat Food', 'Run Dishwasher', 'Take Out Trash', 'Take Out Recycling', 'Vacuum Living Room', 'Wash Dishes'];

    private function best(string $input, array $names = self::CHORES): array
    {
        return FuzzyMatcher::rank($names, fn ($name) => [$name], $input)[0];
    }

    public static function misheardChores(): array
    {
        return [
            'mishearing' => ['kitty layer', 'Kitty Litter'],
            'mishearing, both words' => ['kiddie liter', 'Kitty Litter'],
            'leading article' => ['the kitty litter', 'Kitty Litter'],
            'possessive' => ['my kitty litter', 'Kitty Litter'],
            'verb with an ending' => ['vacuumed the living room', 'Vacuum Living Room'],
            'irregular verb' => ['took out the trash', 'Take Out Trash'],
            'extra word, missing verb' => ['dishwasher cycle', 'Run Dishwasher'],
            'command words left in' => ['completed dishwasher cycle', 'Run Dishwasher'],
            'question words left in' => ['when was the kitty litter last done', 'Kitty Litter'],
        ];
    }

    #[DataProvider('misheardChores')]
    public function test_misheard_chores_match_with_a_confident_score(string $input, string $expected): void
    {
        $best = $this->best($input);

        $this->assertSame($expected, $best['item']);
        $this->assertGreaterThanOrEqual(0.6, $best['score']);
    }

    public function test_unrelated_words_score_low(): void
    {
        $this->assertLessThan(0.6, $this->best('cat mats')['score']);
        $this->assertLessThan(0.35, $this->best('mow the lawn')['score']);
        $this->assertSame(0.0, FuzzyMatcher::score('Kitty Litter', ''));
    }

    public function test_misheard_first_names(): void
    {
        $this->assertSame('Cody', $this->best('kodi', ['Cody', 'Sarah'])['item']);
        $this->assertGreaterThanOrEqual(0.7, $this->best('kodi', ['Cody', 'Sarah'])['score']);
        $this->assertGreaterThanOrEqual(0.7, $this->best('sara', ['Cody', 'Sarah'])['score']);
        $this->assertLessThan(0.7, $this->best('mike', ['Cody', 'Sarah'])['score']);
    }

    public function test_filler_and_punctuation_are_stripped(): void
    {
        $this->assertSame(['cody'], FuzzyMatcher::contentWords('Mark, Cody,'));
        $this->assertSame(['cody'], FuzzyMatcher::contentWords('okay so has Cody'));
        $this->assertSame(['mark', 'cody'], FuzzyMatcher::contentWords('mark cody', ['mark']));
        $this->assertSame('kitty litter', FuzzyMatcher::stripLeading('The kitty litter!'));
        $this->assertSame(['kage'], FuzzyMatcher::words("Kage's"));
    }

    public function test_stems(): void
    {
        $this->assertSame('vacuum', FuzzyMatcher::stem('vacuumed'));
        $this->assertSame('mop', FuzzyMatcher::stem('mopped'));
        $this->assertSame('dish', FuzzyMatcher::stem('dishes'));
        $this->assertSame('take', FuzzyMatcher::stem('took'));
        $this->assertSame(FuzzyMatcher::stem('change'), FuzzyMatcher::stem('changed'));
    }

    public function test_long_input_stays_cheap(): void
    {
        // Neighbouring words are joined once each, not repeatedly
        $sentence = str_repeat('okay so I think we finally took out the trash this morning ', 20);

        $this->assertSame('Take Out Trash', $this->best($sentence)['item']);
    }
}
