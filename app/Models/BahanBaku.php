<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BahanBaku extends Model
{
    protected $table = 'bahan_bakus';

    protected $fillable = [
        'outlet_id',
        'nama_bahan',
        'stok',
        'satuan',
        'stok_minimal',
    ];

    protected $casts = [
        'stok' => 'decimal:2',
        'stok_minimal' => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Outlet tempat bahan baku ini disimpan */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /** Resep menu yang menggunakan bahan ini */
    public function resepMenus(): HasMany
    {
        return $this->hasMany(ResepMenu::class, 'id_bahan');
    }

    /** Detail restock yang pernah ditambahkan bahan ini */
    public function restockDetails(): HasMany
    {
        return $this->hasMany(RestockDetail::class, 'id_bahan');
    }

    /** Log perubahan stok bahan ini */
    public function stokLogs(): HasMany
    {
        return $this->hasMany(StokLog::class, 'id_bahan');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Cek apakah stok di bawah batas minimal */
    public function isStokKritis(): bool
    {
        return $this->stok <= $this->stok_minimal;
    }
}
