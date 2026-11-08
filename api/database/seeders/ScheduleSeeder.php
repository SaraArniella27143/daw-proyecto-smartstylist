<?php

namespace Database\Seeders;

use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Día informal', 'scheduled_date' => '2026-11-06'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Fin de semana cómodo', 'scheduled_date' => '2026-11-07'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Vestido de verano', 'scheduled_date' => '2026-11-08'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Capas urbanas', 'scheduled_date' => '2026-11-09'],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Comodidad bohemia', 'scheduled_date' => '2026-11-10'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Reunión elegante', 'scheduled_date' => '2026-11-06'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Combinación neutra', 'scheduled_date' => '2026-11-07'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Paseo clásico', 'scheduled_date' => '2026-11-08'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Día minimalista', 'scheduled_date' => '2026-11-09'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Fin de semana deportivo', 'scheduled_date' => '2026-11-10'],
        ];

        foreach ($schedules as $schedule) {
            $user = User::where('email', $schedule['user_email'])->firstOrFail();
            $outfit = Outfit::where('user_id', $user->id)
                ->where('name', $schedule['outfit'])
                ->firstOrFail();

            DB::table('schedules')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'scheduled_date' => $schedule['scheduled_date'],
                ],
                [
                    'outfit_id' => $outfit->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
