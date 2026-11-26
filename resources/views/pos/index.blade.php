<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#DC2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <title>POS Kasir — {{ $outlet->nama_outlet ?? 'Geprek LFC' }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endif

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Styling Cetak Struk Thermal Khusus Print */
        @media print {
            body * {
                visibility: hidden !important;
            }

            #printable-receipt-card,
            #printable-receipt-card * {
                visibility: visible !important;
            }

            #printable-receipt-card {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 78mm !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 4mm !important;
                border: none !important;
                box-shadow: none !important;
                background: white !important;
                color: black !important;
            }

            @page {
                size: auto;
                margin: 2mm;
            }
        }
    </style>
</head>

<body class="bg-stone-50 text-stone-800 font-sans antialiased min-h-screen">
    <span class="sr-only">POS Kasir</span>

    @php
        $isOutletOpen = (bool) $sesiAktif;
        $namaOutlet = $outlet->nama_outlet ?? 'Outlet Belum Diatur';
        $alamatOutlet = $outlet->alamat ?? 'Alamat Outlet Belum Diatur';
        $namaKasir = auth()->user()->nama_lengkap ?? auth()->user()->username;
    @endphp

    <div class="w-full max-w-5xl mx-auto px-3 sm:px-6 py-3 sm:py-5 pb-28">
        {{-- Header Atas --}}
        <header class="bg-white rounded-2xl shadow-sm border border-stone-100 px-3.5 py-3 flex items-center justify-between gap-2.5 mb-3.5">
            {{-- Kiri: Ikon Toko Merah + Nama Outlet Dinamis --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-10 w-10 rounded-xl bg-[#DC2626] text-white flex items-center justify-center shadow-sm shadow-red-600/20 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-extrabold text-stone-900 truncate leading-tight">
                        {{ $namaOutlet }}
                    </h1>
                    <p class="text-[11px] text-stone-500 truncate">
                        Kasir: {{ $namaKasir }}
                    </p>
                </div>
            </div>

            {{-- Kanan: Saklar Status Outlet + Refresh + Logout --}}
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <button
                    type="button"
                    id="btn-toggle-outlet"
                    data-status="{{ $isOutletOpen ? 'BUKA' : 'TUTUP' }}"
                    class="px-3 py-1.5 rounded-full text-xs font-extrabold tracking-wide transition-all cursor-pointer {{ $isOutletOpen ? 'bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200/70' : 'bg-red-100 text-[#DC2626] border border-red-200 hover:bg-red-200/70' }}"
                    title="Klik untuk buka/tutup sesi outlet">
                    {{ $isOutletOpen ? 'OPEN' : 'TUTUP' }}
                </button>

                <button
                    type="button"
                    id="btn-refresh"
                    onclick="window.location.reload()"
                    class="h-9 w-9 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 flex items-center justify-center transition-colors cursor-pointer"
                    aria-label="Muat ulang stok dan menu"
                    title="Muat ulang stok">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </button>

                <form method="POST" action="{{ route('auth.logout') }}" class="inline-flex">
                    @csrf
                    <button
                        type="submit"
                        class="h-9 w-9 rounded-full bg-stone-100 hover:bg-red-50 text-stone-600 hover:text-[#DC2626] flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="Keluar akun"
                        title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                    </button>
                </form>
            </div>
        </header>

        {{-- Search Bar --}}
        <div class="mb-3.5">
            <div class="relative flex items-center bg-white rounded-2xl shadow-sm border border-stone-100">
                <svg class="w-4 h-4 text-stone-400 absolute left-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="search"
                    id="menu-search"
                    placeholder="Cari Ayam, Nasi, Sambal..."
                    class="w-full bg-transparent pl-10 pr-4 py-3 text-xs sm:text-sm text-stone-800 placeholder-stone-400 rounded-2xl focus:outline-none focus:ring-2 focus:ring-red-100">
            </div>
        </div>

        {{-- Filter Kategori Horizontal Scroll --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-2 mb-3.5" id="category-filters" role="tablist" aria-label="Filter Kategori Menu">
            <button
                type="button"
                data-category="ALL"
                class="category-pill shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20">
                Semua
            </button>
            @foreach ($kategoris as $kategori)
                <button
                    type="button"
                    data-category="{{ strtolower($kategori) }}"
                    class="category-pill shrink-0 px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer bg-white text-stone-600 border border-stone-200/80 hover:bg-stone-100">
                    {{ $kategori }}
                </button>
            @endforeach
        </div>

        {{-- Area Menu + Overlay Kunci saat TUTUP --}}
        <div class="relative min-h-[320px]">
            {{-- Overlay Kunci jika Outlet TUTUP --}}
            <div
                id="outlet-lock-overlay"
                class="{{ $isOutletOpen ? 'hidden' : 'flex' }} absolute inset-0 z-20 bg-stone-50/90 backdrop-blur-[2px] rounded-2xl flex-col items-center justify-center p-6 text-center border border-stone-200/80">
                <div class="h-14 w-14 rounded-2xl bg-red-50 text-[#DC2626] flex items-center justify-center mb-3.5 shadow-sm border border-red-100">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg font-extrabold text-stone-900 mb-1">Outlet Sedang Tutup</h2>
                <p class="text-xs sm:text-sm text-stone-500 max-w-xs mb-5 leading-relaxed">
                    Buka sesi outlet terlebih dahulu sebelum menerima dan memproses pesanan pelanggan.
                </p>
                <button
                    type="button"
                    id="btn-open-outlet-overlay"
                    class="px-6 py-3 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-bold shadow-lg shadow-red-600/25 transition-all cursor-pointer">
                    Buka Outlet Sekarang
                </button>
            </div>

            {{-- Grid Kartu Menu --}}
            @if ($menus->isEmpty())
                <div class="bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                    <p class="text-sm font-semibold text-stone-700">Belum ada menu aktif tersedia.</p>
                    <p class="text-xs text-stone-500 mt-1">Hubungi Owner untuk menambahkan atau mengaktifkan menu.</p>
                </div>
            @else
                <div id="menu-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    @foreach ($menus as $menu)
                        @php
                            $isHabis = (bool) $menu->stok_habis;
                            $kategoriLower = strtolower($menu->kategori);
                            $isDrink = str_contains($kategoriLower, 'minum');
                            $isSambal = str_contains($kategoriLower, 'sambal') || str_contains(strtolower($menu->nama_menu), 'sambal');
                        @endphp
                        <article
                            class="menu-card bg-white rounded-2xl shadow-sm border border-stone-100 p-2.5 sm:p-3 flex flex-col justify-between transition-all hover:shadow-md"
                            data-id="{{ $menu->id }}"
                            data-name="{{ strtolower($menu->nama_menu) }}"
                            data-display-name="{{ $menu->nama_menu }}"
                            data-category="{{ $kategoriLower }}"
                            data-price="{{ (float) $menu->harga }}"
                            data-habis="{{ $isHabis ? '1' : '0' }}">
                            <div>
                                {{-- Foto / Placeholder Menu --}}
                                <div class="relative aspect-[4/3] w-full rounded-xl overflow-hidden mb-2.5 bg-gradient-to-br {{ $isDrink ? 'from-amber-100 via-orange-50 to-yellow-100' : ($isSambal ? 'from-red-100 via-orange-100 to-rose-50' : 'from-orange-100 via-amber-50 to-red-50') }} flex items-center justify-center {{ $isHabis ? 'opacity-45 grayscale' : '' }}">
                                    @if (file_exists(public_path('images/menu-' . $menu->id . '.jpg')))
                                        <img src="{{ asset('images/menu-' . $menu->id . '.jpg') }}" alt="{{ $menu->nama_menu }}" class="w-full h-full object-cover">
                                    @elseif (file_exists(public_path('images/menu-' . $menu->id . '.png')))
                                        <img src="{{ asset('images/menu-' . $menu->id . '.png') }}" alt="{{ $menu->nama_menu }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="flex flex-col items-center justify-center text-center p-2">
                                            <div class="h-11 w-11 rounded-full bg-white/85 shadow-sm flex items-center justify-center text-[#DC2626] mb-1">
                                                @if ($isDrink)
                                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                                    </svg>
                                                @else
                                                    <svg class="w-6 h-6 text-[#DC2626]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-stone-500/90">{{ $menu->kategori }}</span>
                                        </div>
                                    @endif

                                    {{-- Badge STOK HABIS --}}
                                    @if ($isHabis)
                                        <span class="badge-stok-habis absolute top-2 right-2 px-2 py-0.5 rounded-full bg-[#DC2626] text-white text-[10px] font-extrabold tracking-wide shadow-sm">
                                            STOK HABIS
                                        </span>
                                    @endif
                                </div>

                                {{-- Nama & Harga Menu --}}
                                <h3 class="menu-title text-xs sm:text-sm font-bold leading-snug line-clamp-2 {{ $isHabis ? 'text-stone-400' : 'text-stone-900' }}">
                                    {{ $menu->nama_menu }}
                                </h3>
                                <p class="menu-price text-xs sm:text-sm font-semibold mt-0.5 mb-3 {{ $isHabis ? 'text-stone-400' : 'text-stone-700' }}">
                                    Rp {{ number_format((float) $menu->harga, 0, ',', '.') }}
                                </p>
                            </div>

                            {{-- Area Tombol Aksi Kartu (+ Tambah atau Stepper - qty +) --}}
                            <div class="menu-action-wrapper" data-menu-id="{{ $menu->id }}">
                                @if ($isHabis)
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full py-2 px-3 rounded-xl bg-stone-200 text-stone-400 text-xs font-bold cursor-not-allowed">
                                        + Tambah
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        onclick="addToCart({{ $menu->id }})"
                                        class="btn-add-menu w-full py-2 px-3 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.98] text-white text-xs font-bold shadow-sm shadow-red-600/20 transition-all cursor-pointer">
                                        + Tambah
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Empty State Pencarian --}}
                <div id="menu-empty-search" class="hidden bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                    <p class="text-sm font-bold text-stone-700">Menu tidak ditemukan</p>
                    <p class="text-xs text-stone-500 mt-1">Coba kata kunci atau kategori lain.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Floating Bottom Cart Bar --}}
    <div
        id="floating-cart-bar"
        class="hidden fixed bottom-3 sm:bottom-5 inset-x-3 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full sm:max-w-lg z-30"
        style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="bg-stone-900 text-white rounded-2xl shadow-2xl px-4 py-3 flex items-center justify-between gap-3 border border-stone-800">
            {{-- Kiri: Ikon Keranjang + Badge Amber + X Pesanan + Total Harga --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="relative shrink-0">
                    <div class="h-10 w-10 rounded-xl bg-stone-800 flex items-center justify-center text-stone-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </div>
                    <span
                        id="cart-badge-count"
                        class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 rounded-full bg-amber-400 text-stone-900 text-[10px] font-extrabold flex items-center justify-center shadow-sm">
                        0
                    </span>
                </div>
                <div class="min-w-0">
                    <p id="cart-count-text" class="text-[11px] text-stone-300 font-medium truncate">0 Pesanan</p>
                    <p id="cart-total-text" class="text-sm sm:text-base font-extrabold text-white truncate">Rp 0</p>
                </div>
            </div>

            {{-- Kanan: Tombol Pill Merah Bayar / Checkout --}}
            <button
                type="button"
                onclick="openCheckoutSheet()"
                class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2.5 rounded-full bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.98] text-white text-xs sm:text-sm font-bold shadow-md shadow-red-600/30 transition-all cursor-pointer">
                <span>Bayar / Checkout</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Bottom Sheet / Modal: Ringkasan & Pembayaran --}}
    <div
        id="checkout-sheet-backdrop"
        class="hidden fixed inset-0 z-40 bg-stone-900/60 backdrop-blur-xs flex flex-col justify-end md:justify-center p-0 md:p-4 transition-all duration-300"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sheet-title">
        <div
            id="checkout-sheet-container"
            class="bg-white w-full md:max-w-md md:mx-auto rounded-t-3xl md:rounded-2xl max-h-[92vh] flex flex-col shadow-2xl border border-stone-100 overflow-hidden transform transition-all duration-300 translate-y-full md:translate-y-0">
            {{-- Mobile Bar Handle --}}
            <div class="w-10 h-1 bg-stone-300 rounded-full mx-auto mt-2.5 mb-1 md:hidden"></div>

            {{-- Header Sheet --}}
            <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3">
                <h2 id="sheet-title" class="text-sm sm:text-base font-extrabold text-stone-900">
                    Ringkasan &amp; Pembayaran
                </h2>
                <div class="flex items-center gap-1">
                    {{-- Kosongkan Keranjang --}}
                    <button
                        type="button"
                        onclick="emptyCartConfirm()"
                        class="p-2 rounded-xl text-stone-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                        title="Kosongkan keranjang"
                        aria-label="Kosongkan keranjang">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </button>
                    {{-- Tutup Sheet --}}
                    <button
                        type="button"
                        onclick="closeCheckoutSheet()"
                        class="p-2 rounded-xl text-stone-400 hover:text-stone-700 hover:bg-stone-100 transition-colors cursor-pointer"
                        title="Tutup ringkasan"
                        aria-label="Tutup ringkasan">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Body Sheet (Scrollable) --}}
            <div class="p-5 overflow-y-auto no-scrollbar space-y-4 flex-1">
                {{-- Daftar Pesanan Anda --}}
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-stone-800 mb-2.5">
                        <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span>Pesanan Anda</span>
                    </div>

                    <div id="checkout-items-list" class="space-y-2.5 max-h-40 overflow-y-auto pr-1">
                        {{-- Diisi secara dinamis via JavaScript --}}
                    </div>
                </div>

                {{-- Kotak Total Tagihan --}}
                <div class="bg-stone-100 rounded-xl px-4 py-3 flex justify-between items-center border border-stone-200/60">
                    <span class="text-xs sm:text-sm font-bold text-stone-700">Total Tagihan:</span>
                    <span id="checkout-total-bill" class="text-sm sm:text-base font-extrabold text-[#DC2626]">Rp 0</span>
                </div>

                {{-- Toggle Metode Pembayaran --}}
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-stone-800 mb-2.5">
                        <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                        <span>Metode Pembayaran</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            id="btn-method-cash"
                            onclick="selectPaymentMethod('TUNAI')"
                            class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="12" x="2" y="6" rx="2" />
                                <circle cx="12" cy="12" r="2" />
                                <path d="M6 12h.01M18 12h.01" />
                            </svg>
                            <span>TUNAI (Cash)</span>
                        </button>

                        <button
                            type="button"
                            id="btn-method-qris"
                            onclick="selectPaymentMethod('QRIS')"
                            class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h3v3h-3v-3ZM13.5 19.5h3v.75h-3v-.75ZM19.5 13.5h.75v3h-.75v-3ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h3v3h-3v-3Z" />
                            </svg>
                            <span>QRIS</span>
                        </button>
                    </div>
                </div>

                {{-- Kontainer Opsi TUNAI --}}
                <div id="payment-cash-section" class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="cash-received-input" class="flex items-center gap-1.5 text-xs font-bold text-stone-800">
                                <svg class="w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
                                </svg>
                                <span>Uang Diterima</span>
                            </label>
                            <button
                                type="button"
                                onclick="fillExactCash()"
                                class="text-[11px] font-bold text-[#DC2626] hover:underline cursor-pointer">
                                Uang Pas
                            </button>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-stone-400">Rp</span>
                            <input
                                type="text"
                                inputmode="numeric"
                                id="cash-received-input"
                                placeholder="0"
                                oninput="onCashInputChanged(this)"
                                class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-9 pr-3.5 py-2.5 text-xs sm:text-sm font-bold text-stone-900 focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-400">
                        </div>
                    </div>

                    {{-- Kotak Kembalian --}}
                    <div id="cash-change-box" class="bg-emerald-50 text-emerald-800 rounded-xl px-4 py-3 flex justify-between items-center text-xs sm:text-sm font-bold border border-emerald-100">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18m2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Z" />
                            </svg>
                            <span>Kembalian:</span>
                        </div>
                        <span id="cash-change-amount" class="text-sm sm:text-base font-extrabold text-emerald-700">Rp 0</span>
                    </div>

                    <p id="cash-insufficient-warning" class="hidden text-[11px] font-bold text-red-600">
                        * Nominal uang diterima masih kurang dari total tagihan.
                    </p>
                </div>

                {{-- Kontainer Opsi QRIS --}}
                <div id="payment-qris-section" class="hidden space-y-3">
                    <div class="bg-white rounded-2xl border border-stone-200 p-4 flex flex-col items-center justify-center text-center">
                        <div class="p-2.5 bg-white rounded-xl shadow-xs border border-stone-100 mb-2">
                            @if (file_exists(public_path('images/qris-lfc.jpg')))
                                <img src="{{ asset('images/qris-lfc.jpg') }}" alt="QRIS LFC" class="w-44 h-44 object-contain mx-auto rounded-lg">
                            @else
                                {{-- Placeholder SVG QR Code Realistis --}}
                                <svg class="w-40 h-40 text-stone-900" viewBox="0 0 200 200" fill="currentColor">
                                    <path d="M20 20h60v60H20V20zm10 10v40h40V30H30zm10 10h20v20H40V40zm80-20h60v60h-60V20zm10 10v40h40V30h-40zm10 10h20v20h-20V40zM20 120h60v60H20v-60zm10 10v40h40v-40H30zm10 10h20v20H40v-20zm60-10h10v10h-10v-10zm20 0h10v10h-10v-10zm10 10h20v10h-20v-10zm-40 20h20v10h-20v-10zm30 0h10v20h-10v-20zm20 0h10v10h-10v-10zm-30 20h20v10h-20v-10zm40 0h10v10h-10v-10zm-50-60h10v10h-10V90zm20 0h10v10h-10V90zm30 0h20v10h-20V90zm-30 20h10v10h-10v-10zm40 0h10v20h-10v-20z"/>
                                </svg>
                            @endif
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-stone-800">Scan QRIS untuk membayar</p>

                        {{-- Badge Countdown 5 Menit --}}
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200/80 text-[11px] font-bold mt-2">
                            <svg class="w-3.5 h-3.5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>Menunggu Pembayaran... (<span id="qris-countdown-timer">05:00</span>)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Sheet: Tombol Konfirmasi & Cetak Struk --}}
            <div class="p-4 border-t border-stone-100 bg-white" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                <button
                    type="button"
                    id="btn-confirm-transaction"
                    onclick="submitTransaksi()"
                    class="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.98] text-white text-xs sm:text-sm font-bold shadow-lg shadow-red-600/25 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span id="btn-confirm-pay-text">Konfirmasi &amp; Cetak Struk</span>
                    <span id="btn-confirm-pay-spinner" class="hidden items-center gap-1.5">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v8H4Z"></path>
                        </svg>
                        <span>Memproses...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal / Layar Full: Pembayaran Berhasil & Kartu Struk --}}
    <div
        id="modal-success-receipt"
        class="hidden fixed inset-0 z-50 bg-stone-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
        role="dialog"
        aria-modal="true">
        <div class="bg-stone-50 w-full max-w-md rounded-3xl p-4 sm:p-6 shadow-2xl border border-stone-200/80 my-auto text-stone-800">
            {{-- Header Sukses Rata Tengah --}}
            <div class="text-center mb-3">
                <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2.5 shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-stone-900">Pembayaran Berhasil!</h2>
                <p id="success-receipt-datetime-kasir" class="text-xs text-stone-500 font-medium mt-1">
                    {{ now()->translatedFormat('d M Y, H:i') }} WIB • Kasir: {{ $namaKasir }}
                </p>
            </div>

            {{-- Kartu Struk Putih (Yang akan dicetak pada window.print()) --}}
            <div id="printable-receipt-card" class="bg-white rounded-2xl shadow-sm border border-stone-200/80 p-4 sm:p-5 my-3.5 text-xs text-stone-800">
                {{-- Header Toko --}}
                <div class="text-center pb-2">
                    <h3 class="font-extrabold text-sm sm:text-base text-stone-900 tracking-wide uppercase">LANA FRIED CHICKEN</h3>
                    <p id="receipt-store-outlet" class="text-[11px] text-stone-500 mt-0.5">{{ $namaOutlet }}</p>
                    <p id="receipt-store-address" class="text-[10px] text-stone-400 leading-tight">{{ $alamatOutlet }}</p>
                    <div class="flex justify-center mt-2.5">
                        <span id="receipt-order-badge" class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200/70 font-bold text-[11px]">
                            No. Order: #TRX
                        </span>
                    </div>
                </div>

                {{-- Garis Putus-putus --}}
                <div class="border-t border-dashed border-stone-300 my-3"></div>

                {{-- Daftar Item Struk --}}
                <div id="receipt-items-container" class="space-y-2">
                    {{-- Dinamis via JS --}}
                </div>

                {{-- Garis Putus-putus --}}
                <div class="border-t border-dashed border-stone-300 my-3"></div>

                {{-- Rincian Pembayaran --}}
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-stone-500">Metode Bayar</span>
                        <span id="receipt-method-text" class="font-bold text-stone-900">TUNAI (Cash)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">Total Tagihan</span>
                        <span id="receipt-total-bill-text" class="font-extrabold text-stone-900">Rp 0</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">Uang Diterima</span>
                        <span id="receipt-received-text" class="font-bold text-stone-900">Rp 0</span>
                    </div>

                    {{-- Kotak Kembalian --}}
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2 flex justify-between items-center text-xs font-bold text-emerald-800 mt-2">
                        <span>Kembalian</span>
                        <span id="receipt-change-text" class="text-sm font-extrabold text-emerald-700">Rp 0</span>
                    </div>
                </div>

                {{-- Footer Struk --}}
                <p class="text-[10px] sm:text-[11px] text-stone-400 italic text-center mt-4 pt-2 border-t border-stone-100">
                    Terima kasih telah berbelanja di LFC!
                </p>
            </div>

            {{-- Tombol Aksi Bawah --}}
            <div class="space-y-2 mt-4">
                <button
                    type="button"
                    onclick="printReceipt()"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white border border-[#DC2626] text-[#DC2626] hover:bg-red-50 active:scale-[0.98] text-xs sm:text-sm font-bold shadow-xs transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>Cetak Ulang Struk</span>
                </button>

                <button
                    type="button"
                    onclick="startNewTransaction()"
                    class="w-full py-3.5 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.98] text-white text-xs sm:text-sm font-bold shadow-lg shadow-red-600/25 transition-all cursor-pointer">
                    + Transaksi Baru
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Kosongkan Keranjang --}}
    <div id="modal-confirm-empty-cart" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl max-w-xs w-full p-6 text-center shadow-2xl border border-stone-100">
            <div class="h-12 w-12 rounded-2xl bg-red-50 text-[#DC2626] flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-stone-900 mb-1.5">Kosongkan Keranjang?</h3>
            <p class="text-xs text-stone-500 leading-relaxed mb-5">
                Semua item pesanan yang dipilih akan dihapus dari keranjang.
            </p>
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" onclick="document.getElementById('modal-confirm-empty-cart').classList.add('hidden')" class="py-2.5 px-3 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="button" onclick="executeEmptyCart()" class="py-2.5 px-3 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white text-xs font-bold cursor-pointer">
                    Ya, Kosongkan
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Tutup Outlet --}}
    <div id="modal-confirm-close" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl max-w-xs w-full p-6 text-center shadow-2xl border border-stone-100">
            <div class="h-12 w-12 rounded-2xl bg-red-50 text-[#DC2626] flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-stone-900 mb-1.5">Tutup Sesi Outlet?</h3>
            <p class="text-xs text-stone-500 leading-relaxed mb-5">
                Sistem akan menghitung rekap penjualan harian (Tunai &amp; QRIS) dan menutup kasir hari ini.
            </p>
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" id="btn-cancel-close" class="py-2.5 px-3 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold cursor-pointer">
                    Batal
                </button>
                <button type="button" id="btn-confirm-close" class="py-2.5 px-3 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white text-xs font-bold cursor-pointer">
                    Ya, Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Rekap Harian Setelah Tutup Outlet --}}
    <div id="modal-recap" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-xs" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-stone-100">
            <div class="text-center mb-4">
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-stone-900">Outlet Berhasil Ditutup</h3>
                <p class="text-xs text-stone-500 mt-0.5">Rekap Penjualan Harian</p>
            </div>

            <div class="bg-stone-50 rounded-xl p-3.5 space-y-2 text-xs mb-5 border border-stone-100">
                <div class="flex justify-between">
                    <span class="text-stone-500">Total Transaksi</span>
                    <span id="recap-total-trx" class="font-bold text-stone-900">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Tunai (Laci Kasir)</span>
                    <span id="recap-tunai" class="font-bold text-stone-900">Rp 0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">QRIS (Non-Tunai)</span>
                    <span id="recap-qris" class="font-bold text-stone-900">Rp 0</span>
                </div>
                <div class="border-t border-stone-200 pt-2 flex justify-between text-sm">
                    <span class="font-bold text-stone-800">Total Penjualan</span>
                    <span id="recap-total-sales" class="font-extrabold text-[#DC2626]">Rp 0</span>
                </div>
            </div>

            <button type="button" id="btn-close-recap" class="w-full py-2.5 px-4 rounded-xl bg-stone-900 hover:bg-stone-800 text-white text-xs font-bold cursor-pointer">
                Selesai
            </button>
        </div>
    </div>

    {{-- Toast Container --}}
    <div id="pos-toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-xs pointer-events-none" aria-live="polite"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const cart = {}; // { [menuId]: { id, name, price, qty } }
        let isOutletOpen = {{ $isOutletOpen ? 'true' : 'false' }};
        let activeCategory = 'ALL';
        let selectedPaymentMethod = 'TUNAI'; // 'TUNAI' | 'QRIS'
        let qrisCountdownInterval = null;
        let qrisSecondsRemaining = 300; // 5 menit

        function formatRupiah(amount) {
            return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
        }

        function parseRawNumber(str) {
            return parseFloat(String(str || '0').replace(/[^0-9]/g, '')) || 0;
        }

        function showPosToast(message, type = 'error') {
            const container = document.getElementById('pos-toast-container');
            if (!container) return;

            const isError = type === 'error';
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-start gap-2 px-3.5 py-2.5 rounded-xl shadow-lg border text-xs font-semibold transition-all duration-200 ${
                isError
                    ? 'bg-red-50 text-red-800 border-red-200'
                    : 'bg-emerald-50 text-emerald-800 border-emerald-200'
            }`;
            toast.setAttribute('role', 'alert');
            toast.textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-1');
                setTimeout(() => toast.remove(), 220);
            }, 3200);
        }

        // 1. Filter Search & Kategori Real-time
        function filterMenus() {
            const searchInput = document.getElementById('menu-search');
            const query = (searchInput?.value || '').trim().toLowerCase();
            const cards = document.querySelectorAll('.menu-card');
            let visibleCount = 0;

            cards.forEach((card) => {
                const name = card.dataset.name || '';
                const category = card.dataset.category || '';
                const matchSearch = !query || name.includes(query);
                const matchCategory = activeCategory === 'ALL' || category === activeCategory;

                if (matchSearch && matchCategory) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            const emptyState = document.getElementById('menu-empty-search');
            if (emptyState) {
                emptyState.classList.toggle('hidden', visibleCount > 0 || cards.length === 0);
            }
        }

        document.getElementById('menu-search')?.addEventListener('input', filterMenus);

        document.querySelectorAll('.category-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                activeCategory = pill.dataset.category || 'ALL';

                document.querySelectorAll('.category-pill').forEach((btn) => {
                    const isActive = btn === pill;
                    btn.className = `category-pill shrink-0 px-4 py-2 rounded-full text-xs transition-all cursor-pointer ${
                        isActive
                            ? 'font-bold bg-[#DC2626] text-white shadow-sm shadow-red-600/20'
                            : 'font-semibold bg-white text-stone-600 border border-stone-200/80 hover:bg-stone-100'
                    }`;
                });

                filterMenus();
            });
        });

        // 2. Buka / Tutup Outlet via Fetch API
        function syncOutletUI(open) {
            isOutletOpen = open;
            const toggleBtn = document.getElementById('btn-toggle-outlet');
            const lockOverlay = document.getElementById('outlet-lock-overlay');

            if (toggleBtn) {
                toggleBtn.dataset.status = open ? 'BUKA' : 'TUTUP';
                toggleBtn.textContent = open ? 'OPEN' : 'TUTUP';
                toggleBtn.className = `px-3 py-1.5 rounded-full text-xs font-extrabold tracking-wide transition-all cursor-pointer ${
                    open
                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200/70'
                        : 'bg-red-100 text-[#DC2626] border border-red-200 hover:bg-red-200/70'
                }`;
            }

            if (lockOverlay) {
                lockOverlay.classList.toggle('hidden', open);
                lockOverlay.classList.toggle('flex', !open);
            }
        }

        async function openOutlet() {
            try {
                const res = await fetch('{{ route('outlet.buka') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    showPosToast(json.message || 'Gagal membuka outlet.', 'error');
                    return;
                }

                syncOutletUI(true);
                showPosToast(json.message || 'Outlet berhasil dibuka.', 'success');
            } catch (e) {
                showPosToast('Terjadi kesalahan jaringan saat membuka outlet.', 'error');
            }
        }

        async function closeOutlet() {
            const modalConfirm = document.getElementById('modal-confirm-close');
            try {
                const res = await fetch('{{ route('outlet.tutup') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const json = await res.json();
                modalConfirm?.classList.add('hidden');

                if (!res.ok || !json.success) {
                    showPosToast(json.message || 'Gagal menutup outlet.', 'error');
                    return;
                }

                syncOutletUI(false);

                const rekap = json.data?.rekap || {};
                document.getElementById('recap-total-trx').textContent = `${rekap.total_transaksi ?? 0} Transaksi`;
                document.getElementById('recap-tunai').textContent = formatRupiah(rekap.tunai?.total ?? 0);
                document.getElementById('recap-qris').textContent = formatRupiah(rekap.qris?.total ?? 0);
                document.getElementById('recap-total-sales').textContent = formatRupiah(rekap.total_rekap_penjualan ?? 0);

                document.getElementById('modal-recap')?.classList.remove('hidden');
            } catch (e) {
                modalConfirm?.classList.add('hidden');
                showPosToast('Terjadi kesalahan jaringan saat menutup outlet.', 'error');
            }
        }

        document.getElementById('btn-toggle-outlet')?.addEventListener('click', () => {
            if (!isOutletOpen) {
                openOutlet();
            } else {
                document.getElementById('modal-confirm-close')?.classList.remove('hidden');
            }
        });

        document.getElementById('btn-open-outlet-overlay')?.addEventListener('click', openOutlet);
        document.getElementById('btn-cancel-close')?.addEventListener('click', () => {
            document.getElementById('modal-confirm-close')?.classList.add('hidden');
        });
        document.getElementById('btn-confirm-close')?.addEventListener('click', closeOutlet);
        document.getElementById('btn-close-recap')?.addEventListener('click', () => {
            document.getElementById('modal-recap')?.classList.add('hidden');
        });

        // 3. Cek Stok & State Keranjang
        async function cekStokMenu(menuId, targetQty) {
            const res = await fetch('{{ route('transaksi.cek-stok') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    id_menu: menuId,
                    jumlah: targetQty,
                }),
            });

            const json = await res.json();
            return { ok: res.ok && json.success, json };
        }

        async function addToCart(menuId) {
            if (!isOutletOpen) {
                showPosToast('Silakan buka outlet terlebih dahulu.', 'error');
                return;
            }

            const card = document.querySelector(`.menu-card[data-id="${menuId}"]`);
            if (!card) return;

            const currentQty = cart[menuId]?.qty || 0;
            const nextQty = currentQty + 1;

            try {
                const { ok, json } = await cekStokMenu(menuId, nextQty);
                if (!ok) {
                    showPosToast(json.message || 'Stok Habis', 'error');
                    return;
                }

                cart[menuId] = {
                    id: menuId,
                    name: card.dataset.displayName || '',
                    price: parseFloat(card.dataset.price || '0'),
                    qty: nextQty,
                };

                renderCardAction(menuId);
                renderFloatingCart();
                renderCheckoutItemsIfOpen();
            } catch (e) {
                showPosToast('Gagal memeriksa ketersediaan stok.', 'error');
            }
        }

        function decreaseCart(menuId) {
            if (!cart[menuId]) return;

            if (cart[menuId].qty <= 1) {
                delete cart[menuId];
            } else {
                cart[menuId].qty -= 1;
            }

            renderCardAction(menuId);
            renderFloatingCart();
            renderCheckoutItemsIfOpen();
        }

        function renderCardAction(menuId) {
            const wrapper = document.querySelector(`.menu-action-wrapper[data-menu-id="${menuId}"]`);
            if (!wrapper) return;

            const qty = cart[menuId]?.qty || 0;

            if (qty <= 0) {
                wrapper.innerHTML = `
                    <button
                        type="button"
                        onclick="addToCart(${menuId})"
                        class="btn-add-menu w-full py-2 px-3 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.98] text-white text-xs font-bold shadow-sm shadow-red-600/20 transition-all cursor-pointer">
                        + Tambah
                    </button>
                `;
            } else {
                wrapper.innerHTML = `
                    <div class="flex items-center justify-between bg-stone-100 rounded-xl p-1 border border-stone-200/80">
                        <button
                            type="button"
                            onclick="decreaseCart(${menuId})"
                            class="h-7 w-8 rounded-lg bg-white hover:bg-stone-50 text-stone-800 font-extrabold text-sm shadow-xs flex items-center justify-center cursor-pointer"
                            aria-label="Kurangi porsi">-</button>
                        <span class="text-xs font-extrabold text-stone-900 px-2">${qty}</span>
                        <button
                            type="button"
                            onclick="addToCart(${menuId})"
                            class="h-7 w-8 rounded-lg bg-[#DC2626] hover:bg-[#B91C1C] text-white font-extrabold text-sm shadow-xs flex items-center justify-center cursor-pointer"
                            aria-label="Tambah porsi">+</button>
                    </div>
                `;
            }
        }

        function getCartSummary() {
            const items = Object.values(cart);
            const totalQty = items.reduce((sum, item) => sum + item.qty, 0);
            const totalPrice = items.reduce((sum, item) => sum + item.qty * item.price, 0);
            return { items, totalQty, totalPrice };
        }

        function renderFloatingCart() {
            const bar = document.getElementById('floating-cart-bar');
            const badge = document.getElementById('cart-badge-count');
            const countText = document.getElementById('cart-count-text');
            const totalText = document.getElementById('cart-total-text');

            const { totalQty, totalPrice } = getCartSummary();

            if (totalQty <= 0) {
                bar?.classList.add('hidden');
                return;
            }

            bar?.classList.remove('hidden');
            if (badge) badge.textContent = String(totalQty);
            if (countText) countText.textContent = `${totalQty} Pesanan`;
            if (totalText) totalText.textContent = formatRupiah(totalPrice);
        }

        function renderCheckoutItemsIfOpen() {
            const backdrop = document.getElementById('checkout-sheet-backdrop');
            if (backdrop && !backdrop.classList.contains('hidden')) {
                renderCheckoutSheetContent();
            }
        }

        // 4. Bottom Sheet: Ringkasan & Pembayaran (Tahap 2)
        function openCheckoutSheet() {
            const { items, totalPrice } = getCartSummary();
            if (items.length === 0) {
                showPosToast('Keranjang masih kosong.', 'error');
                return;
            }

            const backdrop = document.getElementById('checkout-sheet-backdrop');
            const container = document.getElementById('checkout-sheet-container');

            backdrop?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            setTimeout(() => {
                container?.classList.remove('translate-y-full');
            }, 10);

            // Default ke TUNAI
            selectPaymentMethod('TUNAI');
            renderCheckoutSheetContent();

            // Set default uang pas di input
            const cashInput = document.getElementById('cash-received-input');
            if (cashInput) {
                cashInput.value = formatNumber(totalPrice);
                recalculateCashChange();
            }
        }

        function closeCheckoutSheet() {
            const backdrop = document.getElementById('checkout-sheet-backdrop');
            const container = document.getElementById('checkout-sheet-container');

            container?.classList.add('translate-y-full');
            setTimeout(() => {
                backdrop?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 250);

            stopQrisCountdown();
        }

        function emptyCartConfirm() {
            document.getElementById('modal-confirm-empty-cart')?.classList.remove('hidden');
        }

        function executeEmptyCart() {
            document.getElementById('modal-confirm-empty-cart')?.classList.add('hidden');

            Object.keys(cart).forEach((id) => {
                delete cart[id];
                renderCardAction(id);
            });

            renderFloatingCart();
            closeCheckoutSheet();
            showPosToast('Keranjang telah dikosongkan.', 'success');
        }

        function renderCheckoutSheetContent() {
            const { items, totalPrice } = getCartSummary();
            const listEl = document.getElementById('checkout-items-list');
            const totalBillEl = document.getElementById('checkout-total-bill');

            if (totalBillEl) {
                totalBillEl.textContent = formatRupiah(totalPrice);
            }

            if (!listEl) return;

            if (items.length === 0) {
                closeCheckoutSheet();
                return;
            }

            listEl.innerHTML = items.map((item) => `
                <div class="flex justify-between items-start text-xs border-b border-stone-50 pb-2">
                    <div>
                        <p class="font-bold text-stone-900">${item.qty}x ${item.name}</p>
                        <p class="text-[11px] text-stone-400 mt-0.5">${formatRupiah(item.price)}</p>
                    </div>
                    <span class="font-bold text-stone-800">${formatRupiah(item.qty * item.price)}</span>
                </div>
            `).join('');

            recalculateCashChange();
        }

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;

            const btnCash = document.getElementById('btn-method-cash');
            const btnQris = document.getElementById('btn-method-qris');
            const cashSection = document.getElementById('payment-cash-section');
            const qrisSection = document.getElementById('payment-qris-section');
            const confirmBtn = document.getElementById('btn-confirm-transaction');
            const insufficientWarning = document.getElementById('cash-insufficient-warning');

            if (method === 'TUNAI') {
                btnCash.className = 'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20';
                btnQris.className = 'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-50';

                cashSection?.classList.remove('hidden');
                qrisSection?.classList.add('hidden');
                stopQrisCountdown();
                recalculateCashChange();
            } else {
                btnQris.className = 'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20';
                btnCash.className = 'flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-50';

                qrisSection?.classList.remove('hidden');
                cashSection?.classList.add('hidden');

                insufficientWarning?.classList.add('hidden');
                if (confirmBtn) confirmBtn.disabled = false;
                confirmBtn?.classList.remove('opacity-50', 'cursor-not-allowed');

                startQrisCountdown();
            }
        }

        function formatNumber(num) {
            return Number(num || 0).toLocaleString('id-ID');
        }

        function onCashInputChanged(input) {
            const raw = parseRawNumber(input.value);
            input.value = raw > 0 ? formatNumber(raw) : '';
            recalculateCashChange();
        }

        function fillExactCash() {
            const { totalPrice } = getCartSummary();
            const input = document.getElementById('cash-received-input');
            if (input) {
                input.value = formatNumber(totalPrice);
                recalculateCashChange();
            }
        }

        function recalculateCashChange() {
            if (selectedPaymentMethod !== 'TUNAI') return;

            const { totalPrice } = getCartSummary();
            const input = document.getElementById('cash-received-input');
            const changeBox = document.getElementById('cash-change-amount');
            const warningEl = document.getElementById('cash-insufficient-warning');
            const confirmBtn = document.getElementById('btn-confirm-transaction');

            const received = parseRawNumber(input?.value || '0');
            const change = received - totalPrice;

            if (received < totalPrice) {
                warningEl?.classList.remove('hidden');
                if (changeBox) changeBox.textContent = 'Rp 0';
                if (confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                warningEl?.classList.add('hidden');
                if (changeBox) changeBox.textContent = formatRupiah(change);
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        // 5. QRIS Countdown Timer (05:00)
        function startQrisCountdown() {
            stopQrisCountdown();
            qrisSecondsRemaining = 300; // 5 menit
            updateQrisTimerDisplay();

            qrisCountdownInterval = setInterval(() => {
                qrisSecondsRemaining--;
                if (qrisSecondsRemaining <= 0) {
                    stopQrisCountdown();
                    qrisSecondsRemaining = 0;
                }
                updateQrisTimerDisplay();
            }, 1000);
        }

        function stopQrisCountdown() {
            if (qrisCountdownInterval) {
                clearInterval(qrisCountdownInterval);
                qrisCountdownInterval = null;
            }
        }

        function updateQrisTimerDisplay() {
            const timerEl = document.getElementById('qris-countdown-timer');
            if (!timerEl) return;

            const m = Math.floor(qrisSecondsRemaining / 60);
            const s = qrisSecondsRemaining % 60;
            timerEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        // 6. Submit Transaksi ke Backend (`POST /transaksi/bayar`)
        async function submitTransaksi() {
            const { items, totalPrice } = getCartSummary();
            if (items.length === 0) {
                showPosToast('Keranjang masih kosong.', 'error');
                return;
            }

            let nominalBayar = totalPrice;

            if (selectedPaymentMethod === 'TUNAI') {
                const cashInput = document.getElementById('cash-received-input');
                nominalBayar = parseRawNumber(cashInput?.value || '0');

                if (nominalBayar < totalPrice) {
                    showPosToast('Uang diterima masih kurang dari total tagihan.', 'error');
                    return;
                }
            } else {
                // QRIS: nominal bayar pas sama dengan total
                nominalBayar = totalPrice;
            }

            const payload = {
                items: items.map((item) => ({
                    id_menu: item.id,
                    jumlah: item.qty,
                })),
                metode_pembayaran: selectedPaymentMethod,
                nominal_bayar: nominalBayar,
            };

            const confirmBtn = document.getElementById('btn-confirm-transaction');
            const btnText = document.getElementById('btn-confirm-pay-text');
            const btnSpinner = document.getElementById('btn-confirm-pay-spinner');

            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.classList.add('opacity-90', 'cursor-not-allowed');
                btnText?.classList.add('hidden');
                btnSpinner?.classList.remove('hidden');
                btnSpinner?.classList.add('flex');
            }

            try {
                const res = await fetch('{{ route('transaksi.bayar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const json = await res.json();

                if (!res.ok || !json.success) {
                    showPosToast(json.message || json.detail || 'Gagal memproses transaksi.', 'error');
                    return;
                }

                // Sukses: Tutup checkout sheet dan tampilkan modal struk sukses
                closeCheckoutSheet();
                displaySuccessReceipt(json.data?.struk);
            } catch (e) {
                showPosToast('Terjadi kesalahan jaringan saat memproses pembayaran.', 'error');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.classList.remove('opacity-90', 'cursor-not-allowed');
                    btnText?.classList.remove('hidden');
                    btnSpinner?.classList.add('hidden');
                    btnSpinner?.classList.remove('flex');
                }
            }
        }

        // 7. Tampilan Modal Struk Sukses
        function displaySuccessReceipt(struk) {
            if (!struk) return;

            // Subtitle Tanggal & Jam & Kasir
            const datetimeKasirEl = document.getElementById('success-receipt-datetime-kasir');
            if (datetimeKasirEl) {
                const kasirName = struk.kasir?.nama_lengkap || struk.kasir?.username || '{{ $namaKasir }}';
                datetimeKasirEl.textContent = `${struk.tanggal_waktu || ''} WIB • Kasir: ${kasirName}`;
            }

            // Outlet info
            const outletEl = document.getElementById('receipt-store-outlet');
            const addressEl = document.getElementById('receipt-store-address');
            if (outletEl && struk.outlet?.nama_outlet) outletEl.textContent = struk.outlet.nama_outlet;
            if (addressEl && struk.outlet?.alamat) addressEl.textContent = struk.outlet.alamat;

            // No Order
            const orderBadge = document.getElementById('receipt-order-badge');
            if (orderBadge) {
                orderBadge.textContent = `No. Order: #${struk.kode_transaksi || ''}`;
            }

            // Items list
            const itemsContainer = document.getElementById('receipt-items-container');
            if (itemsContainer && Array.isArray(struk.items)) {
                itemsContainer.innerHTML = struk.items.map((it) => `
                    <div class="flex justify-between items-start text-xs">
                        <span class="font-bold text-stone-900">${it.jumlah}x ${it.nama_menu}</span>
                        <span class="font-extrabold text-stone-900">${formatRupiah(it.subtotal)}</span>
                    </div>
                `).join('');
            }

            // Payment summary
            const methodEl = document.getElementById('receipt-method-text');
            const totalBillEl = document.getElementById('receipt-total-bill-text');
            const receivedEl = document.getElementById('receipt-received-text');
            const changeEl = document.getElementById('receipt-change-text');

            if (methodEl) {
                methodEl.textContent = struk.metode_pembayaran === 'TUNAI' ? 'TUNAI (Cash)' : 'QRIS';
            }
            if (totalBillEl) {
                totalBillEl.textContent = formatRupiah(struk.total_harga);
            }
            if (receivedEl) {
                receivedEl.textContent = formatRupiah(struk.nominal_bayar);
            }
            if (changeEl) {
                if (struk.metode_pembayaran === 'QRIS') {
                    changeEl.textContent = 'Rp 0 (LUNAS)';
                } else {
                    changeEl.textContent = formatRupiah(struk.kembalian);
                }
            }

            // Tampilkan Modal Sukses
            const modal = document.getElementById('modal-success-receipt');
            modal?.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function printReceipt() {
            window.print();
        }

        function startNewTransaction() {
            // Kosongkan keranjang & reload halaman agar stok terupdate
            Object.keys(cart).forEach((id) => delete cart[id]);
            window.location.reload();
        }

        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sheet = document.getElementById('checkout-sheet-backdrop');
                if (sheet && !sheet.classList.contains('hidden')) {
                    closeCheckoutSheet();
                }
            }
        });
    </script>
</body>

</html>
