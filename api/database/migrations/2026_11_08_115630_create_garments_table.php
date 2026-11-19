<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('colors')->restrictOnDelete();
            $table->foreignId('style_id')->nullable()->constrained('styles')->restrictOnDelete();
            $table->string('name', 120);
            $table->string('season', 20);
            $table->string('image_url', 2048)->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE garments ADD CONSTRAINT garments_season_check CHECK (season IN ('spring', 'summer', 'autumn', 'winter', 'year_round'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('garments');
    }
};
