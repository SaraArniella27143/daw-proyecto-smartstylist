<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StyleSeeder extends Seeder
{
    public function run(): void
    {
        $styles = [
            ['name' => 'Informal', 'description' => 'Ropa cómoda para el día a día.'],
            ['name' => 'Formal', 'description' => 'Ropa elegante para ocasiones formales.'],
            ['name' => 'Deportivo', 'description' => 'Ropa diseñada para realizar actividad física.'],
            ['name' => 'Bohemio', 'description' => 'Estilo relajado con prendas holgadas y fluidas.'],
            ['name' => 'Clásico', 'description' => 'Prendas atemporales con detalles sencillos y cuidados.'],
            ['name' => 'Urbano', 'description' => 'Estilo informal inspirado en la cultura de la ciudad.'],
            ['name' => 'Minimalista', 'description' => 'Prendas sencillas, de líneas limpias y colores neutros.'],
            ['name' => 'Informal elegante', 'description' => 'Ropa cuidada adecuada para un entorno de trabajo relajado.'],
            ['name' => 'Retro', 'description' => 'Estilo inspirado en la ropa de décadas anteriores.'],
            ['name' => 'Funcional', 'description' => 'Ropa práctica diseñada para mantenerse activo.'],
        ];

        foreach ($styles as $style) {
            DB::table('styles')->updateOrInsert(
                ['name' => $style['name']],
                [...$style, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
