<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class ApiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::apiUser();
    }
}
