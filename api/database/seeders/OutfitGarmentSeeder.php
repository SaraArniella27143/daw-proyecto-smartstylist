<?php

namespace Database\Seeders;

use App\Models\Garment;
use App\Models\Outfit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutfitGarmentSeeder extends Seeder
{
    public function run(): void
    {
        $relations = [
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Día informal', 'garment' => 'Camiseta blanca de algodón', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Día informal', 'garment' => 'Vaqueros azul marino', 'is_primary' => false, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Reunión elegante', 'garment' => 'Camisa negra', 'is_primary' => true, 'note' => 'Llevar por dentro para un aspecto más elegante.'],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Reunión elegante', 'garment' => 'Pantalones beis', 'is_primary' => false, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Vestido de verano', 'garment' => 'Vestido rojo de verano', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Paseo clásico', 'garment' => 'Botas de cuero marrón', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Fin de semana cómodo', 'garment' => 'Camiseta blanca de algodón', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Capas urbanas', 'garment' => 'Chaqueta verde', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Capas urbanas', 'garment' => 'Jersey azul claro', 'is_primary' => false, 'note' => null],
            ['user_email' => 'user1@cifpaviles.es', 'outfit' => 'Comodidad bohemia', 'garment' => 'Jersey azul claro', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Combinación neutra', 'garment' => 'Pantalones beis', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Combinación neutra', 'garment' => 'Falda midi gris', 'is_primary' => false, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Día minimalista', 'garment' => 'Falda midi gris', 'is_primary' => true, 'note' => null],
            ['user_email' => 'user2@cifpaviles.es', 'outfit' => 'Fin de semana deportivo', 'garment' => 'Pantalones cortos amarillos de correr', 'is_primary' => true, 'note' => null],
        ];

        foreach ($relations as $relation) {
            $user = User::where('email', $relation['user_email'])->firstOrFail();
            $outfit = Outfit::where('user_id', $user->id)
                ->where('name', $relation['outfit'])
                ->firstOrFail();
            $garment = Garment::where('name', $relation['garment'])
                ->where('user_id', $outfit->user_id)
                ->firstOrFail();

            DB::table('outfit_garments')->updateOrInsert(
                ['outfit_id' => $outfit->id, 'garment_id' => $garment->id],
                [
                    'is_primary' => $relation['is_primary'],
                    'note' => $relation['note'],
                ],
            );
        }
    }
}
