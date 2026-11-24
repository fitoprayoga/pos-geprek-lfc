<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\SesiOutletController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Route Autentikasi (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/auth/login', [AuthController::class, 'authenticate'])->name('auth.login');
});

// Route Logout (Harus Terautentikasi)
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

// Route Area OWNER (Terproteksi Auth & Role OWNER)
Route::middleware(['auth', 'role:OWNER'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/filter', [DashboardController::class, 'filterDashboard'])->name('dashboard.filter');
    Route::get('/dashboard/export', [DashboardController::class, 'exportLaporan'])->name('dashboard.export');

    // Manajemen Stok Bahan Baku & Restock
    Route::get('/stok', [StokController::class, 'index'])->name('stok.index');
    Route::post('/stok/restock', [StokController::class, 'storeRestock'])->name('stok.restock');
    Route::post('/stok/keluar', [StokController::class, 'storeStokKeluar'])->name('stok.keluar');

    // Manajemen Menu & Resep
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu/store', [MenuController::class, 'storeMenu'])->name('menu.store');
    Route::put('/menu/{id}/update-harga', [MenuController::class, 'updateMenuPrice'])->name('menu.update-harga');
    Route::patch('/menu/{id}/status', [MenuController::class, 'deactivateMenu'])->name('menu.status');
});

// Route Area POS KASIR (Terproteksi Auth & Role KASIR)
Route::middleware(['auth', 'role:KASIR'])->group(function () {
    Route::get('/pos', function () {
        return response('POS Kasir', 200);
    })->name('pos');

    // Manajemen Sesi Outlet Kasir
    Route::post('/outlet/buka', [SesiOutletController::class, 'openOutlet'])->name('outlet.buka');
    Route::post('/outlet/tutup', [SesiOutletController::class, 'closeOutlet'])->name('outlet.tutup');

    // Transaksi Kasir
    Route::post('/transaksi/cek-stok', [TransaksiController::class, 'checkMenuStock'])->name('transaksi.cek-stok');
    Route::post('/transaksi/bayar', [TransaksiController::class, 'storeTransaksi'])->name('transaksi.bayar');
});
