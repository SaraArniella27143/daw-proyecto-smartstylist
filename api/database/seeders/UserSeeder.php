<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@cifpaviles.es', 'role' => 'admin'],
            ['name' => 'Usuario 1', 'email' => 'user1@cifpaviles.es', 'role' => 'user'],
            ['name' => 'Usuario 2', 'email' => 'user2@cifpaviles.es', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    ...$user,
                    'email_verified_at' => now(),
                    'password' => Hash::make('PasswordSecure123*'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
