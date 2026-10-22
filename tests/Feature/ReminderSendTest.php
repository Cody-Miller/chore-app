<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\ChoreSnooze;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\SentReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReminderSendTest extends TestCase
{
    use RefreshDatabase;

    private User $cody;

    private User $sam;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ntfy.url' => 'http://ntfy.test',
            'services.ntfy.token' => 'tk_test',
            'services.ntfy.chore_time' => '08:00',
        ]);

        $this->cody = User::factory()->create(['name' => 'Cody', 'ntfy_topic' => 'bechore-cody']);
        $this->sam = User::factory()->create(['name' => 'Sam', 'ntfy_topic' => 'bechore-sam']);
        User::factory()->create(['name' => 'No Topic']);

        $this->travelTo(Carbon::parse('2026-10-22 08:00'));
    }

    /**
     * A daily chore last done two days ago, so it's overdue.
     */
    private function dueChore(array $attributes = []): Chore
    {
        $chore = Chore::factory()->create([
            'occurrence_hours' => 24,
            'notifications_enabled' => true,
            ...$attributes,
        ]);
        $this->logChore($chore, now()->subDays(2));

        return $chore;
    }

    private function logChore(Chore $chore, Carbon $completedAt): void
    {
        ChoreLog::create([
            'chore_id' => $chore->id,
            'user_id' => $this->cody->id,
            'completed_at' => $completedAt,
            'weight_percentage' => 100,
            'is_split' => false,
        ]);
    }

    private function createPill(array $attributes = []): Pill
    {
        $pet = Pet::firstOrCreate(['slug' => 'biscuit'], ['name' => 'Biscuit', 'species' => 'Dog']);

        return Pill::create([
            'pet_id' => $pet->id,
            'name' => 'Carprofen',
            'slug' => Str::slug($attributes['name'] ?? 'Carprofen'),
            'dosage' => '75 mg',
            'scheduled_times' => ['08:00', '20:00'],
            'notifications_enabled' => true,
            ...$attributes,
        ]);
    }

    private function sentTopics(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0]['topic'])->sort()->values()->all();
    }

    public function test_due_chore_reminders_go_to_everyone_with_a_topic_from_chore_time(): void
    {
        Http::fake();
        $chore = $this->dueChore(['name' => 'Take out trash', 'slug' => 'take-out-trash']);

        $this->travelTo(Carbon::parse('2026-10-22 07:59'));
        $this->artisan('reminders:send')->assertSuccessful();
        Http::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-10-22 08:00'));
        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertEquals(['bechore-cody', 'bechore-sam'], $this->sentTopics());
        Http::assertSent(function (Request $request) {
            $done = $request['actions'][0];

            return $request->url() === 'http://ntfy.test/'
                && $request->hasHeader('Authorization', 'Bearer tk_test')
                && $request['title'] === 'Chore due: Take out trash'
                && str_contains($request['message'], 'Overdue since')
                && $done['action'] === 'http'
                && str_contains($done['url'], "/api/reminders/chores/take-out-trash/users/{$this->cody->id}/complete")
                && str_contains($done['url'], 'signature=');
        });
    }

    public function test_each_chore_reminder_is_sent_once_a_day(): void
    {
        Http::fake();
        $this->dueChore();

        $this->artisan('reminders:send');
        $this->artisan('reminders:send');
        Http::assertSentCount(2);

        // Still overdue the next morning, so it reminds again
        $this->travelTo(Carbon::parse('2026-10-23 08:00'));
        $this->artisan('reminders:send');
        Http::assertSentCount(4);
    }

    public function test_chores_that_are_opted_out_not_due_or_snoozed_are_skipped(): void
    {
        Http::fake();
        $this->dueChore(['notifications_enabled' => false]);

        $notDue = Chore::factory()->create(['occurrence_hours' => 72, 'notifications_enabled' => true]);
        $this->logChore($notDue, now()->subDay());

        $snoozed = $this->dueChore();
        ChoreSnooze::create(['chore_id' => $snoozed->id, 'user_id' => $this->cody->id, 'snoozed_until' => now()->addDay()]);

        $this->artisan('reminders:send')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_chore_due_today_and_unfinished_one_time_chores_are_reminded(): void
    {
        Http::fake();
        $dueToday = Chore::factory()->create(['occurrence_hours' => 24, 'notifications_enabled' => true]);
        $this->logChore($dueToday, now()->subDay());
        Chore::factory()->create(['occurrence_hours' => 0, 'notifications_enabled' => true]);
        $doneOneTime = Chore::factory()->create(['occurrence_hours' => 0, 'notifications_enabled' => true]);
        $this->logChore($doneOneTime, now()->subDays(3));

        $this->artisan('reminders:send');

        // Two chores, two people each
        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => $request['message'] === 'Due today'."\nLast done by Cody on Wed, Oct 21");
    }

    public function test_assigned_chores_only_remind_the_assignee(): void
    {
        Http::fake();
        $this->dueChore(['assigned_user_id' => $this->sam->id]);

        $this->artisan('reminders:send');

        $this->assertEquals(['bechore-sam'], $this->sentTopics());
    }

    public function test_a_failed_send_is_retried_on_the_next_run(): void
    {
        Http::fake(['*' => Http::sequence()->push('error', 500)->push(['id' => 'abc'])]);
        $this->dueChore(['assigned_user_id' => $this->cody->id]);

        $this->artisan('reminders:send')->expectsOutputToContain('Failed to remind Cody');
        $this->assertDatabaseCount('sent_reminders', 0);

        $this->artisan('reminders:send')->expectsOutputToContain('Sent 1 reminder(s).');
        $this->assertDatabaseCount('sent_reminders', 1);
    }

    public function test_nothing_is_sent_without_an_ntfy_url(): void
    {
        Http::fake();
        config(['services.ntfy.url' => null]);
        $this->dueChore();

        $this->artisan('reminders:send')->expectsOutputToContain('NTFY_URL is not set');

        Http::assertNothingSent();
    }

    public function test_pill_reminders_are_sent_at_each_scheduled_time(): void
    {
        Http::fake();
        $pill = $this->createPill();

        $this->travelTo(Carbon::parse('2026-10-22 07:59'));
        $this->artisan('reminders:send');
        Http::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-10-22 08:00'));
        $this->artisan('reminders:send');
        $this->artisan('reminders:send');

        $this->assertEquals(['bechore-cody', 'bechore-sam'], $this->sentTopics());
        Http::assertSent(function (Request $request) use ($pill) {
            return $request['title'] === 'Biscuit: Carprofen (75 mg)'
                && $request['message'] === 'Scheduled for 8:00 AM'
                && $request['priority'] === 4
                && str_contains($request['actions'][0]['url'], "/api/reminders/pills/{$pill->slug}/users/")
                && str_contains($request['actions'][0]['url'], 'time=08%3A00');
        });
        $this->assertDatabaseHas('sent_reminders', [
            'remindable_type' => Pill::class,
            'remindable_id' => $pill->id,
            'for_date' => '2026-10-22',
            'scheduled_time' => '08:00',
        ]);
    }

    public function test_pill_doses_that_are_given_inactive_or_long_past_are_skipped(): void
    {
        Http::fake();
        $given = $this->createPill(['name' => 'Given']);
        PillLog::create([
            'pill_id' => $given->id,
            'user_id' => $this->cody->id,
            'administered_at' => now()->subMinutes(5),
            'scheduled_time' => '08:00',
        ]);
        $this->createPill(['name' => 'Inactive', 'is_active' => false]);
        $late = $this->createPill(['name' => 'Late', 'scheduled_times' => ['06:59']]);

        $this->artisan('reminders:send');
        Http::assertNothingSent();

        // Just inside the window, the late dose still goes out
        $late->update(['scheduled_times' => ['07:01']]);
        $this->artisan('reminders:send');
        Http::assertSentCount(2);
        $this->assertEquals(2, SentReminder::where('remindable_id', $late->id)->count());
    }
}
