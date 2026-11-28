<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Transaksi;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Menampilkan ringkasan dashboard default bulan ini (global seluruh outlet).
     *
     * GET /dashboard
     */
    public function index(Request $request): mixed
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->toDateString();

        $data = $this->buildDashboardPayload($startDate, $endDate, null);
        $data['outlets'] = Outlet::orderBy('nama_outlet')->get();

        if ($request->wantsJson() || ! view()->exists('dashboard')) {
            return response()->json([
                'success' => true,
                'message' => 'Dashboard Owner berhasil dimuat.',
                'data' => $data,
            ], 200);
        }

        return view('dashboard', $data);
    }

    /**
     * Menampilkan halaman khusus Laporan Penjualan Owner dengan filter outlet & tanggal.
     *
     * GET /laporan
     */
    public function laporan(Request $request): mixed
    {
        $validated = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $today = now('Asia/Jakarta')->toDateString();
        $startDate = $validated['start_date'] ?? $today;
        $endDate = $validated['end_date'] ?? $today;
        $outletId = isset($validated['outlet_id']) ? (int) $validated['outlet_id'] : null;

        $ringkasan = $this->dashboardService->getRingkasanPenjualan($startDate, $endDate, $outletId);
        $pengeluaran = $this->dashboardService->getLaporanPengeluaranRestock($startDate, $endDate, $outletId);
        $performaOutlet = $this->dashboardService->getPerformaOutlet($startDate, $endDate);
        $menuTerlaris = $this->dashboardService->getMenuTerlaris($startDate, $endDate, $outletId, 10);
        $outlets = Outlet::orderBy('nama_outlet')->get();
        $selectedOutlet = $outletId ? $outlets->firstWhere('id', $outletId) : null;

        $activePreset = $this->detectDatePreset($startDate, $endDate, $today);
        $dateRangeLabel = $this->formatDateRangeLabel($startDate, $endDate, $activePreset);

        return view('owner.laporan', [
            'filter' => [
                'outlet_id' => $outletId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'outlets' => $outlets,
            'selectedOutlet' => $selectedOutlet,
            'totalRevenue' => (float) ($ringkasan['total_revenue'] ?? 0),
            'totalTransaksi' => (int) ($ringkasan['total_volume_transaksi'] ?? 0),
            'totalRestock' => (float) ($pengeluaran['total_pengeluaran'] ?? 0),
            'performaOutlet' => $performaOutlet,
            'menuTerlaris' => $menuTerlaris,
            'activePreset' => $activePreset,
            'dateRangeLabel' => $dateRangeLabel,
        ]);
    }

    /**
     * Memproses filter berdasarkan outlet_id dan rentang tanggal, mengembalikan JSON.
     *
     * GET /dashboard/filter
     */
    public function filterDashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $outletId = isset($validated['outlet_id']) ? (int) $validated['outlet_id'] : null;

        $data = $this->buildDashboardPayload($startDate, $endDate, $outletId);

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil difilter.',
            'data' => $data,
        ], 200);
    }

    /**
     * Mengekspor rekap laporan penjualan sesuai filter ke file CSV yang dapat diunduh.
     *
     * GET /dashboard/export
     */
    public function exportLaporan(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $outletId = isset($validated['outlet_id']) ? (int) $validated['outlet_id'] : null;

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transaksis = Transaksi::with(['outlet', 'kasir'])
            ->lunas()
            ->whereBetween('tanggal_waktu', [$start, $end])
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->orderBy('tanggal_waktu')
            ->get();

        $ringkasan = $this->dashboardService->getRingkasanPenjualan($startDate, $endDate, $outletId);
        $pengeluaran = $this->dashboardService->getLaporanPengeluaranRestock($startDate, $endDate, $outletId);

        $filename = "laporan-penjualan-{$startDate}-sd-{$endDate}.csv";

        return response()->streamDownload(function () use ($transaksis, $ringkasan, $pengeluaran, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar Excel membaca karakter dengan benar
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Laporan Penjualan POS Geprek LFC']);
            fputcsv($handle, ['Periode', "{$startDate} s/d {$endDate}"]);
            fputcsv($handle, ['Total Revenue', $ringkasan['total_revenue']]);
            fputcsv($handle, ['Total Volume Transaksi', $ringkasan['total_volume_transaksi']]);
            fputcsv($handle, ['Total Tunai', $ringkasan['metode_pembayaran']['TUNAI']['total']]);
            fputcsv($handle, ['Total QRIS', $ringkasan['metode_pembayaran']['QRIS']['total']]);
            fputcsv($handle, ['Total Pengeluaran Restock', $pengeluaran['total_pengeluaran']]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Kode Transaksi',
                'Tanggal Waktu',
                'Outlet',
                'Kasir',
                'Metode Pembayaran',
                'Total Harga',
                'Nominal Bayar',
                'Kembalian',
                'Status',
            ]);

            foreach ($transaksis as $trx) {
                fputcsv($handle, [
                    $trx->kode_transaksi,
                    $trx->tanggal_waktu->format('Y-m-d H:i:s'),
                    $trx->outlet?->nama_outlet ?? '-',
                    $trx->kasir?->nama_lengkap ?? '-',
                    $trx->metode_pembayaran,
                    (float) $trx->total_harga,
                    (float) $trx->nominal_bayar,
                    (float) $trx->kembalian,
                    'LUNAS',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Menyusun payload lengkap dashboard dari DashboardService.
     */
    private function buildDashboardPayload(string $startDate, string $endDate, ?int $outletId): array
    {
        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'outlet_id' => $outletId,
            ],
            'ringkasan_penjualan' => $this->dashboardService->getRingkasanPenjualan($startDate, $endDate, $outletId),
            'laporan_stok' => $this->dashboardService->getLaporanStokBahan($outletId),
            'laporan_pengeluaran_restock' => $this->dashboardService->getLaporanPengeluaranRestock($startDate, $endDate, $outletId),
        ];
    }

    /**
     * Mendeteksi preset rentang tanggal aktif berdasarkan start_date dan end_date.
     */
    private function detectDatePreset(string $startDate, string $endDate, string $today): string
    {
        $now = Carbon::parse($today, 'Asia/Jakarta');

        if ($startDate === $today && $endDate === $today) {
            return 'today';
        }

        if ($startDate === $now->copy()->subDays(6)->toDateString() && $endDate === $today) {
            return '7d';
        }

        if ($startDate === $now->copy()->subDays(29)->toDateString() && $endDate === $today) {
            return '30d';
        }

        if ($startDate === $now->copy()->startOfMonth()->toDateString() && $endDate === $today) {
            return 'month';
        }

        return 'custom';
    }

    /**
     * Memformat label tombol filter rentang tanggal agar ringkas dan mudah dibaca.
     */
    private function formatDateRangeLabel(string $startDate, string $endDate, string $preset): string
    {
        return match ($preset) {
            'today' => 'Hari Ini',
            '7d' => '7 Hari Terakhir',
            '30d' => '30 Hari Terakhir',
            'month' => 'Bulan Ini',
            default => $startDate === $endDate
                ? Carbon::parse($startDate)->translatedFormat('d M Y')
                : Carbon::parse($startDate)->translatedFormat('d M') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y'),
        };
    }
}
