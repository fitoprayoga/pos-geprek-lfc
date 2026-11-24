<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $suppliers = [
            [
                'nama_supplier' => 'PT Maju Bersama Poultry',
                'no_telepon' => '031-77001122',
                'alamat' => 'Jl. Pasar Hewan No. 5, Sidoarjo',
            ],
            [
                'nama_supplier' => 'CV Sumber Bahan Dapur',
                'no_telepon' => '031-55443322',
                'alamat' => 'Jl. Pasar Atum No. 3, Surabaya',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['nama_supplier' => $supplier['nama_supplier']], $supplier);
        }

        $this->command->info('✅  2 Supplier berhasil dibuat.');
    }
}
