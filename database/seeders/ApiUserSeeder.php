<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'api@system.local'],
            [
                'name' => 'API User',
                'password' => Hash::make('api-system-user-' . bin2hex(random_bytes(16))),
                'email_verified_at' => now(),
            ]
        );
    }
}
