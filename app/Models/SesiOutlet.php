<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesiOutlet extends Model
{
    protected $table = 'sesi_outlets';

    protected $fillable = [
        'id_kasir',
        'outlet_id',
        'tanggal',
        'jam_buka',
        'jam_tutup',
        'status',
        'total_rekap_penjualan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_rekap_penjualan' => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Kasir yang membuka sesi ini */
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    /** Outlet tempat sesi berlangsung */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeBuka($query)
    {
        return $query->where('status', 'BUKA');
    }

    public function scopeTutup($query)
    {
        return $query->where('status', 'TUTUP');
    }
}
