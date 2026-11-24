<?php

namespace Tests\Feature;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\ResepMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $owner;

    private User $kasir;

    private BahanBaku $bahanAyam;

    private BahanBaku $bahanCabai;

    private BahanBaku $bahanNasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlet::create([
            'nama_outlet' => 'LFC Cabang Merdeka',
            'alamat' => 'Jl. Merdeka No. 10',
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
            'outlet_id' => $this->outlet->id,
        ]);

        $this->bahanAyam = BahanBaku::create([
            'outlet_id' => $this->outlet->id,
            'nama_bahan' => 'Ayam Potong',
            'stok' => 50,
            'satuan' => 'potong',
            'stok_minimal' => 10,
        ]);

        $this->bahanCabai = BahanBaku::create([
            'outlet_id' => $this->outlet->id,
            'nama_bahan' => 'Cabai Rawit',
            'stok' => 500,
            'satuan' => 'gram',
            'stok_minimal' => 50,
        ]);

        $this->bahanNasi = BahanBaku::create([
            'outlet_id' => $this->outlet->id,
            'nama_bahan' => 'Nasi Putih',
            'stok' => 100,
            'satuan' => 'porsi',
            'stok_minimal' => 20,
        ]);
    }

    public function test_owner_can_get_all_menus_with_resep_and_bahan_baku(): void
    {
        $menu = Menu::create([
            'nama_menu' => 'Ayam Geprek Original',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        ResepMenu::create([
            'id_menu' => $menu->id,
            'id_bahan' => $this->bahanAyam->id,
            'jumlah_dibutuhkan' => 1,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/menu');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data.menus')
            ->assertJsonPath('data.menus.0.nama_menu', 'Ayam Geprek Original')
            ->assertJsonPath('data.menus.0.resep_menus.0.bahan_baku.nama_bahan', 'Ayam Potong');
    }

    public function test_owner_can_store_new_menu_with_resep(): void
    {
        $payload = [
            'nama_menu' => 'Paket Geprek Komplit',
            'kategori' => 'PAKET',
            'harga' => 22000,
            'resep' => [
                [
                    'id_bahan' => $this->bahanAyam->id,
                    'jumlah_dibutuhkan' => 1,
                ],
                [
                    'id_bahan' => $this->bahanCabai->id,
                    'jumlah_dibutuhkan' => 15,
                ],
                [
                    'id_bahan' => $this->bahanNasi->id,
                    'jumlah_dibutuhkan' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)->postJson('/menu/store', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_menu' => 'Paket Geprek Komplit',
                    'kategori' => 'PAKET',
                    'status_aktif' => true,
                ],
            ])
            ->assertJsonCount(3, 'data.resep_menus');

        $this->assertDatabaseHas('menus', [
            'nama_menu' => 'Paket Geprek Komplit',
            'kategori' => 'PAKET',
            'harga' => 22000,
            'status_aktif' => true,
        ]);

        $menu = Menu::where('nama_menu', 'Paket Geprek Komplit')->first();
        $this->assertDatabaseHas('resep_menus', [
            'id_menu' => $menu->id,
            'id_bahan' => $this->bahanCabai->id,
            'jumlah_dibutuhkan' => 15,
        ]);
    }

    public function test_store_menu_validates_required_fields_and_distinct_bahan(): void
    {
        $responseEmpty = $this->actingAs($this->owner)->postJson('/menu/store', []);
        $responseEmpty->assertStatus(422)
            ->assertJsonValidationErrors(['nama_menu', 'kategori', 'harga', 'resep']);

        $responseDuplicateBahan = $this->actingAs($this->owner)->postJson('/menu/store', [
            'nama_menu' => 'Geprek Double',
            'kategori' => 'MAKANAN',
            'harga' => 18000,
            'resep' => [
                ['id_bahan' => $this->bahanAyam->id, 'jumlah_dibutuhkan' => 1],
                ['id_bahan' => $this->bahanAyam->id, 'jumlah_dibutuhkan' => 2],
            ],
        ]);

        $responseDuplicateBahan->assertStatus(422)
            ->assertJsonValidationErrors(['resep.0.id_bahan', 'resep.1.id_bahan']);

        $this->assertDatabaseCount('menus', 0);
    }

    public function test_owner_can_update_menu_price_data_and_resep(): void
    {
        $menu = Menu::create([
            'nama_menu' => 'Ayam Geprek Biasa',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        ResepMenu::create([
            'id_menu' => $menu->id,
            'id_bahan' => $this->bahanAyam->id,
            'jumlah_dibutuhkan' => 1,
        ]);

        // 1. Update harga saja
        $responsePrice = $this->actingAs($this->owner)
            ->putJson("/menu/{$menu->id}/update-harga", [
                'harga' => 17000,
            ]);

        $responsePrice->assertStatus(200);
        $this->assertEquals(17000.0, (float) $menu->fresh()->harga);

        // 2. Update nama_menu, kategori, harga, dan resep sekaligus
        $responseFull = $this->actingAs($this->owner)
            ->putJson("/menu/{$menu->id}/update-harga", [
                'nama_menu' => 'Ayam Geprek Jumbo',
                'kategori' => 'SPESIAL',
                'harga' => 25000,
                'resep' => [
                    ['id_bahan' => $this->bahanAyam->id, 'jumlah_dibutuhkan' => 2],
                    ['id_bahan' => $this->bahanCabai->id, 'jumlah_dibutuhkan' => 20],
                ],
            ]);

        $responseFull->assertStatus(200)
            ->assertJsonPath('data.nama_menu', 'Ayam Geprek Jumbo')
            ->assertJsonPath('data.kategori', 'SPESIAL')
            ->assertJsonCount(2, 'data.resep_menus');

        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'nama_menu' => 'Ayam Geprek Jumbo',
            'kategori' => 'SPESIAL',
            'harga' => 25000,
        ]);

        $this->assertDatabaseHas('resep_menus', [
            'id_menu' => $menu->id,
            'id_bahan' => $this->bahanAyam->id,
            'jumlah_dibutuhkan' => 2,
        ]);
        $this->assertDatabaseHas('resep_menus', [
            'id_menu' => $menu->id,
            'id_bahan' => $this->bahanCabai->id,
            'jumlah_dibutuhkan' => 20,
        ]);
    }

    public function test_update_menu_price_validates_non_empty_payload(): void
    {
        $menu = Menu::create([
            'nama_menu' => 'Ayam Geprek Original',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/menu/{$menu->id}/update-harga", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['harga']);
    }

    public function test_owner_can_toggle_and_set_menu_status(): void
    {
        $menu = Menu::create([
            'nama_menu' => 'Ayam Geprek Original',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        // Toggle tanpa body -> berubah jadi false
        $responseToggleOff = $this->actingAs($this->owner)
            ->patchJson("/menu/{$menu->id}/status");

        $responseToggleOff->assertStatus(200)
            ->assertJsonPath('data.status_aktif', false);
        $this->assertFalse($menu->fresh()->status_aktif);

        // Set eksplisit menjadi true
        $responseSetOn = $this->actingAs($this->owner)
            ->patchJson("/menu/{$menu->id}/status", [
                'status_aktif' => true,
            ]);

        $responseSetOn->assertStatus(200)
            ->assertJsonPath('data.status_aktif', true);
        $this->assertTrue($menu->fresh()->status_aktif);

        // Set eksplisit menjadi false -> tersembunyi dari scopeAktif()
        $this->actingAs($this->owner)
            ->patchJson("/menu/{$menu->id}/status", [
                'status_aktif' => false,
            ])
            ->assertStatus(200);

        $this->assertEquals(0, Menu::aktif()->count());
    }

    public function test_kasir_cannot_access_menu_owner_endpoints(): void
    {
        $menu = Menu::create([
            'nama_menu' => 'Ayam Geprek Original',
            'kategori' => 'MAKANAN',
            'harga' => 15000,
            'status_aktif' => true,
        ]);

        $this->actingAs($this->kasir)->getJson('/menu')->assertStatus(403);
        $this->actingAs($this->kasir)->postJson('/menu/store', [])->assertStatus(403);
        $this->actingAs($this->kasir)->putJson("/menu/{$menu->id}/update-harga", [])->assertStatus(403);
        $this->actingAs($this->kasir)->patchJson("/menu/{$menu->id}/status")->assertStatus(403);
    }
}
