<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChoreTagTest extends TestCase
{
    use RefreshDatabase;

    private function choreData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Wash Dishes',
            'desc' => 'Scrub everything',
            'occurMonth' => 0,
            'occurDay' => 1,
            'weight' => 2,
        ], $overrides);
    }

    public function test_tags_are_created_with_pastel_color_when_storing_a_chore(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/chores', $this->choreData(['tags' => ['Kitchen', ' Daily ', '']]))
            ->assertRedirect(route('chores.index'));

        $chore = Chore::where('slug', 'wash-dishes')->firstOrFail();
        $this->assertEquals(['Daily', 'Kitchen'], $chore->tags->pluck('name')->all());
        foreach ($chore->tags as $tag) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $tag->color);
        }
    }

    public function test_new_tags_get_distinct_palette_colors_before_repeating(): void
    {
        $paletteSize = count(Tag::PALETTE);
        $colors = collect(range(1, $paletteSize + 1))->map(fn ($i) => Tag::create(['name' => "Tag {$i}"])->color);

        $this->assertEquals(Tag::PALETTE, $colors->take($paletteSize)->all());
        $this->assertEquals(Tag::PALETTE[0], $colors->last());

        // A freed-up color is reused before doubling up on others
        Tag::where('color', Tag::PALETTE[3])->delete();
        $this->assertEquals(Tag::PALETTE[3], Tag::create(['name' => 'Replacement'])->color);
    }

    public function test_updating_reuses_existing_tags_and_syncs(): void
    {
        $kitchen = Tag::create(['name' => 'Kitchen']);
        $old = Tag::create(['name' => 'Old']);
        $chore = Chore::factory()->create(['slug' => 'wash-dishes']);
        $chore->tags()->attach([$kitchen->id, $old->id]);

        $this->actingAs(User::factory()->create())
            ->patch('/chores/wash-dishes', $this->choreData(['tags' => ['kitchen', 'New']]))
            ->assertRedirect(route('chores.index'));

        $this->assertEquals(['Kitchen', 'New'], $chore->fresh()->tags->pluck('name')->all());
        $this->assertEquals($kitchen->color, $kitchen->fresh()->color);
        $this->assertEquals(3, Tag::count());
    }

    public function test_case_insensitive_duplicate_tags_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/chores', $this->choreData(['tags' => ['Kitchen', 'kitchen']]))
            ->assertSessionHasErrors('tags.1');
    }

    public function test_chores_index_filters_by_tag(): void
    {
        $tagged = Chore::factory()->create(['name' => 'Tagged Chore']);
        Chore::factory()->create(['name' => 'Untagged Chore']);
        $tagged->tags()->attach(Tag::create(['name' => 'Kitchen']));

        $this->actingAs(User::factory()->create())
            ->get('/chores?tag=Kitchen')
            ->assertOk()
            ->assertSee('Tagged Chore')
            ->assertDontSee('Untagged Chore');
    }

    public function test_create_and_edit_forms_render_tag_input(): void
    {
        $chore = Chore::factory()->create(['slug' => 'wash-dishes']);
        $chore->tags()->attach(Tag::create(['name' => 'Kitchen']));
        $user = User::factory()->create();

        $this->actingAs($user)->get('/chores/create')->assertOk()->assertSee('tags-options', false);
        $this->actingAs($user)->get('/chores/wash-dishes/edit')->assertOk()->assertSee('Kitchen');
    }

    public function test_dashboard_shows_tags(): void
    {
        $chore = Chore::factory()->create(['occurrence_hours' => 0]);
        $chore->tags()->attach(Tag::create(['name' => 'Errands']));

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Errands');
    }
}
