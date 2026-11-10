<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outfit_garments', function (Blueprint $table) {
            $table->foreignId('outfit_id')->constrained('outfits')->cascadeOnDelete();
            $table->foreignId('garment_id')->constrained('garments')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('note', 255)->nullable();
            $table->primary(['outfit_id', 'garment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outfit_garments');
    }
};
