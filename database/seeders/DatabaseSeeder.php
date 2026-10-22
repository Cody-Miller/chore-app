<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create API user first
        $this->call([
            ApiUserSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '$2y$10$r/WVg9UQs3yBsvnwYeRjAe3eEHJAJSFOT1UF3/K.zr0PX8r2CKZbO', //test1234
        ]);
        User::factory()->create([
            'name' => 'Other Test User',
            'email' => 'test2@example.com',
            'password' => '$2y$10$r/WVg9UQs3yBsvnwYeRjAe3eEHJAJSFOT1UF3/K.zr0PX8r2CKZbO', //test1234
        ]);

        $this->call([
            ChoreSeeder::class,
            PetSeeder::class,
        ]);
    }
}
