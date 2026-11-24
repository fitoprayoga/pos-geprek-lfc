<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_restock')->constrained('restock_headers')->cascadeOnDelete();
            $table->foreignId('id_bahan')->constrained('bahan_bakus')->restrictOnDelete();
            $table->decimal('jumlah', 10, 2);
            $table->decimal('harga_satuan', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_details');
    }
};
