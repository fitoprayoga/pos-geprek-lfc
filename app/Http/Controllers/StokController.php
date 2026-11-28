<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Outlet;
use App\Models\RestockDetail;
use App\Models\RestockHeader;
use App\Models\StokLog;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokController extends Controller
{
    /**
     * Menampilkan daftar stok bahan baku, supplier, dan riwayat mutasi stok.
     * Dapat difilter berdasarkan outlet_id.
     *
     * GET /stok
     */
    public function index(Request $request): mixed
    {
        $outlets = Outlet::orderBy('id')->get();
        $requestedOutletId = $request->query('outlet_id');
        $outletAktif = $requestedOutletId
            ? ($outlets->firstWhere('id', (int) $requestedOutletId) ?? $outlets->first())
            : $outlets->first();
        $activeOutletId = $outletAktif?->id;

        // 1. Ambil daftar stok bahan baku untuk outlet aktif
        $bahanBakuQuery = BahanBaku::with('outlet');
        if ($activeOutletId) {
            $bahanBakuQuery->where('outlet_id', $activeOutletId);
        }
        $bahanBakus = $bahanBakuQuery->orderBy('nama_bahan')->get();

        // 2. Ambil daftar suppliers
        $suppliers = Supplier::orderBy('nama_supplier')->get();

        // 3. Ambil riwayat mutasi stok untuk outlet aktif
        $stokLogQuery = StokLog::with('bahanBaku.outlet')->latest('tanggal');
        if ($activeOutletId) {
            $stokLogQuery->whereHas('bahanBaku', function ($query) use ($activeOutletId) {
                $query->where('outlet_id', $activeOutletId);
            });
        }
        $mutasiStok = $stokLogQuery->limit(100)->get();

        // 4. Data Tab Stok Keluar untuk outlet aktif
        $stokKeluarBaseQuery = StokLog::with('bahanBaku')->keluar();
        if ($activeOutletId) {
            $stokKeluarBaseQuery->whereHas('bahanBaku', function ($query) use ($activeOutletId) {
                $query->where('outlet_id', $activeOutletId);
            });
        }

        $stokKeluarLogs = (clone $stokKeluarBaseQuery)
            ->latest('tanggal')
            ->latest('id')
            ->limit(200)
            ->get();

        $awalBulan = now()->startOfMonth();
        $akhirBulan = now()->endOfMonth();

        $stokKeluarBulanIni = (clone $stokKeluarBaseQuery)
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->get(['id', 'id_bahan', 'jumlah', 'tanggal']);

        $jumlahStokKeluarBulanIni = $stokKeluarBulanIni->count();

        // Hitung estimasi kerugian bulan ini berdasarkan harga_satuan terakhir di restock_details
        $estimasiKerugian = 0.0;
        $bahanIdsKeluar = $stokKeluarBulanIni->pluck('id_bahan')->unique()->values();
        if ($bahanIdsKeluar->isNotEmpty()) {
            $latestPrices = [];
            $restockDetails = RestockDetail::whereIn('id_bahan', $bahanIdsKeluar)
                ->orderByDesc('id')
                ->get(['id_bahan', 'harga_satuan']);

            foreach ($restockDetails as $detail) {
                if (! array_key_exists($detail->id_bahan, $latestPrices)) {
                    $latestPrices[$detail->id_bahan] = (float) $detail->harga_satuan;
                }
            }

            foreach ($stokKeluarBulanIni as $log) {
                $hargaTerakhir = $latestPrices[$log->id_bahan] ?? 0.0;
                $estimasiKerugian += (float) $log->jumlah * $hargaTerakhir;
            }
        }

        $totalBahan = $bahanBakus->count();
        $perluRestock = $bahanBakus->filter(fn ($b) => (float) $b->stok <= (float) $b->stok_minimal)->count();

        $data = [
            'filter_outlet_id' => $activeOutletId,
            'outlets' => $outlets,
            'outletAktif' => $outletAktif,
            'bahan_baku' => $bahanBakus,
            'bahanBakus' => $bahanBakus,
            'suppliers' => $suppliers,
            'mutasi_stok' => $mutasiStok,
            'stokKeluarLogs' => $stokKeluarLogs,
            'jumlahStokKeluarBulanIni' => $jumlahStokKeluarBulanIni,
            'estimasiKerugian' => $estimasiKerugian,
            'totalBahan' => $totalBahan,
            'perluRestock' => $perluRestock,
        ];

        if ($request->wantsJson() || ! view()->exists('stok.index')) {
            return response()->json([
                'success' => true,
                'message' => 'Data stok berhasil diambil.',
                'data' => $data,
            ], 200);
        }

        return view('stok.index', $data);
    }

    /**
     * Menyimpan nota restock bahan baku dari supplier ke outlet.
     *
     * POST /stok/restock
     */
    public function storeRestock(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'id_supplier' => ['nullable', 'exists:suppliers,id'],
            'tanggal_restock' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_bahan' => ['required', 'exists:bahan_bakus,id'],
            'items.*.jumlah' => ['required', 'numeric', 'min:0.01'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        $supplierId = $request->id_supplier ?: Supplier::orderBy('id')->value('id');
        if (! $supplierId) {
            return response()->json([
                'success' => false,
                'message' => 'Data supplier belum tersedia. Tambahkan supplier terlebih dahulu.',
            ], 422);
        }

        $owner = $request->user();
        $catatan = trim((string) $request->input('catatan', ''));

        // Validasi bahwa seluruh bahan baku yang direstok benar milik outlet terkait
        foreach ($request->items as $item) {
            $bahan = BahanBaku::where('id', $item['id_bahan'])
                ->where('outlet_id', $request->outlet_id)
                ->first();

            if (! $bahan) {
                return response()->json([
                    'success' => false,
                    'message' => "Bahan baku ID {$item['id_bahan']} tidak terdaftar di outlet yang dipilih.",
                ], 422);
            }
        }

        // Hitung total biaya dan siapkan rincian nota
        $totalBiaya = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $subtotal = $item['jumlah'] * $item['harga_satuan'];
            $totalBiaya += $subtotal;
            $itemsData[] = [
                'id_bahan' => $item['id_bahan'],
                'jumlah' => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $subtotal,
            ];
        }

        // DB::transaction untuk integritas data
        $restockHeader = DB::transaction(function () use ($owner, $request, $supplierId, $catatan, $totalBiaya, $itemsData) {
            // 1. Simpan nota ke restock_headers
            $header = RestockHeader::create([
                'id_owner' => $owner->id,
                'outlet_id' => $request->outlet_id,
                'id_supplier' => $supplierId,
                'tanggal_restock' => $request->tanggal_restock ?? now()->toDateString(),
                'total_biaya' => $totalBiaya,
            ]);

            // 2. Simpan rincian ke restock_details dan tambahkan stok di bahan_bakus
            foreach ($itemsData as $item) {
                RestockDetail::create([
                    'id_restock' => $header->id,
                    'id_bahan' => $item['id_bahan'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Tambahkan (increment) stok bahan baku outlet terkait
                $bahanBaku = BahanBaku::where('id', $item['id_bahan'])
                    ->where('outlet_id', $request->outlet_id)
                    ->lockForUpdate()
                    ->first();

                if ($bahanBaku) {
                    $bahanBaku->increment('stok', $item['jumlah']);

                    // Catat riwayat mutasi stok MASUK
                    $alasan = 'Restock Supplier #'.$supplierId.' (Nota #'.$header->id.')';
                    if ($catatan !== '') {
                        $alasan .= ' — '.$catatan;
                    }

                    StokLog::create([
                        'id_bahan' => $bahanBaku->id,
                        'jenis_aksi' => 'MASUK',
                        'jumlah' => $item['jumlah'],
                        'alasan' => $alasan,
                        'tanggal' => now(),
                    ]);
                }
            }

            return $header;
        });

        $restockHeader->load(['supplier', 'outlet', 'restockDetails.bahanBaku']);

        return response()->json([
            'success' => true,
            'message' => 'Restock bahan baku berhasil disimpan dan stok outlet telah diperbarui.',
            'data' => $restockHeader,
        ], 201);
    }

    /**
     * Mencatat pemotongan stok keluar (misal: rusak/kadaluarsa).
     *
     * POST /stok/keluar
     */
    public function storeStokKeluar(Request $request): mixed
    {
        $request->validate([
            'id_bahan' => ['required', 'exists:bahan_bakus,id'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'alasan' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $bahanBaku = BahanBaku::findOrFail($request->id_bahan);
        $jumlahKeluar = (float) $request->jumlah;
        $alasanUtama = trim((string) $request->input('alasan'));
        $catatan = trim((string) $request->input('catatan', ''));
        $alasanLengkap = mb_substr(
            $catatan !== '' ? $alasanUtama.' — '.$catatan : $alasanUtama,
            0,
            255
        );

        // Pastikan stok tidak menjadi minus
        if ((float) $bahanBaku->stok < $jumlahKeluar) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi untuk dikeluarkan. Stok saat ini: '.(float) $bahanBaku->stok.' '.$bahanBaku->satuan,
                'detail' => [
                    'id_bahan' => $bahanBaku->id,
                    'nama_bahan' => $bahanBaku->nama_bahan,
                    'stok_tersedia' => (float) $bahanBaku->stok,
                    'jumlah_keluar' => $jumlahKeluar,
                ],
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $jumlahKeluar, $alasanLengkap) {
                $bahan = BahanBaku::where('id', $request->id_bahan)->lockForUpdate()->first();

                if ((float) $bahan->stok < $jumlahKeluar) {
                    throw new \RuntimeException('Stok tidak mencukupi untuk dikeluarkan.');
                }

                // Otomatis memotong (decrement) kolom stok
                $bahan->decrement('stok', $jumlahKeluar);

                // Mencatat ke tabel stok_logs
                StokLog::create([
                    'id_bahan' => $bahan->id,
                    'jenis_aksi' => 'KELUAR',
                    'jumlah' => $jumlahKeluar,
                    'alasan' => $alasanLengkap,
                    'tanggal' => now(),
                ]);
            });

            $bahanUpdated = $bahanBaku->fresh();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengeluaran stok berhasil dicatat dan stok bahan baku telah dikurangi.',
                    'data' => [
                        'id_bahan' => $bahanUpdated->id,
                        'nama_bahan' => $bahanUpdated->nama_bahan,
                        'jumlah_keluar' => $jumlahKeluar,
                        'sisa_stok' => (float) $bahanUpdated->stok,
                        'alasan' => $alasanLengkap,
                    ],
                ], 200);
            }

            return redirect()
                ->route('stok.index', ['outlet_id' => $bahanUpdated->outlet_id, 'tab' => 'keluar'])
                ->with('success', 'Stok Keluar Berhasil Dicatat');
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
