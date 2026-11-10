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
            ],
            [
                'name' => 'Pantalones',
                'description' => 'Prendas que cubren el cuerpo desde la cintura hasta los tobillos.',
            ],
            [
                'name' => 'Vestidos',
                'description' => 'Prendas de una sola pieza que cubren el cuerpo.',
            ],
            [
                'name' => 'Calzado',
                'description' => 'Artículos que protegen y cubren los pies.',
            ],
            [
                'name' => 'Abrigos',
                'description' => 'Prendas que se llevan sobre otras para abrigarse o protegerse.',
            ],
            [
                'name' => 'Faldas',
                'description' => 'Prendas sujetas a la cintura que caen alrededor de las piernas.',
            ],
            [
                'name' => 'Accesorios',
                'description' => 'Artículos que completan o complementan un conjunto.',
            ],
            [
                'name' => 'Prendas de punto',
                'description' => 'Prendas de tejido de punto, como los jerséis.',
            ],
            [
                'name' => 'Pantalones cortos',
                'description' => 'Prendas que cubren la cadera y la parte superior de las piernas.',
            ],
            [
                'name' => 'Camisas',
                'description' => 'Prendas para la parte superior del cuerpo, con cuello o botones.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']],
            );
        }
    }
}
