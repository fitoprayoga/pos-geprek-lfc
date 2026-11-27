<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\ResepMenu;
use App\Models\RestockDetail;
use App\Models\RestockHeader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MenuController extends Controller
{
    /**
     * Mengambil semua data menu beserta relasi resep_menus dan bahan bakunya.
     *
     * GET /menu
     */
    public function index(Request $request): mixed
    {
        $menus = Menu::with('resepMenus.bahanBaku')
            ->orderBy('nama_menu')
            ->get()
            ->map(function (Menu $menu) {
                $menu->setAttribute('foto_url', $this->resolveMenuPhotoUrl($menu->id));

                return $menu;
            });

        $outlets = Outlet::orderBy('id')->get();
        $selectedOutletId = $request->query('outlet_id')
            ? (int) $request->query('outlet_id')
            : ($outlets->first()?->id);

        $latestPricesByBahan = RestockDetail::query()
            ->select('id_bahan', 'harga_satuan')
            ->orderByDesc('id')
            ->get()
            ->unique('id_bahan')
            ->pluck('harga_satuan', 'id_bahan');

        $bahanBakus = BahanBaku::with('outlet')
            ->orderBy('nama_bahan')
            ->get()
            ->map(function (BahanBaku $bahan) use ($latestPricesByBahan) {
                $hargaSatuan = $latestPricesByBahan->get($bahan->id);
                $bahan->setAttribute('harga_satuan_terakhir', $hargaSatuan !== null ? (float) $hargaSatuan : null);

                return $bahan;
            });

        $stokAlerts = $bahanBakus
            ->filter(fn (BahanBaku $bahan) => (float) $bahan->stok <= (float) $bahan->stok_minimal)
            ->values();

        $riwayatRestock = RestockHeader::with(['outlet', 'supplier'])
            ->latest('tanggal_restock')
            ->limit(5)
            ->get();

        $data = [
            'menus' => $menus,
            'outlets' => $outlets,
            'selected_outlet_id' => $selectedOutletId,
            'bahan_bakus' => $bahanBakus,
            'stokAlerts' => $stokAlerts,
            'totalAlert' => $stokAlerts->count(),
            'riwayatRestock' => $riwayatRestock,
        ];

        if ($request->wantsJson() || ! view()->exists('menu.index')) {
            return response()->json([
                'success' => true,
                'message' => 'Data menu berhasil diambil.',
                'data' => $data,
            ], 200);
        }

        return view('menu.index', $data);
    }

    /**
     * Menyimpan menu baru beserta komposisi resep bahan bakunya.
     *
     * POST /menu/store
     */
    public function storeMenu(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_menu' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status_aktif' => ['sometimes', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'resep' => ['required', 'array', 'min:1'],
            'resep.*.id_bahan' => ['required', 'integer', 'distinct', 'exists:bahan_bakus,id'],
            'resep.*.jumlah_dibutuhkan' => ['required', 'numeric', 'min:0.01'],
            'resep.*.satuan' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        $menu = DB::transaction(function () use ($validated) {
            $menuBaru = Menu::create([
                'nama_menu' => $validated['nama_menu'],
                'kategori' => $validated['kategori'],
                'harga' => $validated['harga'],
                'status_aktif' => array_key_exists('status_aktif', $validated)
                    ? (bool) $validated['status_aktif']
                    : true,
            ]);

            foreach ($validated['resep'] as $item) {
                if (! empty($item['satuan'])) {
                    BahanBaku::where('id', $item['id_bahan'])->update([
                        'satuan' => trim($item['satuan']),
                    ]);
                }

                ResepMenu::create([
                    'id_menu' => $menuBaru->id,
                    'id_bahan' => $item['id_bahan'],
                    'jumlah_dibutuhkan' => $item['jumlah_dibutuhkan'],
                ]);
            }

            return $menuBaru;
        });

        if ($request->hasFile('foto')) {
            $this->saveMenuPhoto($menu->id, $request->file('foto'));
        }

        $menu->load('resepMenus.bahanBaku');
        $menu->setAttribute('foto_url', $this->resolveMenuPhotoUrl($menu->id));

        return response()->json([
            'success' => true,
            'message' => 'Menu baru dan komposisi resep berhasil disimpan.',
            'data' => $menu,
        ], 201);
    }

    /**
     * Memperbarui harga, data menu, atau rincian resep_menus yang ada.
     *
     * PUT /menu/{id}/update-harga
     */
    public function updateMenuPrice(Request $request, int|string $id): JsonResponse
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'nama_menu' => ['sometimes', 'required', 'string', 'max:100'],
            'kategori' => ['sometimes', 'required', 'string', 'max:50'],
            'harga' => ['required_without_all:nama_menu,kategori,resep', 'numeric', 'min:0'],
            'status_aktif' => ['sometimes', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'resep' => ['sometimes', 'array', 'min:1'],
            'resep.*.id_bahan' => ['required_with:resep', 'integer', 'distinct', 'exists:bahan_bakus,id'],
            'resep.*.jumlah_dibutuhkan' => ['required_with:resep', 'numeric', 'min:0.01'],
            'resep.*.satuan' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($menu, $validated) {
            $menuData = array_intersect_key($validated, array_flip(['nama_menu', 'kategori', 'harga', 'status_aktif']));

            if (array_key_exists('status_aktif', $menuData)) {
                $menuData['status_aktif'] = (bool) $menuData['status_aktif'];
            }

            if (! empty($menuData)) {
                $menu->update($menuData);
            }

            if (array_key_exists('resep', $validated)) {
                $menu->resepMenus()->delete();

                foreach ($validated['resep'] as $item) {
                    if (! empty($item['satuan'])) {
                        BahanBaku::where('id', $item['id_bahan'])->update([
                            'satuan' => trim($item['satuan']),
                        ]);
                    }

                    ResepMenu::create([
                        'id_menu' => $menu->id,
                        'id_bahan' => $item['id_bahan'],
                        'jumlah_dibutuhkan' => $item['jumlah_dibutuhkan'],
                    ]);
                }
            }
        });

        if ($request->hasFile('foto')) {
            $this->saveMenuPhoto($menu->id, $request->file('foto'));
        }

        $menu->refresh()->load('resepMenus.bahanBaku');
        $menu->setAttribute('foto_url', $this->resolveMenuPhotoUrl($menu->id));

        return response()->json([
            'success' => true,
            'message' => 'Data menu berhasil diperbarui.',
            'data' => $menu,
        ], 200);
    }

    /**
     * Mengubah status_aktif menu (aktif / tidak aktif).
     *
     * PATCH /menu/{id}/status
     */
    public function deactivateMenu(Request $request, int|string $id): JsonResponse
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'status_aktif' => ['sometimes', 'boolean'],
        ]);

        $statusBaru = array_key_exists('status_aktif', $validated)
            ? (bool) $validated['status_aktif']
            : ! $menu->status_aktif;

        $menu->update([
            'status_aktif' => $statusBaru,
        ]);

        $menuUpdated = $menu->fresh();
        $menuUpdated->setAttribute('foto_url', $this->resolveMenuPhotoUrl($menu->id));

        return response()->json([
            'success' => true,
            'message' => $statusBaru
                ? 'Menu berhasil diaktifkan kembali.'
                : 'Menu berhasil dinonaktifkan.',
            'data' => $menuUpdated,
        ], 200);
    }

    /**
     * Menghapus menu beserta komposisi resepnya jika belum pernah dipakai transaksi.
     *
     * DELETE /menu/{id}
     */
    public function destroyMenu(int|string $id): JsonResponse
    {
        $menu = Menu::findOrFail($id);

        if ($menu->detailTransaksis()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Menu ini sudah memiliki riwayat transaksi sehingga tidak dapat dihapus permanen. Gunakan toggle Nonaktif untuk menyembunyikannya dari POS.',
            ], 422);
        }

        $namaMenu = $menu->nama_menu;
        $menuId = $menu->id;

        DB::transaction(function () use ($menu) {
            $menu->resepMenus()->delete();
            $menu->delete();
        });

        $this->deleteMenuPhotos($menuId);

        return response()->json([
            'success' => true,
            'message' => "Menu \"{$namaMenu}\" berhasil dihapus.",
            'data' => [
                'id' => $menuId,
            ],
        ], 200);
    }

    /**
     * Menambahkan / menyinkronkan master bahan baku baru dari halaman Kelola Menu
     * ke tabel bahan_bakus (disinkronkan ke seluruh outlet agar POS di semua cabang tetap konsisten).
     *
     * POST /menu/bahan-baku
     */
    public function storeBahanBaku(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'nama_bahan' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:30'],
            'stok' => ['nullable', 'numeric', 'min:0'],
            'stok_minimal' => ['nullable', 'numeric', 'min:0'],
            'sync_all_outlets' => ['sometimes', 'boolean'],
        ]);

        $namaBahan = trim($validated['nama_bahan']);
        $satuan = trim($validated['satuan']);
        $stokAwal = isset($validated['stok']) ? (float) $validated['stok'] : 0;
        $stokMinimal = isset($validated['stok_minimal']) ? (float) $validated['stok_minimal'] : 5;
        $syncAll = array_key_exists('sync_all_outlets', $validated) ? (bool) $validated['sync_all_outlets'] : true;

        $outlets = $syncAll
            ? Outlet::orderBy('id')->get()
            : Outlet::where('id', $validated['outlet_id'] ?? Outlet::orderBy('id')->value('id'))->get();

        $createdItems = DB::transaction(function () use ($outlets, $namaBahan, $satuan, $stokAwal, $stokMinimal, $validated) {
            $items = collect();

            foreach ($outlets as $outlet) {
                $existing = BahanBaku::where('outlet_id', $outlet->id)
                    ->whereRaw('LOWER(nama_bahan) = ?', [mb_strtolower($namaBahan)])
                    ->first();

                if ($existing) {
                    $items->push($existing->load('outlet'));
                    continue;
                }

                $isPrimaryOutlet = ! isset($validated['outlet_id']) || (int) $validated['outlet_id'] === (int) $outlet->id;

                $created = BahanBaku::create([
                    'outlet_id' => $outlet->id,
                    'nama_bahan' => $namaBahan,
                    'satuan' => $satuan,
                    'stok' => $isPrimaryOutlet ? $stokAwal : 0,
                    'stok_minimal' => $stokMinimal,
                ]);

                $items->push($created->load('outlet'));
            }

            return $items;
        });

        $selectedOutletId = $validated['outlet_id'] ?? $outlets->first()?->id;
        $primaryItem = $createdItems->firstWhere('outlet_id', (int) $selectedOutletId) ?? $createdItems->first();

        return response()->json([
            'success' => true,
            'message' => "Bahan baku \"{$namaBahan}\" ({$satuan}) siap digunakan di resep.",
            'data' => [
                'bahan' => $primaryItem,
                'all_outlets' => $createdItems->values(),
            ],
        ], 201);
    }

    /**
     * Mengembalikan URL foto menu jika file ada di public/images/menu-{id}.{jpg|png}.
     */
    private function resolveMenuPhotoUrl(int $menuId): ?string
    {
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $relative = "images/menu-{$menuId}.{$ext}";
            if (file_exists(public_path($relative))) {
                return asset($relative).'?v='.filemtime(public_path($relative));
            }
        }

        return null;
    }

    /**
     * Menyimpan file foto menu ke public/images/menu-{id}.{ext}.
     */
    private function saveMenuPhoto(int $menuId, UploadedFile $file): void
    {
        $directory = public_path('images');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $this->deleteMenuPhotos($menuId);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png'], true)) {
            $ext = 'jpg';
        }

        $file->move($directory, "menu-{$menuId}.{$ext}");
    }

    /**
     * Menghapus file foto menu yang tersimpan di public/images.
     */
    private function deleteMenuPhotos(int $menuId): void
    {
        $directory = public_path('images');
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            $path = $directory.DIRECTORY_SEPARATOR."menu-{$menuId}.{$ext}";
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
