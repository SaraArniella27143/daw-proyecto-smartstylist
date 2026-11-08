<?php

namespace Database\Seeders;

use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Seeder;

class OutfitSeeder extends Seeder
{
    public function run(): void
    {
        $outfits = [
            [
                'user_email' => 'user1@cifpaviles.es',
                'name' => 'Día informal',
                'description' => 'Un conjunto sencillo e informal para el día a día.',
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'name' => 'Fin de semana cómodo',
                'description' => 'Prendas cómodas para un fin de semana relajado.',
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'name' => 'Reunión elegante',
                'description' => 'Un conjunto cuidado para trabajar o salir a cenar.',
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'name' => 'Combinación neutra',
                'description' => 'Un conjunto de tonos neutros para el uso diario.',
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'name' => 'Vestido de verano',
                'description' => 'Un conjunto colorido para los días cálidos.',
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'name' => 'Paseo clásico',
                'description' => 'Una combinación atemporal para pasear por la ciudad.',
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'name' => 'Capas urbanas',
                'description' => 'Un conjunto práctico con varias capas.',
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'name' => 'Día minimalista',
                'description' => 'Un conjunto sencillo y discreto.',
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'name' => 'Comodidad bohemia',
                'description' => 'Un conjunto relajado para los días frescos.',
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'name' => 'Fin de semana deportivo',
                'description' => 'Un conjunto cómodo para un día de actividad.',
            ],
        ];

        foreach ($outfits as $outfit) {
            $user = User::where('email', $outfit['user_email'])->firstOrFail();

            Outfit::updateOrCreate(
                ['user_id' => $user->id, 'name' => $outfit['name']],
                ['description' => $outfit['description']],
            );
        }
    }
}
