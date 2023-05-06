<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test admin user. Password is "password".
//        User::factory()->admin()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
    }
}
