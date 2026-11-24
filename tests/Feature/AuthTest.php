<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('POS Geprek LFC');
    }

    public function test_kasir_can_authenticate_and_redirect_to_pos(): void
    {
        $user = User::create([
            'username' => 'kasir1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
        ]);

        $response = $this->post('/auth/login', [
            'username' => 'kasir1',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/pos');
    }

    public function test_owner_can_authenticate_and_redirect_to_dashboard(): void
    {
        $user = User::create([
            'username' => 'owner1',
            'password' => Hash::make('secretowner'),
            'nama_lengkap' => 'Owner Utama',
            'role' => 'OWNER',
            'status_aktif' => true,
        ]);

        $response = $this->post('/auth/login', [
            'username' => 'owner1',
            'password' => 'secretowner',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::create([
            'username' => 'kasir_nonaktif',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Nonaktif',
            'role' => 'KASIR',
            'status_aktif' => false,
        ]);

        $response = $this->from('/login')->post('/auth/login', [
            'username' => 'kasir_nonaktif',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
    }

    public function test_user_cannot_authenticate_with_wrong_password(): void
    {
        User::create([
            'username' => 'kasir1',
            'password' => Hash::make('correct_password'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
        ]);

        $response = $this->from('/login')->post('/auth/login', [
            'username' => 'kasir1',
            'password' => 'wrong_password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'username' => 'kasir1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($user)->post('/auth/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_protected_routes(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responsePos = $this->get('/pos');
        $responsePos->assertRedirect('/login');
    }

    public function test_role_middleware_restricts_kasir_from_owner_dashboard(): void
    {
        $kasir = User::create([
            'username' => 'kasir1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($kasir)->get('/dashboard');
        $response->assertStatus(403);
    }

    public function test_role_middleware_allows_owner_to_access_dashboard(): void
    {
        $owner = User::create([
            'username' => 'owner1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Owner Utama',
            'role' => 'OWNER',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Owner');
    }

    public function test_role_middleware_allows_kasir_to_access_pos(): void
    {
        $kasir = User::create([
            'username' => 'kasir1',
            'password' => Hash::make('password123'),
            'nama_lengkap' => 'Kasir Satu',
            'role' => 'KASIR',
            'status_aktif' => true,
        ]);

        $response = $this->actingAs($kasir)->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('POS Kasir');
    }
}
