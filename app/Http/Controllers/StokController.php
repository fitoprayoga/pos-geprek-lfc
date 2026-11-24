<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
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
        $outletId = $request->query('outlet_id');

        // 1. Ambil daftar stok bahan baku (dengan filter outlet jika ada)
        $bahanBakuQuery = BahanBaku::with('outlet');
        if ($outletId) {
            $bahanBakuQuery->where('outlet_id', $outletId);
        }
        $bahanBakus = $bahanBakuQuery->orderBy('nama_bahan')->get();

        // 2. Ambil daftar suppliers
        $suppliers = Supplier::orderBy('nama_supplier')->get();

        // 3. Ambil riwayat mutasi stok (dengan filter outlet jika ada)
        $stokLogQuery = StokLog::with('bahanBaku.outlet')->latest('tanggal');
        if ($outletId) {
            $stokLogQuery->whereHas('bahanBaku', function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId);
            });
        }
        $mutasiStok = $stokLogQuery->limit(100)->get();

        $data = [
            'filter_outlet_id' => $outletId ? (int) $outletId : null,
            'bahan_baku' => $bahanBakus,
            'suppliers' => $suppliers,
            'mutasi_stok' => $mutasiStok,
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
            'id_supplier' => ['required', 'exists:suppliers,id'],
            'tanggal_restock' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_bahan' => ['required', 'exists:bahan_bakus,id'],
            'items.*.jumlah' => ['required', 'numeric', 'min:0.01'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        $owner = $request->user();

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
        $restockHeader = DB::transaction(function () use ($owner, $request, $totalBiaya, $itemsData) {
            // 1. Simpan nota ke restock_headers
            $header = RestockHeader::create([
                'id_owner' => $owner->id,
                'outlet_id' => $request->outlet_id,
                'id_supplier' => $request->id_supplier,
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
                    StokLog::create([
                        'id_bahan' => $bahanBaku->id,
                        'jenis_aksi' => 'MASUK',
                        'jumlah' => $item['jumlah'],
                        'alasan' => 'Restock Supplier #'.$request->id_supplier.' (Nota #'.$header->id.')',
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
    public function storeStokKeluar(Request $request): JsonResponse
    {
        $request->validate([
            'id_bahan' => ['required', 'exists:bahan_bakus,id'],
            'jumlah' => ['required', 'numeric', 'min:0.01'],
            'alasan' => ['required', 'string', 'max:255'],
        ]);

        $bahanBaku = BahanBaku::findOrFail($request->id_bahan);
        $jumlahKeluar = (float) $request->jumlah;

        // Pastikan stok tidak menjadi minus
        if ($bahanBaku->stok < $jumlahKeluar) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi untuk dikeluarkan. Stok saat ini: '.(float) $bahanBaku->stok,
                'detail' => [
                    'id_bahan' => $bahanBaku->id,
                    'nama_bahan' => $bahanBaku->nama_bahan,
                    'stok_tersedia' => (float) $bahanBaku->stok,
                    'jumlah_keluar' => $jumlahKeluar,
                ],
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $jumlahKeluar) {
                $bahan = BahanBaku::where('id', $request->id_bahan)->lockForUpdate()->first();

                if ($bahan->stok < $jumlahKeluar) {
                    throw new \RuntimeException('Stok tidak mencukupi untuk dikeluarkan.');
                }

                // Otomatis memotong (decrement) kolom stok
                $bahan->decrement('stok', $jumlahKeluar);

                // Mencatat ke tabel stok_logs
                StokLog::create([
                    'id_bahan' => $bahan->id,
                    'jenis_aksi' => 'KELUAR',
                    'jumlah' => $jumlahKeluar,
                    'alasan' => $request->alasan,
                    'tanggal' => now(),
                ]);
            });

            $bahanUpdated = $bahanBaku->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran stok berhasil dicatat dan stok bahan baku telah dikurangi.',
                'data' => [
                    'id_bahan' => $bahanUpdated->id,
                    'nama_bahan' => $bahanUpdated->nama_bahan,
                    'jumlah_keluar' => $jumlahKeluar,
                    'sisa_stok' => (float) $bahanUpdated->stok,
                    'alasan' => $request->alasan,
                ],
            ], 200);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
