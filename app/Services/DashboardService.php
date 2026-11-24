<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\RestockHeader;
use App\Models\Transaksi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardService
{
    /**
     * Mengambil ringkasan penjualan, rincian metode pembayaran, dan tren harian.
     */
    public function getRingkasanPenjualan(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transaksis = Transaksi::lunas()
            ->whereBetween('tanggal_waktu', [$start, $end])
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->get();

        $tunai = $transaksis->where('metode_pembayaran', 'TUNAI');
        $qris = $transaksis->where('metode_pembayaran', 'QRIS');

        $groupedByDate = $transaksis->groupBy(
            fn (Transaksi $transaksi) => $transaksi->tanggal_waktu->format('Y-m-d')
        );

        $trenHarian = [];
        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            $tanggal = $date->format('Y-m-d');
            $harian = $groupedByDate->get($tanggal, collect());

            $trenHarian[] = [
                'tanggal' => $tanggal,
                'total_penjualan' => (float) $harian->sum('total_harga'),
                'jumlah_transaksi' => $harian->count(),
            ];
        }

        return [
            'total_revenue' => (float) $transaksis->sum('total_harga'),
            'total_volume_transaksi' => $transaksis->count(),
            'metode_pembayaran' => [
                'TUNAI' => [
                    'total' => (float) $tunai->sum('total_harga'),
                    'jumlah_transaksi' => $tunai->count(),
                ],
                'QRIS' => [
                    'total' => (float) $qris->sum('total_harga'),
                    'jumlah_transaksi' => $qris->count(),
                ],
            ],
            'tren_penjualan_harian' => $trenHarian,
        ];
    }

    /**
     * Mengambil daftar stok bahan baku beserta daftar alert stok kritis (stok <= stok_minimal).
     */
    public function getLaporanStokBahan(?int $outletId = null): array
    {
        $daftarStok = BahanBaku::with('outlet')
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->orderBy('nama_bahan')
            ->get();

        $stokAlert = BahanBaku::with('outlet')
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->whereColumn('stok', '<=', 'stok_minimal')
            ->orderBy('stok')
            ->get();

        return [
            'total_item_stok' => $daftarStok->count(),
            'total_alert' => $stokAlert->count(),
            'daftar_stok' => $daftarStok,
            'stok_alert' => $stokAlert,
        ];
    }

    /**
     * Menghitung total biaya pengeluaran restock dari restock_headers.
     */
    public function getLaporanPengeluaranRestock(string $startDate, string $endDate, ?int $outletId = null): array
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        $restocks = RestockHeader::with(['outlet', 'supplier'])
            ->whereDate('tanggal_restock', '>=', $start)
            ->whereDate('tanggal_restock', '<=', $end)
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->latest('tanggal_restock')
            ->get();

        return [
            'total_pengeluaran' => (float) $restocks->sum('total_biaya'),
            'jumlah_restock' => $restocks->count(),
            'riwayat_restock' => $restocks,
        ];
    }
}
