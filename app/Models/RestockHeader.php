<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestockHeader extends Model
{
    protected $table = 'restock_headers';

    protected $fillable = [
        'id_owner',
        'outlet_id',
        'id_supplier',
        'tanggal_restock',
        'total_biaya',
    ];

    protected $casts = [
        'tanggal_restock' => 'date',
        'total_biaya' => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Owner yang menginput restock ini */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_owner');
    }

    /** Outlet yang menerima restock ini */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /** Supplier yang menyuplai restock ini */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    /** Detail item bahan yang direstok */
    public function restockDetails(): HasMany
    {
        return $this->hasMany(RestockDetail::class, 'id_restock');
    }
}
