<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'nama_menu',
        'kategori',
        'harga',
        'status_aktif',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'status_aktif' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Resep bahan-bahan untuk menu ini */
    public function resepMenus(): HasMany
    {
        return $this->hasMany(ResepMenu::class, 'id_menu');
    }

    /** Detail transaksi yang memesan menu ini */
    public function detailTransaksis(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_menu');
    }

    /** Bahan baku yang dipakai (melalui resep) */
    public function bahanBakus(): BelongsToMany
    {
        return $this->belongsToMany(BahanBaku::class, 'resep_menus', 'id_menu', 'id_bahan')
            ->withPivot('jumlah_dibutuhkan')
            ->withTimestamps();
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }
}
