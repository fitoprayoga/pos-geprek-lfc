@extends('layouts.owner')

@section('title', 'Kelola Stok')
@section('page_title', 'Kelola Stok')
@section('page_subtitle', 'Pantau ketersediaan bahan baku dan catat restock per outlet')
@section('notif_badge_count', $perluRestock ?? 0)

@php
    $formatAngka = function ($val): string {
        $num = (float) $val;
        $formatted = number_format($num, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    };

    $pelakuNama = auth()->user()->nama_lengkap ?? 'Owner LFC';

    $bahanJson = $bahanBakus->map(function ($b) {
        return [
            'id' => $b->id,
            'nama_bahan' => $b->nama_bahan,
            'stok' => (float) $b->stok,
            'satuan' => $b->satuan,
            'stok_minimal' => (float) $b->stok_minimal,
        ];
    })->values();
@endphp

@section('content')
    <div class="space-y-4">
        {{-- Header Halaman: Kembali + Judul di Kiri, Dropdown Outlet di Kanan --}}
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-white border border-stone-200 text-stone-700 hover:bg-stone-100 hover:text-stone-900 transition-colors shrink-0 shadow-2xs"
                    aria-label="Kembali ke Dashboard">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-900 truncate">Kelola Stok</h1>
            </div>

            {{-- Dropdown Pill Pemilih Cabang --}}
            <div class="relative max-w-[190px] sm:max-w-[230px] shrink-0">
                <span
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-2 w-2 rounded-full bg-[#DC2626]"></span>
                <select id="outlet-selector" aria-label="Pilih Outlet Cabang"
                    class="w-full appearance-none rounded-full border border-stone-200 bg-white pl-7 pr-8 py-2 text-xs sm:text-sm font-bold text-stone-800 shadow-2xs hover:border-stone-300 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100 truncate cursor-pointer min-h-[38px]">
                    @forelse ($outlets as $index => $outlet)
                        <option value="{{ $outlet->id }}" @selected((int) ($outletAktif?->id) === (int) $outlet->id)>
                            Outlet {{ $index + 1 }} - {{ str_replace('LFC Cabang ', '', $outlet->nama_outlet) }}
                        </option>
                    @empty
                        <option value="">Belum ada outlet</option>
                    @endforelse
                </select>
                <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-500"
                    fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </div>
        </div>

        {{-- 3 Tab Navigasi (Pill Segmented Control) --}}
        <div class="grid grid-cols-3 gap-1 bg-stone-100 rounded-2xl p-1" role="tablist" aria-label="Navigasi Kelola Stok">
            <button type="button" id="tab-btn-daftar" data-tab="daftar" role="tab" aria-selected="true"
                class="stok-tab-btn px-3 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer bg-[#DC2626] text-white shadow-sm">
                Daftar Stok
            </button>
            <button type="button" id="tab-btn-restock" data-tab="restock" role="tab" aria-selected="false"
                class="stok-tab-btn px-3 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer text-stone-600 hover:text-stone-900">
                + Restock
            </button>
            <button type="button" id="tab-btn-keluar" data-tab="keluar" role="tab" aria-selected="false"
                class="stok-tab-btn px-3 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer text-stone-600 hover:text-stone-900">
                - Stok Keluar
            </button>
        </div>

        {{-- TAB 1: DAFTAR STOK --}}
        <div id="tab-panel-daftar" class="space-y-4">
            {{-- 2 Kartu Ringkasan Sejajar --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Kartu Kiri: Total Bahan --}}
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-stone-100">
                    <div
                        class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-amber-50 text-amber-600 mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </div>
                    <p id="summary-total-bahan" class="text-2xl font-extrabold text-stone-900 leading-none">
                        {{ $totalBahan }}
                    </p>
                    <p class="text-xs font-medium text-stone-500 mt-1.5">Total Bahan</p>
                </div>

                {{-- Kartu Kanan: Perlu Restock --}}
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 border-l-4 border-l-[#DC2626]">
                    <div class="inline-flex items-center justify-center h-8 w-8 rounded-xl bg-red-50 text-[#DC2626] mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <p id="summary-perlu-restock" class="text-2xl font-extrabold text-[#DC2626] leading-none">
                        {{ $perluRestock }}
                    </p>
                    <p class="text-xs font-medium text-stone-500 mt-1.5">Perlu Restock</p>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="relative flex items-center bg-white rounded-2xl shadow-sm border border-stone-200/80">
                <svg class="w-4 h-4 text-stone-400 absolute left-4 pointer-events-none" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="search" id="stok-search-input" placeholder="Cari bahan baku..."
                    class="w-full bg-transparent pl-10 pr-4 py-3 text-sm text-stone-800 placeholder-stone-400 rounded-2xl focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[46px]">
            </div>

            {{-- Daftar Kartu Bahan Baku (1 kolom mobile, 2 kolom desktop) --}}
            <div id="stok-card-grid" class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                @forelse ($bahanBakus as $bahan)
                    @php
                        $stokVal = (float) $bahan->stok;
                        $minVal = (float) $bahan->stok_minimal;
                        $isKritis = $minVal > 0 ? $stokVal <= $minVal * 0.5 : $stokVal <= 0;
                        $isPerluRestock = $stokVal <= $minVal;

                        if ($minVal > 0) {
                            $percent = min(100, max(0, round(($stokVal / $minVal) * 100)));
                        } else {
                            $percent = $stokVal > 0 ? 100 : 0;
                        }

                        if ($isKritis) {
                            $barColor = 'bg-[#DC2626]';
                            $iconBoxClass = 'bg-red-50 text-[#DC2626]';
                            $badgeClass = 'bg-red-50 text-[#DC2626]';
                            $badgeText = '! Perlu Restock';
                        } elseif ($isPerluRestock) {
                            $barColor = 'bg-amber-500';
                            $iconBoxClass = 'bg-amber-50 text-amber-600';
                            $badgeClass = 'bg-amber-50 text-amber-700';
                            $badgeText = '! Perlu Restock';
                        } else {
                            $barColor = 'bg-emerald-500';
                            $iconBoxClass = 'bg-emerald-50 text-emerald-600';
                            $badgeClass = 'bg-emerald-50 text-emerald-700';
                            $badgeText = '✓ Stok Aman';
                        }
                    @endphp

                    <article
                        class="stok-card bg-white rounded-2xl shadow-sm border border-stone-100 p-4 flex flex-col justify-between gap-3"
                        data-id="{{ $bahan->id }}"
                        data-nama="{{ mb_strtolower($bahan->nama_bahan) }}"
                        data-stok="{{ $stokVal }}"
                        data-min="{{ $minVal }}"
                        data-satuan="{{ $bahan->satuan }}">
                        {{-- Baris Atas: Ikon + Nama Bahan di Kiri, Stok / Min di Kanan --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="stok-icon-box h-9 w-9 rounded-xl {{ $iconBoxClass }} flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                    </svg>
                                </div>
                                <h2 class="text-sm font-bold text-stone-900 truncate">{{ $bahan->nama_bahan }}</h2>
                            </div>

                            <p class="stok-ratio text-xs sm:text-sm font-bold text-stone-800 whitespace-nowrap shrink-0">
                                <span class="stok-current-val">{{ $formatAngka($stokVal) }}</span>
                                <span class="font-medium text-stone-400"> / {{ $formatAngka($minVal) }}
                                    {{ $bahan->satuan }}</span>
                            </p>
                        </div>

                        {{-- Baris Tengah: Progress Bar Horizontal --}}
                        <div class="h-2 w-full rounded-full bg-stone-100 overflow-hidden">
                            <div class="stok-progress-bar h-full rounded-full transition-all duration-300 {{ $barColor }}"
                                style="width: {{ $percent }}%"></div>
                        </div>

                        {{-- Baris Bawah: Badge Status di Kiri, Tombol + Restock di Kanan --}}
                        <div class="flex items-center justify-between gap-2 pt-0.5">
                            <span
                                class="stok-status-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                {{ $badgeText }}
                            </span>

                            <button type="button" data-open-restock-id="{{ $bahan->id }}"
                                class="stok-restock-btn px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors cursor-pointer min-h-[34px] {{ $isPerluRestock ? 'bg-[#DC2626] hover:bg-[#B91C1C] text-white shadow-2xs' : 'bg-white border border-stone-200 text-stone-700 hover:bg-stone-50' }}">
                                + Restock
                            </button>
                        </div>
                    </article>
                @empty
                    <div
                        class="md:col-span-2 bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                        <p class="text-sm font-bold text-stone-700">Belum ada data bahan baku di outlet ini.</p>
                        <p class="text-xs text-stone-500 mt-1">Tambahkan bahan baku melalui menu Kelola Menu atau pilih outlet
                            lain.</p>
                    </div>
                @endforelse
            </div>

            {{-- Empty State Pencarian --}}
            <div id="stok-search-empty"
                class="hidden bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-stone-700">Bahan baku tidak ditemukan</p>
                <p class="text-xs text-stone-500 mt-1">Coba kata kunci pencarian lain.</p>
            </div>

            {{-- Tombol Bawah Tetap --}}
            <div class="pt-2 pb-4">
                <button type="button" id="btn-bottom-restock"
                    class="w-full py-3.5 px-5 rounded-2xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-bold shadow-md shadow-red-600/20 transition-all cursor-pointer min-h-[48px]">
                    + Input Restock / Stok Keluar
                </button>
            </div>
        </div>

        {{-- TAB 3: - STOK KELUAR --}}
        <div id="tab-panel-keluar" class="hidden space-y-4">
            {{-- 2 Kartu Ringkasan Sejajar --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Kartu Kiri: Stok Keluar Bulan Ini --}}
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-stone-100">
                    <div
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-50 text-[#DC2626] mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                        </svg>
                    </div>
                    <p id="summary-keluar-count" class="text-xl sm:text-2xl font-extrabold text-stone-900 leading-none">
                        {{ $jumlahStokKeluarBulanIni ?? 0 }} Kali
                    </p>
                    <p class="text-xs font-medium text-stone-500 mt-1.5">Stok Keluar Bulan Ini</p>
                </div>

                {{-- Kartu Kanan: Estimasi Kerugian --}}
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-stone-100">
                    <div
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-amber-50 text-amber-500 mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <p id="summary-estimasi-kerugian"
                        class="text-xl sm:text-2xl font-extrabold text-[#DC2626] leading-none truncate">
                        Rp {{ number_format($estimasiKerugian ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-xs font-medium text-stone-500 mt-1.5">Estimasi Kerugian</p>
                </div>
            </div>

            {{-- Search Bar + Tombol Filter --}}
            <div class="flex items-center gap-2.5">
                <div class="relative flex-1 flex items-center bg-white rounded-2xl shadow-sm border border-stone-200/80">
                    <svg class="w-4 h-4 text-stone-400 absolute left-4 pointer-events-none" fill="none"
                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="search" id="keluar-search-input" placeholder="Cari riwayat stok keluar..."
                        class="w-full bg-transparent pl-10 pr-4 py-3 text-sm text-stone-800 placeholder-stone-400 rounded-2xl focus:outline-none focus:ring-2 focus:ring-red-100 min-h-[46px]">
                </div>

                <button type="button" id="btn-open-filter-keluar"
                    class="relative inline-flex items-center justify-center h-[46px] w-[46px] rounded-xl bg-white border border-stone-200/80 shadow-sm text-stone-700 hover:bg-stone-50 hover:text-[#DC2626] transition-colors cursor-pointer shrink-0"
                    aria-label="Filter dan Urutkan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    <span id="keluar-filter-dot"
                        class="hidden absolute top-2 right-2 h-2 w-2 rounded-full bg-[#DC2626] ring-2 ring-white"></span>
                </button>
            </div>

            {{-- Daftar Kartu Riwayat Stok Keluar --}}
            <div id="keluar-card-grid" class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                @forelse ($stokKeluarLogs ?? [] as $log)
                    @php
                        $namaBahan = $log->bahanBaku?->nama_bahan ?? 'Bahan Dihapus';
                        $satuanBahan = $log->bahanBaku?->satuan ?? '';
                        $tanggalLog = $log->tanggal ?? $log->created_at;
                        $tanggalIso = $tanggalLog ? $tanggalLog->format('Y-m-d') : '';
                        $tanggalTs = $tanggalLog ? $tanggalLog->timestamp : 0;
                        $tanggalLabel = $tanggalLog ? $tanggalLog->locale('id')->translatedFormat('d M Y') : '-';
                    @endphp
                    <article
                        class="keluar-card bg-white rounded-2xl shadow-sm border border-stone-100 p-4 flex flex-col gap-2.5"
                        data-nama="{{ mb_strtolower($namaBahan) }}"
                        data-alasan="{{ mb_strtolower($log->alasan ?? '') }}"
                        data-tanggal="{{ $tanggalIso }}"
                        data-timestamp="{{ $tanggalTs }}"
                        data-jumlah="{{ (float) $log->jumlah }}">
                        {{-- Baris Atas: Nama Bahan + Badge Merah Muda --}}
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-bold text-stone-900 truncate">{{ $namaBahan }}</h2>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 text-[#DC2626] text-xs font-extrabold whitespace-nowrap shrink-0">
                                -{{ $formatAngka($log->jumlah) }}{{ $satuanBahan !== '' ? ' '.$satuanBahan : '' }}
                            </span>
                        </div>

                        {{-- Baris Tengah: Pill Alasan --}}
                        <div>
                            <span
                                class="inline-block bg-stone-100 text-stone-600 rounded-lg px-2.5 py-1 text-xs font-medium">
                                Alasan: {{ $log->alasan }}
                            </span>
                        </div>

                        {{-- Baris Bawah: Tanggal & Pelaku --}}
                        <p class="text-[11px] font-medium text-stone-400">
                            {{ $tanggalLabel }} • Oleh: {{ $pelakuNama }}
                        </p>
                    </article>
                @empty
                    <div id="keluar-empty-initial"
                        class="md:col-span-2 bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                        <div
                            class="mx-auto h-12 w-12 rounded-2xl bg-red-50 text-[#DC2626] flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-stone-800">Belum ada riwayat stok keluar</p>
                        <p class="text-xs text-stone-500 mt-1">Catat pengurangan stok seperti bahan layu, ganti minyak,
                            atau kemasan rusak melalui tombol di bawah.</p>
                    </div>
                @endforelse
            </div>

            {{-- Empty State Hasil Filter / Pencarian --}}
            <div id="keluar-filter-empty"
                class="hidden bg-white rounded-2xl border border-stone-100 p-8 text-center shadow-sm">
                <p class="text-sm font-bold text-stone-700">Riwayat stok keluar tidak ditemukan</p>
                <p class="text-xs text-stone-500 mt-1">Coba ubah kata kunci pencarian atau atur ulang filter tanggal &
                    kategori.</p>
                <button type="button" id="btn-reset-filter-inline"
                    class="mt-3 inline-flex items-center px-3.5 py-1.5 rounded-full bg-stone-100 hover:bg-stone-200 text-xs font-bold text-stone-700 transition-colors cursor-pointer">
                    Reset Filter
                </button>
            </div>

            {{-- Tombol Bawah Tab Stok Keluar --}}
            <div class="pt-2 pb-24 md:pb-4">
                <button type="button" id="btn-bottom-keluar"
                    class="w-full py-3.5 px-5 rounded-2xl bg-[#DC2626] hover:bg-[#B91C1C] active:scale-[0.99] text-white text-sm font-bold shadow-md shadow-red-600/20 transition-all cursor-pointer min-h-[48px]">
                    + Catat Stok Keluar
                </button>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- Toast Sukses Melayang di Atas Layar (Persis Mockup 3) --}}
    <div id="restock-success-toast"
        class="fixed top-5 left-1/2 -translate-x-1/2 z-[70] hidden items-center gap-2 px-5 py-2.5 rounded-full bg-emerald-600 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/30 transition-all duration-200"
        role="status" aria-live="polite">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <span id="restock-success-toast-text">Restock Berhasil</span>
    </div>

    {{-- Bottom Sheet / Modal Input Restock Bahan Baku --}}
    <div id="restock-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="restock-modal-title">
        {{-- Backdrop Gelap --}}
        <div id="restock-backdrop" class="fixed inset-0 bg-stone-900/50 backdrop-blur-[2px] transition-opacity"></div>

        {{-- Container Sheet: Bawah di HP, Terpusat di Desktop --}}
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4 pointer-events-none">
            <div id="restock-sheet"
                class="pointer-events-auto w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-stone-100 p-5 sm:p-6 max-h-[92vh] overflow-y-auto transform transition-all">
                {{-- Drag Handle Mobile --}}
                <div class="w-10 h-1 rounded-full bg-stone-200 mx-auto mb-3 md:hidden"></div>

                {{-- Header Sheet --}}
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 id="restock-modal-title" class="text-base sm:text-lg font-extrabold text-stone-900">
                        Input Restock Bahan Baku
                    </h2>
                    <button type="button" id="btn-close-restock"
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors cursor-pointer"
                        aria-label="Tutup modal">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="restock-form" class="space-y-4">
                    {{-- Pilih Bahan Baku --}}
                    <div>
                        <label for="restock-bahan-select" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Pilih Bahan Baku
                        </label>
                        <div class="relative">
                            <select id="restock-bahan-select" name="id_bahan" required
                                class="w-full appearance-none rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 pr-9 text-sm font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                                @foreach ($bahanBakus as $bahan)
                                    <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    {{-- Banner Info Sisa Stok --}}
                    <div id="restock-info-banner"
                        class="rounded-xl bg-amber-50 border border-amber-200/80 px-3 py-2 text-xs font-semibold text-amber-800 flex items-center gap-1.5">
                        <span aria-hidden="true">!</span>
                        <span id="restock-info-text">Sisa stok saat ini: -</span>
                    </div>

                    {{-- 2 Kolom Sejajar: Jumlah Stok Masuk & Satuan --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label for="restock-jumlah" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                Jumlah Stok Masuk
                            </label>
                            <input type="number" id="restock-jumlah" name="jumlah" step="any" min="0.01" required
                                placeholder="15"
                                class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-bold text-stone-900 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                        </div>
                        <div>
                            <label for="restock-satuan" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                Satuan
                            </label>
                            <input type="text" id="restock-satuan" readonly
                                class="w-full rounded-xl border border-stone-200 bg-stone-100 px-3 py-2.5 text-sm font-semibold text-stone-600 text-center focus:outline-none cursor-default">
                        </div>
                    </div>

                    {{-- Total Biaya Belanja --}}
                    <div>
                        <label for="restock-total-biaya" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Total Biaya Belanja
                        </label>
                        <div class="relative flex items-center">
                            <span
                                class="absolute left-3.5 text-sm font-bold text-stone-500 pointer-events-none">Rp</span>
                            <input type="text" id="restock-total-biaya" inputmode="numeric" required
                                placeholder="525.000"
                                class="w-full rounded-xl border border-stone-200 bg-white pl-10 pr-3.5 py-2.5 text-sm font-bold text-stone-900 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div>
                        <label for="restock-supplier-select" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Supplier
                        </label>
                        <div class="relative">
                            <select id="restock-supplier-select" name="id_supplier"
                                class="w-full appearance-none rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 pr-9 text-sm font-medium text-stone-800 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                                <option value="">Pilih Supplier (Opsional)</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->nama_supplier }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    {{-- Catatan (Opsional) --}}
                    <div>
                        <label for="restock-catatan" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Catatan (Opsional)
                        </label>
                        <input type="text" id="restock-catatan" name="catatan" maxlength="255"
                            placeholder="Contoh: Supplier Ayam Segar Darjo"
                            class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                    </div>

                    {{-- Tombol Bawah Sejajar --}}
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" id="btn-cancel-restock"
                            class="w-full py-3 px-4 rounded-xl bg-white border border-stone-200 text-sm font-bold text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer min-h-[44px]">
                            Batal
                        </button>
                        <button type="submit" id="btn-submit-restock"
                            class="w-full py-3 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] disabled:opacity-60 disabled:cursor-not-allowed text-sm font-bold text-white shadow-sm transition-colors cursor-pointer min-h-[44px]">
                            Simpan Restock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bottom Sheet / Modal Filter & Urutkan Stok Keluar --}}
    <div id="filter-keluar-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="filter-keluar-title">
        {{-- Backdrop Gelap --}}
        <div id="filter-keluar-backdrop" class="fixed inset-0 bg-stone-900/50 backdrop-blur-[2px] transition-opacity">
        </div>

        {{-- Container Sheet: Bawah di HP, Terpusat di Desktop --}}
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4 pointer-events-none">
            <div
                class="pointer-events-auto w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-stone-100 p-5 sm:p-6 max-h-[92vh] overflow-y-auto transform transition-all">
                {{-- Drag Handle Mobile --}}
                <div class="w-10 h-1 rounded-full bg-stone-200 mx-auto mb-3 md:hidden"></div>

                {{-- Header Sheet --}}
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 id="filter-keluar-title" class="text-base sm:text-lg font-extrabold text-stone-900">
                        Filter &amp; Urutkan
                    </h2>
                    <button type="button" id="btn-close-filter-keluar"
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors cursor-pointer"
                        aria-label="Tutup modal filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Rentang Tanggal --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-600 mb-2">Rentang Tanggal</p>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <button type="button" data-date-preset="today"
                                class="filter-date-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Hari Ini
                            </button>
                            <button type="button" data-date-preset="7d"
                                class="filter-date-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                7 Hari
                            </button>
                            <button type="button" data-date-preset="30d"
                                class="filter-date-pill px-3.5 py-1.5 rounded-full text-xs font-bold border border-[#DC2626] bg-[#DC2626] text-white transition-colors cursor-pointer">
                                30 Hari
                            </button>
                            <button type="button" data-date-preset="month"
                                class="filter-date-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Bulan Ini
                            </button>
                            <button type="button" data-date-preset="custom"
                                class="filter-date-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Kustom
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="filter-date-from"
                                    class="block text-[11px] font-medium text-stone-500 mb-1">Dari</label>
                                <input type="date" id="filter-date-from"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-800 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                            </div>
                            <div>
                                <label for="filter-date-to"
                                    class="block text-[11px] font-medium text-stone-500 mb-1">Sampai</label>
                                <input type="date" id="filter-date-to"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-800 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                            </div>
                        </div>
                    </div>

                    {{-- Kategori Item --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-600 mb-2">Kategori Item</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" data-kategori="all"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-bold border border-[#DC2626] bg-[#DC2626] text-white transition-colors cursor-pointer">
                                Semua
                            </button>
                            <button type="button" data-kategori="bahan_baku"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Bahan Baku
                            </button>
                            <button type="button" data-kategori="bumbu"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Bumbu
                            </button>
                            <button type="button" data-kategori="minyak"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Minyak
                            </button>
                            <button type="button" data-kategori="ayam"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Ayam
                            </button>
                            <button type="button" data-kategori="kemasan"
                                class="filter-kategori-pill px-3.5 py-1.5 rounded-full text-xs font-semibold border border-stone-200 bg-white text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer">
                                Kemasan
                            </button>
                        </div>
                    </div>

                    {{-- Urutkan Berdasarkan --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-600 mb-2">Urutkan Berdasarkan</p>
                        <div class="space-y-2">
                            <label
                                class="flex items-center gap-2.5 py-1 text-sm font-medium text-stone-800 cursor-pointer">
                                <input type="radio" name="keluar_sort" value="newest" checked
                                    class="h-4 w-4 accent-[#DC2626] cursor-pointer">
                                <span>Tanggal Terbaru</span>
                            </label>
                            <label
                                class="flex items-center gap-2.5 py-1 text-sm font-medium text-stone-800 cursor-pointer">
                                <input type="radio" name="keluar_sort" value="oldest"
                                    class="h-4 w-4 accent-[#DC2626] cursor-pointer">
                                <span>Tanggal Terlama</span>
                            </label>
                            <label
                                class="flex items-center gap-2.5 py-1 text-sm font-medium text-stone-800 cursor-pointer">
                                <input type="radio" name="keluar_sort" value="name_asc"
                                    class="h-4 w-4 accent-[#DC2626] cursor-pointer">
                                <span>Nama Item A–Z</span>
                            </label>
                            <label
                                class="flex items-center gap-2.5 py-1 text-sm font-medium text-stone-800 cursor-pointer">
                                <input type="radio" name="keluar_sort" value="qty_desc"
                                    class="h-4 w-4 accent-[#DC2626] cursor-pointer">
                                <span>Jumlah Terbanyak</span>
                            </label>
                        </div>
                    </div>

                    {{-- Tombol Bawah Sejajar --}}
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" id="btn-reset-filter-keluar"
                            class="w-full py-3 px-4 rounded-xl bg-white border border-stone-200 text-sm font-bold text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer min-h-[44px]">
                            Reset
                        </button>
                        <button type="button" id="btn-apply-filter-keluar"
                            class="w-full py-3 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-sm font-bold text-white shadow-sm transition-colors cursor-pointer min-h-[44px]">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Sheet / Modal Catat Stok Keluar --}}
    <div id="keluar-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true"
        aria-labelledby="keluar-modal-title">
        {{-- Backdrop Gelap --}}
        <div id="keluar-backdrop" class="fixed inset-0 bg-stone-900/50 backdrop-blur-[2px] transition-opacity"></div>

        {{-- Container Sheet: Bawah di HP, Terpusat di Desktop --}}
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4 pointer-events-none">
            <div
                class="pointer-events-auto w-full md:max-w-md bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-stone-100 p-5 sm:p-6 max-h-[92vh] overflow-y-auto transform transition-all">
                {{-- Drag Handle Mobile --}}
                <div class="w-10 h-1 rounded-full bg-stone-200 mx-auto mb-3 md:hidden"></div>

                {{-- Header Sheet --}}
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 id="keluar-modal-title" class="text-base sm:text-lg font-extrabold text-stone-900">
                        Catat Stok Keluar
                    </h2>
                    <button type="button" id="btn-close-keluar"
                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors cursor-pointer"
                        aria-label="Tutup modal catat stok keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="keluar-form" class="space-y-4">
                    {{-- Pilih Bahan Baku --}}
                    <div>
                        <label for="keluar-bahan-select" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Pilih Bahan Baku
                        </label>
                        <div class="relative">
                            <select id="keluar-bahan-select" name="id_bahan" required
                                class="w-full appearance-none rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 pr-9 text-sm font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                                @foreach ($bahanBakus as $bahan)
                                    <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    {{-- Banner Info Sisa Stok --}}
                    <div id="keluar-info-banner"
                        class="rounded-xl bg-red-50 border border-red-100 px-3 py-2 text-xs font-semibold text-red-600 flex items-center gap-1.5">
                        <span aria-hidden="true">!</span>
                        <span id="keluar-info-text">Sisa stok saat ini: -</span>
                    </div>

                    {{-- 2 Kolom Sejajar: Jumlah Stok Keluar & Satuan --}}
                    <div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label for="keluar-jumlah" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                    Jumlah Stok Keluar
                                </label>
                                <input type="number" id="keluar-jumlah" name="jumlah" step="any" min="0.01" required
                                    placeholder="1.5"
                                    class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-bold text-stone-900 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                            </div>
                            <div>
                                <label for="keluar-satuan" class="block text-xs font-semibold text-stone-600 mb-1.5">
                                    Satuan
                                </label>
                                <input type="text" id="keluar-satuan" readonly
                                    class="w-full rounded-xl border border-stone-200 bg-stone-100 px-3 py-2.5 text-sm font-semibold text-stone-600 text-center focus:outline-none cursor-default">
                            </div>
                        </div>
                        <p id="keluar-jumlah-error" class="hidden text-xs font-semibold text-[#DC2626] mt-1.5"></p>
                    </div>

                    {{-- Alasan Pengurangan --}}
                    <div>
                        <label for="keluar-alasan-select" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Alasan Pengurangan
                        </label>
                        <div class="relative">
                            <select id="keluar-alasan-select" name="alasan" required
                                class="w-full appearance-none rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 pr-9 text-sm font-semibold text-stone-900 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                                <option value="Bahan Busuk / Layu">Bahan Busuk / Layu</option>
                                <option value="Ganti Minyak Penggorengan">Ganti Minyak Penggorengan</option>
                                <option value="Kualitas Tidak Layak">Kualitas Tidak Layak</option>
                                <option value="Kemasan Sobek / Tumpah">Kemasan Sobek / Tumpah</option>
                                <option value="Pemakaian Operasional">Pemakaian Operasional</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    {{-- Catatan Tambahan (Opsional) --}}
                    <div>
                        <label for="keluar-catatan" class="block text-xs font-semibold text-stone-600 mb-1.5">
                            Catatan Tambahan (Opsional)
                        </label>
                        <input type="text" id="keluar-catatan" name="catatan" maxlength="200"
                            placeholder="Sebagian stok layu di penyimpanan"
                            class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:border-[#DC2626] focus:outline-none focus:ring-2 focus:ring-red-100">
                    </div>

                    {{-- Tombol Bawah Sejajar --}}
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" id="btn-cancel-keluar"
                            class="w-full py-3 px-4 rounded-xl bg-white border border-stone-200 text-sm font-bold text-stone-700 hover:bg-stone-50 transition-colors cursor-pointer min-h-[44px]">
                            Batal
                        </button>
                        <button type="submit" id="btn-submit-keluar"
                            class="w-full py-3 px-4 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] disabled:opacity-60 disabled:cursor-not-allowed text-sm font-bold text-white shadow-sm transition-colors cursor-pointer min-h-[44px]">
                            Simpan Stok Keluar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        (function() {
            const ACTIVE_OUTLET_ID = @json($outletAktif?->id);
            const BAHAN_LIST = @json($bahanJson);
            const RESTOCK_URL = @json(route('stok.restock'));
            const KELUAR_URL = @json(route('stok.keluar'));
            const INDEX_URL = @json(route('stok.index'));
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const formatAngka = (val) => {
                const num = Number(val) || 0;
                return Number(num.toFixed(2)).toString();
            };

            const parseRupiah = (str) => {
                const digits = String(str || '').replace(/\D/g, '');
                return digits ? parseInt(digits, 10) : 0;
            };

            const formatRibuan = (num) => {
                return num > 0 ? num.toLocaleString('id-ID') : '';
            };

            let currentTab = new URLSearchParams(window.location.search).get('tab') === 'keluar' ? 'keluar' : 'daftar';

            // 1. Outlet Selector -> Reload halaman dengan ?outlet_id=...
            const outletSelect = document.getElementById('outlet-selector');
            if (outletSelect) {
                outletSelect.addEventListener('change', (e) => {
                    const selectedId = e.target.value;
                    if (!selectedId) return;
                    const tabParam = currentTab === 'keluar' ? '&tab=keluar' : '';
                    window.location.href = `${INDEX_URL}?outlet_id=${encodeURIComponent(selectedId)}${tabParam}`;
                });
            }

            // 2. Tab Navigasi (Daftar Stok, + Restock, - Stok Keluar)
            const tabDaftarBtn = document.getElementById('tab-btn-daftar');
            const tabRestockBtn = document.getElementById('tab-btn-restock');
            const tabKeluarBtn = document.getElementById('tab-btn-keluar');
            const panelDaftar = document.getElementById('tab-panel-daftar');
            const panelKeluar = document.getElementById('tab-panel-keluar');

            const setActiveTab = (tabName) => {
                currentTab = tabName === 'keluar' ? 'keluar' : 'daftar';
                const activeClasses = ['bg-[#DC2626]', 'text-white', 'font-bold', 'shadow-sm'];
                const inactiveClasses = ['text-stone-600', 'font-semibold', 'hover:text-stone-900'];

                [tabDaftarBtn, tabKeluarBtn].forEach((btn) => {
                    if (!btn) return;
                    const isMatch = btn.dataset.tab === currentTab;
                    btn.setAttribute('aria-selected', isMatch ? 'true' : 'false');
                    if (isMatch) {
                        btn.classList.remove(...inactiveClasses);
                        btn.classList.add(...activeClasses);
                    } else {
                        btn.classList.remove(...activeClasses);
                        btn.classList.add(...inactiveClasses);
                    }
                });

                if (currentTab === 'keluar') {
                    panelDaftar?.classList.add('hidden');
                    panelKeluar?.classList.remove('hidden');
                } else {
                    panelKeluar?.classList.add('hidden');
                    panelDaftar?.classList.remove('hidden');
                }
            };

            tabDaftarBtn?.addEventListener('click', () => setActiveTab('daftar'));
            tabKeluarBtn?.addEventListener('click', () => setActiveTab('keluar'));

            // Aktifkan tab sesuai query ?tab=keluar saat halaman dimuat
            setActiveTab(currentTab);

            // 3. Filter Pencarian Real-time
            const searchInput = document.getElementById('stok-search-input');
            const cardGrid = document.getElementById('stok-card-grid');
            const searchEmpty = document.getElementById('stok-search-empty');

            searchInput?.addEventListener('input', (e) => {
                const query = e.target.value.trim().toLowerCase();
                const cards = document.querySelectorAll('.stok-card');
                let visibleCount = 0;

                cards.forEach((card) => {
                    const nama = card.dataset.nama || '';
                    const match = nama.includes(query);
                    card.classList.toggle('hidden', !match);
                    if (match) visibleCount++;
                });

                if (cards.length > 0) {
                    cardGrid?.classList.toggle('hidden', visibleCount === 0);
                    searchEmpty?.classList.toggle('hidden', visibleCount > 0);
                }
            });

            // 4. Bottom Sheet Input Restock
            const modal = document.getElementById('restock-modal');
            const backdrop = document.getElementById('restock-backdrop');
            const closeBtn = document.getElementById('btn-close-restock');
            const cancelBtn = document.getElementById('btn-cancel-restock');
            const bottomRestockBtn = document.getElementById('btn-bottom-restock');
            const bahanSelect = document.getElementById('restock-bahan-select');
            const infoText = document.getElementById('restock-info-text');
            const jumlahInput = document.getElementById('restock-jumlah');
            const satuanInput = document.getElementById('restock-satuan');
            const totalBiayaInput = document.getElementById('restock-total-biaya');
            const supplierSelect = document.getElementById('restock-supplier-select');
            const catatanInput = document.getElementById('restock-catatan');
            const form = document.getElementById('restock-form');
            const submitBtn = document.getElementById('btn-submit-restock');
            const successToast = document.getElementById('restock-success-toast');

            const updateSelectedBahanInfo = () => {
                const selectedId = Number(bahanSelect?.value);
                const bahan = BAHAN_LIST.find((b) => Number(b.id) === selectedId);
                if (!bahan) {
                    if (infoText) infoText.textContent = 'Pilih bahan baku terlebih dahulu.';
                    if (satuanInput) satuanInput.value = '-';
                    return;
                }
                if (infoText) {
                    infoText.textContent =
                        `Sisa stok saat ini: ${formatAngka(bahan.stok)} ${bahan.satuan} (Min: ${formatAngka(bahan.stok_minimal)} ${bahan.satuan})`;
                }
                if (satuanInput) {
                    satuanInput.value = bahan.satuan;
                }
            };

            const openRestockModal = (preselectedBahanId = null) => {
                if (BAHAN_LIST.length === 0) {
                    window.showOwnerToast?.('Belum ada bahan baku di outlet ini.', 'error');
                    return;
                }

                if (preselectedBahanId && bahanSelect) {
                    bahanSelect.value = String(preselectedBahanId);
                } else if (bahanSelect && !bahanSelect.value && BAHAN_LIST[0]) {
                    bahanSelect.value = String(BAHAN_LIST[0].id);
                }

                updateSelectedBahanInfo();
                modal?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeRestockModal = () => {
                modal?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            bahanSelect?.addEventListener('change', updateSelectedBahanInfo);
            tabRestockBtn?.addEventListener('click', () => openRestockModal());
            bottomRestockBtn?.addEventListener('click', () => openRestockModal());
            backdrop?.addEventListener('click', closeRestockModal);
            closeBtn?.addEventListener('click', closeRestockModal);
            cancelBtn?.addEventListener('click', closeRestockModal);

            document.querySelectorAll('[data-open-restock-id]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    openRestockModal(btn.dataset.openRestockId);
                });
            });

            // Format otomatis input Total Biaya Belanja
            totalBiayaInput?.addEventListener('input', (e) => {
                const numeric = parseRupiah(e.target.value);
                e.target.value = formatRibuan(numeric);
            });

            const successToastText = document.getElementById('restock-success-toast-text');

            const showSuccessToast = (message = 'Restock Berhasil') => {
                if (!successToast) return;
                if (successToastText) successToastText.textContent = message;
                successToast.classList.remove('hidden');
                successToast.classList.add('flex');
            };

            const updateCardInDom = (bahanId, addedJumlah) => {
                const bahan = BAHAN_LIST.find((b) => Number(b.id) === Number(bahanId));
                if (bahan) {
                    bahan.stok = Number(bahan.stok) + Number(addedJumlah);
                }

                const card = document.querySelector(`.stok-card[data-id="${bahanId}"]`);
                if (!card || !bahan) return;

                const newStok = bahan.stok;
                const minVal = bahan.stok_minimal;
                const isKritis = minVal > 0 ? newStok <= minVal * 0.5 : newStok <= 0;
                const isPerluRestock = newStok <= minVal;
                const percent = minVal > 0 ? Math.min(100, Math.max(0, Math.round((newStok / minVal) * 100))) : (
                    newStok > 0 ? 100 : 0);

                const valEl = card.querySelector('.stok-current-val');
                if (valEl) valEl.textContent = formatAngka(newStok);

                const barEl = card.querySelector('.stok-progress-bar');
                if (barEl) {
                    barEl.style.width = `${percent}%`;
                    barEl.className = 'stok-progress-bar h-full rounded-full transition-all duration-300 ' +
                        (isKritis ? 'bg-[#DC2626]' : (isPerluRestock ? 'bg-amber-500' : 'bg-emerald-500'));
                }

                const badgeEl = card.querySelector('.stok-status-badge');
                if (badgeEl) {
                    badgeEl.textContent = isPerluRestock ? '! Perlu Restock' : '✓ Stok Aman';
                    badgeEl.className =
                        'stok-status-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold ' +
                        (isKritis ? 'bg-red-50 text-[#DC2626]' : (isPerluRestock ?
                            'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'));
                }

                const perluCount = BAHAN_LIST.filter((b) => Number(b.stok) <= Number(b.stok_minimal)).length;
                const summaryPerlu = document.getElementById('summary-perlu-restock');
                if (summaryPerlu) summaryPerlu.textContent = String(perluCount);
            };

            // 5. Submit Restock via Fetch API
            let isSubmitting = false;
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (isSubmitting) return;

                const idBahan = Number(bahanSelect?.value);
                const jumlah = parseFloat(jumlahInput?.value || '0');
                const totalBiaya = parseRupiah(totalBiayaInput?.value || '0');
                const idSupplier = supplierSelect?.value ? Number(supplierSelect.value) : null;
                const catatan = catatanInput?.value?.trim() || '';

                if (!ACTIVE_OUTLET_ID || !idBahan || !(jumlah > 0)) {
                    window.showOwnerToast?.('Mohon lengkapi bahan baku dan jumlah stok masuk.', 'error');
                    return;
                }

                const hargaSatuan = totalBiaya / jumlah;

                isSubmitting = true;
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Menyimpan...';
                }

                try {
                    const response = await fetch(RESTOCK_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({
                            outlet_id: ACTIVE_OUTLET_ID,
                            id_supplier: idSupplier,
                            catatan: catatan,
                            items: [{
                                id_bahan: idBahan,
                                jumlah: jumlah,
                                harga_satuan: hargaSatuan,
                            }],
                        }),
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        const msg = result.message || 'Gagal menyimpan data restock.';
                        window.showOwnerToast?.(msg, 'error');
                        return;
                    }

                    closeRestockModal();
                    updateCardInDom(idBahan, jumlah);
                    showSuccessToast();

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } catch (err) {
                    window.showOwnerToast?.('Terjadi kesalahan jaringan saat menyimpan restock.', 'error');
                } finally {
                    isSubmitting = false;
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Simpan Restock';
                    }
                }
            });

            // 6. Tab 3: Pencarian, Filter & Urutkan Riwayat Stok Keluar
            const keluarSearchInput = document.getElementById('keluar-search-input');
            const keluarCardGrid = document.getElementById('keluar-card-grid');
            const keluarFilterEmpty = document.getElementById('keluar-filter-empty');
            const keluarFilterDot = document.getElementById('keluar-filter-dot');
            const btnOpenFilterKeluar = document.getElementById('btn-open-filter-keluar');
            const filterKeluarModal = document.getElementById('filter-keluar-modal');
            const filterKeluarBackdrop = document.getElementById('filter-keluar-backdrop');
            const btnCloseFilterKeluar = document.getElementById('btn-close-filter-keluar');
            const btnResetFilterKeluar = document.getElementById('btn-reset-filter-keluar');
            const btnResetFilterInline = document.getElementById('btn-reset-filter-inline');
            const btnApplyFilterKeluar = document.getElementById('btn-apply-filter-keluar');
            const filterDateFrom = document.getElementById('filter-date-from');
            const filterDateTo = document.getElementById('filter-date-to');
            const datePresetPills = document.querySelectorAll('.filter-date-pill');
            const kategoriPills = document.querySelectorAll('.filter-kategori-pill');
            const sortRadios = document.querySelectorAll('input[name="keluar_sort"]');

            let keluarFilterState = {
                preset: '30d',
                dateFrom: '',
                dateTo: '',
                kategori: 'all',
                sort: 'newest',
            };

            const toLocalDateStr = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };

            const computePresetDates = (preset) => {
                const now = new Date();
                const today = toLocalDateStr(now);
                if (preset === 'today') {
                    return { from: today, to: today };
                }
                if (preset === '7d') {
                    const start = new Date(now);
                    start.setDate(now.getDate() - 6);
                    return { from: toLocalDateStr(start), to: today };
                }
                if (preset === '30d') {
                    const start = new Date(now);
                    start.setDate(now.getDate() - 29);
                    return { from: toLocalDateStr(start), to: today };
                }
                if (preset === 'month') {
                    const start = new Date(now.getFullYear(), now.getMonth(), 1);
                    return { from: toLocalDateStr(start), to: today };
                }
                return {
                    from: filterDateFrom?.value || '',
                    to: filterDateTo?.value || '',
                };
            };

            const syncPillStyles = (pills, activeAttr, activeVal) => {
                pills.forEach((pill) => {
                    const isMatch = pill.dataset[activeAttr] === activeVal;
                    if (isMatch) {
                        pill.classList.remove('border-stone-200', 'bg-white', 'text-stone-700', 'font-semibold',
                            'hover:bg-stone-50');
                        pill.classList.add('border-[#DC2626]', 'bg-[#DC2626]', 'text-white', 'font-bold');
                    } else {
                        pill.classList.remove('border-[#DC2626]', 'bg-[#DC2626]', 'text-white', 'font-bold');
                        pill.classList.add('border-stone-200', 'bg-white', 'text-stone-700', 'font-semibold',
                            'hover:bg-stone-50');
                    }
                });
            };

            const matchKategoriBahan = (namaLower, kategori) => {
                if (kategori === 'all') return true;
                const isAyam = /(ayam|karkas|daging|dada|paha|sayap|fillet|ceker)/.test(namaLower);
                const isMinyak = /(minyak|goreng|mentega|margarin|olive)/.test(namaLower);
                const isBumbu = /(bumbu|cabai|cabe|bawang|garam|gula|lada|merica|kecap|saus|saos|kaldu|penyedap|kunyit|jahe|lengkuas|ketumbar|rempah)/.test(
                    namaLower);
                const isKemasan = /(kemasan|box|dus|kardus|plastik|cup|gelas|sendok|garpu|kertas|bungkus|stiker|kantong|kresek|sedotan|mika)/.test(
                    namaLower);

                if (kategori === 'ayam') return isAyam;
                if (kategori === 'minyak') return isMinyak;
                if (kategori === 'bumbu') return isBumbu;
                if (kategori === 'kemasan') return isKemasan;
                if (kategori === 'bahan_baku') {
                    return (!isAyam && !isMinyak && !isBumbu && !isKemasan) ||
                        /(tepung|beras|nasi|telur|tahu|tempe|sayur|es|air|susu|keju|bahan)/.test(namaLower);
                }
                return true;
            };

            const updateFilterIndicator = () => {
                const isDefault = keluarFilterState.preset === '30d' &&
                    keluarFilterState.kategori === 'all' &&
                    keluarFilterState.sort === 'newest';
                keluarFilterDot?.classList.toggle('hidden', isDefault);
            };

            const applyKeluarFilterAndSort = () => {
                if (!keluarCardGrid) return;
                const cards = Array.from(keluarCardGrid.querySelectorAll('.keluar-card'));
                if (cards.length === 0) return;

                const query = (keluarSearchInput?.value || '').trim().toLowerCase();
                const { dateFrom, dateTo, kategori, sort } = keluarFilterState;

                // Urutkan elemen DOM
                cards.sort((a, b) => {
                    const tsA = Number(a.dataset.timestamp || 0);
                    const tsB = Number(b.dataset.timestamp || 0);
                    const namaA = a.dataset.nama || '';
                    const namaB = b.dataset.nama || '';
                    const qtyA = Number(a.dataset.jumlah || 0);
                    const qtyB = Number(b.dataset.jumlah || 0);

                    if (sort === 'oldest') return tsA - tsB;
                    if (sort === 'name_asc') return namaA.localeCompare(namaB, 'id');
                    if (sort === 'qty_desc') return qtyB - qtyA;
                    return tsB - tsA; // newest
                });

                cards.forEach((card) => keluarCardGrid.appendChild(card));

                let visibleCount = 0;
                cards.forEach((card) => {
                    const nama = card.dataset.nama || '';
                    const alasan = card.dataset.alasan || '';
                    const tanggal = card.dataset.tanggal || '';

                    const matchSearch = !query || nama.includes(query) || alasan.includes(query);
                    const matchFrom = !dateFrom || !tanggal || tanggal >= dateFrom;
                    const matchTo = !dateTo || !tanggal || tanggal <= dateTo;
                    const matchKat = matchKategoriBahan(nama, kategori);

                    const visible = matchSearch && matchFrom && matchTo && matchKat;
                    card.classList.toggle('hidden', !visible);
                    if (visible) visibleCount++;
                });

                keluarCardGrid.classList.toggle('hidden', visibleCount === 0);
                keluarFilterEmpty?.classList.toggle('hidden', visibleCount > 0);
                updateFilterIndicator();
            };

            const initDefaultKeluarFilter = () => {
                const range = computePresetDates('30d');
                keluarFilterState = {
                    preset: '30d',
                    dateFrom: range.from,
                    dateTo: range.to,
                    kategori: 'all',
                    sort: 'newest',
                };
                if (filterDateFrom) filterDateFrom.value = range.from;
                if (filterDateTo) filterDateTo.value = range.to;
                syncPillStyles(datePresetPills, 'datePreset', '30d');
                syncPillStyles(kategoriPills, 'kategori', 'all');
                sortRadios.forEach((r) => {
                    r.checked = r.value === 'newest';
                });
                applyKeluarFilterAndSort();
            };

            initDefaultKeluarFilter();

            keluarSearchInput?.addEventListener('input', applyKeluarFilterAndSort);

            const openFilterModal = () => {
                filterKeluarModal?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };
            const closeFilterModal = () => {
                filterKeluarModal?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            btnOpenFilterKeluar?.addEventListener('click', openFilterModal);
            filterKeluarBackdrop?.addEventListener('click', closeFilterModal);
            btnCloseFilterKeluar?.addEventListener('click', closeFilterModal);

            datePresetPills.forEach((pill) => {
                pill.addEventListener('click', () => {
                    const preset = pill.dataset.datePreset || '30d';
                    keluarFilterState.preset = preset;
                    syncPillStyles(datePresetPills, 'datePreset', preset);
                    if (preset !== 'custom') {
                        const range = computePresetDates(preset);
                        if (filterDateFrom) filterDateFrom.value = range.from;
                        if (filterDateTo) filterDateTo.value = range.to;
                    }
                });
            });

            [filterDateFrom, filterDateTo].forEach((input) => {
                input?.addEventListener('change', () => {
                    keluarFilterState.preset = 'custom';
                    syncPillStyles(datePresetPills, 'datePreset', 'custom');
                });
            });

            kategoriPills.forEach((pill) => {
                pill.addEventListener('click', () => {
                    const kat = pill.dataset.kategori || 'all';
                    keluarFilterState.kategori = kat;
                    syncPillStyles(kategoriPills, 'kategori', kat);
                });
            });

            btnApplyFilterKeluar?.addEventListener('click', () => {
                keluarFilterState.dateFrom = filterDateFrom?.value || '';
                keluarFilterState.dateTo = filterDateTo?.value || '';
                const checkedSort = document.querySelector('input[name="keluar_sort"]:checked');
                keluarFilterState.sort = checkedSort ? checkedSort.value : 'newest';
                applyKeluarFilterAndSort();
                closeFilterModal();
            });

            const resetKeluarFilters = () => {
                if (keluarSearchInput) keluarSearchInput.value = '';
                initDefaultKeluarFilter();
            };

            btnResetFilterKeluar?.addEventListener('click', () => {
                resetKeluarFilters();
                closeFilterModal();
            });
            btnResetFilterInline?.addEventListener('click', resetKeluarFilters);

            // 7. Bottom Sheet Catat Stok Keluar
            const keluarModal = document.getElementById('keluar-modal');
            const keluarBackdrop = document.getElementById('keluar-backdrop');
            const btnCloseKeluar = document.getElementById('btn-close-keluar');
            const btnCancelKeluar = document.getElementById('btn-cancel-keluar');
            const btnBottomKeluar = document.getElementById('btn-bottom-keluar');
            const keluarBahanSelect = document.getElementById('keluar-bahan-select');
            const keluarInfoText = document.getElementById('keluar-info-text');
            const keluarJumlahInput = document.getElementById('keluar-jumlah');
            const keluarJumlahError = document.getElementById('keluar-jumlah-error');
            const keluarSatuanInput = document.getElementById('keluar-satuan');
            const keluarAlasanSelect = document.getElementById('keluar-alasan-select');
            const keluarCatatanInput = document.getElementById('keluar-catatan');
            const keluarForm = document.getElementById('keluar-form');
            const btnSubmitKeluar = document.getElementById('btn-submit-keluar');

            const getSelectedKeluarBahan = () => {
                const selectedId = Number(keluarBahanSelect?.value);
                return BAHAN_LIST.find((b) => Number(b.id) === selectedId) || null;
            };

            const validateKeluarJumlah = () => {
                const bahan = getSelectedKeluarBahan();
                const val = parseFloat(keluarJumlahInput?.value || '0');
                if (!bahan || !(val > 0)) {
                    keluarJumlahError?.classList.add('hidden');
                    keluarJumlahInput?.classList.remove('border-[#DC2626]', 'focus:ring-red-200');
                    return true;
                }
                if (val > Number(bahan.stok)) {
                    if (keluarJumlahError) {
                        keluarJumlahError.textContent =
                            `Jumlah melebihi sisa stok saat ini (${formatAngka(bahan.stok)} ${bahan.satuan}).`;
                        keluarJumlahError.classList.remove('hidden');
                    }
                    keluarJumlahInput?.classList.add('border-[#DC2626]', 'focus:ring-red-200');
                    return false;
                }
                keluarJumlahError?.classList.add('hidden');
                keluarJumlahInput?.classList.remove('border-[#DC2626]', 'focus:ring-red-200');
                return true;
            };

            const updateSelectedKeluarBahanInfo = () => {
                const bahan = getSelectedKeluarBahan();
                if (!bahan) {
                    if (keluarInfoText) keluarInfoText.textContent = 'Pilih bahan baku terlebih dahulu.';
                    if (keluarSatuanInput) keluarSatuanInput.value = '-';
                    return;
                }
                if (keluarInfoText) {
                    keluarInfoText.textContent =
                        `Sisa stok saat ini: ${formatAngka(bahan.stok)} ${bahan.satuan} (Min: ${formatAngka(bahan.stok_minimal)} ${bahan.satuan})`;
                }
                if (keluarSatuanInput) {
                    keluarSatuanInput.value = bahan.satuan;
                }
                validateKeluarJumlah();
            };

            const openKeluarModal = () => {
                if (BAHAN_LIST.length === 0) {
                    window.showOwnerToast?.('Belum ada bahan baku di outlet ini.', 'error');
                    return;
                }
                if (keluarBahanSelect && !keluarBahanSelect.value && BAHAN_LIST[0]) {
                    keluarBahanSelect.value = String(BAHAN_LIST[0].id);
                }
                updateSelectedKeluarBahanInfo();
                keluarModal?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeKeluarModal = () => {
                keluarModal?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            keluarBahanSelect?.addEventListener('change', updateSelectedKeluarBahanInfo);
            keluarJumlahInput?.addEventListener('input', validateKeluarJumlah);
            btnBottomKeluar?.addEventListener('click', openKeluarModal);
            keluarBackdrop?.addEventListener('click', closeKeluarModal);
            btnCloseKeluar?.addEventListener('click', closeKeluarModal);
            btnCancelKeluar?.addEventListener('click', closeKeluarModal);

            let isSubmittingKeluar = false;
            keluarForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (isSubmittingKeluar) return;

                const bahan = getSelectedKeluarBahan();
                const idBahan = Number(keluarBahanSelect?.value);
                const jumlah = parseFloat(keluarJumlahInput?.value || '0');
                const alasan = keluarAlasanSelect?.value || '';
                const catatan = keluarCatatanInput?.value?.trim() || '';

                if (!idBahan || !(jumlah > 0) || !alasan) {
                    window.showOwnerToast?.('Mohon lengkapi bahan baku, jumlah, dan alasan pengurangan.', 'error');
                    return;
                }

                if (!validateKeluarJumlah()) {
                    window.showOwnerToast?.(
                        `Jumlah melebihi sisa stok saat ini (${formatAngka(bahan?.stok || 0)} ${bahan?.satuan || ''}).`,
                        'error'
                    );
                    return;
                }

                isSubmittingKeluar = true;
                if (btnSubmitKeluar) {
                    btnSubmitKeluar.disabled = true;
                    btnSubmitKeluar.textContent = 'Menyimpan...';
                }

                try {
                    const response = await fetch(KELUAR_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({
                            id_bahan: idBahan,
                            jumlah: jumlah,
                            alasan: alasan,
                            catatan: catatan,
                        }),
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        const msg = result.message || 'Gagal mencatat stok keluar.';
                        window.showOwnerToast?.(msg, 'error');
                        return;
                    }

                    closeKeluarModal();
                    showSuccessToast('Stok Keluar Berhasil Dicatat');

                    setTimeout(() => {
                        const outletParam = ACTIVE_OUTLET_ID ? `outlet_id=${encodeURIComponent(ACTIVE_OUTLET_ID)}&` : '';
                        window.location.href = `${INDEX_URL}?${outletParam}tab=keluar`;
                    }, 900);
                } catch (err) {
                    window.showOwnerToast?.('Terjadi kesalahan jaringan saat mencatat stok keluar.', 'error');
                } finally {
                    isSubmittingKeluar = false;
                    if (btnSubmitKeluar) {
                        btnSubmitKeluar.disabled = false;
                        btnSubmitKeluar.textContent = 'Simpan Stok Keluar';
                    }
                }
            });
        })();
    </script>
@endpush
