<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with initial POS LFC data.
     *
     * Urutan pemanggilan penting karena ada foreign key dependency:
     *   Outlet → User, BahanBaku, Supplier → Menu → ResepMenu
     */
    public function run(): void
    {
        $this->command->info('🚀  Memulai seeding data awal POS Lana Fried Chicken...');
        $this->command->newLine();

        $this->call([
            OutletSeeder::class,
            SupplierSeeder::class,
            UserSeeder::class,
            BahanBakuSeeder::class,
            MenuSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎉  Seeding selesai! POS LFC siap digunakan.');
        $this->command->table(
            ['Role', 'Username', 'Password'],
            [
                ['OWNER', 'owner',  'password123'],
                ['KASIR', 'kasir1', 'password123 (Outlet: LFC Cabang Rungkut)'],
                ['KASIR', 'kasir2', 'password123 (Outlet: LFC Cabang Sukolilo)'],
                ['KASIR', 'kasir3', 'password123 (Outlet: LFC Cabang Wonokromo)'],
                ['KASIR', 'kasir4', 'password123 (Outlet: LFC Cabang Gubeng)'],
                ['KASIR', 'kasir5', 'password123 (Outlet: LFC Cabang Ketintang)'],
            ]
        );
    }
}
