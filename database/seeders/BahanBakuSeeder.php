<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Outlet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BahanBakuSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Template bahan baku yang akan diduplikasi ke tiap outlet.
     * stok: stok awal, satuan: satuan ukur, stok_minimal: batas alert kritis.
     */
    private array $templates = [
        [
            'nama_bahan' => 'Ayam Potong',
            'stok' => 150,
            'satuan' => 'potong',
            'stok_minimal' => 20,
        ],
        [
            'nama_bahan' => 'Beras',
            'stok' => 50,
            'satuan' => 'kg',
            'stok_minimal' => 10,
        ],
        [
            'nama_bahan' => 'Sambal Bawang',
            'stok' => 10,
            'satuan' => 'kg',
            'stok_minimal' => 2,
        ],
        [
            'nama_bahan' => 'Sambal Korek',
            'stok' => 10,
            'satuan' => 'kg',
            'stok_minimal' => 2,
        ],
        [
            'nama_bahan' => 'Minyak Goreng',
            'stok' => 30,
            'satuan' => 'liter',
            'stok_minimal' => 5,
        ],
    ];

    public function run(): void
    {
        $outlets = Outlet::orderBy('id')->get();
        $jumlahBuat = 0;

        foreach ($outlets as $outlet) {
            foreach ($this->templates as $bahan) {
                BahanBaku::firstOrCreate(
                    ['outlet_id' => $outlet->id, 'nama_bahan' => $bahan['nama_bahan']],
                    $bahan
                );
                $jumlahBuat++;
            }
        }

        $this->command->info("✅  {$jumlahBuat} bahan baku berhasil dibuat ({$outlets->count()} outlet × ".count($this->templates).' jenis).');
    }
}
