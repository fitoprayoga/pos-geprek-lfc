<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokLog extends Model
{
    protected $table = 'stok_logs';

    protected $fillable = [
        'id_bahan',
        'jenis_aksi',
        'jumlah',
        'alasan',
        'tanggal',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tanggal' => 'datetime',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Bahan baku yang dicatat perubahannya */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'id_bahan');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeMasuk($query)
    {
        return $query->where('jenis_aksi', 'MASUK');
    }

    public function scopeKeluar($query)
    {
        return $query->where('jenis_aksi', 'KELUAR');
    }

    public function scopeKoreksi($query)
    {
        return $query->where('jenis_aksi', 'KOREKSI');
    }
}
