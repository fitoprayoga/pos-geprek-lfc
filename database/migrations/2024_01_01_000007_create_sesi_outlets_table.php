<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kasir')->constrained('users')->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
            $table->date('tanggal');
            $table->time('jam_buka');
            $table->time('jam_tutup')->nullable();
            $table->enum('status', ['BUKA', 'TUTUP'])->default('BUKA');
            $table->decimal('total_rekap_penjualan', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_outlets');
    }
};
