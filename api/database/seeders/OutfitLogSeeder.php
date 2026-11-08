<?php

namespace Database\Seeders;

use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutfitLogSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Día informal', 'used_at' => '2026-11-02 08:00:00+00'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Fin de semana cómodo', 'used_at' => '2026-11-03 08:00:00+00'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Vestido de verano', 'used_at' => '2026-11-04 08:00:00+00'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Capas urbanas', 'used_at' => '2026-11-05 08:00:00+00'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Comodidad bohemia', 'used_at' => '2026-11-06 08:00:00+00'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Reunión elegante', 'used_at' => '2026-11-07 08:30:00+00'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Combinación neutra', 'used_at' => '2026-11-08 08:30:00+00'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Paseo clásico', 'used_at' => '2026-11-09 08:30:00+00'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Día minimalista', 'used_at' => '2026-11-10 08:30:00+00'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Fin de semana deportivo', 'used_at' => '2026-11-11 08:30:00+00'],
        ];

        foreach ($logs as $log) {
            $user = User::where('email', $log['user_email'])->firstOrFail();
            $outfit = Outfit::where('user_id', $user->id)
                ->where('name', $log['outfit'])
                ->firstOrFail();

            DB::table('outfit_logs')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'outfit_id' => $outfit->id,
                    'used_at' => $log['used_at'],
                ],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
