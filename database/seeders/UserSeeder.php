<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Owner tunggal (tidak terikat outlet)
        User::firstOrCreate(
            ['username' => 'owner'],
            [
                'password' => Hash::make('password123'),
                'nama_lengkap' => 'Owner Lana Fried Chicken',
                'role' => 'OWNER',
                'status_aktif' => true,
                'outlet_id' => null,
            ]
        );

        // 5 Kasir, masing-masing terhubung ke 1 outlet
        $outlets = Outlet::orderBy('id')->pluck('id');

        foreach (range(1, 5) as $i) {
            User::firstOrCreate(
                ['username' => "kasir{$i}"],
                [
                    'password' => Hash::make('password123'),
                    'nama_lengkap' => "Kasir LFC {$i}",
                    'role' => 'KASIR',
                    'status_aktif' => true,
                    'outlet_id' => $outlets[$i - 1],
                ]
            );
        }

        $this->command->info('✅  1 Owner dan 5 Kasir berhasil dibuat.');
    }
}
