<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use App\Notifications\ChoreDueNotification;
use App\Notifications\PillDueNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $cody;

    private User $sam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cody = User::factory()->create(['name' => 'Cody']);
        $this->sam = User::factory()->create(['name' => 'Sam']);

        $this->travelTo(Carbon::parse('2026-10-22 08:00'));
    }

    private function choreDoneUrl(Chore $chore, User $user): string
    {
        return (new ChoreDueNotification($chore))->toNtfy($user)['actions'][0]['url'];
    }

    private function pillGivenUrl(Pill $pill, User $user, string $time = '08:00'): string
    {
        return (new PillDueNotification($pill, $time))->toNtfy($user)['actions'][0]['url'];
    }

    private function createPill(): Pill
    {
        $pet = Pet::create(['name' => 'Biscuit', 'slug' => 'biscuit', 'species' => 'Dog']);

        return Pill::create([
            'pet_id' => $pet->id,
            'name' => 'Carprofen',
            'slug' => 'carprofen',
            'dosage' => '75 mg',
            'scheduled_times' => ['08:00', '20:00'],
        ]);
    }

    public function test_done_button_logs_the_chore_for_the_recipient(): void
    {
        $chore = Chore::factory()->create(['name' => 'Take out trash']);

        $this->postJson($this->choreDoneUrl($chore, $this->sam))
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => "Logged 'Take out trash' for Sam"]);

        $this->assertDatabaseHas('chore_logs', [
            'chore_id' => $chore->id,
            'user_id' => $this->sam->id,
            'weight_percentage' => 100,
            'is_split' => false,
        ]);
    }

    public function test_a_second_tap_does_not_log_the_chore_twice(): void
    {
        $chore = Chore::factory()->create(['name' => 'Take out trash']);

        $this->postJson($this->choreDoneUrl($chore, $this->cody))->assertCreated();
        $this->postJson($this->choreDoneUrl($chore, $this->sam))
            ->assertOk()
            ->assertJson(['message' => "'Take out trash' was already done by Cody"]);

        $this->assertEquals(1, ChoreLog::where('chore_id', $chore->id)->count());
    }

    public function test_a_chore_done_yesterday_does_not_count_as_already_done(): void
    {
        $chore = Chore::factory()->create();
        ChoreLog::create([
            'chore_id' => $chore->id,
            'user_id' => $this->sam->id,
            'completed_at' => now()->subDay(),
            'weight_percentage' => 100,
            'is_split' => false,
        ]);

        $this->postJson($this->choreDoneUrl($chore, $this->cody))->assertCreated();
    }

    public function test_tampered_or_expired_links_are_rejected(): void
    {
        $chore = Chore::factory()->create();
        $url = $this->choreDoneUrl($chore, $this->cody);

        $this->postJson(str_replace("/users/{$this->cody->id}/", "/users/{$this->sam->id}/", $url))->assertForbidden();

        $this->travelTo(now()->addDay()->addMinute());
        $this->postJson($url)->assertForbidden();

        $this->assertDatabaseCount('chore_logs', 0);
    }

    public function test_given_button_logs_the_dose_once(): void
    {
        $pill = $this->createPill();

        $this->postJson($this->pillGivenUrl($pill, $this->cody))
            ->assertCreated()
            ->assertJson(['message' => 'Logged the 08:00 dose of Carprofen for Cody']);
        $this->postJson($this->pillGivenUrl($pill, $this->sam))
            ->assertOk()
            ->assertJson(['message' => 'The 08:00 dose of Carprofen was already given']);

        $this->assertEquals(1, PillLog::count());
        $this->assertDatabaseHas('pill_logs', [
            'pill_id' => $pill->id,
            'user_id' => $this->cody->id,
            'scheduled_time' => '08:00',
        ]);
    }

    public function test_pill_link_expires_when_the_doses_day_ends(): void
    {
        $url = $this->pillGivenUrl($this->createPill(), $this->cody, '20:00');

        // Still the same effective day just before the 3 AM cutoff
        $this->travelTo(Carbon::parse('2026-10-23 02:59'));
        $this->postJson($url)->assertCreated();

        PillLog::query()->delete();
        $this->travelTo(Carbon::parse('2026-10-23 03:00'));
        $this->postJson($url)->assertForbidden();
    }
}
