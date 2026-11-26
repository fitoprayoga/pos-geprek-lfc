<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#DC2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <title>Masuk — POS Geprek LFC</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif
</head>

<body class="bg-stone-50 text-stone-800 font-sans antialiased min-h-screen flex flex-col items-center justify-center p-4 sm:p-6">
    <span class="sr-only">POS Geprek LFC</span>

    {{-- Main Container Card --}}
    <div class="w-full max-w-sm">
        {{-- Card Putih Tengah --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_10px_35px_-5px_rgba(0,0,0,0.06),0_4px_15px_-3px_rgba(0,0,0,0.03)] border border-stone-100/80">
            {{-- Logo / Icon Placeholder --}}
            <div class="flex justify-center mb-4">
                @if (file_exists(public_path('images/logo-lfc.png')))
                    <img src="{{ asset('images/logo-lfc.png') }}" alt="Logo Geprek LFC"
                        class="h-16 w-auto object-contain">
                @else
                    <div class="h-14 w-14 rounded-2xl bg-[#DC2626] text-white flex items-center justify-center shadow-md shadow-red-500/20">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Judul & Subjudul --}}
            <h1 class="text-2xl font-extrabold text-stone-900 text-center tracking-tight mb-1.5">Masuk</h1>
            <p class="text-xs text-stone-500 text-center mb-6 leading-relaxed">Silakan masukkan username dan kata sandi Anda</p>

            {{-- Alert Notifikasi Error Validasi --}}
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5" role="alert">
                    <svg class="w-4 h-4 text-[#DC2626] shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <div class="flex-1 font-medium space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Form Login --}}
            <form action="{{ route('auth.login') }}" method="POST" id="loginForm" class="space-y-4">
                @csrf

                {{-- Input Username --}}
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Username" required autofocus
                        class="w-full bg-stone-100 text-stone-900 text-sm placeholder-stone-400 rounded-xl px-4 py-3.5 border border-transparent focus:border-red-400 focus:bg-white focus:ring-2 focus:ring-red-100 focus:outline-none transition-all">
                </div>

                {{-- Input Password dengan Toggle Show/Hide --}}
                <div class="relative">
                    <label for="password" class="sr-only">Kata Sandi</label>
                    <input type="password" id="password" name="password" placeholder="Kata Sandi" required
                        class="w-full bg-stone-100 text-stone-900 text-sm placeholder-stone-400 rounded-xl px-4 py-3.5 pr-11 border border-transparent focus:border-red-400 focus:bg-white focus:ring-2 focus:ring-red-100 focus:outline-none transition-all">
                    <button type="button" id="togglePasswordBtn" aria-label="Tampilkan atau sembunyikan kata sandi"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 focus:outline-none cursor-pointer p-1.5 rounded-lg transition-colors">
                        {{-- Ikon Mata Terbuka --}}
                        <svg id="eyeOpenIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        {{-- Ikon Mata Dicoret --}}
                        <svg id="eyeClosedIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>

                {{-- Tautan Lupa Kata Sandi --}}
                <div class="flex justify-end pt-0.5">
                    <button type="button" id="openForgotModalBtn"
                        class="text-xs font-semibold text-amber-500 hover:text-amber-600 transition-colors cursor-pointer focus:outline-none">
                        Lupa kata sandi?
                    </button>
                </div>

                {{-- Tombol Masuk --}}
                <div class="pt-2">
                    <button type="submit" id="submitBtn"
                        class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white font-bold text-sm shadow-lg shadow-red-600/25 transition-all cursor-pointer">
                        <span id="btnText">Masuk</span>
                        <svg id="btnSpinner" class="w-4 h-4 animate-spin hidden text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- Footer Branding --}}
        <p class="text-[11px] font-bold text-stone-400 tracking-[0.25em] uppercase text-center mt-6">
            LANA FRIED CHICKEN
        </p>
    </div>

    {{-- Modal Pop-up Lupa Kata Sandi (Bergaya Mobile) --}}
    <div id="forgotPasswordModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="bg-white rounded-3xl max-w-xs w-full p-6 text-center shadow-2xl transform transition-all duration-200 border border-stone-100">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3.5 shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
            </div>
            <h3 id="modalTitle" class="text-base font-bold text-stone-900 mb-2">Lupa Kata Sandi?</h3>
            <p class="text-xs text-stone-600 leading-relaxed mb-6">
                Silakan hubungi Owner Geprek LFC untuk melakukan reset kata sandi akun Anda.
            </p>
            <button type="button" id="closeForgotModalBtn"
                class="w-full py-3 px-4 rounded-xl bg-stone-900 hover:bg-stone-800 active:scale-[0.99] text-white text-xs font-bold transition-all cursor-pointer">
                Mengerti
            </button>
        </div>
    </div>

    {{-- Script Interaktivitas --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Toggle Show / Hide Password
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const eyeOpenIcon = document.getElementById('eyeOpenIcon');
            const eyeClosedIcon = document.getElementById('eyeClosedIcon');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', () => {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    eyeOpenIcon.classList.toggle('hidden', isPassword);
                    eyeClosedIcon.classList.toggle('hidden', !isPassword);
                });
            }

            // 2. Modal Lupa Kata Sandi
            const forgotModal = document.getElementById('forgotPasswordModal');
            const openForgotModalBtn = document.getElementById('openForgotModalBtn');
            const closeForgotModalBtn = document.getElementById('closeForgotModalBtn');

            function openModal() {
                if (!forgotModal) return;
                forgotModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                if (!forgotModal) return;
                forgotModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            if (openForgotModalBtn) {
                openForgotModalBtn.addEventListener('click', openModal);
            }

            if (closeForgotModalBtn) {
                closeForgotModalBtn.addEventListener('click', closeModal);
            }

            if (forgotModal) {
                forgotModal.addEventListener('click', (e) => {
                    if (e.target === forgotModal) {
                        closeModal();
                    }
                });
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && forgotModal && !forgotModal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // 3. Form Submit Loading State
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', () => {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-90', 'cursor-not-allowed');
                    if (btnSpinner) btnSpinner.classList.remove('hidden');
                    if (btnText) btnText.textContent = 'Memproses...';
                });
            }
        });
    </script>
</body>

</html>
