<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resep_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_menu')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('id_bahan')->constrained('bahan_bakus')->cascadeOnDelete();
            $table->decimal('jumlah_dibutuhkan', 10, 2); // jumlah bahan untuk 1 porsi menu
            $table->timestamps();

            // Satu menu tidak boleh punya bahan yang sama dua kali
            $table->unique(['id_menu', 'id_bahan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resep_menus');
    }
};
