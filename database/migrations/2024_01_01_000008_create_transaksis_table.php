<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kasir')->constrained('users')->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
            $table->string('kode_transaksi', 30)->unique();
            $table->timestampTz('tanggal_waktu')->useCurrent();
            $table->decimal('total_harga', 12, 2);
            $table->enum('metode_pembayaran', ['TUNAI', 'QRIS']);
            $table->decimal('nominal_bayar', 12, 2);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->boolean('status_pembayaran')->default(false); // false=belum lunas, true=lunas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
