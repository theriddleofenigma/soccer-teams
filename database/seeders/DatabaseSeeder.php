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
        // Todo: Uncomment the below line to seed the default user.
//        User::factory()->admin()->create([
//            'name' => 'Test User',
//            'email' => 'test@example.com',
//        ]);
    }
}
