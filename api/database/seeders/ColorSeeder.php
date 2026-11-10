<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Blanco', 'hex_code' => '#FFFFFF'],
            ['name' => 'Negro', 'hex_code' => '#000000'],
            ['name' => 'Azul marino', 'hex_code' => '#000080'],
            ['name' => 'Beis', 'hex_code' => '#F5F5DC'],
            ['name' => 'Rojo', 'hex_code' => '#FF0000'],
            ['name' => 'Verde', 'hex_code' => '#008000'],
            ['name' => 'Gris', 'hex_code' => '#808080'],
            ['name' => 'Marrón', 'hex_code' => '#A52A2A'],
            ['name' => 'Azul claro', 'hex_code' => '#ADD8E6'],
            ['name' => 'Amarillo', 'hex_code' => '#FFFF00'],
        ];

        foreach ($colors as $color) {
            DB::table('colors')->updateOrInsert(
                ['name' => $color['name']],
                [...$color, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
