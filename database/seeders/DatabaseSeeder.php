<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!App::environment('production')) {
            // Create test admin user. Password is "password".
            User::factory()->admin()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            echo "Default admin user seeded successfully. \n\n";
        }
    }
}
