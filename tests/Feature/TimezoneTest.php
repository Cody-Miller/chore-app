<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Times are stored in UTC. The household timezone (New York by default) decides
 * schedules and "today"; a user's or API token's timezone only changes how times
 * are shown and read.
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    const LA = 'America/Los_Angeles';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function choreLogAt(string $utc, ?User $user = null): ChoreLog
    {
        return ChoreLog::factory()->create([
            'chore_id' => Chore::factory()->create()->id,
            'user_id' => ($user ?? User::factory()->create())->id,
            'completed_at' => Carbon::parse($utc, 'UTC'),
        ]);
    }

    public function test_migration_converts_new_york_wall_clock_times_to_utc_and_back(): void
    {
        $summer = $this->choreLogAt('2026-07-01 08:00:00');
        $winter = $this->choreLogAt('2026-01-15 08:00:00');
        $migration = require database_path('migrations/2026_10_23_000001_convert_timestamps_to_utc.php');

        // Pretend these were written as New York wall-clock times, as they used to be
        DB::table('chore_logs')->whereIn('id', [$summer->id, $winter->id])->update(['created_at' => '2026-01-15 08:00:00']);
        $migration->up();

        $this->assertDatabaseHas('chore_logs', ['id' => $summer->id, 'completed_at' => '2026-07-01 12:00:00']); // EDT, UTC-4
        $this->assertDatabaseHas('chore_logs', ['id' => $winter->id, 'completed_at' => '2026-01-15 13:00:00']); // EST, UTC-5
        $this->assertDatabaseHas('chore_logs', ['id' => $winter->id, 'created_at' => '2026-01-15 13:00:00']); // TIMESTAMP column

        $migration->down();
        $this->assertDatabaseHas('chore_logs', ['id' => $summer->id, 'completed_at' => '2026-07-01 08:00:00']);
    }

    public function test_website_shows_times_in_the_users_timezone(): void
    {
        $user = User::factory()->create(['timezone' => self::LA]);
        $this->choreLogAt('2026-10-23 19:05:00', $user);

        // 19:05 UTC is 12:05 PM in Los Angeles (and 3:05 PM in New York)
        $this->actingAs($user)->get('/chorelog')->assertOk()->assertSee('12:05 pm')->assertDontSee('03:05 pm');
    }

    public function test_website_reads_edited_times_in_the_users_timezone(): void
    {
        $user = User::factory()->create(['timezone' => self::LA]);
        $log = $this->choreLogAt('2026-10-23 19:05:00', $user);

        $this->actingAs($user)
            ->get("/chorelog/{$log->id}/edit")
            ->assertSee('2026-10-23T12:05', false);

        $this->actingAs($user)
            ->patch("/chorelog/{$log->id}", ['chore_id' => $log->chore_id, 'user_id' => $user->id, 'completed_time' => '2026-10-23T09:30'])
            ->assertRedirect(route('chorelog.index'));

        // 9:30 AM PDT is 16:30 UTC
        $this->assertDatabaseHas('chore_logs', ['id' => $log->id, 'completed_at' => '2026-10-23 16:30:00']);
    }

    public function test_api_shows_and_reads_times_in_the_tokens_timezone_but_schedules_stay_household(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'America/New_York'));
        $user = User::factory()->create();
        $token = $user->createToken('Kitchen speaker');
        $token->accessToken->update(['timezone' => self::LA]);
        $pet = Pet::factory()->create(['name' => 'Kage', 'slug' => 'kage']);
        Pill::factory()->create(['pet_id' => $pet->id, 'name' => 'Metacam', 'slug' => 'metacam', 'scheduled_times' => ['08:00', '20:00']]);

        // "7am" is the token's 7 AM (Los Angeles), which is 14:00 UTC; the slot is the household's 8:00 AM
        $this->withToken($token->plainTextToken)
            ->postJson('/api/pills/give', ['pet' => 'kage', 'at' => 'today 7am'])
            ->assertCreated()
            ->assertHeader('X-Timezone', self::LA)
            ->assertJsonPath('data.scheduled_label', '8:00 AM')
            ->assertJsonPath('data.administered_at', '2026-10-23T07:00:00-07:00');
        $this->assertDatabaseHas('pill_logs', ['administered_at' => '2026-10-23 14:00:00']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/pills/today')
            ->assertJsonPath('data.0.label', '8:00 AM')
            ->assertJsonPath('data.0.scheduled_at', '2026-10-23T05:00:00-07:00')
            ->assertJsonPath('data.0.given_at', '2026-10-23T07:00:00-07:00');

        // A token without its own timezone uses the owner's
        $user->update(['timezone' => 'America/Chicago']);
        $other = $user->createToken('Phone');
        $other->accessToken->update(['timezone' => '']);
        $this->app['auth']->forgetGuards();
        $this->withToken($other->plainTextToken)
            ->getJson('/api/pill-logs?limit=1')
            ->assertHeader('X-Timezone', 'America/Chicago')
            ->assertJsonPath('data.0.administered_at', '2026-10-23T09:00:00-05:00');
    }

    public function test_chore_due_dates_roll_over_at_household_midnight(): void
    {
        $chore = Chore::factory()->create(['name' => 'Dishes', 'slug' => 'dishes', 'occurrence_hours' => 24]);
        ChoreLog::factory()->create([
            'chore_id' => $chore->id,
            'completed_at' => Carbon::parse('2026-10-22 08:00:00', 'America/New_York'),
        ]);

        // Due on the 23rd, so it's on the Due list from midnight on the 24th, not at 8 PM (midnight UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-23 21:00:00', 'America/New_York'));
        $this->assertTrue(Chore::getDue()->isEmpty());
        $this->assertEquals(['Dishes'], Chore::getUpcoming()->pluck('name')->all());

        Carbon::setTestNow(Carbon::parse('2026-10-24 00:30:00', 'America/New_York'));
        $this->assertEquals(['Dishes'], Chore::getDue()->pluck('name')->all());
    }

    public function test_profile_and_token_timezones_can_be_changed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'timezone' => self::LA])
            ->assertSessionHasNoErrors();
        $this->assertSame(self::LA, $user->fresh()->timezone);

        $this->actingAs($user)
            ->patch('/profile', ['name' => $user->name, 'email' => $user->email, 'timezone' => 'Mars/Olympus_Mons'])
            ->assertSessionHasErrors('timezone');

        $this->actingAs($user)
            ->post('/profile/api-tokens', ['token_name' => 'Home Assistant', 'timezone' => 'America/Denver'])
            ->assertRedirect(route('profile.edit'));
        $token = $user->tokens()->sole();
        $this->assertSame('America/Denver', $token->timezone);

        $this->actingAs($user)
            ->patch("/profile/api-tokens/{$token->id}", ['timezone' => 'Pacific/Honolulu'])
            ->assertRedirect(route('profile.edit'));
        $this->assertSame('Pacific/Honolulu', $token->fresh()->timezone);

        $this->actingAs($user)
            ->patch("/profile/api-tokens/{$token->id}", ['timezone' => 'Nowhere'])
            ->assertSessionHasErrors('timezone');

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('value="Pacific/Honolulu" selected', false)->assertSee('household timezone (America/New York)', false);
    }
}
