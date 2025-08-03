<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
       User::create([
    'id' => Str::uuid(),
    'name' => 'Admin Super',
    'username' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('password123'),
    'role' => 'admin',
    'school_id' => null, // super admin
    'is_active' => true,
    'last_login_at' => null,
]);

    }
}
