<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Seeder;

class GarmentSeeder extends Seeder
{
    public function run(): void
    {
        $garments = [
            [
                'user_email' => 'user1@cifpaviles.es',
                'category' => 'Camisetas',
                'color' => 'Blanco',
                'style' => 'Informal',
                'name' => 'Camiseta blanca de algodón',
                'season' => 'year_round',
                'image_url' => null,
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'category' => 'Pantalones',
                'color' => 'Azul marino',
                'style' => 'Informal',
                'name' => 'Vaqueros azul marino',
                'season' => 'year_round',
                'image_url' => null,
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'category' => 'Camisas',
                'color' => 'Negro',
                'style' => 'Formal',
                'name' => 'Camisa negra',
                'season' => 'autumn',
                'image_url' => null,
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'category' => 'Pantalones',
                'color' => 'Beis',
                'style' => 'Informal elegante',
                'name' => 'Pantalones beis',
                'season' => 'year_round',
                'image_url' => null,
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'category' => 'Vestidos',
                'color' => 'Rojo',
                'style' => 'Retro',
                'name' => 'Vestido rojo de verano',
                'season' => 'summer',
                'image_url' => null,
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'category' => 'Calzado',
                'color' => 'Marrón',
                'style' => 'Clásico',
                'name' => 'Botas de cuero marrón',
                'season' => 'autumn',
                'image_url' => null,
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'category' => 'Abrigos',
                'color' => 'Verde',
                'style' => 'Urbano',
                'name' => 'Chaqueta verde',
                'season' => 'autumn',
                'image_url' => null,
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'category' => 'Faldas',
                'color' => 'Gris',
                'style' => 'Minimalista',
                'name' => 'Falda midi gris',
                'season' => 'year_round',
                'image_url' => null,
            ],
            [
                'user_email' => 'user1@cifpaviles.es',
                'category' => 'Prendas de punto',
                'color' => 'Azul claro',
                'style' => 'Bohemio',
                'name' => 'Jersey azul claro',
                'season' => 'winter',
                'image_url' => null,
            ],
            [
                'user_email' => 'user2@cifpaviles.es',
                'category' => 'Pantalones cortos',
                'color' => 'Amarillo',
                'style' => 'Deportivo',
                'name' => 'Pantalones cortos amarillos de correr',
                'season' => 'summer',
                'image_url' => null,
            ],
        ];

        foreach ($garments as $garment) {
            $user = User::where('email', $garment['user_email'])->firstOrFail();
            $category = Category::where('name', $garment['category'])->firstOrFail();
            $color = Color::where('name', $garment['color'])->firstOrFail();
            $style = Style::where('name', $garment['style'])->firstOrFail();

            Garment::updateOrCreate(
                ['user_id' => $user->id, 'name' => $garment['name']],
                [
                    'category_id' => $category->id,
                    'color_id' => $color->id,
                    'style_id' => $style->id,
                    'season' => $garment['season'],
                    'image_url' => $garment['image_url'],
                ],
            );
        }
    }
}
