<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\ResepMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->get();

        $data = [
            'menus' => $menus,
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
            'resep' => ['required', 'array', 'min:1'],
            'resep.*.id_bahan' => ['required', 'integer', 'distinct', 'exists:bahan_bakus,id'],
            'resep.*.jumlah_dibutuhkan' => ['required', 'numeric', 'min:0.01'],
        ]);

        $menu = DB::transaction(function () use ($validated) {
            $menuBaru = Menu::create([
                'nama_menu' => $validated['nama_menu'],
                'kategori' => $validated['kategori'],
                'harga' => $validated['harga'],
                'status_aktif' => true,
            ]);

            foreach ($validated['resep'] as $item) {
                ResepMenu::create([
                    'id_menu' => $menuBaru->id,
                    'id_bahan' => $item['id_bahan'],
                    'jumlah_dibutuhkan' => $item['jumlah_dibutuhkan'],
                ]);
            }

            return $menuBaru;
        });

        $menu->load('resepMenus.bahanBaku');

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
            'resep' => ['sometimes', 'array', 'min:1'],
            'resep.*.id_bahan' => ['required_with:resep', 'integer', 'distinct', 'exists:bahan_bakus,id'],
            'resep.*.jumlah_dibutuhkan' => ['required_with:resep', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($menu, $validated) {
            $menuData = array_intersect_key($validated, array_flip(['nama_menu', 'kategori', 'harga']));

            if (! empty($menuData)) {
                $menu->update($menuData);
            }

            if (array_key_exists('resep', $validated)) {
                $menu->resepMenus()->delete();

                foreach ($validated['resep'] as $item) {
                    ResepMenu::create([
                        'id_menu' => $menu->id,
                        'id_bahan' => $item['id_bahan'],
                        'jumlah_dibutuhkan' => $item['jumlah_dibutuhkan'],
                    ]);
                }
            }
        });

        $menu->refresh()->load('resepMenus.bahanBaku');

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

        return response()->json([
            'success' => true,
            'message' => $statusBaru
                ? 'Menu berhasil diaktifkan kembali.'
                : 'Menu berhasil dinonaktifkan.',
            'data' => $menu->fresh(),
        ], 200);
    }
}
