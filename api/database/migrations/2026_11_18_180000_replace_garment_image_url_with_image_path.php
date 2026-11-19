<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('garments', function (Blueprint $table): void {
            $table->renameColumn('image_url', 'legacy_image_url');
            $table->string('image_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('garments', function (Blueprint $table): void {
            $table->dropColumn('image_path');
            $table->renameColumn('legacy_image_url', 'image_url');
        });
    }
};
