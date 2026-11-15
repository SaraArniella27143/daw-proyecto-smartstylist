<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('outfit_group', 20)
                ->default('optional');
        });

        $categoriesByGroup = [
            'upper_body' => ['Camisetas', 'Camisas', 'Prendas de punto'],
            'lower_body' => ['Pantalones', 'Faldas', 'Pantalones cortos'],
            'full_body' => ['Vestidos'],
        ];

        foreach ($categoriesByGroup as $group => $categoryNames) {
            DB::table('categories')
                ->whereIn('name', $categoryNames)
                ->update(['outfit_group' => $group]);
        }

        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_outfit_group_check CHECK (outfit_group IN ('upper_body', 'lower_body', 'full_body', 'optional'))");
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE categories DROP CHECK categories_outfit_group_check');
        } else {
            DB::statement('ALTER TABLE categories DROP CONSTRAINT categories_outfit_group_check');
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('outfit_group');
        });
    }
};
