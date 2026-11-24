<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Outlet;
use App\Models\RestockHeader;
use App\Models\Supplier;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet1;

    private Outlet $outlet2;

    private User $owner;

    private User $kasir1;

    private User $kasir2;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet1 = Outlet::create([
            'nama_outlet' => 'LFC Cabang Merdeka',
            'alamat' => 'Jl. Merdeka No. 10',
        ]);

        $this->outlet2 = Outlet::create([
            'nama_outlet' => 'LFC Cabang Sudirman',
            'alamat' => 'Jl. Sudirman No. 5',
        ]);

        $this->owner = User::create([
            'username' => 'owner_lfc',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Owner LFC',
            'role' => 'OWNER',
            'status_aktif' => true,
        ]);

        $this->kasir1 = User::create([
            'username' => 'kasir1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
            'outlet_id' => $this->outlet1->id,
        ]);

        $this->kasir2 = User::create([
            'username' => 'kasir2',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Dua',
            'role' => 'KASIR',
            'status_aktif' => true,
            'outlet_id' => $this->outlet2->id,
        ]);

        $this->supplier = Supplier::create([
            'nama_supplier' => 'PT Ayam Segar',
            'no_telepon' => '08123456789',
            'alamat' => 'Jl. Peternakan',
        ]);

        // Stok bahan baku (1 kritis di outlet1, 1 aman di outlet2)
        BahanBaku::create([
            'outlet_id' => $this->outlet1->id,
            'nama_bahan' => 'Ayam Potong',
            'stok' => 3,
            'satuan' => 'potong',
            'stok_minimal' => 5,
        ]);

        BahanBaku::create([
            'outlet_id' => $this->outlet2->id,
            'nama_bahan' => 'Beras',
            'stok' => 50,
            'satuan' => 'kg',
            'stok_minimal' => 10,
        ]);

        // Transaksi hari ini (outlet1: 100k TUNAI + 50k QRIS, outlet2: 75k TUNAI, plus 1 belum lunas)
        Transaksi::create([
            'id_kasir' => $this->kasir1->id,
            'outlet_id' => $this->outlet1->id,
            'kode_transaksi' => 'TRX-O1-001',
            'tanggal_waktu' => now(),
            'total_harga' => 100000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 100000,
            'kembalian' => 0,
            'status_pembayaran' => true,
        ]);

        Transaksi::create([
            'id_kasir' => $this->kasir1->id,
            'outlet_id' => $this->outlet1->id,
            'kode_transaksi' => 'TRX-O1-002',
            'tanggal_waktu' => now(),
            'total_harga' => 50000,
            'metode_pembayaran' => 'QRIS',
            'nominal_bayar' => 50000,
            'kembalian' => 0,
            'status_pembayaran' => true,
        ]);

        Transaksi::create([
            'id_kasir' => $this->kasir1->id,
            'outlet_id' => $this->outlet1->id,
            'kode_transaksi' => 'TRX-O1-UNPAID',
            'tanggal_waktu' => now(),
            'total_harga' => 999000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 0,
            'kembalian' => 0,
            'status_pembayaran' => false,
        ]);

        Transaksi::create([
            'id_kasir' => $this->kasir2->id,
            'outlet_id' => $this->outlet2->id,
            'kode_transaksi' => 'TRX-O2-001',
            'tanggal_waktu' => now(),
            'total_harga' => 75000,
            'metode_pembayaran' => 'TUNAI',
            'nominal_bayar' => 75000,
            'kembalian' => 0,
            'status_pembayaran' => true,
        ]);

        // Restock bulan ini
        RestockHeader::create([
            'id_owner' => $this->owner->id,
            'outlet_id' => $this->outlet1->id,
            'id_supplier' => $this->supplier->id,
            'tanggal_restock' => now()->toDateString(),
            'total_biaya' => 300000,
        ]);

        RestockHeader::create([
            'id_owner' => $this->owner->id,
            'outlet_id' => $this->outlet2->id,
            'id_supplier' => $this->supplier->id,
            'tanggal_restock' => now()->toDateString(),
            'total_biaya' => 200000,
        ]);
    }

    public function test_owner_can_access_global_dashboard_index(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ringkasan_penjualan' => [
                        'total_revenue' => 225000,
                        'total_volume_transaksi' => 3,
                        'metode_pembayaran' => [
                            'TUNAI' => [
                                'total' => 175000,
                                'jumlah_transaksi' => 2,
                            ],
                            'QRIS' => [
                                'total' => 50000,
                                'jumlah_transaksi' => 1,
                            ],
                        ],
                    ],
                    'laporan_stok' => [
                        'total_item_stok' => 2,
                        'total_alert' => 1,
                    ],
                    'laporan_pengeluaran_restock' => [
                        'total_pengeluaran' => 500000,
                        'jumlah_restock' => 2,
                    ],
                ],
            ])
            ->assertJsonPath('data.laporan_stok.stok_alert.0.nama_bahan', 'Ayam Potong');
    }

    public function test_owner_can_filter_dashboard_by_outlet_and_date_range(): void
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->owner)
            ->getJson("/dashboard/filter?outlet_id={$this->outlet1->id}&start_date={$today}&end_date={$today}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'filter' => [
                        'start_date' => $today,
                        'end_date' => $today,
                        'outlet_id' => $this->outlet1->id,
                    ],
                    'ringkasan_penjualan' => [
                        'total_revenue' => 150000,
                        'total_volume_transaksi' => 2,
                        'metode_pembayaran' => [
                            'TUNAI' => ['total' => 100000, 'jumlah_transaksi' => 1],
                            'QRIS' => ['total' => 50000, 'jumlah_transaksi' => 1],
                        ],
                    ],
                    'laporan_stok' => [
                        'total_item_stok' => 1,
                        'total_alert' => 1,
                    ],
                    'laporan_pengeluaran_restock' => [
                        'total_pengeluaran' => 300000,
                        'jumlah_restock' => 1,
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data.ringkasan_penjualan.tren_penjualan_harian');
    }

    public function test_dashboard_filter_validates_date_range_and_outlet(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/dashboard/filter?outlet_id=9999&start_date=2026-11-25&end_date=2026-11-20');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['outlet_id', 'end_date']);
    }

    public function test_owner_can_export_laporan_csv(): void
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->owner)
            ->get("/dashboard/export?outlet_id={$this->outlet1->id}&start_date={$today}&end_date={$today}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', "attachment; filename=laporan-penjualan-{$today}-sd-{$today}.csv");

        $content = $response->streamedContent();
        $this->assertStringContainsString('TRX-O1-001', $content);
        $this->assertStringContainsString('TRX-O1-002', $content);
        $this->assertStringNotContainsString('TRX-O2-001', $content);
        $this->assertStringNotContainsString('TRX-O1-UNPAID', $content);
    }

    public function test_kasir_cannot_access_dashboard_endpoints(): void
    {
        $this->actingAs($this->kasir1)->getJson('/dashboard')->assertStatus(403);
        $this->actingAs($this->kasir1)->getJson('/dashboard/filter')->assertStatus(403);
        $this->actingAs($this->kasir1)->get('/dashboard/export')->assertStatus(403);
    }
}
