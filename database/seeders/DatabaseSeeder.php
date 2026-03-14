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
        User::factory()->admin()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Sample Helper',
            'username' => 'G1234567X',
            'email' => null,
        ]);
    }
}
