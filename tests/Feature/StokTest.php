<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StokTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet1;

    private Outlet $outlet2;

    private User $owner;

    private User $kasir;

    private Supplier $supplier;

    private BahanBaku $bahanAyam;

    private BahanBaku $bahanBeras;

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

        $this->kasir = User::create([
            'username' => 'kasir_lfc',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Merdeka',
            'role' => 'KASIR',
            'status_aktif' => true,
            'outlet_id' => $this->outlet1->id,
        ]);

        $this->supplier = Supplier::create([
            'nama_supplier' => 'PT Ayam Segar Abadi',
            'no_telepon' => '081234567890',
            'alamat' => 'Jl. Peternakan Unggas',
        ]);

        $this->bahanAyam = BahanBaku::create([
            'outlet_id' => $this->outlet1->id,
            'nama_bahan' => 'Ayam Potong',
            'stok' => 10.0,
            'satuan' => 'potong',
            'stok_minimal' => 2.0,
        ]);

        $this->bahanBeras = BahanBaku::create([
            'outlet_id' => $this->outlet2->id,
            'nama_bahan' => 'Beras Pulen',
            'stok' => 25.0,
            'satuan' => 'kg',
            'stok_minimal' => 5.0,
        ]);
    }

    public function test_owner_can_view_stok_list_and_filter_by_outlet(): void
    {
        // 1. Ambil semua stok tanpa filter
        $response = $this->actingAs($this->owner)
            ->getJson('/stok');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data.bahan_baku')
            ->assertJsonCount(1, 'data.suppliers');

        // 2. Ambil stok difilter berdasarkan outlet 1
        $responseFilter = $this->actingAs($this->owner)
            ->getJson('/stok?outlet_id='.$this->outlet1->id);

        $responseFilter->assertStatus(200)
            ->assertJsonCount(1, 'data.bahan_baku')
            ->assertJsonPath('data.bahan_baku.0.id', $this->bahanAyam->id);
    }

    public function test_owner_can_store_restock_and_increment_stock(): void
    {
        $payload = [
            'outlet_id' => $this->outlet1->id,
            'id_supplier' => $this->supplier->id,
            'tanggal_restock' => now()->toDateString(),
            'items' => [
                [
                    'id_bahan' => $this->bahanAyam->id,
                    'jumlah' => 20,
                    'harga_satuan' => 25000,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/stok/restock', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'outlet_id' => $this->outlet1->id,
                    'id_supplier' => $this->supplier->id,
                    'total_biaya' => 500000,
                ],
            ]);

        // Verifikasi restock_headers
        $this->assertDatabaseHas('restock_headers', [
            'outlet_id' => $this->outlet1->id,
            'id_supplier' => $this->supplier->id,
            'id_owner' => $this->owner->id,
            'total_biaya' => 500000,
        ]);

        // Verifikasi restock_details
        $this->assertDatabaseHas('restock_details', [
            'id_bahan' => $this->bahanAyam->id,
            'jumlah' => 20,
            'harga_satuan' => 25000,
            'subtotal' => 500000,
        ]);

        // Verifikasi stok bahan baku bertambah: 10 + 20 = 30
        $this->assertEquals(30.0, (float) $this->bahanAyam->fresh()->stok);

        // Verifikasi stok_logs dicatat MASUK
        $this->assertDatabaseHas('stok_logs', [
            'id_bahan' => $this->bahanAyam->id,
            'jenis_aksi' => 'MASUK',
            'jumlah' => 20,
        ]);
    }

    public function test_store_restock_fails_if_bahan_does_not_belong_to_outlet(): void
    {
        // Mencoba restock bahan milik outlet2 pada outlet1
        $payload = [
            'outlet_id' => $this->outlet1->id,
            'id_supplier' => $this->supplier->id,
            'items' => [
                [
                    'id_bahan' => $this->bahanBeras->id, // milik outlet2
                    'jumlah' => 10,
                    'harga_satuan' => 15000,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/stok/restock', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseCount('restock_headers', 0);
    }

    public function test_owner_can_record_stok_keluar_and_decrement_stock(): void
    {
        $payload = [
            'id_bahan' => $this->bahanAyam->id,
            'jumlah' => 4,
            'alasan' => 'Daging ayam rusak/kadaluarsa',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/stok/keluar', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id_bahan' => $this->bahanAyam->id,
                    'jumlah_keluar' => 4,
                    'sisa_stok' => 6,
                    'alasan' => 'Daging ayam rusak/kadaluarsa',
                ],
            ]);

        // Verifikasi stok berkurang: 10 - 4 = 6
        $this->assertEquals(6.0, (float) $this->bahanAyam->fresh()->stok);

        // Verifikasi mutasi stok KELUAR dicatat
        $this->assertDatabaseHas('stok_logs', [
            'id_bahan' => $this->bahanAyam->id,
            'jenis_aksi' => 'KELUAR',
            'jumlah' => 4,
            'alasan' => 'Daging ayam rusak/kadaluarsa',
        ]);
    }

    public function test_store_stok_keluar_fails_if_stock_insufficient_preventing_negative(): void
    {
        $payload = [
            'id_bahan' => $this->bahanAyam->id,
            'jumlah' => 15, // stok hanya 10
            'alasan' => 'Dibuang semua',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson('/stok/keluar', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Pastikan stok tidak berubah dan tidak minus
        $this->assertEquals(10.0, (float) $this->bahanAyam->fresh()->stok);
        $this->assertDatabaseMissing('stok_logs', [
            'id_bahan' => $this->bahanAyam->id,
            'alasan' => 'Dibuang semua',
        ]);
    }

    public function test_kasir_cannot_access_stok_endpoints(): void
    {
        $responseGet = $this->actingAs($this->kasir)->getJson('/stok');
        $responseGet->assertStatus(403);

        $responseRestock = $this->actingAs($this->kasir)->postJson('/stok/restock', []);
        $responseRestock->assertStatus(403);

        $responseKeluar = $this->actingAs($this->kasir)->postJson('/stok/keluar', []);
        $responseKeluar->assertStatus(403);
    }
}
