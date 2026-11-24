<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    protected $fillable = [
        'nama_outlet',
        'alamat',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Kasir / User yang ditempatkan di outlet ini */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'outlet_id');
    }

    /** Stok bahan baku milik outlet ini */
    public function bahanBakus(): HasMany
    {
        return $this->hasMany(BahanBaku::class, 'outlet_id');
    }

    /** Sesi kasir yang dibuka di outlet ini */
    public function sesiOutlets(): HasMany
    {
        return $this->hasMany(SesiOutlet::class, 'outlet_id');
    }

    /** Transaksi yang terjadi di outlet ini */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'outlet_id');
    }

    /** Restock yang dilakukan untuk outlet ini */
    public function restockHeaders(): HasMany
    {
        return $this->hasMany(RestockHeader::class, 'outlet_id');
    }
}
