<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\ResepMenu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        /**
         * Daftar menu beserta resep komposisinya.
         * Resep mengacu pada nama_bahan sehingga hanya butuh 1 record global per menu.
         * Catatan: ResepMenu mengacu ke id_bahan di outlet tertentu; karena bahan
         * diduplikasi ke tiap outlet, kita pakai bahan dari outlet pertama (id terkecil)
         * sebagai referensi resep. Setiap outlet memiliki stok sendiri-sendiri.
         */
        $menus = [
            [
                'menu' => [
                    'nama_menu' => 'Paket Ayam Geprek Original',
                    'kategori' => 'PAKET',
                    'harga' => 20000,
                    'status_aktif' => true,
                ],
                'resep' => [
                    ['nama_bahan' => 'Ayam Potong',   'jumlah_dibutuhkan' => 1],
                    ['nama_bahan' => 'Beras',          'jumlah_dibutuhkan' => 0.25],
                    ['nama_bahan' => 'Sambal Bawang',  'jumlah_dibutuhkan' => 0.05],
                    ['nama_bahan' => 'Minyak Goreng',  'jumlah_dibutuhkan' => 0.10],
                ],
            ],
            [
                'menu' => [
                    'nama_menu' => 'Paket Ayam Crispy',
                    'kategori' => 'PAKET',
                    'harga' => 18000,
                    'status_aktif' => true,
                ],
                'resep' => [
                    ['nama_bahan' => 'Ayam Potong',   'jumlah_dibutuhkan' => 1],
                    ['nama_bahan' => 'Beras',          'jumlah_dibutuhkan' => 0.25],
                    ['nama_bahan' => 'Minyak Goreng',  'jumlah_dibutuhkan' => 0.15],
                ],
            ],
            [
                'menu' => [
                    'nama_menu' => 'Nasi Putih',
                    'kategori' => 'TAMBAHAN',
                    'harga' => 4000,
                    'status_aktif' => true,
                ],
                'resep' => [
                    ['nama_bahan' => 'Beras', 'jumlah_dibutuhkan' => 0.25],
                ],
            ],
            [
                'menu' => [
                    'nama_menu' => 'Ekstra Sambal',
                    'kategori' => 'TAMBAHAN',
                    'harga' => 2000,
                    'status_aktif' => true,
                ],
                'resep' => [
                    ['nama_bahan' => 'Sambal Korek', 'jumlah_dibutuhkan' => 0.03],
                ],
            ],
        ];

        foreach ($menus as $entry) {
            $menu = Menu::firstOrCreate(
                ['nama_menu' => $entry['menu']['nama_menu']],
                $entry['menu']
            );

            foreach ($entry['resep'] as $item) {
                // Ambil bahan dari outlet pertama (id terkecil) sebagai referensi resep
                $bahan = BahanBaku::where('nama_bahan', $item['nama_bahan'])
                    ->orderBy('id')
                    ->first();

                if (! $bahan) {
                    $this->command->warn("⚠️  Bahan '{$item['nama_bahan']}' tidak ditemukan, resep untuk menu '{$menu->nama_menu}' dilewati.");

                    continue;
                }

                ResepMenu::firstOrCreate(
                    ['id_menu' => $menu->id, 'id_bahan' => $bahan->id],
                    ['jumlah_dibutuhkan' => $item['jumlah_dibutuhkan']]
                );
            }
        }

        $this->command->info('✅  '.count($menus).' menu beserta resep berhasil dibuat.');
    }
}
