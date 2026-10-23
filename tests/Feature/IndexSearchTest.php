<?php

namespace Tests\Feature;

use App\Models\Chore;
use App\Models\Pet;
use App\Models\Pill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_chores_search_escapes_quotes_in_names_and_descriptions(): void
    {
        Chore::factory()->create(['name' => "Mow Bob's Lawn", 'description' => 'Use the "good" mower']);

        $this->actingAs(User::factory()->create())
            ->get('/chores')
            ->assertOk()
            ->assertSee("'mow bob\\u0027s lawn'.includes", false)
            ->assertSee("'use the \\u0022good\\u0022 mower'.includes", false);
    }

    public function test_pills_search_escapes_quotes_in_pill_and_pet_names(): void
    {
        $pet = Pet::create(['name' => "Mr. O'Malley", 'slug' => 'mr-omalley']);
        Pill::create([
            'pet_id' => $pet->id,
            'name' => "Vet's Choice",
            'slug' => 'vets-choice',
            'dosage' => '75 mg',
            'scheduled_times' => ['08:00'],
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/pills')
            ->assertOk()
            ->assertSee("'vet\\u0027s choice'.includes", false)
            ->assertSee("'mr. o\\u0027malley'.includes", false);
    }

    public function test_pets_search_escapes_quotes_in_names(): void
    {
        Pet::create(['name' => "Mr. O'Malley", 'slug' => 'mr-omalley']);

        $this->actingAs(User::factory()->create())
            ->get('/pets')
            ->assertOk()
            ->assertSee("'mr. o\\u0027malley'.includes", false);
    }
}
