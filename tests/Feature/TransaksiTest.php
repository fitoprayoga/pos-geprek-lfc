<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\ResepMenu;
use App\Models\SesiOutlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransaksiTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $kasir;

    private User $owner;

    private Menu $menuAyamGeprek;

    private BahanBaku $bahanAyam;

    private BahanBaku $bahanCabai;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat Outlet
        $this->outlet = Outlet::create([
            'nama_outlet' => 'LFC Cabang Merdeka',
            'alamat' => 'Jl. Merdeka No. 10',
        ]);

        // 2. Buat Kasir & Owner
        $this->kasir = User::create([
            'username' => 'kasir_lfc',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Merdeka',
            'role' => 'KASIR',
            'status_aktif' => true,
            'outlet_id' => $this->outlet->id,
        ]);

        $this->owner = User::create([
            'username' => 'owner_lfc',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Owner LFC',
            'role' => 'OWNER',
            'status_aktif' => true,
        ]);

        // 3. Buat Bahan Baku di Outlet
        $this->bahanAyam = BahanBaku::create([
            'outlet_id' => $this->outlet->id,
            'nama_bahan' => 'Ayam Potong',
            'stok' => 10.0,
            'satuan' => 'potong',
            'stok_minimal' => 2.0,
        ]);

        $this->bahanCabai = BahanBaku::create([
            'outlet_id' => $this->outlet->id,
            'nama_bahan' => 'Cabai Rawit',
            'stok' => 50.0,
            'satuan' => 'gram',
            'stok_minimal' => 10.0,
        ]);

        // 4. Buat Menu & Resep
        $this->menuAyamGeprek = Menu::create([
            'nama_menu' => 'Ayam Geprek Sambal Korek',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        // Resep 1 porsi butuh 1 potong ayam dan 10 gram cabai
        ResepMenu::create([
            'id_menu' => $this->menuAyamGeprek->id,
            'id_bahan' => $this->bahanAyam->id,
            'jumlah_dibutuhkan' => 1.0,
        ]);

        ResepMenu::create([
            'id_menu' => $this->menuAyamGeprek->id,
            'id_bahan' => $this->bahanCabai->id,
            'jumlah_dibutuhkan' => 10.0,
        ]);
    }

    public function test_check_menu_stock_success_when_stock_is_sufficient(): void
    {
        // Butuh 3 potong ayam dan 30g cabai, stok ada 10 ayam dan 50g cabai -> cukup
        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/cek-stok', [
                'id_menu' => $this->menuAyamGeprek->id,
                'jumlah' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_menu' => $this->menuAyamGeprek->id,
                    'jumlah' => 3,
                    'status' => 'TERSEDIA',
                ],
            ]);
    }

    public function test_check_menu_stock_fails_when_stock_is_insufficient(): void
    {
        // Butuh 15 potong ayam, stok hanya 10 -> habis
        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/cek-stok', [
                'id_menu' => $this->menuAyamGeprek->id,
                'jumlah' => 15,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Stok Habis',
            ]);
    }

    public function test_store_transaksi_fails_if_outlet_session_not_open(): void
    {
        // Belum ada sesi BUKA
        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 2,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'nominal_bayar' => 50000,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_store_transaksi_success_with_cash_deducts_stock_and_creates_records(): void
    {
        // 1. Buka sesi outlet
        SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        // 2. Eksekusi bayar tunai (2 porsi x 15.000 = 30.000, bayar 50.000, kembalian 20.000)
        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 2,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'nominal_bayar' => 50000,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'struk' => [
                        'total_harga' => 30000,
                        'metode_pembayaran' => 'TUNAI',
                        'nominal_bayar' => 50000,
                        'kembalian' => 20000,
                        'status_pembayaran' => 'SUKSES',
                        'outlet' => [
                            'nama_outlet' => 'LFC Cabang Merdeka',
                        ],
                        'kasir' => [
                            'nama_lengkap' => 'Kasir Merdeka',
                        ],
                        'items' => [
                            [
                                'id_menu' => $this->menuAyamGeprek->id,
                                'nama_menu' => 'Ayam Geprek Sambal Korek',
                                'jumlah' => 2,
                                'harga_satuan' => 15000,
                                'subtotal' => 30000,
                            ],
                        ],
                    ],
                ],
            ]);

        // 3. Verifikasi transaksi dan detail transaksi di database
        $this->assertDatabaseHas('transaksis', [
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'total_harga' => 30000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 50000,
            'kembalian' => 20000,
            'status_pembayaran' => true,
        ]);

        $this->assertDatabaseHas('detail_transaksis', [
            'id_menu' => $this->menuAyamGeprek->id,
            'jumlah' => 2,
            'harga_satuan' => 15000,
            'subtotal' => 30000,
        ]);

        // 4. Verifikasi pengurangan stok bahan baku
        // Ayam: 10 - (2 x 1) = 8
        $this->assertEquals(8.0, (float) $this->bahanAyam->fresh()->stok);

        // Cabai: 50 - (2 x 10) = 30
        $this->assertEquals(30.0, (float) $this->bahanCabai->fresh()->stok);

        // 5. Verifikasi stok_logs
        $this->assertDatabaseHas('stok_logs', [
            'id_bahan' => $this->bahanAyam->id,
            'jenis_aksi' => 'KELUAR',
            'jumlah' => 2.0,
        ]);
        $this->assertDatabaseHas('stok_logs', [
            'id_bahan' => $this->bahanCabai->id,
            'jenis_aksi' => 'KELUAR',
            'jumlah' => 20.0,
        ]);
    }

    public function test_store_transaksi_success_with_qris(): void
    {
        // Buka sesi
        SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 1,
                    ],
                ],
                'metode_pembayaran' => 'QRIS',
                'nominal_bayar' => 15000,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'struk' => [
                        'total_harga' => 15000,
                        'metode_pembayaran' => 'QRIS',
                        'nominal_bayar' => 15000,
                        'kembalian' => 0,
                    ],
                ],
            ]);
    }

    public function test_store_transaksi_fails_when_cash_payment_is_insufficient(): void
    {
        SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 2,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'nominal_bayar' => 20000, // Total adalah 30.000
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_store_transaksi_rolls_back_if_stock_runs_out(): void
    {
        SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        // Pesan 20 porsi sedangkan ayam hanya ada 10
        $response = $this->actingAs($this->kasir)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 20,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'nominal_bayar' => 300000,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Stok Habis',
            ]);

        // Tidak ada transaksi yang tersimpan dan stok tidak berkurang
        $this->assertDatabaseCount('transaksis', 0);
        $this->assertEquals(10.0, (float) $this->bahanAyam->fresh()->stok);
    }

    public function test_owner_cannot_access_transaksi_endpoints(): void
    {
        $responseCek = $this->actingAs($this->owner)
            ->postJson('/transaksi/cek-stok', [
                'id_menu' => $this->menuAyamGeprek->id,
                'jumlah' => 1,
            ]);

        $responseCek->assertStatus(403);

        $responseBayar = $this->actingAs($this->owner)
            ->postJson('/transaksi/bayar', [
                'items' => [
                    [
                        'id_menu' => $this->menuAyamGeprek->id,
                        'jumlah' => 1,
                    ],
                ],
                'metode_pembayaran' => 'TUNAI',
                'nominal_bayar' => 15000,
            ]);

        $responseBayar->assertStatus(403);
    }
}
