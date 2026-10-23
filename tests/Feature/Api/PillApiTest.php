<?php

namespace Tests\Feature\Api;

use App\Models\Pet;
use App\Models\Pill;
use App\Models\PillLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PillApiTest extends TestCase
{
    use RefreshDatabase;

    private Pet $kage;
    private Pet $luna;
    private Pill $metacam;
    private Pill $gabapentin;
    private User $sarah;

    protected function setUp(): void
    {
        parent::setUp();

        // Times are the household's (New York); "now" is 10:00 there
        Carbon::setTestNow(Carbon::parse('2026-10-23 10:00:00', 'America/New_York'));
        Sanctum::actingAs(User::factory()->create(['name' => 'Cody Miller']));
        $this->sarah = User::factory()->create(['name' => 'Sarah Smith']);

        // Kage has one pill, Luna has two, Sunny has none
        $this->kage = Pet::factory()->create(['name' => 'Kage', 'slug' => 'kage']);
        $this->luna = Pet::factory()->create(['name' => 'Luna', 'slug' => 'luna']);
        Pet::factory()->create(['name' => 'Sunny', 'slug' => 'sunny']);
        $this->metacam = $this->createPill($this->kage, 'Metacam', ['08:00', '20:00']);
        $this->gabapentin = $this->createPill($this->luna, 'Gabapentin', ['08:00']);
        $this->createPill($this->luna, 'Thyroid Pill', ['09:00', '21:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createPill(Pet $pet, string $name, array $times): Pill
    {
        return Pill::factory()->create([
            'pet_id' => $pet->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'dosage' => '1 tablet',
            'scheduled_times' => $times,
        ]);
    }

    private function logDose(Pill $pill, string $time, ?Carbon $at = null): PillLog
    {
        return PillLog::create([
            'pill_id' => $pill->id,
            'user_id' => $this->sarah->id,
            'administered_at' => $at ?? now(),
            'scheduled_time' => $time,
        ]);
    }

    public function test_pet_with_one_pill_gives_the_due_dose_as_the_api_user(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', "Logged Kage's 8:00 AM dose of Metacam.")
            ->assertJsonPath('data.pill', 'Metacam')
            ->assertJsonPath('data.scheduled_time', '08:00')
            ->assertJsonPath('data.early', false)
            ->assertJsonPath('data.administered_by', 'API')
            ->assertJsonPath('data.remaining_today', ['20:00'])
            ->assertJsonPath('data.log_id', PillLog::sole()->id);

        $this->assertTrue(PillLog::sole()->user->isApiUser());
    }

    public function test_pet_with_several_pills_and_no_pill_named_is_an_error_listing_them(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'Luna'])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ambiguous_pill')
            ->assertJsonPath('error', 'Luna has more than one medication: Gabapentin or Thyroid Pill. Which one?')
            ->assertJsonPath('candidates.*.name', ['Gabapentin', 'Thyroid Pill']);

        $this->assertSame(0, PillLog::count());
    }

    public function test_pill_alone_finds_its_pet(): void
    {
        $this->postJson('/api/pills/give', ['pill' => 'GABAPENTIN'])
            ->assertCreated()
            ->assertJsonPath('data.pet', 'Luna')
            ->assertJsonPath('data.pill', 'Gabapentin');
    }

    public function test_pill_name_can_be_a_unique_part_of_the_name_within_the_pet(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'luna', 'pill' => 'thyroid'])
            ->assertCreated()
            ->assertJsonPath('data.pill', 'Thyroid Pill')
            ->assertJsonPath('data.scheduled_time', '09:00');
    }

    public function test_pill_of_another_pet_is_not_found_and_lists_the_pets_pills(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'pill' => 'gabapentin'])
            ->assertNotFound()
            ->assertJsonPath('code', 'pill_not_found')
            ->assertJsonPath('error', "I couldn't find a medication called gabapentin for Kage. Try Metacam.")
            ->assertJsonPath('candidates.0.name', 'Metacam');
    }

    public function test_pet_without_pills_or_missing_names_are_errors(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'sunny'])->assertStatus(422)->assertJsonPath('code', 'no_active_pills');
        $this->postJson('/api/pills/give', [])->assertStatus(422)->assertJsonPath('code', 'pet_or_pill_required');
        $this->postJson('/api/pills/give', ['pet' => 'rex'])
            ->assertNotFound()
            ->assertJsonPath('code', 'pet_not_found')
            ->assertJsonPath('error', "I couldn't find a pet called rex. Try Kage, Luna or Sunny.");
    }

    public function test_dose_that_is_not_due_yet_needs_early(): void
    {
        $this->logDose($this->metacam, '08:00');

        $this->postJson('/api/pills/give', ['pet' => 'kage'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'dose_not_due')
            ->assertJsonPath('next_time', '20:00')
            ->assertJsonPath('error', "Kage's Metacam isn't due until 8:00 PM. Nothing was logged.");
        $this->assertSame(1, PillLog::count());

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'early' => 'true'])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_time', '20:00')
            ->assertJsonPath('data.early', true)
            ->assertJsonPath('message', "Logged Kage's 8:00 PM dose of Metacam early.");
    }

    public function test_all_doses_given_is_an_error(): void
    {
        $this->logDose($this->metacam, '08:00');
        $this->logDose($this->metacam, '20:00');

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'early' => true])
            ->assertStatus(422)
            ->assertJsonPath('code', 'all_doses_given');
    }

    public function test_a_specific_time_can_be_given(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'time' => '8pm'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'dose_not_due');

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'time' => '8pm', 'early' => true])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_time', '20:00');

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'time' => '20:00', 'early' => true])
            ->assertStatus(422)
            ->assertJsonPath('code', 'slot_already_given');

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'time' => '9am'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_slot')
            ->assertJsonPath('scheduled_times', ['08:00', '20:00']);
    }

    public function test_doses_after_midnight_belong_to_the_end_of_the_day(): void
    {
        $nightly = $this->createPill($this->kage, 'Night Drops', ['22:00', '01:00']);
        Carbon::setTestNow(Carbon::parse('2026-10-23 23:00:00', 'America/New_York'));
        $this->logDose($nightly, '22:00');

        // 01:00 is still two hours away, not overdue
        $this->postJson('/api/pills/give', ['pill' => 'night drops'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'dose_not_due')
            ->assertJsonPath('next_time', '01:00');

        // At 01:30 it's the same effective day, and the 01:00 dose is due
        Carbon::setTestNow(Carbon::parse('2026-10-24 01:30:00', 'America/New_York'));
        $this->postJson('/api/pills/give', ['pill' => 'night drops'])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_time', '01:00')
            ->assertJsonPath('data.remaining_today', []);
    }

    public function test_by_credits_a_household_member_by_first_name(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'by' => 'sarah'])
            ->assertCreated()
            ->assertJsonPath('data.administered_by', 'Sarah')
            ->assertJsonPath('message', "Logged Kage's 8:00 AM dose of Metacam, given by Sarah.");

        $this->assertTrue(PillLog::sole()->user->is($this->sarah));

        $this->postJson('/api/pills/give', ['pet' => 'luna', 'pill' => 'gabapentin', 'by' => 'Bob'])
            ->assertNotFound()
            ->assertJsonPath('code', 'user_not_found');
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'dry_run' => true])
            ->assertOk()
            ->assertJsonPath('data.dry_run', true)
            ->assertJsonPath('data.scheduled_time', '08:00')
            ->assertJsonPath('message', "Would log Kage's 8:00 AM dose of Metacam.");

        $this->assertSame(0, PillLog::count());
    }

    public function test_at_backdates_the_dose(): void
    {
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'notes' => 'with food', 'at' => '2 hours ago'])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_time', '08:00');

        $log = PillLog::sole();
        $this->assertEquals('2026-10-23 08:00:00', $log->administered_at->setTimezone('America/New_York')->format('Y-m-d H:i:s'));
        // Stored in UTC
        $this->assertDatabaseHas('pill_logs', ['id' => $log->id, 'administered_at' => '2026-10-23 12:00:00']);
        $this->assertSame('with food', $log->notes);

        $this->postJson('/api/pills/give', ['pet' => 'kage', 'at' => 'tomorrow'])->assertStatus(422)->assertJsonPath('code', 'invalid_time');
        $this->postJson('/api/pills/give', ['pet' => 'kage', 'at' => '3 days ago'])->assertStatus(422)->assertJsonPath('code', 'invalid_time');
    }

    public function test_pill_path_and_legacy_record_medication_still_work(): void
    {
        $this->postJson('/api/pills/metacam/give')
            ->assertCreated()
            ->assertJsonPath('data.scheduled_time', '08:00');

        $this->postJson('/api/record-medication', ['pet' => 'luna', 'pill' => 'gabapentin'])
            ->assertCreated()
            ->assertJsonStructure(['success', 'message', 'data' => ['pet', 'pill', 'dosage', 'scheduled_time', 'administered_at', 'administered_by']]);
    }

    public function test_due_upcoming_and_today_list_doses_in_time_order(): void
    {
        $this->logDose($this->gabapentin, '08:00');

        $this->getJson('/api/pills/due')
            ->assertOk()
            ->assertJsonPath('message', "2 medication doses are due: Kage's Metacam at 8:00 AM and Luna's Thyroid Pill at 9:00 AM.")
            ->assertJsonPath('data.*.pill', ['Metacam', 'Thyroid Pill']);

        $this->getJson('/api/pills/upcoming')
            ->assertOk()
            ->assertJsonPath('data.*.time', ['20:00', '21:00']);

        $this->getJson('/api/pills/today')
            ->assertOk()
            ->assertJsonPath('data.*.status', ['due', 'given', 'due', 'upcoming', 'upcoming'])
            ->assertJsonPath('data.1.given_by', 'Sarah')
            ->assertJsonPath('message', "1 of 5 medication doses has been given today. Still due: Kage's Metacam at 8:00 AM and Luna's Thyroid Pill at 9:00 AM.");
    }

    public function test_pets_and_pills_show_todays_doses(): void
    {
        $this->logDose($this->metacam, '08:00');

        $this->getJson('/api/pets')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Kage', 'Luna', 'Sunny'])
            ->assertJsonPath('data.1.pills.*.name', ['Gabapentin', 'Thyroid Pill'])
            ->assertJsonPath('data.0.pills.0.doses.0.status', 'given')
            ->assertJsonPath('data.0.pills.0.last_given_by', 'Sarah');

        $this->getJson('/api/pets/kage/pills')
            ->assertOk()
            ->assertJsonPath('data.pet', 'Kage')
            ->assertJsonPath('data.pills.0.completed_today', ['08:00'])
            ->assertJsonPath('data.pills.0.next_scheduled', '20:00');

        $this->getJson('/api/pills/metacam')
            ->assertOk()
            ->assertJsonPath('data.pet', 'Kage')
            ->assertJsonPath('data.recent_logs.0.scheduled_time', '08:00');
    }

    public function test_recent_dose_can_be_undone(): void
    {
        $id = $this->postJson('/api/pills/give', ['pet' => 'kage'])->json('data.log_id');

        $this->deleteJson("/api/pill-logs/{$id}")
            ->assertOk()
            ->assertJsonPath('message', "Removed Kage's 8:00 AM dose of Metacam.");
        $this->assertSoftDeleted('pill_logs', ['id' => $id]);

        $old = $this->logDose($this->gabapentin, '08:00');
        $old->forceFill(['created_at' => now()->subDays(2)])->save();
        $this->deleteJson('/api/pill-logs/last')->assertStatus(422)->assertJsonPath('code', 'undo_window_passed');
    }

    public function test_pill_dashboard_still_lists_due_and_upcoming_pills(): void
    {
        $this->logDose($this->metacam, '08:00');

        $this->assertEquals(['Gabapentin', 'Thyroid Pill'], Pill::getDueNow()->pluck('name')->sort()->values()->all());
        $this->assertEquals(['Metacam', 'Thyroid Pill'], Pill::getUpcoming()->pluck('name')->sort()->values()->all());

        $this->actingAs(User::factory()->create())->get('/pills/dashboard')->assertOk()->assertSee('Gabapentin');
    }
}
