<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Camisetas',
                'description' => 'Prendas ligeras para la parte superior, de manga corta o larga.',
                'outfit_group' => 'upper_body',
            ],
            [
                'name' => 'Pantalones',
                'description' => 'Prendas que cubren el cuerpo desde la cintura hasta los tobillos.',
                'outfit_group' => 'lower_body',
            ],
            [
                'name' => 'Vestidos',
                'description' => 'Prendas de una sola pieza que cubren el cuerpo.',
                'outfit_group' => 'full_body',
            ],
            [
                'name' => 'Calzado',
                'description' => 'Artículos que protegen y cubren los pies.',
                'outfit_group' => 'optional',
            ],
            [
                'name' => 'Abrigos',
                'description' => 'Prendas que se llevan sobre otras para abrigarse o protegerse.',
                'outfit_group' => 'optional',
            ],
            [
                'name' => 'Faldas',
                'description' => 'Prendas sujetas a la cintura que caen alrededor de las piernas.',
                'outfit_group' => 'lower_body',
            ],
            [
                'name' => 'Accesorios',
                'description' => 'Artículos que completan o complementan un conjunto.',
                'outfit_group' => 'optional',
            ],
            [
                'name' => 'Prendas de punto',
                'description' => 'Prendas de tejido de punto, como los jerséis.',
                'outfit_group' => 'upper_body',
            ],
            [
                'name' => 'Pantalones cortos',
                'description' => 'Prendas que cubren la cadera y la parte superior de las piernas.',
                'outfit_group' => 'lower_body',
            ],
            [
                'name' => 'Camisas',
                'description' => 'Prendas para la parte superior del cuerpo, con cuello o botones.',
                'outfit_group' => 'upper_body',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'outfit_group' => $category['outfit_group'],
                ],
            );
        }
    }
}
