<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \Faker\Factory;
use \App\Models\User;
use \App\Models\Chore;
use \App\Models\ChoreLog;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = Factory::create();

        $user1 = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '$2y$10$r/WVg9UQs3yBsvnwYeRjAe3eEHJAJSFOT1UF3/K.zr0PX8r2CKZbO', //test1234
        ]);
        $user2 = User::factory()->create([
            'name' => 'Other Test User',
            'email' => 'test2@example.com',
            'password' => '$2y$10$r/WVg9UQs3yBsvnwYeRjAe3eEHJAJSFOT1UF3/K.zr0PX8r2CKZbO', //test1234
        ]);

        $chores = Chore::factory(15)->create();

        for($actionCount = 0; $actionCount < 200; $actionCount++) {
            ChoreLog::factory(15)->create([
                'chore_id' => $chores[rand(1, 14)],
                'user_id' => rand(1, 2),
            ]);
        }

        
    }
}
