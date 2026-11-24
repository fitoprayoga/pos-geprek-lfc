<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\SesiOutlet;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SesiOutletTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $kasir;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'nama_outlet' => 'LFC Cabang Merdeka',
            'alamat' => 'Jl. Merdeka No. 10',
        ]);

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
    }

    public function test_kasir_can_open_outlet(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson('/outlet/buka');

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 'BUKA',
                'data' => [
                    'outlet_id' => $this->outlet->id,
                    'id_kasir' => $this->kasir->id,
                    'status' => 'BUKA',
                ],
            ]);

        $this->assertDatabaseHas('sesi_outlets', [
            'outlet_id' => $this->outlet->id,
            'id_kasir' => $this->kasir->id,
            'status' => 'BUKA',
        ]);
        $this->assertEquals(now()->toDateString(), SesiOutlet::first()->tanggal->toDateString());
    }

    public function test_open_outlet_returns_existing_session_if_already_open_today(): void
    {
        // Buat sesi buka terlebih dahulu
        $existingSession = SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => '08:00:00',
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
        ]);

        $response = $this->actingAs($this->kasir)
            ->postJson('/outlet/buka');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'BUKA',
                'data' => [
                    'id' => $existingSession->id,
                    'status' => 'BUKA',
                ],
            ]);

        // Pastikan tidak ada duplikasi sesi baru
        $this->assertEquals(1, SesiOutlet::where('outlet_id', $this->outlet->id)->count());
    }

    public function test_kasir_without_outlet_cannot_open_session(): void
    {
        $kasirTanpaOutlet = User::create([
            'username' => 'kasir_no_outlet',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Lepas',
            'role' => 'KASIR',
            'status_aktif' => true,
            'outlet_id' => null,
        ]);

        $response = $this->actingAs($kasirTanpaOutlet)
            ->postJson('/outlet/buka');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_owner_cannot_access_open_outlet(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/outlet/buka');

        $response->assertStatus(403);
    }

    public function test_kasir_can_close_outlet_and_calculate_recap(): void
    {
        // 1. Buka sesi
        $sesi = SesiOutlet::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'tanggal' => now()->toDateString(),
            'jam_buka' => now()->subHours(5)->format('H:i:s'),
            'status' => 'BUKA',
            'total_rekap_penjualan' => 0,
            'created_at' => now()->subHours(5),
        ]);

        // Outlet lain untuk menguji isolasi transaksi
        $otherOutlet = Outlet::create([
            'nama_outlet' => 'LFC Cabang Sudirman',
            'alamat' => 'Jl. Sudirman',
        ]);

        // 2. Buat transaksi:
        // Transaksi 1: TUNAI - Lunas (50.000)
        Transaksi::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'kode_transaksi' => 'TRX-001',
            'tanggal_waktu' => now()->subHours(3),
            'total_harga' => 50000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 50000,
            'kembalian' => 0,
            'status_pembayaran' => true,
            'created_at' => now()->subHours(3),
        ]);

        // Transaksi 2: TUNAI - Lunas (35.000)
        Transaksi::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'kode_transaksi' => 'TRX-002',
            'tanggal_waktu' => now()->subHours(2),
            'total_harga' => 35000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 40000,
            'kembalian' => 5000,
            'status_pembayaran' => true,
            'created_at' => now()->subHours(2),
        ]);

        // Transaksi 3: QRIS - Lunas (70.000)
        Transaksi::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'kode_transaksi' => 'TRX-003',
            'tanggal_waktu' => now()->subHours(1),
            'total_harga' => 70000,
            'metode_pembayaran' => 'QRIS',
            'nominal_bayar' => 70000,
            'kembalian' => 0,
            'status_pembayaran' => true,
            'created_at' => now()->subHours(1),
        ]);

        // Transaksi 4: Belum lunas (tidak boleh dihitung)
        Transaksi::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $this->outlet->id,
            'kode_transaksi' => 'TRX-PENDING',
            'tanggal_waktu' => now()->subMinutes(30),
            'total_harga' => 100000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 0,
            'kembalian' => 0,
            'status_pembayaran' => false,
            'created_at' => now()->subMinutes(30),
        ]);

        // Transaksi 5: Outlet lain (tidak boleh dihitung)
        Transaksi::create([
            'id_kasir' => $this->kasir->id,
            'outlet_id' => $otherOutlet->id,
            'kode_transaksi' => 'TRX-OTHER',
            'tanggal_waktu' => now()->subMinutes(20),
            'total_harga' => 80000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 80000,
            'kembalian' => 0,
            'status_pembayaran' => true,
            'created_at' => now()->subMinutes(20),
        ]);

        // 3. Panggil endpoint tutup outlet
        $response = $this->actingAs($this->kasir)
            ->postJson('/outlet/tutup');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'TUTUP',
                'data' => [
                    'sesi' => [
                        'id' => $sesi->id,
                        'status' => 'TUTUP',
                        'total_rekap_penjualan' => 155000,
                    ],
                    'rekap' => [
                        'total_transaksi' => 3,
                        'total_rekap_penjualan' => 155000,
                        'tunai' => [
                            'total' => 85000,
                            'jumlah_transaksi' => 2,
                        ],
                        'qris' => [
                            'total' => 70000,
                            'jumlah_transaksi' => 1,
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('sesi_outlets', [
            'id' => $sesi->id,
            'status' => 'TUTUP',
            'total_rekap_penjualan' => 155000,
        ]);

        $updatedSesi = $sesi->fresh();
        $this->assertNotNull($updatedSesi->jam_tutup);
    }

    public function test_close_outlet_fails_if_no_open_session(): void
    {
        $response = $this->actingAs($this->kasir)
            ->postJson('/outlet/tutup');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_owner_cannot_access_close_outlet(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/outlet/tutup');

        $response->assertStatus(403);
    }
}
