<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\DetailTransaksi;
use App\Models\Menu;
use App\Models\ResepMenu;
use App\Models\SesiOutlet;
use App\Models\StokLog;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    /**
     * Menampilkan halaman utama POS Kasir (Home Menu).
     *
     * GET /pos
     */
    public function index(Request $request): View
    {
        $kasir = $request->user();
        $outlet = $kasir->outlet;

        $sesiAktif = $kasir->outlet_id
            ? SesiOutlet::where('outlet_id', $kasir->outlet_id)
                ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
                ->where('status', 'BUKA')
                ->latest()
                ->first()
            : null;

        $menus = Menu::aktif()
            ->with('resepMenus.bahanBaku')
            ->orderBy('kategori')
            ->orderBy('nama_menu')
            ->get();

        $bahanOutlet = $kasir->outlet_id
            ? BahanBaku::where('outlet_id', $kasir->outlet_id)->get()
            : collect();

        $bahanById = $bahanOutlet->keyBy('id');
        $bahanByNama = $bahanOutlet->keyBy('nama_bahan');

        foreach ($menus as $menu) {
            $stokHabis = false;

            if ($kasir->outlet_id) {
                foreach ($menu->resepMenus as $resep) {
                    $bahan = $bahanById->get($resep->id_bahan)
                        ?? ($resep->bahanBaku ? $bahanByNama->get($resep->bahanBaku->nama_bahan) : null);

                    if (! $bahan || (float) $bahan->stok < (float) $resep->jumlah_dibutuhkan) {
                        $stokHabis = true;
                        break;
                    }
                }
            }

            $menu->stok_habis = $stokHabis;
        }

        $kategoris = $menus->pluck('kategori')->filter()->unique()->values();

        return view('pos.index', compact('outlet', 'sesiAktif', 'menus', 'kategoris'));
    }

    /**
     * Memeriksa ketersediaan stok bahan baku untuk menu yang dipilih.
     *
     * POST /transaksi/cek-stok
     */
    public function checkMenuStock(Request $request): JsonResponse
    {
        $request->validate([
            'id_menu' => ['required', 'exists:menus,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $kasir = $request->user();

        if (! $kasir->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum terhubung dengan outlet manapun.',
            ], 422);
        }

        $idMenu = $request->id_menu;
        $jumlah = (int) $request->jumlah;

        // Ambil resep komposisi bahan untuk menu tersebut
        $resepMenus = ResepMenu::where('id_menu', $idMenu)->with('bahanBaku')->get();

        foreach ($resepMenus as $resep) {
            $totalDibutuhkan = $resep->jumlah_dibutuhkan * $jumlah;

            // Cari bahan baku terkait pada outlet kasir
            $bahanBaku = BahanBaku::where('outlet_id', $kasir->outlet_id)
                ->where(function ($query) use ($resep) {
                    $query->where('id', $resep->id_bahan);
                    if ($resep->bahanBaku) {
                        $query->orWhere('nama_bahan', $resep->bahanBaku->nama_bahan);
                    }
                })
                ->first();

            // Jika bahan baku tidak ditemukan di outlet atau stok tidak mencukupi
            if (! $bahanBaku || $bahanBaku->stok < $totalDibutuhkan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok Habis',
                    'detail' => [
                        'nama_bahan' => $bahanBaku?->nama_bahan ?? $resep->bahanBaku?->nama_bahan ?? 'Bahan Baku',
                        'dibutuhkan' => (float) $totalDibutuhkan,
                        'tersedia' => (float) ($bahanBaku?->stok ?? 0),
                    ],
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Stok mencukupi.',
            'data' => [
                'id_menu' => (int) $idMenu,
                'jumlah' => $jumlah,
                'status' => 'TERSEDIA',
            ],
        ], 200);
    }

    /**
     * Memproses pembayaran transaksi dan memotong stok bahan baku.
     *
     * POST /transaksi/bayar
     */
    public function storeTransaksi(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_menu' => ['required', 'exists:menus,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'metode_pembayaran' => ['required', 'in:TUNAI,QRIS'],
            'nominal_bayar' => ['required', 'numeric', 'min:0'],
        ]);

        $kasir = $request->user();

        if (! $kasir->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kasir belum terhubung dengan outlet manapun.',
            ], 422);
        }

        // 1. Validasi sesi_outlets milik kasir sedang berstatus BUKA
        $sesiAktif = SesiOutlet::where('outlet_id', $kasir->outlet_id)
            ->where('status', 'BUKA')
            ->latest()
            ->first();

        if (! $sesiAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi outlet belum dibuka. Silakan buka outlet terlebih dahulu sebelum bertransaksi.',
            ], 422);
        }

        // Hitung total harga transaksi dan siapkan data item pesanan
        $itemsData = [];
        $totalHarga = 0;

        foreach ($request->items as $item) {
            $menu = Menu::findOrFail($item['id_menu']);
            $subtotal = $menu->harga * $item['jumlah'];
            $totalHarga += $subtotal;

            $itemsData[] = [
                'menu' => $menu,
                'jumlah' => (int) $item['jumlah'],
                'harga_satuan' => (float) $menu->harga,
                'subtotal' => (float) $subtotal,
            ];
        }

        $nominalBayar = (float) $request->nominal_bayar;
        $metode = strtoupper($request->metode_pembayaran);

        // Validasi nominal bayar jika tunai
        if ($metode === 'TUNAI' && $nominalBayar < $totalHarga) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal bayar kurang dari total harga belanja.',
                'total_harga' => (float) $totalHarga,
                'nominal_bayar' => $nominalBayar,
            ], 422);
        }

        $kembalian = max(0, $nominalBayar - $totalHarga);
        $waktuTransaksi = now('Asia/Jakarta');

        try {
            // 2. Gunakan DB::transaction
            $transaksi = DB::transaction(function () use ($kasir, $itemsData, $totalHarga, $metode, $nominalBayar, $kembalian, $waktuTransaksi) {
                // Verifikasi ketersediaan stok seluruh item sebelum memotong
                foreach ($itemsData as $item) {
                    $resepMenus = ResepMenu::where('id_menu', $item['menu']->id)->with('bahanBaku')->get();

                    foreach ($resepMenus as $resep) {
                        $totalDibutuhkan = $resep->jumlah_dibutuhkan * $item['jumlah'];

                        $bahanBaku = BahanBaku::where('outlet_id', $kasir->outlet_id)
                            ->where(function ($query) use ($resep) {
                                $query->where('id', $resep->id_bahan);
                                if ($resep->bahanBaku) {
                                    $query->orWhere('nama_bahan', $resep->bahanBaku->nama_bahan);
                                }
                            })
                            ->lockForUpdate()
                            ->first();

                        if (! $bahanBaku || $bahanBaku->stok < $totalDibutuhkan) {
                            $namaBahan = $bahanBaku?->nama_bahan ?? $resep->bahanBaku?->nama_bahan ?? 'Bahan Baku';
                            throw new \RuntimeException('Stok Habis: '.$namaBahan);
                        }
                    }
                }

                // 3. Simpan transaksi ke tabel transaksis
                $kodeTransaksi = 'TRX-'.$waktuTransaksi->format('YmdHis').'-'.strtoupper(Str::random(4));

                $transaksi = Transaksi::create([
                    'id_kasir' => $kasir->id,
                    'outlet_id' => $kasir->outlet_id,
                    'kode_transaksi' => $kodeTransaksi,
                    'tanggal_waktu' => $waktuTransaksi,
                    'total_harga' => $totalHarga,
                    'metode_pembayaran' => $metode,
                    'nominal_bayar' => $nominalBayar,
                    'kembalian' => $kembalian,
                    'status_pembayaran' => true,
                ]);

                // 4. Simpan item pesanan ke detail_transaksis dan potong stok bahan baku
                foreach ($itemsData as $item) {
                    DetailTransaksi::create([
                        'id_transaksi' => $transaksi->id,
                        'id_menu' => $item['menu']->id,
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $item['harga_satuan'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Otomatis potong stok pada tabel bahan_bakus
                    $resepMenus = ResepMenu::where('id_menu', $item['menu']->id)->with('bahanBaku')->get();

                    foreach ($resepMenus as $resep) {
                        $pemakaian = $resep->jumlah_dibutuhkan * $item['jumlah'];

                        $bahanBaku = BahanBaku::where('outlet_id', $kasir->outlet_id)
                            ->where(function ($query) use ($resep) {
                                $query->where('id', $resep->id_bahan);
                                if ($resep->bahanBaku) {
                                    $query->orWhere('nama_bahan', $resep->bahanBaku->nama_bahan);
                                }
                            })
                            ->first();

                        if ($bahanBaku) {
                            $bahanBaku->decrement('stok', $pemakaian);

                            // Catat log perubahan stok
                            StokLog::create([
                                'id_bahan' => $bahanBaku->id,
                                'jenis_aksi' => 'KELUAR',
                                'jumlah' => $pemakaian,
                                'alasan' => 'Pemakaian transaksi '.$transaksi->kode_transaksi,
                                'tanggal' => $waktuTransaksi,
                            ]);
                        }
                    }
                }

                return $transaksi;
            });

            // Load data relasi untuk kebutuhan cetak struk
            $transaksi->load([
                'outlet',
                'kasir',
                'detailTransaksis.menu',
            ]);

            // 5. Kembalikan response JSON berisi detail transaksi lengkap untuk cetak struk
            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses.',
                'data' => [
                    'struk' => [
                        'kode_transaksi' => $transaksi->kode_transaksi,
                        'tanggal_waktu' => $waktuTransaksi->copy()->locale('id')->translatedFormat('d M Y, H:i'),
                        'outlet' => [
                            'id' => $transaksi->outlet->id,
                            'nama_outlet' => $transaksi->outlet->nama_outlet,
                            'alamat' => $transaksi->outlet->alamat,
                        ],
                        'kasir' => [
                            'id' => $transaksi->kasir->id,
                            'nama_lengkap' => $transaksi->kasir->nama_lengkap,
                            'username' => $transaksi->kasir->username,
                        ],
                        'items' => $transaksi->detailTransaksis->map(function ($detail) {
                            return [
                                'id_menu' => $detail->id_menu,
                                'nama_menu' => $detail->menu->nama_menu,
                                'jumlah' => $detail->jumlah,
                                'harga_satuan' => (float) $detail->harga_satuan,
                                'subtotal' => (float) $detail->subtotal,
                            ];
                        }),
                        'total_harga' => (float) $transaksi->total_harga,
                        'metode_pembayaran' => $transaksi->metode_pembayaran,
                        'nominal_bayar' => (float) $transaksi->nominal_bayar,
                        'kembalian' => (float) $transaksi->kembalian,
                        'status_pembayaran' => 'SUKSES',
                    ],
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stok Habis',
                'detail' => $e->getMessage(),
            ], 422);
        }
    }
}
