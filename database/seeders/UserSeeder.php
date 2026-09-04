<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::forceCreate([
            'name' => 'ユーザー1（一般）',
            'email' => 'user1@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => false,
        ]);

        User::forceCreate([
            'name' => 'ユーザー2（一般）',
            'email' => 'user2@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => false,
        ]);

        User::forceCreate([
            'name' => 'ユーザー3（管理者）',
            'email' => 'user3@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'admin_status' => true,
        ]);
    }
}
