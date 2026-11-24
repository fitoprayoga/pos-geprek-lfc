<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->role === 'OWNER'
                ? redirect()->intended('/dashboard')
                : redirect()->intended('/pos');
        }

        return view('auth.login');
    }

    /**
     * Alias method untuk menampilkan halaman login jika diakses via route resource / helper.
     */
    public function login()
    {
        return $this->showLoginForm();
    }

    /**
     * Memproses autentikasi login user POS / Owner.
     */
    public function authenticate(Request $request)
    {
        // 1. Validasi input username dan password
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Cari user berdasarkan username
        $user = User::where('username', $credentials['username'])->first();

        // 3. Verifikasi apakah user ditemukan dan password cocok
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'username' => 'Username atau password yang Anda masukkan salah.',
            ])->onlyInput('username');
        }

        // 4. Verifikasi status_aktif
        if (! $user->status_aktif) {
            return back()->withErrors([
                'username' => 'Akun Anda tidak aktif. Silakan hubungi Owner.',
            ])->onlyInput('username');
        }

        // 5. Login user dan regenerasi session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // 6. Cek role dan arahkan sesuai hak akses
        if ($user->role === 'OWNER') {
            return redirect()->intended('/dashboard');
        }

        return redirect()->intended('/pos');
    }

    /**
     * Keluar dari sesi (logout) dan redirect ke /login.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
