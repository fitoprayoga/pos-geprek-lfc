<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResepMenu extends Model
{
    protected $table = 'resep_menus';

    protected $fillable = [
        'id_menu',
        'id_bahan',
        'jumlah_dibutuhkan',
    ];

    protected $casts = [
        'jumlah_dibutuhkan' => 'decimal:2',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Menu yang dimiliki resep ini */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'id_menu');
    }

    /** Bahan baku yang digunakan dalam resep ini */
    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'id_bahan');
    }
}
