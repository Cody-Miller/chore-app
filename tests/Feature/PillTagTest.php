<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Pill;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PillTagTest extends TestCase
{
    use RefreshDatabase;

    private Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pet = Pet::create(['name' => 'Biscuit', 'slug' => 'biscuit', 'species' => 'Dog']);
    }

    private function createPill(string $name): Pill
    {
        return Pill::create([
            'pet_id' => $this->pet->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'dosage' => '75 mg',
            'scheduled_times' => ['08:00'],
        ]);
    }

    private function pillData(array $overrides = []): array
    {
        return array_merge([
            'pet_id' => $this->pet->id,
            'name' => 'Carprofen',
            'dosage' => '75 mg',
            'scheduled_times' => ['08:00'],
            'is_active' => 1,
        ], $overrides);
    }

    public function test_tags_are_created_as_pill_tags_when_storing_a_pill(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/pills', $this->pillData(['tags' => ['Pain Relief', ' Long-term ', '']]))
            ->assertRedirect(route('pills.index'));

        $pill = Pill::where('slug', 'carprofen')->firstOrFail();
        $this->assertEquals(['Long-term', 'Pain Relief'], $pill->tags->pluck('name')->all());
        foreach ($pill->tags as $tag) {
            $this->assertEquals('pill', $tag->type);
            $this->assertContains($tag->color, Tag::PALETTE);
        }
    }

    public function test_pill_tags_are_separate_from_chore_tags(): void
    {
        $choreTag = Tag::create(['type' => 'chore', 'name' => 'Daily']);
        Tag::create(['type' => 'chore', 'name' => 'Kitchen']);

        $pill = $this->createPill('Carprofen');
        $pill->syncTagNames(['daily']);

        $pillTag = $pill->tags()->sole();
        $this->assertNotEquals($choreTag->id, $pillTag->id);
        $this->assertEquals('pill', $pillTag->type);
        // Each tag list picks colors on its own
        $this->assertEquals(Tag::PALETTE[0], $pillTag->color);
        $this->assertEquals(['daily'], Pill::tagOptions()->pluck('name')->all());
    }

    public function test_updating_reuses_existing_pill_tags_and_syncs(): void
    {
        $food = Tag::create(['type' => 'pill', 'name' => 'Give With Food']);
        $old = Tag::create(['type' => 'pill', 'name' => 'Old']);
        $pill = $this->createPill('Carprofen');
        $pill->tags()->attach([$food->id, $old->id]);

        $this->actingAs(User::factory()->create())
            ->patch('/pills/carprofen', $this->pillData(['tags' => ['give with food', 'New']]))
            ->assertRedirect(route('pills.index'));

        $this->assertEquals(['Give With Food', 'New'], $pill->fresh()->tags->pluck('name')->all());
        $this->assertEquals(3, Tag::ofType('pill')->count());
    }

    public function test_pills_index_filters_by_tag_and_badges_link_to_it(): void
    {
        $this->createPill('Tagged Pill')->syncTagNames(['Thyroid']);
        $this->createPill('Untagged Pill');

        $this->actingAs(User::factory()->create())
            ->get('/pills?tag=Thyroid')
            ->assertOk()
            ->assertSee('Tagged Pill')
            ->assertDontSee('Untagged Pill')
            ->assertSee(route('pills.index', ['tag' => 'Thyroid']), false);
    }

    public function test_pill_forms_only_suggest_pill_tags(): void
    {
        Tag::create(['type' => 'chore', 'name' => 'Kitchen']);
        $this->createPill('Carprofen')->syncTagNames(['Arthritis']);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pills/create')
            ->assertOk()
            ->assertSee('tags-options', false)
            ->assertSee('Arthritis')
            ->assertDontSee('Kitchen');
        $this->actingAs($user)->get('/pills/carprofen/edit')->assertOk()->assertSee('Arthritis');
        $this->actingAs($user)->get('/chores/create')->assertOk()->assertDontSee('Arthritis');
    }
}
