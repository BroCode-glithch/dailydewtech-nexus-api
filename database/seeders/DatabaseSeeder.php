<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders run in order: users first so posts can reference user_id
        $this->call([
            UsersSeeder::class,
            PostsSeeder::class,
            ProjectsSeeder::class,
            MessagesSeeder::class,
        ]);
    }
}
