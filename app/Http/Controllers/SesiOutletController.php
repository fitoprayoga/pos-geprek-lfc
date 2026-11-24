<?php

namespace App\Http\Controllers;

use App\Models\SesiOutlet;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesiOutletController extends Controller
{
    /**
     * Membuka sesi outlet untuk kasir yang sedang login.
     *
     * POST /outlet/buka
     */
    public function openOutlet(Request $request): JsonResponse
    {
        $kasir = $request->user();

        // Pastikan kasir terhubung dengan outlet
        if (! $kasir->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum terhubung dengan outlet manapun.',
            ], 422);
        }

        // Cek apakah outlet kasir tersebut sudah punya sesi berstatus BUKA hari ini
        $sesiAktif = SesiOutlet::where('outlet_id', $kasir->outlet_id)
            ->whereDate('tanggal', now()->toDateString())
            ->where('status', 'BUKA')
            ->first();

        // Jika sudah buka, langsung kembalikan status BUKA
        if ($sesiAktif) {
            return response()->json([
                'success' => true,
                'message' => 'Outlet sudah memiliki sesi aktif (BUKA) hari ini.',
                'status' => 'BUKA',
                'data' => $sesiAktif,
            ], 200);
        }

        // Jika belum, buat record baru di sesi_outlets
        $sesiBaru = SesiOutlet::create([
            'id_kasir' => $kasir->id,
            'outlet_id' => $kasir->outlet_id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesi outlet berhasil dibuka.',
            'status' => 'BUKA',
            'data' => $sesiBaru,
        ], 201);
    }

    /**
     * Menutup sesi outlet aktif kasir dan menghitung rekap penjualan harian.
     *
     * POST /outlet/tutup
     */
    public function closeOutlet(Request $request): JsonResponse
    {
        $kasir = $request->user();

        // Pastikan kasir terhubung dengan outlet
        if (! $kasir->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum terhubung dengan outlet manapun.',
            ], 422);
        }

        // Ambil sesi aktif kasir
        $sesi = SesiOutlet::where('outlet_id', $kasir->outlet_id)
            ->where('status', 'BUKA')
            ->latest()
            ->first();

        if (! $sesi) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan sesi outlet yang sedang berstatus BUKA.',
            ], 404);
        }

        // Waktu mulai sesi untuk filter transaksi selama sesi ini
        $waktuMulai = $sesi->created_at ?? Carbon::parse($sesi->tanggal->format('Y-m-d').' '.$sesi->jam_buka);

        // Ambil transaksi yang lunas selama sesi tersebut
        $transaksis = Transaksi::where('outlet_id', $sesi->outlet_id)
            ->where('status_pembayaran', true)
            ->where(function ($query) use ($sesi, $waktuMulai) {
                $query->where('created_at', '>=', $waktuMulai)
                    ->orWhere(function ($sub) use ($sesi) {
                        $sub->whereDate('tanggal_waktu', $sesi->tanggal)
                            ->whereTime('tanggal_waktu', '>=', $sesi->jam_buka);
                    });
            })
            ->get();

        // Hitung ringkasan Tunai dan QRIS
        $transaksiTunai = $transaksis->where('metode_pembayaran', 'TUNAI');
        $transaksiQris = $transaksis->where('metode_pembayaran', 'QRIS');

        $totalTunai = (float) $transaksiTunai->sum('total_harga');
        $jumlahTunai = $transaksiTunai->count();

        $totalQris = (float) $transaksiQris->sum('total_harga');
        $jumlahQris = $transaksiQris->count();

        $totalRekapPenjualan = $totalTunai + $totalQris;
        $totalTransaksi = $transaksis->count();

        // Simpan jam_tutup, ubah status menjadi TUTUP, dan simpan total_rekap_penjualan
        $sesi->update([
            'jam_tutup' => now()->format('H:i:s'),
            'status' => 'TUTUP',
            'total_rekap_penjualan' => $totalRekapPenjualan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesi outlet berhasil ditutup. Rekap harian berhasil dihitung.',
            'status' => 'TUTUP',
            'data' => [
                'sesi' => [
                    'id' => $sesi->id,
                    'id_kasir' => $sesi->id_kasir,
                    'outlet_id' => $sesi->outlet_id,
                    'tanggal' => $sesi->tanggal->format('Y-m-d'),
                    'jam_buka' => $sesi->jam_buka,
                    'jam_tutup' => $sesi->jam_tutup,
                    'status' => $sesi->status,
                    'total_rekap_penjualan' => (float) $sesi->total_rekap_penjualan,
                ],
                'rekap' => [
                    'total_transaksi' => $totalTransaksi,
                    'total_rekap_penjualan' => $totalRekapPenjualan,
                    'tunai' => [
                        'total' => $totalTunai,
                        'jumlah_transaksi' => $jumlahTunai,
                    ],
                    'qris' => [
                        'total' => $totalQris,
                        'jumlah_transaksi' => $jumlahQris,
                    ],
                ],
            ],
        ], 200);
    }
}
