<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\ChoreLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChoreLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_chore_flashes_a_link_to_edit_the_new_log(): void
    {
        $chore = Chore::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('chorelog.store', $chore), ['redirect_to' => route('chores.index', ['tag' => 'Kitchen'])])
            ->assertRedirect(route('chores.index', ['tag' => 'Kitchen']))
            ->assertSessionHas('success_edit_url', route('chorelog.edit', ChoreLog::sole()));
    }

    public function test_split_completion_does_not_flash_an_edit_link(): void
    {
        $chore = Chore::factory()->create();
        $partner = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('chorelog.store', $chore), ['split_with_user_id' => $partner->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('success_edit_url');

        $this->assertSame(2, ChoreLog::count());
    }

    public function test_chore_list_can_complete_chores_and_shows_the_chores_sub_nav(): void
    {
        $chore = Chore::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('chores.index'))
            ->assertOk()
            ->assertSee("['complete_chore', '{$chore->slug}'", false)
            ->assertSeeInOrder(['Dashboard', 'All Chores', 'History']);
    }

    public function test_graphs_page_has_no_sub_nav(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('graphs.index'))
            ->assertOk()
            ->assertDontSee('All Chores')
            ->assertDontSee('All Pills');
    }
}
