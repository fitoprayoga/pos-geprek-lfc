<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $outlets = [
            ['nama_outlet' => 'LFC Cabang Rungkut',    'alamat' => 'Jl. Rungkut Madya No. 12, Surabaya'],
            ['nama_outlet' => 'LFC Cabang Sukolilo',   'alamat' => 'Jl. Semolowaru Utara No. 5, Surabaya'],
            ['nama_outlet' => 'LFC Cabang Wonokromo',  'alamat' => 'Jl. Wonokromo No. 88, Surabaya'],
            ['nama_outlet' => 'LFC Cabang Gubeng',     'alamat' => 'Jl. Gubeng Jaya No. 23, Surabaya'],
            ['nama_outlet' => 'LFC Cabang Ketintang',  'alamat' => 'Jl. Ketintang Baru No. 7, Surabaya'],
        ];

        foreach ($outlets as $outlet) {
            Outlet::firstOrCreate(['nama_outlet' => $outlet['nama_outlet']], $outlet);
        }

        $this->command->info('✅  5 outlet LFC berhasil dibuat.');
    }
}
