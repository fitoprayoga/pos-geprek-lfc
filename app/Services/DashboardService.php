<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\Outlet;
use App\Models\RestockHeader;
use App\Models\Transaksi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Menghitung total revenue masing-masing outlet pada rentang tanggal terpilih,
     * diurutkan dari revenue tertinggi ke terendah.
     */
    public function getPerformaOutlet(string $startDate, string $endDate): Collection
    {
        $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay();

        $revenues = Transaksi::lunas()
            ->whereBetween('tanggal_waktu', [$start, $end])
            ->select('outlet_id', DB::raw('SUM(total_harga) as total_revenue'))
            ->groupBy('outlet_id')
            ->pluck('total_revenue', 'outlet_id');

        return Outlet::orderBy('nama_outlet')
            ->get()
            ->map(function (Outlet $outlet) use ($revenues) {
                return [
                    'id' => $outlet->id,
                    'nama_outlet' => $outlet->nama_outlet,
                    'total_revenue' => (float) ($revenues->get($outlet->id, 0)),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();
    }

    /**
     * Mengambil daftar menu terlaris berdasarkan total porsi terjual dari transaksi lunas.
     * Setelah query, foto_url di-resolve dari file public/images/menu-{id}.{ext} (aman untuk PostgreSQL GROUP BY).
     */
    public function getMenuTerlaris(string $startDate, string $endDate, ?int $outletId = null, int $limit = 10): Collection
    {
        $start = Carbon::parse($startDate, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($endDate, 'Asia/Jakarta')->endOfDay();

        $rows = DB::table('detail_transaksis')
            ->join('transaksis', 'detail_transaksis.id_transaksi', '=', 'transaksis.id')
            ->join('menus', 'detail_transaksis.id_menu', '=', 'menus.id')
            ->where('transaksis.status_pembayaran', true)
            ->whereBetween('transaksis.tanggal_waktu', [$start, $end])
            ->when($outletId, fn ($query) => $query->where('transaksis.outlet_id', $outletId))
            ->groupBy('menus.id', 'menus.nama_menu', 'menus.kategori')
            ->select(
                'menus.id',
                'menus.nama_menu',
                'menus.kategori',
                DB::raw('SUM(detail_transaksis.jumlah) as total_porsi'),
                DB::raw('SUM(detail_transaksis.subtotal) as total_penjualan')
            )
            ->orderByDesc('total_porsi')
            ->orderByDesc('total_penjualan')
            ->limit($limit)
            ->get();

        // Resolve foto_url untuk setiap menu (cek file di public/images/menu-{id}.{ext})
        return $rows->map(function ($row) {
            $row->foto_url = $this->resolveMenuPhotoUrl((int) $row->id);
            return $row;
        });
    }

    /**
     * Mengembalikan URL foto menu jika file ada di public/images/menu-{id}.{jpg|jpeg|png}.
     * Menggunakan mekanisme yang sama dengan MenuController::resolveMenuPhotoUrl.
     */
    private function resolveMenuPhotoUrl(int $menuId): ?string
    {
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $relative = "images/menu-{$menuId}.{$ext}";
            if (file_exists(public_path($relative))) {
                return asset($relative) . '?v=' . filemtime(public_path($relative));
            }
        }

        return null;
    }
}
