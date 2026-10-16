<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@budaya.test'],
            [
                'name'     => 'Admin',
                'password' => 'ganti-password-ini', // di-hash otomatis oleh cast
                'role'     => 'admin',
            ]
        );
    }
}