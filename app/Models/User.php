<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'role',
        'status_aktif',
        'outlet_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'password' => 'hashed',
    ];

    // ─── Relasi ──────────────────────────────────────────────────

    /** Outlet tempat user (kasir) bertugas */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    /** Sesi outlet yang dibuka oleh kasir ini */
    public function sesiOutlets(): HasMany
    {
        return $this->hasMany(SesiOutlet::class, 'id_kasir');
    }

    /** Transaksi yang dilayani oleh kasir ini */
    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_kasir');
    }

    /** Restock yang diinput oleh owner ini */
    public function restockHeaders(): HasMany
    {
        return $this->hasMany(RestockHeader::class, 'id_owner');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }

    public function scopeKasir($query)
    {
        return $query->where('role', 'KASIR');
    }

    public function scopeOwner($query)
    {
        return $query->where('role', 'OWNER');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === 'OWNER';
    }

    public function isKasir(): bool
    {
        return $this->role === 'KASIR';
    }
}
