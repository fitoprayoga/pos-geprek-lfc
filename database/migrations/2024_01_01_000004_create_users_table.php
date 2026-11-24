<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti tabel users bawaan Laravel dengan skema LFC
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('nama_lengkap', 100);
            $table->enum('role', ['OWNER', 'KASIR'])->default('KASIR');
            $table->boolean('status_aktif')->default(true);
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
