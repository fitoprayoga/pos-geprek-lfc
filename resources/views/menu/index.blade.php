@extends('layouts.owner')

@section('title', 'Kelola Menu & Harga')
@section('page_title', 'Kelola Menu & Harga')
@section('page_subtitle', 'Atur katalog menu, harga jual, status POS & komposisi resep per outlet')
@section('notif_badge_count', $totalAlert ?? 0)

@push('head')
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
@endpush

{{-- Dropdown Outlet di Header Mobile --}}
@section('mobile_header_actions')
    <div class="relative max-w-[165px]">
        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 h-2 w-2 rounded-full bg-[#DC2626]"></span>
        <select
            id="mobile-outlet-select"
            aria-label="Pilih Outlet Cabang"
            class="outlet-selector w-full appearance-none rounded-full border border-stone-200 bg-white pl-6 pr-6 py-1.5 text-xs font-bold text-stone-800 shadow-2xs focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 truncate cursor-pointer min-h-[38px]"
        >
            @foreach ($outlets as $index => $outlet)
                <option value="{{ $outlet->id }}" @selected((int) $selected_outlet_id === (int) $outlet->id)>
                    Outlet {{ $index + 1 }} - {{ str_replace('LFC Cabang ', '', $outlet->nama_outlet) }}
                </option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </div>
@endsection

{{-- Aksi Header Desktop/Tablet --}}
@section('header_actions')
    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-2.5 w-2.5 rounded-full bg-[#DC2626]"></span>
        <select
            id="desktop-outlet-select"
            aria-label="Pilih Outlet Cabang"
            class="outlet-selector appearance-none rounded-full border border-stone-200 bg-white pl-7 pr-8 py-2 text-xs sm:text-sm font-bold text-stone-800 shadow-2xs hover:border-stone-300 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 cursor-pointer min-h-[44px]"
        >
            @foreach ($outlets as $index => $outlet)
                <option value="{{ $outlet->id }}" @selected((int) $selected_outlet_id === (int) $outlet->id)>
                    Outlet {{ $index + 1 }} - {{ str_replace('LFC Cabang ', '', $outlet->nama_outlet) }}
                </option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </div>
@endsection

@section('content')
    <div class="space-y-4 pb-28 md:pb-24">
        {{-- Banner Info Outlet Aktif & Ringkasan Katalog --}}
        <section class="flex flex-wrap items-center justify-between gap-2 bg-white rounded-2xl border border-stone-100 px-4 py-2.5 shadow-2xs">
            <div class="flex items-center gap-2 min-w-0">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <p class="text-xs text-stone-600 truncate">
                    Stok &amp; resep aktif untuk:
                    <span id="active-outlet-label" class="font-bold text-stone-900">
                        {{ $outlets->firstWhere('id', $selected_outlet_id)?->nama_outlet ?? 'Outlet 1' }}
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2 text-[11px] font-semibold">
                <span id="summary-total-menu" class="px-2.5 py-1 rounded-full bg-stone-100 text-stone-700">
                    {{ $menus->count() }} Menu
                </span>
                <span id="summary-aktif-menu" class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                    {{ $menus->where('status_aktif', true)->count() }} Aktif
                </span>
            </div>
        </section>

        {{-- Search Bar (Sesuai Tampilan Kelola Menu) --}}
        <div class="relative flex items-center bg-white rounded-2xl shadow-2xs border border-stone-200/80">
            <svg class="w-4 h-4 text-stone-400 absolute left-4 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                type="search"
                id="menu-search-input"
                placeholder="Cari nama menu..."
                class="w-full bg-transparent pl-10 pr-4 py-3 text-sm text-stone-800 placeholder-stone-400 rounded-2xl focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[46px]"
            >
        </div>

        {{-- Filter Pills Kategori: Semua, Ayam Geprek, All Sambal, Minuman --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1" id="menu-category-filters" role="tablist" aria-label="Filter Kategori Menu">
            <button
                type="button"
                data-category="ALL"
                class="menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20 min-h-[38px]"
            >
                Semua
            </button>
            <button
                type="button"
                data-category="AYAM_GEPREK"
                class="menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-100 min-h-[38px]"
            >
                Ayam Geprek
            </button>
            <button
                type="button"
                data-category="ALL_SAMBAL"
                class="menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-100 min-h-[38px]"
            >
                All Sambal
            </button>
            <button
                type="button"
                data-category="MINUMAN"
                class="menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-100 min-h-[38px]"
            >
                Minuman
            </button>
        </div>

        {{-- Daftar / Tabel Katalog Menu & Resep --}}
        <section aria-label="Daftar Katalog Menu">
            {{-- Tampilan Kartu Khusus Mobile (< md) --}}
            <div class="block md:hidden">
                <div id="menu-card-list" class="grid grid-cols-1 gap-3"></div>
            </div>

            {{-- Tampilan Tabel Katalog Khusus Desktop (>= md) --}}
            <div class="hidden md:block">
                <div id="menu-table-wrapper" class="bg-white rounded-2xl border border-stone-200/80 shadow-2xs overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-stone-50/80 border-b border-stone-100 text-[11px] font-bold uppercase tracking-wider text-stone-500">
                                <th class="py-3.5 px-4">Menu</th>
                                <th class="py-3.5 px-3 whitespace-nowrap">Kategori</th>
                                <th class="py-3.5 px-3 whitespace-nowrap min-w-[140px]">Harga Jual</th>
                                <th class="py-3.5 px-3">Komposisi Resep (Stok Outlet)</th>
                                <th class="py-3.5 px-3 text-center whitespace-nowrap">Status POS</th>
                                <th class="py-3.5 px-4 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="menu-table-body" class="divide-y divide-stone-100 text-sm"></tbody>
                    </table>
                </div>
            </div>

            {{-- Empty State --}}
            <div id="menu-empty-state" class="hidden bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-2xs">
                <div class="mx-auto h-12 w-12 rounded-2xl bg-red-50 text-[#DC2626] flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-stone-800">Menu tidak ditemukan</p>
                <p class="text-xs text-stone-500 mt-1">Coba kata kunci lain atau klik "+ Tambah Menu Baru" untuk menambah menu.</p>
            </div>
        </section>
    </div>

    {{-- Tombol Fixed + Tambah Menu Baru di Bagian Bawah (Tetap/Tidak Ikut Scroll di HP, Tablet & Desktop) --}}
    <div class="fixed bottom-[64px] md:bottom-0 inset-x-0 md:left-56 lg:left-60 z-20 px-4 sm:px-6 py-3 bg-gradient-to-t from-stone-50 via-stone-50/95 to-transparent pointer-events-none">
        <div class="max-w-6xl mx-auto">
            <button
                type="button"
                data-open-add-menu
                class="pointer-events-auto w-full flex items-center justify-center gap-2 py-3.5 px-5 rounded-2xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-extrabold shadow-lg shadow-red-600/25 transition-all cursor-pointer min-h-[48px]"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Menu Baru</span>
            </button>
        </div>
    </div>
@endsection

@push('modals')
    {{-- 1. Bottom Sheet Notifikasi Stok --}}
    @include('partials.notif-stok-sheet')

    {{-- 2. Modal Tambah / Edit Menu & Resep (Full-screen di Mobile, Dialog Tengah di Tablet/Desktop) --}}
    <div
        id="menu-form-modal"
        class="fixed inset-0 z-40 hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="menu-modal-title"
    >
        <div id="menu-modal-backdrop" class="fixed inset-0 bg-stone-950/50 backdrop-blur-[1px]"></div>

        <div class="fixed inset-0 flex items-end md:items-center justify-center md:p-4 pointer-events-none">
            <div class="pointer-events-auto w-full h-full md:h-auto md:max-h-[90vh] md:max-w-xl bg-stone-50 md:rounded-3xl shadow-2xl border border-stone-200 flex flex-col overflow-hidden">
                {{-- Header Modal --}}
                <header class="bg-white border-b border-stone-100 px-4 py-3 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button
                            type="button"
                            id="btn-close-menu-modal"
                            class="inline-flex items-center justify-center h-10 w-10 rounded-full hover:bg-stone-100 text-stone-700 transition-colors cursor-pointer shrink-0"
                            aria-label="Kembali ke daftar menu"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </button>
                        <h2 id="menu-modal-title" class="text-base sm:text-lg font-extrabold text-stone-900 truncate">
                            Tambah Menu &amp; Resep
                        </h2>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-stone-200 bg-white text-xs font-bold text-stone-800 shrink-0 max-w-[175px]">
                        <span class="h-2 w-2 rounded-full bg-[#DC2626] shrink-0"></span>
                        <span id="modal-outlet-badge" class="truncate">Outlet 1</span>
                    </div>
                </header>

                {{-- Body Form Scrollable --}}
                <form id="menu-form" class="flex-1 overflow-y-auto p-4 space-y-4">
                    <input type="hidden" id="form-menu-id" value="">

                    {{-- Card 1: Foto, Nama, Kategori, Harga, Status POS --}}
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-2xs p-4 space-y-4">
                        {{-- Upload / Preview Foto --}}
                        <div class="flex items-center gap-4">
                            <div id="menu-photo-preview-box" class="h-20 w-20 rounded-2xl bg-gradient-to-br from-orange-100 via-amber-50 to-red-50 border border-stone-200 overflow-hidden flex items-center justify-center shrink-0">
                                <img id="menu-photo-preview-img" src="" alt="Preview Foto Menu" class="hidden w-full h-full object-cover">
                                <div id="menu-photo-placeholder" class="flex flex-col items-center justify-center text-[#DC2626]">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <input type="file" id="menu-foto-input" accept="image/jpeg,image/png" class="hidden">
                                <button
                                    type="button"
                                    id="btn-pick-photo"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-full border border-dashed border-red-300 bg-red-50/40 hover:bg-red-50 text-[#DC2626] text-xs font-extrabold transition-colors cursor-pointer min-h-[40px]"
                                >
                                    Ganti Foto Menu
                                </button>
                                <p id="menu-foto-hint" class="text-[11px] text-stone-400 mt-1.5">
                                    Format JPG/PNG, maks 2MB
                                </p>
                            </div>
                        </div>

                        {{-- Nama Menu --}}
                        <div>
                            <label for="input-nama-menu" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                Nama Menu
                            </label>
                            <input
                                type="text"
                                id="input-nama-menu"
                                name="nama_menu"
                                required
                                maxlength="100"
                                placeholder="Contoh: Ayam Geprek Sambal Matah"
                                class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-stone-900 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[44px]"
                            >
                        </div>

                        {{-- Kategori & Harga Jual --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="input-kategori-menu" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                    Kategori
                                </label>
                                <div class="relative">
                                    <select
                                        id="input-kategori-menu"
                                        name="kategori"
                                        required
                                        class="w-full appearance-none rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 pr-9 text-sm font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 cursor-pointer min-h-[44px]"
                                    >
                                        <option value="Ayam Geprek">Ayam Geprek</option>
                                        <option value="All Sambal">All Sambal</option>
                                        <option value="Minuman">Minuman</option>
                                        <option value="PAKET">Paket</option>
                                        <option value="TAMBAHAN">Tambahan</option>
                                    </select>
                                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>

                            <div>
                                <label for="input-harga-menu" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                    Harga Jual
                                </label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-3.5 text-sm font-extrabold text-stone-900 pointer-events-none">Rp</span>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        id="input-harga-menu"
                                        name="harga"
                                        required
                                        placeholder="17.000"
                                        class="w-full rounded-xl border border-stone-200 bg-white pl-10 pr-3.5 py-2.5 text-sm font-extrabold text-stone-900 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[44px]"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- Toggle Tampilkan di Kasir (POS) --}}
                        <div class="pt-1 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-stone-800">Tampilkan di Kasir (POS)</p>
                                <p class="text-[11px] text-stone-400">Aktifkan agar langsung muncul di layar kasir</p>
                            </div>
                            <button
                                type="button"
                                id="toggle-status-pos"
                                role="switch"
                                aria-checked="true"
                                data-active="1"
                                class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-emerald-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                            >
                                <span class="pointer-events-none inline-block h-6 w-6 translate-x-5 rounded-full bg-white shadow-sm transition duration-200"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Card 2: Komposisi Resep Bahan Baku --}}
                    <div class="bg-white rounded-2xl border border-stone-100 shadow-2xs p-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-extrabold text-stone-900">Komposisi Resep Bahan Baku</h3>
                            <span id="resep-count-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 text-xs font-extrabold">
                                0 Bahan
                            </span>
                        </div>

                        <p class="text-xs text-stone-500 leading-relaxed">
                            Stok bahan baku di bawah otomatis berkurang per 1 porsi terjual.
                        </p>

                        {{-- List Bahan Baku yang Sudah Ditambahkan --}}
                        <div id="modal-resep-list" class="space-y-2"></div>

                        {{-- Empty Hint Saat Resep Belum Ada --}}
                        <div id="modal-resep-empty" class="rounded-xl bg-stone-50 border border-stone-100 px-3.5 py-3 text-center text-xs text-stone-500">
                            Belum ada bahan baku di resep. Klik tombol di bawah untuk menambahkan bahan.
                        </div>

                        {{-- Tombol + Tambah Bahan Baku --}}
                        <button
                            type="button"
                            id="btn-open-ingredient-sheet"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-dashed border-red-300 bg-red-50/30 hover:bg-red-50 text-[#DC2626] text-xs sm:text-sm font-extrabold transition-colors cursor-pointer min-h-[44px]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Tambah Bahan Baku</span>
                        </button>
                    </div>
                </form>

                {{-- Footer Modal: Tombol Hapus (Mode Edit) & Simpan --}}
                <div class="bg-white border-t border-stone-100 px-4 py-3 shrink-0 flex items-center gap-2.5" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
                    <button
                        type="button"
                        id="btn-delete-menu"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-3.5 rounded-2xl border border-red-200 bg-red-50 hover:bg-red-100 active:scale-[0.99] text-[#DC2626] text-sm font-extrabold transition-all cursor-pointer min-h-[48px] shrink-0"
                        style="display:none"
                        aria-label="Hapus menu ini"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        <span>Hapus</span>
                    </button>

                    <button
                        type="submit"
                        form="menu-form"
                        id="btn-submit-menu"
                        class="flex-1 inline-flex items-center justify-center gap-2 py-3.5 px-5 rounded-2xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-extrabold shadow-lg shadow-red-600/20 transition-all cursor-pointer min-h-[48px]"
                    >
                        <svg id="submit-menu-spinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="btn-submit-menu-text">Simpan Menu &amp; Resep</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Overlay Pilih Bahan Baku (Bottom Sheet Mobile / Modal Dialog Desktop) --}}
    <div
        id="ingredient-sheet-modal"
        class="fixed inset-0 z-[60] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ingredient-sheet-title"
    >
        <div id="ingredient-sheet-backdrop" class="fixed inset-0 bg-stone-950/55 backdrop-blur-[1px]"></div>

        <div class="fixed inset-x-0 bottom-0 md:inset-0 flex items-end md:items-center justify-center md:p-4 pointer-events-none">
            <div
                class="pointer-events-auto w-full md:max-w-md bg-white rounded-t-3xl md:rounded-3xl shadow-2xl border border-stone-100 flex flex-col max-h-[90vh] overflow-hidden"
                style="padding-bottom: env(safe-area-inset-bottom);"
            >
                {{-- Drag Handle Mobile --}}
                <div class="flex justify-center pt-2.5 pb-1 md:hidden">
                    <span class="h-1 w-10 rounded-full bg-stone-300"></span>
                </div>

                {{-- Header Sheet --}}
                <div class="px-5 pt-2 pb-3 flex items-center justify-between gap-3">
                    <h3 id="ingredient-sheet-title" class="text-lg font-extrabold text-stone-900">
                        Pilih Bahan Baku
                    </h3>
                    <button
                        type="button"
                        id="btn-close-ingredient-sheet"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors cursor-pointer"
                        aria-label="Tutup pilih bahan baku"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Form Pilih Bahan Baku --}}
                <div class="px-5 pb-5 space-y-4 overflow-y-auto">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <label for="select-ingredient-name" class="block text-xs font-semibold text-stone-600">
                                Nama Bahan Baku
                            </label>
                            <button
                                type="button"
                                id="btn-toggle-new-bahan"
                                class="text-[11px] font-bold text-[#DC2626] hover:underline cursor-pointer"
                            >
                                + Bahan Baru
                            </button>
                        </div>

                        <div class="relative">
                            <select
                                id="select-ingredient-name"
                                class="w-full appearance-none rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 pr-9 text-sm font-bold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 cursor-pointer min-h-[46px]"
                            ></select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>

                        {{-- Status Ketersediaan Stok di Outlet Terpilih --}}
                        <p id="ingredient-stock-status" class="mt-2 text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span id="ingredient-stock-text">Stok tersedia di outlet: -</span>
                        </p>
                    </div>

                    {{-- Panel Mini Inisialisasi Bahan Baku Baru (Sync ke DB bahan_bakus) --}}
                    <div id="new-bahan-panel" class="hidden rounded-2xl border border-amber-200 bg-amber-50/70 p-3.5 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-extrabold text-stone-900">Inisialisasi Bahan Baku Baru</p>
                            <button type="button" id="btn-cancel-new-bahan" class="text-[11px] font-bold text-stone-500 hover:text-stone-800 cursor-pointer">
                                Tutup
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="sm:col-span-2">
                                <label for="new-bahan-nama" class="block text-[11px] font-semibold text-stone-600 mb-1">Nama Bahan</label>
                                <input
                                    type="text"
                                    id="new-bahan-nama"
                                    placeholder="Contoh: Cabai Rawit / Tepung Bumbu Crispy"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none min-h-[40px]"
                                >
                            </div>

                            <div>
                                <label for="new-bahan-satuan" class="block text-[11px] font-semibold text-stone-600 mb-1">Satuan</label>
                                <select
                                    id="new-bahan-satuan"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none min-h-[40px]"
                                >
                                    <option value="kg">kg (Kilogram)</option>
                                    <option value="gram">gram (Gram)</option>
                                    <option value="liter">liter (Liter)</option>
                                    <option value="ml">ml (Mililiter)</option>
                                    <option value="potong">potong (Potong)</option>
                                    <option value="porsi">porsi (Porsi)</option>
                                    <option value="pcs">pcs (Pcs)</option>
                                    <option value="ekor">ekor (Ekor)</option>
                                    <option value="bungkus">bungkus (Bungkus)</option>
                                    <option value="sachet">sachet (Sachet)</option>
                                    <option value="botol">botol (Botol)</option>
                                    <option value="ikat">ikat (Ikat)</option>
                                    <option value="sdm">sdm (Sendok Makan)</option>
                                </select>
                            </div>

                            <div>
                                <label for="new-bahan-stok" class="block text-[11px] font-semibold text-stone-600 mb-1">Stok Awal Outlet</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="new-bahan-stok"
                                    value="10"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none min-h-[40px]"
                                >
                            </div>
                        </div>

                        {{-- Preset Cepat Bahan Populer --}}
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" data-preset-nama="Cabai Rawit" data-preset-satuan="kg" class="preset-bahan-btn px-2.5 py-1 rounded-full bg-white border border-amber-200 text-[11px] font-semibold text-stone-700 hover:border-[#DC2626] hover:text-[#DC2626] cursor-pointer">
                                + Cabai Rawit (kg)
                            </button>
                            <button type="button" data-preset-nama="Tepung Bumbu Crispy" data-preset-satuan="kg" class="preset-bahan-btn px-2.5 py-1 rounded-full bg-white border border-amber-200 text-[11px] font-semibold text-stone-700 hover:border-[#DC2626] hover:text-[#DC2626] cursor-pointer">
                                + Tepung Crispy (kg)
                            </button>
                            <button type="button" data-preset-nama="Teh" data-preset-satuan="pcs" class="preset-bahan-btn px-2.5 py-1 rounded-full bg-white border border-amber-200 text-[11px] font-semibold text-stone-700 hover:border-[#DC2626] hover:text-[#DC2626] cursor-pointer">
                                + Teh (pcs)
                            </button>
                            <button type="button" data-preset-nama="Gula Pasir" data-preset-satuan="kg" class="preset-bahan-btn px-2.5 py-1 rounded-full bg-white border border-amber-200 text-[11px] font-semibold text-stone-700 hover:border-[#DC2626] hover:text-[#DC2626] cursor-pointer">
                                + Gula Pasir (kg)
                            </button>
                        </div>

                        <button
                            type="button"
                            id="btn-save-new-bahan"
                            class="w-full py-2.5 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-red-950 text-xs font-extrabold transition-colors cursor-pointer min-h-[40px]"
                        >
                            Simpan &amp; Sinkronkan ke Bahan Baku
                        </button>
                    </div>

                    {{-- Takaran per Porsi & Satuan --}}
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-7">
                            <label for="input-ingredient-qty" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                Takaran per Porsi
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                id="input-ingredient-qty"
                                value="0.05"
                                class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-sm font-extrabold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[46px]"
                            >
                        </div>

                        <div class="col-span-5">
                            <label for="select-ingredient-unit" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                Satuan
                            </label>
                            <div class="relative">
                                <select
                                    id="select-ingredient-unit"
                                    class="w-full appearance-none rounded-xl border border-stone-300 bg-white px-3 py-2.5 pr-8 text-sm font-bold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 cursor-pointer min-h-[46px]"
                                >
                                    <option value="kg">kg (Kilogram)</option>
                                    <option value="gram">gram (Gram)</option>
                                    <option value="liter">L (Liter)</option>
                                    <option value="ml">ml (Mililiter)</option>
                                    <option value="potong">potong</option>
                                    <option value="porsi">porsi</option>
                                    <option value="pcs">pcs (Buah)</option>
                                    <option value="ekor">ekor</option>
                                    <option value="bungkus">bungkus</option>
                                    <option value="sachet">sachet</option>
                                    <option value="botol">botol</option>
                                    <option value="ikat">ikat</option>
                                    <option value="sdm">sdm</option>
                                </select>
                                <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Estimasi HPP Bahan --}}
                    <div class="rounded-xl bg-stone-50 border border-stone-100 px-4 py-3 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-stone-500">Estimasi HPP Bahan:</span>
                        <span id="ingredient-hpp-estimate" class="text-sm font-extrabold text-stone-900">Rp 0 / porsi</span>
                    </div>

                    {{-- Tombol Aksi Overlay --}}
                    <div class="grid grid-cols-12 gap-2.5 pt-1">
                        <button
                            type="button"
                            id="btn-cancel-ingredient-sheet"
                            class="col-span-4 py-3 px-4 rounded-xl border border-stone-200 bg-white hover:bg-stone-50 text-stone-800 text-sm font-bold transition-colors cursor-pointer min-h-[46px]"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            id="btn-add-ingredient-to-recipe"
                            class="col-span-8 py-3 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-extrabold shadow-md shadow-red-600/20 transition-all cursor-pointer min-h-[46px]"
                        >
                            Tambahkan ke Resep
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Modal Konfirmasi Hapus Menu --}}
    <div
        id="delete-menu-confirm-modal"
        class="fixed inset-0 z-[70] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-confirm-title"
    >
        <div id="delete-confirm-backdrop" class="fixed inset-0 bg-stone-950/55 backdrop-blur-[1px]"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="pointer-events-auto w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-stone-100 p-6 text-center space-y-4">
                <div class="mx-auto h-14 w-14 rounded-full bg-red-100 text-[#DC2626] flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </div>

                <div>
                    <h3 id="delete-confirm-title" class="text-base sm:text-lg font-extrabold text-stone-900">
                        Hapus Menu Ini?
                    </h3>
                    <p id="delete-confirm-desc" class="text-xs sm:text-sm text-stone-500 mt-1 leading-relaxed">
                        Menu beserta resepnya akan dihapus dari katalog.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <button
                        type="button"
                        id="btn-cancel-delete-menu"
                        class="py-3 px-4 rounded-xl border border-stone-200 bg-white hover:bg-stone-50 text-stone-800 text-sm font-bold transition-colors cursor-pointer min-h-[44px]"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        id="btn-confirm-delete-menu"
                        class="inline-flex items-center justify-center gap-1.5 py-3 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white text-sm font-extrabold shadow-md shadow-red-600/20 transition-colors cursor-pointer min-h-[44px]"
                    >
                        <svg id="delete-menu-spinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Modal Sukses Card Putih (Tambah / Edit Menu) --}}
    <div
        id="menu-success-card-modal"
        class="fixed inset-0 z-[75] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="success-card-title"
    >
        <div id="success-card-backdrop" class="fixed inset-0 bg-stone-950/55 backdrop-blur-[2px]"></div>
        <div class="fixed inset-0 flex items-center justify-center p-6 pointer-events-none">
            <div class="pointer-events-auto w-full max-w-sm bg-white rounded-3xl shadow-2xl px-6 pt-8 pb-6 text-center space-y-5">
                {{-- Lingkaran Hijau Besar --}}
                <div class="mx-auto h-24 w-24 rounded-full bg-emerald-100 flex items-center justify-center">
                    <div class="h-16 w-16 rounded-full bg-emerald-200/70 flex items-center justify-center">
                        <svg class="w-9 h-9 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
                {{-- Judul & Deskripsi --}}
                <div class="space-y-2">
                    <h3 id="success-card-title" class="text-lg font-extrabold text-stone-900 leading-tight">
                        Menu &amp; Resep Berhasil Ditambahkan!
                    </h3>
                    <p id="success-card-desc" class="text-sm text-stone-500 leading-relaxed">
                        Menu baru tersedia di katalog.
                    </p>
                </div>
                {{-- Tombol Hijau Rounded Full --}}
                <button
                    type="button"
                    id="btn-close-success-card"
                    class="w-full py-3.5 px-5 rounded-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.99] text-white text-sm font-extrabold shadow-md shadow-emerald-500/25 transition-all cursor-pointer min-h-[48px]"
                >
                    Lihat di Katalog Menu
                </button>
            </div>
        </div>
    </div>

    {{-- 6. Pop-up Snackbar Gelap dengan Checklist Hijau (Bahan berhasil ditambahkan dll) --}}
    <div
        id="dark-check-toast"
        class="fixed top-6 left-1/2 -translate-x-1/2 z-[80] hidden pointer-events-none px-4 w-full max-w-sm flex justify-center"
        role="status"
        aria-live="polite"
    >
        <div class="pointer-events-auto inline-flex items-center gap-3 rounded-xl bg-[#1E1E1E] text-white px-4 py-3 shadow-2xl border border-white/10 transition-all duration-200">
            <span class="inline-flex h-6 w-6 rounded-full bg-[#15803D] text-white items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </span>
            <span id="dark-check-toast-text" class="text-sm font-medium tracking-tight text-white">
                Menu berhasil ditambahkan
            </span>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const routes = {
                storeMenu: @json(route('menu.store')),
                updateHargaTemplate: @json(route('menu.update-harga', ['id' => '__ID__'])),
                toggleStatusTemplate: @json(route('menu.status', ['id' => '__ID__'])),
                destroyMenuTemplate: @json(route('menu.destroy', ['id' => '__ID__'])),
                storeBahanBaku: @json(route('menu.bahan-baku.store')),
            };

            let stateMenus = @json($menus);
            const stateOutlets = @json($outlets);
            let stateBahanBakus = @json($bahan_bakus);
            let selectedOutletId = Number(@json($selected_outlet_id)) || (stateOutlets[0]?.id || 1);

            let activeCategory = 'ALL';
            let searchQuery = '';

            // State Modal Menu
            let editingMenuId = null;
            let modalStatusAktif = true;
            let modalPhotoFile = null;
            let modalRecipe = []; // [{ id_bahan, nama_bahan, satuan, jumlah_dibutuhkan }]
            let editingRecipeIndex = null;

            // Referensi harga satuan fallback (Rp per satuan) jika belum ada histori restock
            const fallbackHargaSatuan = {
                'ayam potong': 14000,
                'beras': 14500,
                'minyak goreng': 17000,
                'sambal bawang': 35000,
                'sambal korek': 35000,
                'cabai rawit': 45000,
                'tepung bumbu crispy': 18000,
                'gula pasir': 17000,
                'teh': 1200,
            };

            // Elemen DOM Utama
            const outletSelectors = document.querySelectorAll('.outlet-selector');
            const activeOutletLabel = document.getElementById('active-outlet-label');
            const modalOutletBadge = document.getElementById('modal-outlet-badge');
            const summaryTotalMenu = document.getElementById('summary-total-menu');
            const summaryAktifMenu = document.getElementById('summary-aktif-menu');
            const searchInput = document.getElementById('menu-search-input');
            const catPills = document.querySelectorAll('.menu-cat-pill');
            const cardListEl = document.getElementById('menu-card-list');
            const tableWrapperEl = document.getElementById('menu-table-wrapper');
            const tableBodyEl = document.getElementById('menu-table-body');
            const emptyStateEl = document.getElementById('menu-empty-state');

            // Elemen Modal Menu
            const menuModal = document.getElementById('menu-form-modal');
            const menuModalBackdrop = document.getElementById('menu-modal-backdrop');
            const menuModalTitle = document.getElementById('menu-modal-title');
            const menuForm = document.getElementById('menu-form');
            const formMenuId = document.getElementById('form-menu-id');
            const inputNamaMenu = document.getElementById('input-nama-menu');
            const inputKategoriMenu = document.getElementById('input-kategori-menu');
            const inputHargaMenu = document.getElementById('input-harga-menu');
            const toggleStatusPos = document.getElementById('toggle-status-pos');
            const menuFotoInput = document.getElementById('menu-foto-input');
            const btnPickPhoto = document.getElementById('btn-pick-photo');
            const menuPhotoPreviewImg = document.getElementById('menu-photo-preview-img');
            const menuPhotoPlaceholder = document.getElementById('menu-photo-placeholder');
            const menuFotoHint = document.getElementById('menu-foto-hint');
            const modalResepList = document.getElementById('modal-resep-list');
            const modalResepEmpty = document.getElementById('modal-resep-empty');
            const resepCountBadge = document.getElementById('resep-count-badge');
            const btnDeleteMenu = document.getElementById('btn-delete-menu');
            const btnSubmitMenu = document.getElementById('btn-submit-menu');
            const btnSubmitMenuText = document.getElementById('btn-submit-menu-text');
            const submitMenuSpinner = document.getElementById('submit-menu-spinner');

            // Elemen Overlay Pilih Bahan Baku
            const ingredientSheet = document.getElementById('ingredient-sheet-modal');
            const ingredientSheetBackdrop = document.getElementById('ingredient-sheet-backdrop');
            const selectIngredientName = document.getElementById('select-ingredient-name');
            const ingredientStockStatus = document.getElementById('ingredient-stock-status');
            const ingredientStockText = document.getElementById('ingredient-stock-text');
            const inputIngredientQty = document.getElementById('input-ingredient-qty');
            const selectIngredientUnit = document.getElementById('select-ingredient-unit');
            const ingredientHppEstimate = document.getElementById('ingredient-hpp-estimate');
            const btnAddIngredientToRecipe = document.getElementById('btn-add-ingredient-to-recipe');

            // Elemen Tambah Bahan Baku Baru
            const btnToggleNewBahan = document.getElementById('btn-toggle-new-bahan');
            const btnCancelNewBahan = document.getElementById('btn-cancel-new-bahan');
            const newBahanPanel = document.getElementById('new-bahan-panel');
            const newBahanNama = document.getElementById('new-bahan-nama');
            const newBahanSatuan = document.getElementById('new-bahan-satuan');
            const newBahanStok = document.getElementById('new-bahan-stok');
            const btnSaveNewBahan = document.getElementById('btn-save-new-bahan');

            // Elemen Konfirmasi Hapus Menu & Pop-up Snackbar Gelap Checklist Hijau
            const deleteConfirmModal = document.getElementById('delete-menu-confirm-modal');
            const deleteConfirmBackdrop = document.getElementById('delete-confirm-backdrop');
            const deleteConfirmDesc = document.getElementById('delete-confirm-desc');
            const btnCancelDeleteMenu = document.getElementById('btn-cancel-delete-menu');
            const btnConfirmDeleteMenu = document.getElementById('btn-confirm-delete-menu');
            const deleteMenuSpinner = document.getElementById('delete-menu-spinner');

            const darkCheckToast = document.getElementById('dark-check-toast');
            const darkCheckToastText = document.getElementById('dark-check-toast-text');

            // ─── Modal Sukses Card ───────────────────────────────────────────
            const successCardModal     = document.getElementById('menu-success-card-modal');
            const successCardBackdrop  = document.getElementById('success-card-backdrop');
            const successCardTitle     = document.getElementById('success-card-title');
            const successCardDesc      = document.getElementById('success-card-desc');
            const btnCloseSuccessCard  = document.getElementById('btn-close-success-card');
            let darkToastTimer = null;

            // ─── Helpers ──────────────────────────────────────────────────
            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatRupiah(amount) {
                const num = Number(amount) || 0;
                return 'Rp ' + Math.round(num).toLocaleString('id-ID');
            }

            function formatAngka(amount) {
                const num = Number(amount) || 0;
                return Number.isInteger(num)
                    ? String(num)
                    : num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            }

            function parseHargaInput(raw) {
                const digits = String(raw || '').replace(/[^\d]/g, '');
                return digits ? Number(digits) : 0;
            }

            function formatUnitDisplay(satuan) {
                const s = String(satuan || '').trim().toLowerCase();
                const map = {
                    'liter': 'L (Liter)',
                    'l': 'L (Liter)',
                    'kg': 'kg (Kilogram)',
                    'gram': 'gr (Gram)',
                    'gr': 'gr (Gram)',
                    'potong': 'potong (Potong)',
                    'porsi': 'porsi (Porsi)',
                    'pcs': 'pcs (Pcs)',
                };
                return map[s] || (satuan ? `${satuan}` : '-');
            }

            function formatUnitShort(satuan) {
                const s = String(satuan || '').trim().toLowerCase();
                if (s === 'liter') return 'L';
                return satuan || '';
            }

            function getOutletLabel(outletId) {
                const idx = stateOutlets.findIndex(o => Number(o.id) === Number(outletId));
                if (idx === -1) return 'Outlet';
                const outlet = stateOutlets[idx];
                return `Outlet ${idx + 1} - ${String(outlet.nama_outlet || '').replace('LFC Cabang ', '')}`;
            }

            function getBahanForSelectedOutlet() {
                return stateBahanBakus
                    .filter(b => Number(b.outlet_id) === Number(selectedOutletId))
                    .sort((a, b) => String(a.nama_bahan).localeCompare(String(b.nama_bahan)));
            }

            function resolveBahanInSelectedOutlet(resepItem) {
                const outletBahans = getBahanForSelectedOutlet();
                const direct = outletBahans.find(b => Number(b.id) === Number(resepItem.id_bahan));
                if (direct) return direct;

                const nama = resepItem.bahan_baku?.nama_bahan || resepItem.nama_bahan;
                if (!nama) return null;

                return outletBahans.find(
                    b => String(b.nama_bahan).trim().toLowerCase() === String(nama).trim().toLowerCase()
                ) || null;
            }

            function estimateUnitPrice(bahan) {
                if (!bahan) return 0;
                if (bahan.harga_satuan_terakhir !== null && bahan.harga_satuan_terakhir !== undefined) {
                    return Number(bahan.harga_satuan_terakhir) || 0;
                }
                const key = String(bahan.nama_bahan || '').trim().toLowerCase();
                return fallbackHargaSatuan[key] || 0;
            }

            function matchesCategoryFilter(menu, filterKey) {
                if (filterKey === 'ALL') return true;
                const kat = String(menu.kategori || '').toLowerCase();
                const nama = String(menu.nama_menu || '').toLowerCase();

                if (filterKey === 'AYAM_GEPREK') {
                    const isDrink = kat.includes('minum') || nama.includes('es teh') || nama.includes('jus');
                    const isPureSambal = kat.includes('sambal') || (kat === 'tambahan' && nama.includes('sambal'));
                    return !isDrink && !isPureSambal && (kat.includes('geprek') || kat.includes('ayam') || kat.includes('paket') || nama.includes('ayam') || nama.includes('geprek'));
                }

                if (filterKey === 'ALL_SAMBAL') {
                    return kat.includes('sambal') || nama.includes('sambal');
                }

                if (filterKey === 'MINUMAN') {
                    return kat.includes('minum') || nama.includes('teh') || nama.includes('es ') || nama.includes('jus') || nama.includes('kopi') || nama.includes('air');
                }

                return true;
            }

            function getFilteredMenus() {
                const q = searchQuery.trim().toLowerCase();
                return stateMenus.filter(menu => {
                    const matchSearch = !q || String(menu.nama_menu || '').toLowerCase().includes(q)
                        || String(menu.kategori || '').toLowerCase().includes(q);
                    const matchCat = matchesCategoryFilter(menu, activeCategory);
                    return matchSearch && matchCat;
                });
            }

            function buildThumbnailHtml(menu) {
                const isAktif = Boolean(menu.status_aktif);
                const kat = String(menu.kategori || '').toLowerCase();
                const nama = String(menu.nama_menu || '').toLowerCase();
                const isDrink = kat.includes('minum') || nama.includes('teh') || nama.includes('es ');
                const isSambal = kat.includes('sambal') || nama.includes('sambal');
                const inactiveClass = isAktif ? '' : 'opacity-45 grayscale';

                if (menu.foto_url) {
                    return `
                        <div class="h-16 w-16 rounded-xl overflow-hidden bg-stone-100 border border-stone-100 shrink-0 ${inactiveClass}">
                            <img src="${escapeHtml(menu.foto_url)}" alt="${escapeHtml(menu.nama_menu)}" class="w-full h-full object-cover">
                        </div>
                    `;
                }

                const bgGrad = isDrink
                    ? 'from-amber-100 via-orange-50 to-yellow-100 text-amber-600'
                    : (isSambal
                        ? 'from-red-100 via-orange-100 to-rose-50 text-[#DC2626]'
                        : 'from-orange-100 via-amber-50 to-red-50 text-[#DC2626]');

                const svgIcon = isDrink
                    ? `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5" /></svg>`
                    : `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" /></svg>`;

                return `
                    <div class="h-16 w-16 rounded-xl bg-gradient-to-br ${bgGrad} border border-stone-100 flex items-center justify-center shrink-0 ${inactiveClass}">
                        ${svgIcon}
                    </div>
                `;
            }

            function buildResepSummary(menu) {
                const items = Array.isArray(menu.resep_menus) ? menu.resep_menus : [];
                if (!items.length) return { text: 'Belum ada komposisi resep', hasLowStock: false };

                let hasLowStock = false;
                const parts = items.map(item => {
                    const bahanOutlet = resolveBahanInSelectedOutlet(item);
                    const nama = bahanOutlet?.nama_bahan || item.bahan_baku?.nama_bahan || 'Bahan';
                    const qty = formatAngka(item.jumlah_dibutuhkan);
                    const unit = formatUnitShort(bahanOutlet?.satuan || item.bahan_baku?.satuan || '');

                    if (bahanOutlet && Number(bahanOutlet.stok) < Number(item.jumlah_dibutuhkan)) {
                        hasLowStock = true;
                    }

                    return `${qty}${unit ? ' ' + unit : ''} ${nama}`;
                });

                return {
                    text: 'Resep: ' + parts.join(', '),
                    hasLowStock,
                };
            }

            // ─── Render Daftar Menu (Card Mobile/Tablet & Tabel Desktop) ──
            function renderMenuCatalog() {
                const filtered = getFilteredMenus();
                const totalCount = stateMenus.length;
                const activeCount = stateMenus.filter(m => Boolean(m.status_aktif)).length;

                if (summaryTotalMenu) summaryTotalMenu.textContent = `${totalCount} Menu`;
                if (summaryAktifMenu) summaryAktifMenu.textContent = `${activeCount} Aktif`;

                if (filtered.length === 0) {
                    cardListEl.innerHTML = '';
                    tableBodyEl.innerHTML = '';
                    tableWrapperEl.classList.add('hidden');
                    emptyStateEl.classList.remove('hidden');
                    return;
                }

                emptyStateEl.classList.add('hidden');
                tableWrapperEl.classList.remove('hidden');

                // 1. Kartu Mobile/Tablet (Matching tampilan Kelola menu.png)
                cardListEl.innerHTML = filtered.map(menu => {
                    const isAktif = Boolean(menu.status_aktif);
                    const resepInfo = buildResepSummary(menu);

                    return `
                        <article class="bg-white rounded-2xl border border-stone-100 shadow-2xs p-3.5 flex items-center justify-between gap-3 transition-all ${isAktif ? '' : 'bg-stone-50/70'}">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                ${buildThumbnailHtml(menu)}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm sm:text-base font-extrabold leading-snug truncate ${isAktif ? 'text-stone-900' : 'text-stone-400'}">
                                            ${escapeHtml(menu.nama_menu)}
                                        </h3>
                                        ${!isAktif ? `<span class="inline-flex items-center px-2 py-0.5 rounded-md bg-red-50 text-[#DC2626] text-[10px] font-extrabold shrink-0">Nonaktif</span>` : ''}
                                    </div>
                                    <p class="text-sm font-extrabold mt-0.5 whitespace-nowrap ${isAktif ? 'text-[#DC2626]' : 'text-stone-400'}">
                                        ${formatRupiah(menu.harga)}
                                    </p>
                                    <p class="text-xs mt-0.5 line-clamp-2 ${isAktif ? (resepInfo.hasLowStock ? 'text-amber-700 font-semibold' : 'text-stone-500') : 'text-stone-400'}">
                                        ${isAktif ? escapeHtml(resepInfo.text) : 'Nonaktif (Disembunyikan di POS)'}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col items-end justify-between gap-2.5 shrink-0">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="${isAktif ? 'true' : 'false'}"
                                    aria-label="Toggle status ${escapeHtml(menu.nama_menu)}"
                                    onclick="window.toggleMenuStatus(${menu.id}, this)"
                                    class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-200 ${isAktif ? 'bg-emerald-500' : 'bg-stone-200'}"
                                >
                                    <span class="pointer-events-none inline-block h-6 w-6 rounded-full bg-white shadow-sm transition duration-200 ${isAktif ? 'translate-x-5' : 'translate-x-0'}"></span>
                                </button>

                                <button
                                    type="button"
                                    onclick="window.openEditMenuModal(${menu.id})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-bold transition-colors cursor-pointer min-h-[34px] ${isAktif ? 'border-stone-200 bg-white hover:bg-stone-50 text-stone-800' : 'border-stone-200 bg-stone-100/70 text-stone-400 hover:text-stone-700'}"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                        </article>
                    `;
                }).join('');

                // 2. Tabel Desktop
                tableBodyEl.innerHTML = filtered.map(menu => {
                    const isAktif = Boolean(menu.status_aktif);
                    const items = Array.isArray(menu.resep_menus) ? menu.resep_menus : [];
                    const resepBadges = items.map(item => {
                        const bahanOutlet = resolveBahanInSelectedOutlet(item);
                        const nama = bahanOutlet?.nama_bahan || item.bahan_baku?.nama_bahan || 'Bahan';
                        const qty = formatAngka(item.jumlah_dibutuhkan);
                        const unit = formatUnitShort(bahanOutlet?.satuan || item.bahan_baku?.satuan || '');
                        const kurang = bahanOutlet && Number(bahanOutlet.stok) < Number(item.jumlah_dibutuhkan);

                        return `<span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold ${kurang ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-stone-100 text-stone-700'}">${escapeHtml(nama)}: ${qty} ${escapeHtml(unit)}</span>`;
                    }).join(' ');

                    return `
                        <tr class="hover:bg-stone-50/70 transition-colors ${isAktif ? '' : 'bg-stone-50/50'}">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    ${buildThumbnailHtml(menu)}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-extrabold ${isAktif ? 'text-stone-900' : 'text-stone-400'}">${escapeHtml(menu.nama_menu)}</p>
                                            ${!isAktif ? `<span class="px-2 py-0.5 rounded-md bg-red-50 text-[#DC2626] text-[10px] font-extrabold">Nonaktif</span>` : ''}
                                        </div>
                                        ${!isAktif ? `<p class="text-[11px] text-stone-400 mt-0.5">Disembunyikan di POS</p>` : ''}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-stone-100 text-stone-700 text-xs font-bold">
                                    ${escapeHtml(menu.kategori)}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-extrabold whitespace-nowrap min-w-[140px] ${isAktif ? 'text-[#DC2626]' : 'text-stone-400'}">
                                ${formatRupiah(menu.harga)}
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="flex flex-wrap gap-1.5 max-w-md">
                                    ${resepBadges || '<span class="text-xs text-stone-400">-</span>'}
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked="${isAktif ? 'true' : 'false'}"
                                    aria-label="Toggle status ${escapeHtml(menu.nama_menu)}"
                                    onclick="window.toggleMenuStatus(${menu.id}, this)"
                                    class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-200 ${isAktif ? 'bg-emerald-500' : 'bg-stone-200'}"
                                >
                                    <span class="pointer-events-none inline-block h-6 w-6 rounded-full bg-white shadow-sm transition duration-200 ${isAktif ? 'translate-x-5' : 'translate-x-0'}"></span>
                                </button>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button
                                    type="button"
                                    onclick="window.openEditMenuModal(${menu.id})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-stone-200 bg-white hover:bg-stone-50 text-xs font-bold text-stone-800 transition-colors cursor-pointer min-h-[38px]"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // ─── Sinkronisasi Outlet Selector (5 Cabang) ──────────────────
            function syncOutletSelection(newOutletId) {
                selectedOutletId = Number(newOutletId);
                outletSelectors.forEach(sel => {
                    sel.value = String(selectedOutletId);
                });

                const outletObj = stateOutlets.find(o => Number(o.id) === selectedOutletId);
                if (activeOutletLabel && outletObj) {
                    activeOutletLabel.textContent = outletObj.nama_outlet;
                }
                if (modalOutletBadge) {
                    modalOutletBadge.textContent = getOutletLabel(selectedOutletId);
                }

                // Remap modalRecipe id_bahan ke bahan baku milik outlet terpilih (matching nama_bahan)
                if (modalRecipe.length > 0) {
                    const outletBahans = getBahanForSelectedOutlet();
                    modalRecipe = modalRecipe.map(item => {
                        const matched = outletBahans.find(
                            b => String(b.nama_bahan).trim().toLowerCase() === String(item.nama_bahan).trim().toLowerCase()
                        );
                        return matched ? { ...item, id_bahan: matched.id, satuan: matched.satuan } : item;
                    });
                    renderModalRecipeList();
                }

                // Update URL param tanpa reload
                const url = new URL(window.location.href);
                url.searchParams.set('outlet_id', String(selectedOutletId));
                window.history.replaceState({}, '', url.toString());

                renderMenuCatalog();
            }

            outletSelectors.forEach(sel => {
                sel.addEventListener('change', function () {
                    syncOutletSelection(this.value);
                });
            });

            // ─── Pencarian & Filter Kategori ──────────────────────────────
            searchInput.addEventListener('input', function () {
                searchQuery = this.value || '';
                renderMenuCatalog();
            });

            catPills.forEach(pill => {
                pill.addEventListener('click', function () {
                    activeCategory = this.dataset.category || 'ALL';
                    catPills.forEach(btn => {
                        const isSelected = btn.dataset.category === activeCategory;
                        btn.className = isSelected
                            ? 'menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm shadow-red-600/20 min-h-[38px]'
                            : 'menu-cat-pill shrink-0 px-4 py-2 rounded-full text-xs font-semibold transition-all cursor-pointer bg-white text-stone-700 border border-stone-200 hover:bg-stone-100 min-h-[38px]';
                    });
                    renderMenuCatalog();
                });
            });

            // ─── Toggle Status Aktif/Nonaktif via PATCH /menu/{id}/status ─
            window.toggleMenuStatus = async function (menuId, buttonEl) {
                const menu = stateMenus.find(m => Number(m.id) === Number(menuId));
                if (!menu) return;

                const nextStatus = !Boolean(menu.status_aktif);
                buttonEl.disabled = true;

                try {
                    const url = routes.toggleStatusTemplate.replace('__ID__', String(menuId));
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ status_aktif: nextStatus }),
                    });

                    const json = await response.json();
                    if (!response.ok || !json.success) {
                        window.showOwnerToast(json.message || 'Gagal mengubah status menu.', 'error');
                        return;
                    }

                    menu.status_aktif = Boolean(json.data.status_aktif);
                    renderMenuCatalog();
                    window.showOwnerToast(json.message, 'success');
                } catch (err) {
                    window.showOwnerToast('Terjadi gangguan koneksi saat mengubah status menu.', 'error');
                } finally {
                    buttonEl.disabled = false;
                }
            };

            // ─── Modal Tambah / Edit Menu & Resep ─────────────────────────
            function setStatusSwitchUI(isActive) {
                modalStatusAktif = Boolean(isActive);
                toggleStatusPos.setAttribute('aria-checked', modalStatusAktif ? 'true' : 'false');
                toggleStatusPos.className = `relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-200 ${modalStatusAktif ? 'bg-emerald-500' : 'bg-stone-200'}`;
                const knob = toggleStatusPos.querySelector('span');
                if (knob) {
                    knob.className = `pointer-events-none inline-block h-6 w-6 rounded-full bg-white shadow-sm transition duration-200 ${modalStatusAktif ? 'translate-x-5' : 'translate-x-0'}`;
                }
            }

            toggleStatusPos.addEventListener('click', function () {
                setStatusSwitchUI(!modalStatusAktif);
            });

            function setPhotoPreview(url) {
                if (url) {
                    menuPhotoPreviewImg.src = url;
                    menuPhotoPreviewImg.classList.remove('hidden');
                    menuPhotoPlaceholder.classList.add('hidden');
                } else {
                    menuPhotoPreviewImg.src = '';
                    menuPhotoPreviewImg.classList.add('hidden');
                    menuPhotoPlaceholder.classList.remove('hidden');
                }
            }

            btnPickPhoto.addEventListener('click', function () {
                menuFotoInput.click();
            });

            menuFotoInput.addEventListener('change', function () {
                const file = this.files?.[0];
                if (!file) return;

                if (file.size > 2 * 1024 * 1024) {
                    window.showOwnerToast('Ukuran foto maksimal 2MB.', 'error');
                    this.value = '';
                    return;
                }

                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    window.showOwnerToast('Format foto harus JPG atau PNG.', 'error');
                    this.value = '';
                    return;
                }

                modalPhotoFile = file;
                menuFotoHint.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (e) {
                    setPhotoPreview(e.target?.result || '');
                };
                reader.readAsDataURL(file);
            });

            inputHargaMenu.addEventListener('input', function () {
                const numeric = parseHargaInput(this.value);
                this.value = numeric > 0 ? numeric.toLocaleString('id-ID') : '';
            });

            function renderModalRecipeList() {
                resepCountBadge.textContent = `${modalRecipe.length} Bahan`;

                if (modalRecipe.length === 0) {
                    modalResepList.innerHTML = '';
                    modalResepEmpty.classList.remove('hidden');
                    return;
                }

                modalResepEmpty.classList.add('hidden');
                modalResepList.innerHTML = modalRecipe.map((item, idx) => `
                    <div class="rounded-xl bg-stone-50 border border-stone-100 px-3.5 py-2.5 flex items-center justify-between gap-2">
                        <span class="text-sm font-bold text-stone-800 truncate">${escapeHtml(item.nama_bahan)}</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                onclick="window.editRecipeItem(${idx})"
                                class="px-3 py-1 rounded-full bg-white border border-stone-200 hover:border-stone-300 text-xs font-extrabold text-stone-800 cursor-pointer"
                                title="Klik untuk ubah takaran"
                            >
                                ${formatAngka(item.jumlah_dibutuhkan)} ${escapeHtml(formatUnitShort(item.satuan))}
                            </button>
                            <button
                                type="button"
                                onclick="window.removeRecipeItem(${idx})"
                                class="inline-flex items-center justify-center h-8 w-8 rounded-full hover:bg-red-50 text-[#DC2626] transition-colors cursor-pointer"
                                aria-label="Hapus ${escapeHtml(item.nama_bahan)} dari resep"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            window.removeRecipeItem = function (idx) {
                modalRecipe.splice(idx, 1);
                renderModalRecipeList();
            };

            window.editRecipeItem = function (idx) {
                const item = modalRecipe[idx];
                if (!item) return;
                editingRecipeIndex = idx;
                openIngredientSheet(item.id_bahan, item.jumlah_dibutuhkan, item.satuan);
            };

            function openAddMenuModal() {
                editingMenuId = null;
                formMenuId.value = '';
                menuModalTitle.textContent = 'Tambah Menu & Resep';
                btnSubmitMenuText.textContent = 'Simpan Menu & Resep';
                btnDeleteMenu.style.display = 'none';
                modalOutletBadge.textContent = getOutletLabel(selectedOutletId);

                inputNamaMenu.value = '';
                inputKategoriMenu.value = 'Ayam Geprek';
                inputHargaMenu.value = '';
                setStatusSwitchUI(true);

                modalPhotoFile = null;
                menuFotoInput.value = '';
                menuFotoHint.textContent = 'Format JPG/PNG, maks 2MB';
                setPhotoPreview(null);

                modalRecipe = [];
                renderModalRecipeList();

                menuModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            window.openEditMenuModal = function (menuId) {
                const menu = stateMenus.find(m => Number(m.id) === Number(menuId));
                if (!menu) return;

                editingMenuId = menu.id;
                formMenuId.value = String(menu.id);
                menuModalTitle.textContent = 'Edit Menu & Resep';
                btnSubmitMenuText.textContent = 'Simpan Perubahan';
                btnDeleteMenu.style.display = 'inline-flex';
                modalOutletBadge.textContent = getOutletLabel(selectedOutletId);

                inputNamaMenu.value = menu.nama_menu || '';

                // Pastikan nilai kategori ada di select (tambah sementara jika custom)
                const katValue = menu.kategori || 'Ayam Geprek';
                const hasOpt = Array.from(inputKategoriMenu.options).some(opt => opt.value === katValue);
                if (!hasOpt) {
                    const opt = document.createElement('option');
                    opt.value = katValue;
                    opt.textContent = katValue;
                    inputKategoriMenu.appendChild(opt);
                }
                inputKategoriMenu.value = katValue;

                const hargaNum = Number(menu.harga) || 0;
                inputHargaMenu.value = hargaNum > 0 ? Math.round(hargaNum).toLocaleString('id-ID') : '0';
                setStatusSwitchUI(Boolean(menu.status_aktif));

                modalPhotoFile = null;
                menuFotoInput.value = '';
                menuFotoHint.textContent = 'Format JPG/PNG, maks 2MB';
                setPhotoPreview(menu.foto_url || null);

                // Petakan resep ke id_bahan outlet terpilih
                modalRecipe = (menu.resep_menus || []).map(rm => {
                    const resolved = resolveBahanInSelectedOutlet(rm);
                    return {
                        id_bahan: resolved?.id || rm.id_bahan,
                        nama_bahan: resolved?.nama_bahan || rm.bahan_baku?.nama_bahan || 'Bahan Baku',
                        satuan: resolved?.satuan || rm.bahan_baku?.satuan || '',
                        jumlah_dibutuhkan: Number(rm.jumlah_dibutuhkan) || 0,
                    };
                });
                renderModalRecipeList();

                menuModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            function closeMenuModal() {
                menuModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            document.querySelectorAll('[data-open-add-menu]').forEach(btn => {
                btn.addEventListener('click', openAddMenuModal);
            });
            document.getElementById('btn-close-menu-modal').addEventListener('click', closeMenuModal);
            menuModalBackdrop.addEventListener('click', closeMenuModal);

            // ─── Overlay Pilih Bahan Baku ─────────────────────────────────
            function populateIngredientDropdown(preferredId = null) {
                const bahans = getBahanForSelectedOutlet();

                selectIngredientName.innerHTML = bahans.map(b => `
                    <option value="${b.id}">${escapeHtml(b.nama_bahan)}</option>
                `).join('') + `<option value="__NEW__">+ Tambah Bahan Baku Baru...</option>`;

                if (preferredId && bahans.some(b => Number(b.id) === Number(preferredId))) {
                    selectIngredientName.value = String(preferredId);
                } else if (bahans.length > 0) {
                    // Pilih yang belum ada di modalRecipe jika memungkinkan
                    const unused = bahans.find(b => !modalRecipe.some(r => Number(r.id_bahan) === Number(b.id)));
                    selectIngredientName.value = String((unused || bahans[0]).id);
                } else {
                    selectIngredientName.value = '__NEW__';
                }

                updateSelectedIngredientInfo();
            }

            function setIngredientUnitSelectValue(satuan) {
                const raw = String(satuan || '').trim();
                const lower = raw.toLowerCase();
                const normalized = lower === 'l' ? 'liter' : (lower === 'gr' ? 'gram' : (raw || 'kg'));

                const matchedOpt = Array.from(selectIngredientUnit.options).find(
                    opt => opt.value.toLowerCase() === normalized.toLowerCase()
                );

                if (matchedOpt) {
                    selectIngredientUnit.value = matchedOpt.value;
                } else {
                    const customOpt = document.createElement('option');
                    customOpt.value = normalized;
                    customOpt.textContent = normalized;
                    selectIngredientUnit.appendChild(customOpt);
                    selectIngredientUnit.value = normalized;
                }
            }

            function updateSelectedIngredientInfo(preferredUnit = null) {
                const val = selectIngredientName.value;
                if (val === '__NEW__') {
                    newBahanPanel.classList.remove('hidden');
                    ingredientStockText.textContent = 'Isi nama & satuan bahan baku baru di bawah';
                    ingredientStockStatus.className = 'mt-2 text-xs font-semibold text-amber-700 flex items-center gap-1.5';
                    setIngredientUnitSelectValue(preferredUnit || newBahanSatuan.value);
                    ingredientHppEstimate.textContent = 'Rp 0 / porsi';
                    return;
                }

                newBahanPanel.classList.add('hidden');
                const bahan = getBahanForSelectedOutlet().find(b => Number(b.id) === Number(val));
                if (!bahan) return;

                const stok = Number(bahan.stok) || 0;
                const minimal = Number(bahan.stok_minimal) || 0;
                const activeUnit = preferredUnit || bahan.satuan;
                const unitShort = formatUnitShort(activeUnit);

                ingredientStockText.textContent = `Stok tersedia di outlet: ${formatAngka(stok)} ${unitShort}`;
                if (stok <= 0) {
                    ingredientStockStatus.className = 'mt-2 text-xs font-semibold text-[#DC2626] flex items-center gap-1.5';
                } else if (stok <= minimal) {
                    ingredientStockStatus.className = 'mt-2 text-xs font-semibold text-amber-600 flex items-center gap-1.5';
                } else {
                    ingredientStockStatus.className = 'mt-2 text-xs font-semibold text-emerald-600 flex items-center gap-1.5';
                }

                setIngredientUnitSelectValue(activeUnit);

                // Default takaran nyaman sesuai satuan saat memilih bahan baru (bukan edit)
                if (editingRecipeIndex === null && document.activeElement !== inputIngredientQty) {
                    const s = String(activeUnit || '').toLowerCase();
                    if (s === 'potong' || s === 'porsi' || s === 'pcs' || s === 'ekor' || s === 'bungkus' || s === 'sachet' || s === 'botol' || s === 'ikat') {
                        inputIngredientQty.value = '1';
                    } else if (s === 'gram' || s === 'gr' || s === 'ml') {
                        inputIngredientQty.value = '50';
                    } else {
                        inputIngredientQty.value = '0.05';
                    }
                }

                updateHppEstimate(bahan);
            }

            function updateHppEstimate(bahanArg = null) {
                const bahan = bahanArg || getBahanForSelectedOutlet().find(b => Number(b.id) === Number(selectIngredientName.value));
                const qty = Number(inputIngredientQty.value) || 0;
                const unitPrice = estimateUnitPrice(bahan);
                const est = Math.round(qty * unitPrice);
                ingredientHppEstimate.textContent = est > 0 ? `${formatRupiah(est)} / porsi` : 'Rp 0 / porsi';
            }

            function openIngredientSheet(preferredBahanId = null, initialQty = null, preferredUnit = null) {
                newBahanPanel.classList.add('hidden');
                populateIngredientDropdown(preferredBahanId);
                if (preferredUnit) {
                    setIngredientUnitSelectValue(preferredUnit);
                }
                if (initialQty !== null) {
                    inputIngredientQty.value = String(initialQty);
                    updateHppEstimate();
                }
                ingredientSheet.classList.remove('hidden');
            }

            function closeIngredientSheet() {
                editingRecipeIndex = null;
                ingredientSheet.classList.add('hidden');
            }

            document.getElementById('btn-open-ingredient-sheet').addEventListener('click', function () {
                editingRecipeIndex = null;
                openIngredientSheet();
            });
            document.getElementById('btn-close-ingredient-sheet').addEventListener('click', closeIngredientSheet);
            document.getElementById('btn-cancel-ingredient-sheet').addEventListener('click', closeIngredientSheet);
            ingredientSheetBackdrop.addEventListener('click', closeIngredientSheet);

            selectIngredientName.addEventListener('change', () => updateSelectedIngredientInfo());
            inputIngredientQty.addEventListener('input', () => updateHppEstimate());

            // Inisialisasi Bahan Baku Baru dari Overlay
            btnToggleNewBahan.addEventListener('click', function () {
                newBahanPanel.classList.toggle('hidden');
                if (!newBahanPanel.classList.contains('hidden')) {
                    newBahanNama.focus();
                }
            });
            btnCancelNewBahan.addEventListener('click', function () {
                newBahanPanel.classList.add('hidden');
                if (selectIngredientName.value === '__NEW__' && selectIngredientName.options.length > 1) {
                    selectIngredientName.selectedIndex = 0;
                    updateSelectedIngredientInfo();
                }
            });

            newBahanSatuan.addEventListener('change', function () {
                if (selectIngredientName.value === '__NEW__' || !newBahanPanel.classList.contains('hidden')) {
                    setIngredientUnitSelectValue(this.value);
                }
            });

            document.querySelectorAll('.preset-bahan-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    newBahanNama.value = this.dataset.presetNama || '';
                    newBahanSatuan.value = this.dataset.presetSatuan || 'kg';
                    setIngredientUnitSelectValue(newBahanSatuan.value);
                });
            });

            btnSaveNewBahan.addEventListener('click', async function () {
                const nama = newBahanNama.value.trim();
                const satuan = newBahanSatuan.value;
                const stok = Number(newBahanStok.value) || 0;

                if (!nama) {
                    window.showOwnerToast('Nama bahan baku baru wajib diisi.', 'error');
                    newBahanNama.focus();
                    return;
                }

                btnSaveNewBahan.disabled = true;
                btnSaveNewBahan.textContent = 'Menyimpan...';

                try {
                    const response = await fetch(routes.storeBahanBaku, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            outlet_id: selectedOutletId,
                            nama_bahan: nama,
                            satuan: satuan,
                            stok: stok,
                            stok_minimal: Math.max(1, Math.round(stok * 0.2)),
                            sync_all_outlets: true,
                        }),
                    });

                    const json = await response.json();
                    if (!response.ok || !json.success) {
                        window.showOwnerToast(json.message || 'Gagal menambahkan bahan baku.', 'error');
                        return;
                    }

                    const createdList = json.data?.all_outlets || [];
                    createdList.forEach(item => {
                        const exists = stateBahanBakus.some(b => Number(b.id) === Number(item.id));
                        if (!exists) {
                            stateBahanBakus.push(item);
                        }
                    });

                    const primary = json.data?.bahan;
                    newBahanNama.value = '';
                    newBahanPanel.classList.add('hidden');
                    populateIngredientDropdown(primary?.id);
                    window.showOwnerToast(json.message, 'success');
                } catch (err) {
                    window.showOwnerToast('Gagal terhubung ke server saat menambah bahan baku.', 'error');
                } finally {
                    btnSaveNewBahan.disabled = false;
                    btnSaveNewBahan.textContent = 'Simpan & Sinkronkan ke Bahan Baku';
                }
            });

            btnAddIngredientToRecipe.addEventListener('click', function () {
                if (selectIngredientName.value === '__NEW__') {
                    window.showOwnerToast('Simpan bahan baku baru terlebih dahulu atau pilih dari daftar.', 'error');
                    return;
                }

                const bahan = getBahanForSelectedOutlet().find(b => Number(b.id) === Number(selectIngredientName.value));
                if (!bahan) {
                    window.showOwnerToast('Pilih bahan baku terlebih dahulu.', 'error');
                    return;
                }

                const qty = Number(inputIngredientQty.value);
                if (!qty || qty < 0.01) {
                    window.showOwnerToast('Takaran per porsi minimal 0.01.', 'error');
                    inputIngredientQty.focus();
                    return;
                }

                const chosenUnit = (selectIngredientUnit.value || bahan.satuan || 'kg').trim();
                const existingIdx = modalRecipe.findIndex(r => Number(r.id_bahan) === Number(bahan.id));

                if (editingRecipeIndex !== null) {
                    if (existingIdx !== -1 && existingIdx !== editingRecipeIndex) {
                        window.showOwnerToast(`${bahan.nama_bahan} sudah ada di daftar resep.`, 'error');
                        return;
                    }
                    modalRecipe[editingRecipeIndex] = {
                        id_bahan: bahan.id,
                        nama_bahan: bahan.nama_bahan,
                        satuan: chosenUnit,
                        jumlah_dibutuhkan: qty,
                    };
                } else {
                    if (existingIdx !== -1) {
                        modalRecipe[existingIdx].jumlah_dibutuhkan = Number((modalRecipe[existingIdx].jumlah_dibutuhkan + qty).toFixed(2));
                        modalRecipe[existingIdx].satuan = chosenUnit;
                    } else {
                        modalRecipe.push({
                            id_bahan: bahan.id,
                            nama_bahan: bahan.nama_bahan,
                            satuan: chosenUnit,
                            jumlah_dibutuhkan: qty,
                        });
                    }
                }

                // Sinkronkan satuan pilihan owner ke state lokal bahan baku
                bahan.satuan = chosenUnit;

                renderModalRecipeList();
                closeIngredientSheet();
                showDarkCheckToast('Bahan berhasil ditambahkan');
            });

            // ─── Submit Tambah / Edit Menu (POST /menu/store & PUT /menu/{id}/update-harga) ──
            menuForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const namaMenu = inputNamaMenu.value.trim();
                const kategori = inputKategoriMenu.value.trim();
                const harga = parseHargaInput(inputHargaMenu.value);

                if (!namaMenu) {
                    window.showOwnerToast('Nama menu wajib diisi.', 'error');
                    inputNamaMenu.focus();
                    return;
                }
                if (harga <= 0 && inputHargaMenu.value.trim() === '') {
                    window.showOwnerToast('Harga jual wajib diisi.', 'error');
                    inputHargaMenu.focus();
                    return;
                }
                if (modalRecipe.length === 0) {
                    window.showOwnerToast('Minimal tambahkan 1 bahan baku ke dalam resep.', 'error');
                    return;
                }

                btnSubmitMenu.disabled = true;
                submitMenuSpinner.classList.remove('hidden');

                try {
                    const isEdit = Boolean(editingMenuId);
                    const url = isEdit
                        ? routes.updateHargaTemplate.replace('__ID__', String(editingMenuId))
                        : routes.storeMenu;

                    let fetchOptions;

                    if (modalPhotoFile) {
                        const formData = new FormData();
                        if (isEdit) formData.append('_method', 'PUT');
                        formData.append('nama_menu', namaMenu);
                        formData.append('kategori', kategori);
                        formData.append('harga', String(harga));
                        formData.append('status_aktif', modalStatusAktif ? '1' : '0');
                        formData.append('foto', modalPhotoFile);
                        modalRecipe.forEach((item, index) => {
                            formData.append(`resep[${index}][id_bahan]`, String(item.id_bahan));
                            formData.append(`resep[${index}][jumlah_dibutuhkan]`, String(item.jumlah_dibutuhkan));
                            if (item.satuan) {
                                formData.append(`resep[${index}][satuan]`, String(item.satuan));
                            }
                        });

                        fetchOptions = {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: formData,
                        };
                    } else {
                        fetchOptions = {
                            method: isEdit ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                nama_menu: namaMenu,
                                kategori: kategori,
                                harga: harga,
                                status_aktif: modalStatusAktif,
                                resep: modalRecipe.map(item => ({
                                    id_bahan: Number(item.id_bahan),
                                    jumlah_dibutuhkan: Number(item.jumlah_dibutuhkan),
                                    satuan: item.satuan || null,
                                })),
                            }),
                        };
                    }

                    const response = await fetch(url, fetchOptions);
                    const json = await response.json();

                    if (!response.ok || !json.success) {
                        const msg = json.errors
                            ? Object.values(json.errors).flat()[0]
                            : (json.message || 'Gagal menyimpan data menu.');
                        window.showOwnerToast(msg, 'error');
                        return;
                    }

                    const savedMenu = json.data;
                    if (isEdit) {
                        const idx = stateMenus.findIndex(m => Number(m.id) === Number(savedMenu.id));
                        if (idx !== -1) stateMenus[idx] = savedMenu;
                    } else {
                        stateMenus.unshift(savedMenu);
                    }

                    closeMenuModal();
                    renderMenuCatalog();
                    openSuccessCard(isEdit, savedMenu);
                } catch (err) {
                    window.showOwnerToast('Terjadi gangguan koneksi saat menyimpan menu.', 'error');
                } finally {
                    btnSubmitMenu.disabled = false;
                    submitMenuSpinner.classList.add('hidden');
                }
            });

            // ─── Hapus Menu dari Modal Edit (DELETE /menu/{id}) ──────────
            function openDeleteConfirmModal() {
                if (!editingMenuId) return;
                const menu = stateMenus.find(m => Number(m.id) === Number(editingMenuId));
                deleteConfirmDesc.textContent = menu
                    ? `Menu "${menu.nama_menu}" beserta komposisi resepnya akan dihapus permanen dari katalog.`
                    : 'Menu beserta komposisi resepnya akan dihapus permanen.';
                deleteConfirmModal.classList.remove('hidden');
            }

            function closeDeleteConfirmModal() {
                deleteConfirmModal.classList.add('hidden');
            }

            btnDeleteMenu.addEventListener('click', openDeleteConfirmModal);
            btnCancelDeleteMenu.addEventListener('click', closeDeleteConfirmModal);
            deleteConfirmBackdrop.addEventListener('click', closeDeleteConfirmModal);

            btnConfirmDeleteMenu.addEventListener('click', async function () {
                if (!editingMenuId) return;

                btnConfirmDeleteMenu.disabled = true;
                deleteMenuSpinner.classList.remove('hidden');

                try {
                    const url = routes.destroyMenuTemplate.replace('__ID__', String(editingMenuId));
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    const json = await response.json();
                    if (!response.ok || !json.success) {
                        closeDeleteConfirmModal();
                        window.showOwnerToast(json.message || 'Gagal menghapus menu.', 'error');
                        return;
                    }

                    stateMenus = stateMenus.filter(m => Number(m.id) !== Number(editingMenuId));
                    closeDeleteConfirmModal();
                    closeMenuModal();
                    renderMenuCatalog();
                    showDarkCheckToast('Menu berhasil dihapus');
                } catch (err) {
                    closeDeleteConfirmModal();
                    window.showOwnerToast('Terjadi gangguan koneksi saat menghapus menu.', 'error');
                } finally {
                    btnConfirmDeleteMenu.disabled = false;
                    deleteMenuSpinner.classList.add('hidden');
                }
            });

            // ─── Pop-up Snackbar Gelap dengan Checklist Hijau ─────────────
            // --- Modal Sukses Card (Tambah / Edit Menu) ---
            function getOutletLabel(outletId) {
                const outlet = stateOutlets.find(o => Number(o.id) === Number(outletId));
                return outlet ? outlet.nama_outlet : 'Outlet';
            }

            function openSuccessCard(isEdit, menu) {
                if (!successCardModal || !successCardTitle || !successCardDesc) return;
                successCardTitle.textContent = isEdit
                    ? 'Menu & Resep Berhasil Diperbarui!'
                    : 'Menu & Resep Berhasil Ditambahkan!';
                const namaMenu = menu?.nama_menu || menu?.nama || 'Menu';
                const hargaMenu = menu?.harga ?? menu?.harga_jual ?? 0;
                const outletLabel = getOutletLabel(selectedOutletId);
                successCardDesc.textContent = '"' + namaMenu + '" (' + formatRupiah(hargaMenu) + ') kini tersedia di katalog ' + outletLabel + '.';
                successCardModal.classList.remove('hidden');
            }

            function closeSuccessCard() {
                if (successCardModal) successCardModal.classList.add('hidden');
            }

            if (btnCloseSuccessCard) {
                btnCloseSuccessCard.addEventListener('click', closeSuccessCard);
            }
            if (successCardBackdrop) {
                successCardBackdrop.addEventListener('click', closeSuccessCard);
            }

            function showDarkCheckToast(message) {
                if (!darkCheckToast || !darkCheckToastText) return;
                darkCheckToastText.textContent = message;
                darkCheckToast.classList.remove('hidden');

                if (darkToastTimer) {
                    clearTimeout(darkToastTimer);
                }

                darkToastTimer = setTimeout(() => {
                    darkCheckToast.classList.add('hidden');
                }, 3200);
            }

            // ─── Bottom Sheet Notifikasi Stok (Integrasi Header Owner) ────
            const notifSheetRoot = document.getElementById('notif-sheet-root');
            const notifSheetBackdrop = document.getElementById('notif-sheet-backdrop');
            const notifSheetClose = document.getElementById('notif-sheet-close');
            const notifMarkRead = document.getElementById('notif-mark-read');
            const notifTabBtns = document.querySelectorAll('.notif-tab-btn');

            function toggleNotifSheet(show) {
                if (!notifSheetRoot) return;
                notifSheetRoot.classList.toggle('hidden', !show);
            }

            document.querySelectorAll('[data-open-notif-sheet]').forEach(btn => {
                btn.addEventListener('click', () => toggleNotifSheet(true));
            });
            if (notifSheetBackdrop) notifSheetBackdrop.addEventListener('click', () => toggleNotifSheet(false));
            if (notifSheetClose) notifSheetClose.addEventListener('click', () => toggleNotifSheet(false));
            if (notifMarkRead) {
                notifMarkRead.addEventListener('click', () => {
                    document.querySelectorAll('.notif-bell-dot').forEach(dot => dot.classList.add('hidden'));
                    toggleNotifSheet(false);
                    window.showOwnerToast('Semua notifikasi stok ditandai sudah dibaca.', 'success');
                });
            }

            notifTabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const tab = this.dataset.notifTab || 'semua';
                    notifTabBtns.forEach(b => {
                        const active = b.dataset.notifTab === tab;
                        b.className = active
                            ? 'notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#DC2626] text-white transition-colors cursor-pointer min-h-[36px] shrink-0'
                            : 'notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer min-h-[36px] shrink-0';
                    });
                    document.querySelectorAll('#notif-sheet-list .notif-item').forEach(item => {
                        const show = tab === 'semua' || item.dataset.notifType === tab;
                        item.classList.toggle('hidden', !show);
                    });
                });
            });

            // ─── Render Awal ──────────────────────────────────────────────
            syncOutletSelection(selectedOutletId);
        })();
    </script>
@endpush
