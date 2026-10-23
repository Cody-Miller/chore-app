<?php

namespace Tests\Feature\Api;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiConventionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_token_returns_json_even_without_an_accept_header(): void
    {
        $this->get('/api/summary')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'error' => 'The API token is missing or invalid.',
                'code' => 'unauthenticated',
            ]);
    }

    public function test_validation_and_unknown_routes_use_the_error_envelope(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/chores/complete', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['chore']]);

        $this->get('/api/nothing-here')
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');

        $this->delete('/api/summary')
            ->assertStatus(405)
            ->assertJsonPath('code', 'method_not_allowed');
    }

    public function test_users_only_exposes_first_names_and_hides_the_api_user(): void
    {
        $cody = User::factory()->create(['name' => 'Cody Miller']);
        $sarah = User::factory()->create(['name' => 'Sarah Smith']);
        User::apiUser();
        Sanctum::actingAs($cody);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    ['id' => $cody->id, 'first_name' => 'Cody'],
                    ['id' => $sarah->id, 'first_name' => 'Sarah'],
                ],
            ]);

        // The old auth check still returns the raw user
        $this->getJson('/api/user')->assertOk()->assertJsonPath('email', $cody->email);
    }

    public function test_same_first_name_is_ambiguous_but_id_and_full_name_work(): void
    {
        Sanctum::actingAs(User::factory()->create(['name' => 'Cody Miller']));
        $sam = User::factory()->create(['name' => 'Sam Jones']);
        User::factory()->create(['name' => 'Sam Lee']);
        Chore::factory()->create(['name' => 'Dishes', 'slug' => 'dishes', 'occurrence_hours' => 24]);

        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'by' => 'sam', 'dry_run' => true])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ambiguous_user')
            ->assertJsonPath('candidates.*.first_name', ['Sam', 'Sam']);

        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'by' => 'Sam Lee', 'dry_run' => true])->assertOk();
        $this->postJson('/api/chores/complete', ['chore' => 'dishes', 'by' => (string) $sam->id])
            ->assertCreated()
            ->assertJsonPath('data.completed_by', 'Sam');
    }

    public function test_summary_reads_out_whats_due(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Chore::factory()->create(['name' => 'Dishes', 'slug' => 'dishes', 'occurrence_hours' => 24]);
        $pet = Pet::factory()->create(['name' => 'Kage', 'slug' => 'kage']);
        Pill::factory()->create(['pet_id' => $pet->id, 'name' => 'Metacam', 'slug' => 'metacam', 'scheduled_times' => ['03:00']]);

        $this->getJson('/api/summary')
            ->assertOk()
            ->assertJsonPath('data.chores.due.0.name', 'Dishes')
            ->assertJsonPath('data.pills.due.0.pill', 'Metacam')
            ->assertJsonPath('message', fn ($message) => str_starts_with($message, "1 chore is due: Dishes. 1 medication dose is due: Kage's Metacam at 3:00 AM."));
    }

    public function test_stats_rank_people_by_chore_points(): void
    {
        $cody = User::factory()->create(['name' => 'Cody Miller']);
        $sarah = User::factory()->create(['name' => 'Sarah Smith']);
        Sanctum::actingAs($cody);
        $chore = Chore::factory()->create(['weight' => 3]);
        ChoreLog::factory()->create(['chore_id' => $chore->id, 'user_id' => $sarah->id, 'completed_at' => now()]);
        ChoreLog::factory()->create(['chore_id' => $chore->id, 'user_id' => $sarah->id, 'completed_at' => now()]);
        ChoreLog::factory()->create(['chore_id' => $chore->id, 'user_id' => $cody->id, 'completed_at' => now()]);

        $this->getJson('/api/stats?period=month')
            ->assertOk()
            ->assertJsonPath('data.chore_points', [['first_name' => 'Sarah', 'points' => 6], ['first_name' => 'Cody', 'points' => 3]])
            ->assertJsonPath('message', 'In the past month, Sarah has the most chore points with 6, then Cody with 3. 0 medication doses were missed.');

        $this->getJson('/api/stats?period=decade')->assertStatus(422)->assertJsonPath('code', 'validation_failed');
    }
}
