<?php

namespace Tests\Feature\Api;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\ChoreSnooze;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Chore::getDue()/getUpcoming() compare against MySQL's NOW(), so completion
 * times here are relative to the real clock with a day or more of margin.
 */
class ChoreApiTest extends TestCase
{
    use RefreshDatabase;

    private User $cody;
    private User $sarah;
    private Chore $dishes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cody = User::factory()->create(['name' => 'Cody Miller']);
        $this->sarah = User::factory()->create(['name' => 'Sarah Smith']);
        Sanctum::actingAs($this->cody);

        $this->dishes = $this->createChore('Do the Dishes', 24, now()->subDays(3));
        $this->createChore('Clean Litter Box', 48, now()->subHour());
        $this->createChore('Scoop Litter', 24);
        $this->createChore('Clean Gutters', 720, now()->subDay());
        $this->createChore('Fix Fence', 0);
    }

    private function createChore(string $name, int $hours, $lastCompleted = null): Chore
    {
        $chore = Chore::factory()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'occurrence_hours' => $hours,
            'weight' => 2,
        ]);

        if ($lastCompleted) {
            ChoreLog::factory()->create(['chore_id' => $chore->id, 'user_id' => $this->sarah->id, 'completed_at' => $lastCompleted]);
        }

        return $chore;
    }

    private function names(string $uri): array
    {
        return collect($this->getJson($uri)->assertOk()->json('data'))->pluck('name')->sort()->values()->all();
    }

    public function test_dashboard_lists(): void
    {
        $this->assertEquals(['Do the Dishes', 'Scoop Litter'], $this->names('/api/chores/due'));
        $this->assertEquals(['Clean Litter Box'], $this->names('/api/chores/upcoming'));
        $this->assertEquals(['Fix Fence'], $this->names('/api/chores/one-time'));

        $this->getJson('/api/chores/due')
            ->assertJsonPath('data.0.status', 'due')
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, '2 chores are due: '));
        $this->getJson('/api/chores/upcoming')->assertJsonPath('data.0.last_completed_by', 'Sarah');
    }

    public function test_for_limits_lists_to_that_persons_and_unassigned_chores(): void
    {
        $this->dishes->update(['assigned_user_id' => $this->sarah->id]);

        $this->assertEquals(['Scoop Litter'], $this->names('/api/chores/due?for=cody'));
        $this->assertEquals(['Do the Dishes', 'Scoop Litter'], $this->names('/api/chores/due?for=Sarah'));
        $this->getJson('/api/chores/due')->assertJsonPath('data.0.assigned_to', 'Sarah');
    }

    public function test_index_filters_and_show(): void
    {
        $this->assertEquals(['Fix Fence'], $this->names('/api/chores?occurrence=one-time'));
        $this->dishes->syncTagNames(['Kitchen']);
        $this->assertEquals(['Do the Dishes'], $this->names('/api/chores?tag=Kitchen'));

        $this->getJson('/api/chores/do-the-dishes')
            ->assertOk()
            ->assertJsonPath('data.repeats', 'every 1 day')
            ->assertJsonPath('data.tags', ['Kitchen'])
            ->assertJsonPath('data.recent_logs.0.completed_by', 'Sarah');
    }

    public function test_completing_by_a_unique_part_of_the_name_credits_the_api_user(): void
    {
        $this->postJson('/api/chores/complete', ['chore' => 'dishes'])
            ->assertCreated()
            ->assertJsonPath('message', "Marked Do the Dishes as done. It's next due tomorrow.")
            ->assertJsonPath('data.completed_by', 'API')
            ->assertJsonPath('data.chore.status', 'upcoming');

        $log = $this->dishes->lastChoreLog()->first();
        $this->assertTrue($log->user->isApiUser());
        $this->assertEquals(100, $log->weight_percentage);
    }

    public function test_ambiguous_chore_lists_the_matches(): void
    {
        $this->postJson('/api/chores/complete', ['chore' => 'litter'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ambiguous_chore')
            ->assertJsonPath('candidates.*.name', ['Clean Litter Box', 'Scoop Litter'])
            ->assertJsonPath('error', 'More than one chore matches litter: Clean Litter Box or Scoop Litter. Which one?');

        $this->postJson('/api/chores/complete', ['chore' => 'mow lawn'])
            ->assertNotFound()
            ->assertJsonPath('code', 'chore_not_found');
    }

    public function test_completing_for_someone_else_or_split(): void
    {
        $this->postJson('/api/chores/do-the-dishes/complete', ['by' => 'sarah'])
            ->assertCreated()
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, 'Marked Do the Dishes as done for Sarah.'));
        $this->assertTrue($this->dishes->lastChoreLog()->first()->user->is($this->sarah));

        $this->postJson('/api/chores/complete', ['chore' => 'scoop litter', 'by' => 'Cody', 'split_with' => 'Sarah'])
            ->assertCreated()
            ->assertJsonCount(2, 'data.logs')
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, 'Marked Scoop Litter as done, split between Cody and Sarah.'));
        $split = ChoreLog::where('is_split', true)->get();
        $this->assertCount(2, $split);
        $this->assertCount(1, $split->pluck('split_group_id')->unique());
        $this->assertEquals([50, 50], $split->pluck('weight_percentage')->map(fn ($p) => (float) $p)->all());

        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'split_with' => 'Sarah'])
            ->assertStatus(422)->assertJsonPath('code', 'split_requires_by');
        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'by' => 'sarah', 'split_with' => 'Sarah'])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_split');
    }

    public function test_dry_run_and_backdating(): void
    {
        $count = ChoreLog::count();

        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'dry_run' => true])
            ->assertOk()
            ->assertJsonPath('data.dry_run', true)
            ->assertJsonPath('message', 'Would mark Do the Dishes as done.');
        $this->assertSame($count, ChoreLog::count());

        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'at' => '3 hours ago'])->assertCreated();
        $this->assertEqualsWithDelta(now()->subHours(3)->timestamp, $this->dishes->lastChoreLog()->first()->completed_at->timestamp, 5);
    }

    public function test_snooze_and_unsnooze(): void
    {
        $this->postJson('/api/chores/dishes/snooze', ['duration' => 'a week'])
            ->assertOk()
            ->assertJsonPath('data.status', 'snoozed')
            ->assertJsonPath('message', 'Snoozed Do the Dishes until '.now()->addWeek()->format('F j').'.');
        $this->assertEqualsWithDelta(now()->addHours(168)->timestamp, ChoreSnooze::sole()->snoozed_until->timestamp, 5);
        $this->assertTrue(ChoreSnooze::sole()->user->isApiUser());

        $this->assertEquals(['Scoop Litter'], $this->names('/api/chores/due'));
        $this->assertEquals(['Do the Dishes'], $this->names('/api/chores/snoozed'));

        $this->postJson('/api/chores/dishes/snooze', ['hours' => 72])->assertOk();
        $this->assertEqualsWithDelta(now()->addHours(72)->timestamp, ChoreSnooze::sole()->snoozed_until->timestamp, 5);

        $this->postJson('/api/chores/dishes/snooze', ['duration' => 'forever'])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_duration');

        $this->deleteJson('/api/chores/dishes/snooze')
            ->assertOk()
            ->assertJsonPath('message', 'Do the Dishes is no longer snoozed.')
            ->assertJsonPath('data.status', 'due');
        $this->assertSame(0, ChoreSnooze::count());
    }

    public function test_undo_removes_both_halves_of_a_split_and_only_recent_logs(): void
    {
        $this->postJson('/api/chores/complete', ['chore' => 'scoop litter', 'by' => 'Cody', 'split_with' => 'Sarah'])->assertCreated();

        $this->deleteJson('/api/chore-logs/last')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, 'Removed the Scoop Litter completion from '));
        $this->assertSame(0, ChoreLog::where('is_split', true)->count());

        // The seeded logs were created by the factory just now, so age one past the window
        $old = $this->dishes->choreLogs()->first();
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->deleteJson("/api/chore-logs/{$old->id}")->assertStatus(422)->assertJsonPath('code', 'undo_window_passed');
        $this->deleteJson('/api/chore-logs/999999')->assertNotFound()->assertJsonPath('code', 'log_not_found');
    }

    public function test_history_filters(): void
    {
        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'by' => 'cody'])->assertCreated();

        $this->getJson('/api/chore-logs?user=cody')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.chore', 'Do the Dishes');
        $this->getJson('/api/chore-logs?chore=dishes')->assertJsonCount(2, 'data');
        $this->getJson('/api/chore-logs?limit=1')->assertJsonCount(1, 'data');
    }
}
