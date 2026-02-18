<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'info@dailydewtech.com.ng'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );
    }
}
