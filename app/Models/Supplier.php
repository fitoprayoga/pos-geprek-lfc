<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'nama_supplier',
        'no_telepon',
        'alamat',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Riwayat restock dari supplier ini */
    public function restockHeaders(): HasMany
    {
        return $this->hasMany(RestockHeader::class, 'id_supplier');
    }
}
