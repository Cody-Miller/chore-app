<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_and_clear_their_ntfy_topic(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile/notifications', ['ntfy_topic' => 'bechore-cody'])
            ->assertRedirect(route('profile.edit').'#notifications');
        $this->assertEquals('bechore-cody', $user->fresh()->ntfy_topic);

        $this->actingAs($user)->patch('/profile/notifications', ['ntfy_topic' => '']);
        $this->assertNull($user->fresh()->ntfy_topic);
    }

    public function test_topic_must_be_a_valid_ntfy_topic(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile/notifications', ['ntfy_topic' => 'not a topic!'])
            ->assertSessionHasErrors('ntfy_topic');
        $this->assertNull($user->fresh()->ntfy_topic);
    }

    public function test_test_notification_is_sent_to_the_users_topic(): void
    {
        config(['services.ntfy.url' => 'http://ntfy.test']);
        Http::fake();
        $user = User::factory()->create(['ntfy_topic' => 'bechore-cody']);

        $this->actingAs($user)
            ->post('/profile/notifications/test')
            ->assertSessionHas('status', 'notification-test-sent');

        Http::assertSent(fn (Request $request) => $request['topic'] === 'bechore-cody'
            && $request['title'] === 'BeChore test notification');
    }

    public function test_test_notification_shows_why_it_failed(): void
    {
        config(['services.ntfy.url' => 'http://ntfy.test']);
        Http::fake(['*' => Http::response(['error' => 'forbidden'], 403)]);
        $user = User::factory()->create(['ntfy_topic' => 'bechore-cody']);

        $this->actingAs($user)
            ->post('/profile/notifications/test')
            ->assertSessionHas('ntfy_error', fn ($error) => str_contains($error, '403'));

        config(['services.ntfy.url' => null]);
        $this->actingAs($user)
            ->post('/profile/notifications/test')
            ->assertSessionHas('ntfy_error', fn ($error) => str_contains($error, 'NTFY_URL is not set'));
    }

    public function test_profile_page_shows_notification_settings(): void
    {
        $user = User::factory()->create(['ntfy_topic' => 'bechore-cody']);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('ntfy Topic')
            ->assertSee('value="bechore-cody"', false)
            ->assertSee('Send Test Notification');
    }

    public function test_reminder_toggle_is_saved_on_chores(): void
    {
        $user = User::factory()->create();
        $data = ['name' => 'Wash Dishes', 'desc' => 'Scrub', 'occurMonth' => 0, 'occurDay' => 1, 'weight' => 2];

        $this->actingAs($user)->post('/chores', [...$data, 'notifications_enabled' => '1']);
        $this->assertTrue(Chore::where('slug', 'wash-dishes')->first()->notifications_enabled);

        $this->actingAs($user)->patch('/chores/wash-dishes', [...$data, 'notifications_enabled' => '0']);
        $this->assertFalse(Chore::where('slug', 'wash-dishes')->first()->notifications_enabled);
    }

    public function test_reminder_toggle_is_saved_on_pills(): void
    {
        $user = User::factory()->create();
        $pet = Pet::create(['name' => 'Biscuit', 'slug' => 'biscuit', 'species' => 'Dog']);
        $data = ['pet_id' => $pet->id, 'name' => 'Carprofen', 'dosage' => '75 mg', 'scheduled_times' => ['08:00'], 'is_active' => 1];

        $this->actingAs($user)->post('/pills', [...$data, 'notifications_enabled' => '1']);
        $this->assertTrue(Pill::where('slug', 'carprofen')->first()->notifications_enabled);

        $this->actingAs($user)->patch('/pills/carprofen', [...$data, 'notifications_enabled' => '0']);
        $this->assertFalse(Pill::where('slug', 'carprofen')->first()->notifications_enabled);
    }

    public function test_forms_show_the_reminder_toggle_and_topic_hint(): void
    {
        $withoutTopic = User::factory()->create();
        $withTopic = User::factory()->create(['ntfy_topic' => 'bechore-sam']);
        $chore = Chore::factory()->create(['notifications_enabled' => true]);

        $this->actingAs($withoutTopic)
            ->get('/chores/create')
            ->assertOk()
            ->assertSee('Reminders off')
            ->assertSee('Sends an ntfy notification at 8:00 AM')
            ->assertSee('Set your ntfy topic on your');

        $this->actingAs($withTopic)
            ->get("/chores/{$chore->slug}/edit")
            ->assertOk()
            ->assertSee('id="notifications_enabled" type="checkbox" name="notifications_enabled" value="1" class="sr-only peer" checked', false)
            ->assertDontSee('Set your ntfy topic on your');

        $this->actingAs($withTopic)
            ->get('/pills/create')
            ->assertOk()
            ->assertSee('Sends an ntfy notification at each scheduled time');
    }
}
