<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockDetail extends Model
{
    protected $table = 'restock_details';

    protected $fillable = [
        'id_restock',
        'id_bahan',
        'jumlah',
        'harga_satuan',
        'subtotal',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Header restock induk dari detail ini */
    public function restockHeader(): BelongsTo
    {
        return $this->belongsTo(RestockHeader::class, 'id_restock');
    }

    /** Bahan baku yang direstok */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'id_bahan');
    }
}
