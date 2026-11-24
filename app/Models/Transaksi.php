<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    protected $table = 'transaksis';

    protected $fillable = [
        'id_kasir',
        'outlet_id',
        'kode_transaksi',
        'tanggal_waktu',
        'total_harga',
        'metode_pembayaran',
        'nominal_bayar',
        'kembalian',
        'status_pembayaran',
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
        'total_harga' => 'decimal:2',
        'nominal_bayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
        'status_pembayaran' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Kasir yang memproses transaksi ini */
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_kasir');
    }

    /** Outlet tempat transaksi terjadi */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /** Item-item dalam transaksi ini */
    public function detailTransaksis(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeLunas($query)
    {
        return $query->where('status_pembayaran', true);
    }

    public function scopeTunai($query)
    {
        return $query->where('metode_pembayaran', 'TUNAI');
    }

    public function scopeQris($query)
    {
        return $query->where('metode_pembayaran', 'QRIS');
    }
}
