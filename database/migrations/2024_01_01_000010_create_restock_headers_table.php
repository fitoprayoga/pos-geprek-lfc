<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restock_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_owner')->constrained('users')->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
            $table->foreignId('id_supplier')->constrained('suppliers')->restrictOnDelete();
            $table->date('tanggal_restock');
            $table->decimal('total_biaya', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restock_headers');
    }
};
