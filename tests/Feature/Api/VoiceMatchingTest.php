<?php

namespace Tests\Feature\Api;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Names as faster-whisper hears them, through the endpoints Home Assistant calls.
 */
class VoiceMatchingTest extends TestCase
{
    use RefreshDatabase;

    private User $cody;
    private User $sarah;
    private Chore $litter;
    private Pet $kage;
    private Pill $keppra;

    protected function setUp(): void
    {
        parent::setUp();

        // "Now" is 10:00 in the household (New York)
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'America/New_York'));
        $this->cody = User::factory()->create(['name' => 'Cody Miller', 'timezone' => 'America/New_York']);
        $this->sarah = User::factory()->create(['name' => 'Sarah Smith']);
        Sanctum::actingAs($this->cody);

        $this->litter = $this->chore('Kitty Litter', ['litter box', 'scoop the litter']);
        foreach (['Cat Food', 'Run Dishwasher', 'Take Out Trash', 'Take Out Recycling', 'Vacuum Living Room', 'Wash Dishes'] as $name) {
            $this->chore($name);
        }

        $this->kage = Pet::factory()->create(['name' => 'Kage', 'slug' => 'kage']);
        $luna = Pet::factory()->create(['name' => 'Luna', 'slug' => 'luna']);
        $this->keppra = $this->pill($this->kage, 'Keppra', ['08:00', '20:00']);
        $this->pill($luna, 'Gabapentin', ['09:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function chore(string $name, ?array $aliases = null): Chore
    {
        return Chore::factory()->create(['name' => $name, 'slug' => Str::slug($name), 'occurrence_hours' => 24, 'aliases' => $aliases]);
    }

    private function pill(Pet $pet, string $name, array $times): Pill
    {
        return Pill::factory()->create(['pet_id' => $pet->id, 'name' => $name, 'slug' => Str::slug($name), 'scheduled_times' => $times]);
    }

    private function dryRun(array $body)
    {
        return $this->postJson('/api/chores/complete', $body + ['dry_run' => true]);
    }

    public static function misheardChores(): array
    {
        return [
            ['kitty layer', 'Kitty Litter'],
            ['kiddie liter', 'Kitty Litter'],
            ['the kitty litter', 'Kitty Litter'],
            ['my kitty litter', 'Kitty Litter'],
            ['our litter box', 'Kitty Litter'],
            ['scooped the litter', 'Kitty Litter'],
            ['vacuumed the living room', 'Vacuum Living Room'],
            ['took out the trash', 'Take Out Trash'],
            ['dishwasher cycle', 'Run Dishwasher'],
        ];
    }

    #[DataProvider('misheardChores')]
    public function test_misheard_chores_are_matched(string $heard, string $chore): void
    {
        $this->dryRun(['chore' => $heard])
            ->assertOk()
            ->assertJsonPath('data.chore.name', $chore)
            ->assertJsonPath('message', "Would mark {$chore} as done.");

        $this->assertSame(0, ChoreLog::count());
    }

    public function test_close_calls_ask_which_one(): void
    {
        $this->dryRun(['chore' => 'the trash and recycling'])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ambiguous_chore')
            ->assertJsonPath('error', 'Did you mean Take Out Recycling or Take Out Trash?')
            ->assertJsonPath('candidates.*.name', ['Take Out Recycling', 'Take Out Trash']);
    }

    public function test_weak_matches_are_not_found_but_suggested(): void
    {
        $this->dryRun(['chore' => 'cat mats'])
            ->assertNotFound()
            ->assertJsonPath('code', 'chore_not_found')
            ->assertJsonPath('error', "I couldn't find a chore called cat mats. Did you mean Cat Food or Kitty Litter?")
            ->assertJsonPath('candidates.*.name', ['Cat Food', 'Kitty Litter']);

        $this->dryRun(['chore' => 'mow the lawn'])
            ->assertNotFound()
            ->assertJsonPath('code', 'chore_not_found')
            ->assertJsonPath('error', "I couldn't find a chore called mow the lawn. Try Cat Food, Kitty Litter, Run Dishwasher, Take Out Recycling, Take Out Trash, Vacuum Living Room or Wash Dishes.");
    }

    public function test_spoken_sentence_breaks_ties_and_fills_in(): void
    {
        // "take out" is two chores; the sentence says which
        $this->dryRun(['chore' => 'take out', 'text' => 'I took out the recycling'])
            ->assertOk()
            ->assertJsonPath('data.chore.name', 'Take Out Recycling');
        $this->dryRun(['chore' => 'take out'])->assertStatus(409)->assertJsonPath('code', 'ambiguous_chore');

        // Nothing in "chore" matches, or there is no "chore" at all
        $this->dryRun(['chore' => 'the thing', 'text' => 'Mark, Cody, completed dishwasher cycle'])
            ->assertOk()
            ->assertJsonPath('data.chore.name', 'Run Dishwasher');
        $this->dryRun(['text' => 'Mark, Cody, completed dishwasher cycle'])
            ->assertOk()
            ->assertJsonPath('data.chore.name', 'Run Dishwasher');
    }

    public static function misheardPeople(): array
    {
        return [
            ['Kodi', 'Cody'],
            ['mark cody', 'Cody'],
            ['Mark, Cody,', 'Cody'],
            ['okay so Sara', 'Sarah'],
            ["Cody's", 'Cody'],
        ];
    }

    #[DataProvider('misheardPeople')]
    public function test_misheard_people_are_matched(string $heard, string $person): void
    {
        $this->dryRun(['chore' => 'dishwasher cycle', 'by' => $heard])
            ->assertOk()
            ->assertJsonPath('data.completed_by', $person)
            ->assertJsonPath('message', "Would mark Run Dishwasher as done for {$person}.");
    }

    public function test_split_with_a_misheard_name(): void
    {
        $this->postJson('/api/chores/complete', ['chore' => 'kitty layer', 'by' => 'mark sarah', 'split_with' => 'Kodi'])
            ->assertCreated()
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, 'Marked Kitty Litter as done, split between Sarah and Cody.'));
    }

    public function test_unknown_people_and_real_people_named_like_filler(): void
    {
        $this->dryRun(['chore' => 'dishes', 'by' => 'Bob'])
            ->assertNotFound()
            ->assertJsonPath('code', 'user_not_found')
            ->assertJsonPath('error', "I couldn't find anyone named Bob. Try Cody or Sarah.");
        $this->dryRun(['chore' => 'dishes', 'by' => 'hey'])->assertNotFound()->assertJsonPath('code', 'user_not_found');

        // Someone really called Mark can still be named, but "Mark, Cody" is still the command word
        User::factory()->create(['name' => 'Mark Jones']);
        $this->dryRun(['chore' => 'dishes', 'by' => 'Mark'])->assertOk()->assertJsonPath('data.completed_by', 'Mark');
        $this->dryRun(['chore' => 'dishes', 'by' => 'okay mark'])->assertOk()->assertJsonPath('data.completed_by', 'Mark');
        $this->dryRun(['chore' => 'dishes', 'by' => 'mark cody'])->assertOk()->assertJsonPath('data.completed_by', 'Cody');

        // Two close-sounding people is a question
        User::factory()->create(['name' => 'Kody Brown']);
        $this->dryRun(['chore' => 'dishes', 'by' => 'Kodi'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ambiguous_user')
            ->assertJsonPath('error', 'Did you mean Cody or Kody?');
    }

    public function test_when_was_a_chore_last_done(): void
    {
        $this->getJson('/api/chores/last?q=kitty+layer')
            ->assertOk()
            ->assertJsonPath('message', "Kitty Litter hasn't been done yet.")
            ->assertJsonPath('data.completed_at', null);

        ChoreLog::factory()->create([
            'chore_id' => $this->litter->id,
            'user_id' => $this->cody->id,
            'completed_at' => Carbon::parse('2026-11-17 19:10', 'America/New_York'),
        ]);

        $this->getJson('/api/chores/last?q=kitty+layer')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Kitty Litter was last done by Cody yesterday at 7:10 PM.')
            ->assertJsonPath('data.chore.name', 'Kitty Litter')
            ->assertJsonPath('data.completed_by', 'Cody')
            ->assertJsonPath('data.completed_at', '2026-11-17T19:10:00-05:00');

        $this->getJson('/api/chores/last?q=cat+mats')->assertNotFound()->assertJsonPath('code', 'chore_not_found');
        $this->getJson('/api/chores/last?q=take+out')->assertStatus(409)->assertJsonPath('code', 'ambiguous_chore');
        $this->getJson('/api/chores/last')->assertStatus(422)->assertJsonPath('code', 'validation_failed');
    }

    public function test_last_done_says_who_split_it_and_older_days(): void
    {
        ChoreLog::recordCompletion($this->litter, $this->sarah, $this->cody, Carbon::parse('2026-11-15 09:05', 'America/New_York'));

        $this->getJson('/api/chores/last?q=the+litter+box')
            ->assertOk()
            ->assertJsonPath('message', fn ($message) => in_array($message, [
                'Kitty Litter was last done by Sarah and Cody on Sunday at 9:05 AM.',
                'Kitty Litter was last done by Cody and Sarah on Sunday at 9:05 AM.',
            ]));
    }

    public function test_when_did_a_pet_last_have_medication(): void
    {
        $this->getJson('/api/pills/last?pet=kage')
            ->assertOk()
            ->assertJsonPath('message', "Kage hasn't had Keppra logged yet.");

        PillLog::create([
            'pill_id' => $this->keppra->id,
            'user_id' => $this->sarah->id,
            'administered_at' => Carbon::parse('2026-11-18 07:50', 'America/New_York'),
            'scheduled_time' => '08:00',
        ]);

        $this->getJson('/api/pills/last?pet=cage')
            ->assertOk()
            ->assertJsonPath('message', 'Kage last had Keppra today at 7:50 AM.')
            ->assertJsonPath('data.pet', 'Kage')
            ->assertJsonPath('data.pills.0.pill', 'Keppra')
            ->assertJsonPath('data.pills.0.last_given_by', 'Sarah')
            ->assertJsonPath('data.pills.0.scheduled_time', '08:00');

        $this->getJson('/api/pills/last?pill=keppra')->assertOk()->assertJsonPath('data.pet', 'Kage');
        $this->getJson('/api/pills/last')->assertStatus(422)->assertJsonPath('code', 'pet_or_pill_required');
        $this->getJson('/api/pills/last?pet=rex')->assertNotFound()->assertJsonPath('code', 'pet_not_found');
    }

    public function test_one_pets_medication_today(): void
    {
        $this->getJson('/api/pills/today?pet=Kage')
            ->assertOk()
            ->assertJsonPath('message', 'Kage has had 0 of 2 medication doses today. Still due: Keppra at 8:00 AM.')
            ->assertJsonPath('data.*.pet', ['Kage', 'Kage']);

        PillLog::create(['pill_id' => $this->keppra->id, 'user_id' => $this->cody->id, 'administered_at' => now(), 'scheduled_time' => '08:00']);

        $this->getJson('/api/pills/today?pet=cage')
            ->assertOk()
            ->assertJsonPath('message', 'Kage has had 1 of 2 medication doses today. Nothing is due right now. Next is Keppra at 8:00 PM.');

        // Without a pet it is still the whole household
        $this->getJson('/api/pills/today')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('message', "1 of 3 medication doses has been given today. Still due: Luna's Gabapentin at 9:00 AM.");
    }

    public function test_aliases_are_edited_on_the_website_and_used_by_the_api(): void
    {
        $this->actingAs($this->cody)->post('/chores', [
            'name' => 'Water Plants',
            'desc' => 'Inside and out.',
            'aliases' => 'the garden,  watering , Watering, ',
            'occurMonth' => 0,
            'occurDay' => 3,
            'weight' => 1,
        ])->assertRedirect();

        $plants = Chore::where('slug', 'water-plants')->sole();
        $this->assertSame(['the garden', 'watering'], $plants->aliases);

        $this->dryRun(['chore' => 'the garden'])->assertOk()->assertJsonPath('data.chore.aliases', ['the garden', 'watering']);

        $this->actingAs($this->cody)->patch('/chores/water-plants', [
            'name' => 'Water Plants',
            'desc' => 'Inside and out.',
            'aliases' => '',
            'occurMonth' => 0,
            'occurDay' => 3,
            'weight' => 1,
        ])->assertRedirect();

        $this->assertNull($plants->fresh()->aliases);
        $this->get('/chores/water-plants/edit')->assertOk()->assertSee('Also called');
    }
}
