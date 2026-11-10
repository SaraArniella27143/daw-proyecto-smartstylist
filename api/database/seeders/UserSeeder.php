<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const ADMIN_DEMO_PASSWORD = 'Admin1234!';
    private const USER_DEMO_PASSWORD = 'User1234!';

    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@cifpaviles.es', 'role' => 'admin'],
            ['name' => 'Usuario 1', 'email' => 'user1@cifpaviles.es', 'role' => 'user'],
            ['name' => 'Usuario 2', 'email' => 'user2@cifpaviles.es', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            if ($user['role'] === 'admin') {
                $hashedPassword = Hash::make(self::ADMIN_DEMO_PASSWORD);
            } else {
                $hashedPassword = Hash::make(self::USER_DEMO_PASSWORD);
            }
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    ...$user,
                    'email_verified_at' => now(),
                    'password' => $hashedPassword,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
