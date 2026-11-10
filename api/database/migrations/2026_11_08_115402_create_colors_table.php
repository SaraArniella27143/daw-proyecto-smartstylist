<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->char('hex_code', 7)->unique();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE colors ADD CONSTRAINT colors_hex_code_format_check CHECK (hex_code ~ '^#[0-9A-Fa-f]{6}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
