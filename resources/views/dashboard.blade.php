@extends('layouts.owner')

@section('title', 'Dashboard Real-Time')
@section('page_title', 'Dashboard Owner')
@section('page_subtitle', 'Ringkasan performa penjualan, restock & peringatan stok seluruh cabang')

@push('head')
    {{-- Chart.js via CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush

{{-- Tombol Toggle Filter Cepat di Header Mobile --}}
@section('mobile_header_actions')
    <button
        type="button"
        id="mobile-filter-toggle-top"
        class="inline-flex items-center justify-center h-11 w-11 rounded-full bg-stone-50 hover:bg-red-50 text-stone-700 hover:text-[#DC2626] border border-stone-200 transition-colors cursor-pointer"
        aria-label="Tampilkan atau sembunyikan filter"
        aria-controls="filter-collapse-panel"
        aria-expanded="false"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
        </svg>
    </button>
@endsection

{{-- Tombol Export Laporan di Header Desktop --}}
@section('header_actions')
    <a
        id="btn-export-header"
        href="{{ route('dashboard.export', array_filter([
            'outlet_id' => $filter['outlet_id'] ?? null,
            'start_date' => $filter['start_date'] ?? null,
            'end_date' => $filter['end_date'] ?? null,
        ])) }}"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-red-950 font-bold text-sm shadow-2xs transition-colors min-h-[44px]"
    >
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        <span>Export Laporan</span>
    </a>
@endsection

@php
    $totalRevenue = (float) ($ringkasan_penjualan['total_revenue'] ?? 0);
    $totalTransaksi = (int) ($ringkasan_penjualan['total_volume_transaksi'] ?? 0);
    $tunaiData = $ringkasan_penjualan['metode_pembayaran']['TUNAI'] ?? ['total' => 0, 'jumlah_transaksi' => 0];
    $qrisData = $ringkasan_penjualan['metode_pembayaran']['QRIS'] ?? ['total' => 0, 'jumlah_transaksi' => 0];
    $totalRestock = (float) ($laporan_pengeluaran_restock['total_pengeluaran'] ?? 0);
    $jumlahRestock = (int) ($laporan_pengeluaran_restock['jumlah_restock'] ?? 0);
    $riwayatRestock = $laporan_pengeluaran_restock['riwayat_restock'] ?? collect();
    $totalAlert = (int) ($laporan_stok['total_alert'] ?? 0);
    $totalItemStok = (int) ($laporan_stok['total_item_stok'] ?? 0);
    $stokAlerts = $laporan_stok['stok_alert'] ?? collect();
    $daftarStok = $laporan_stok['daftar_stok'] ?? collect();
    $jumlahOutlet = $outlets->count();
@endphp
@section('notif_badge_count', $totalAlert)

@section('content')
    <div class="space-y-4 sm:space-y-5">
        {{-- 1. Hero Card Merah Bergradasi: Total Revenue + Badge Outlet Aktif --}}
        <section
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#DC2626] via-[#C81E1E] to-[#991B1B] text-white p-5 shadow-md"
            aria-label="Ringkasan Utama Revenue"
        >
            {{-- Aksen Dekoratif Bulat Halus --}}
            <div class="pointer-events-none absolute -top-10 -right-10 h-36 w-36 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-12 right-10 h-28 w-28 rounded-full bg-amber-400/10"></div>

            <div class="relative flex items-start justify-between gap-3">
                <p id="hero-period-label" class="text-xs sm:text-sm font-medium text-red-100">
                    Total Revenue ({{ $filter['start_date'] }} s/d {{ $filter['end_date'] }})
                </p>
                <span
                    id="hero-outlet-badge"
                    class="inline-flex items-center px-3 py-1 rounded-full text-[11px] sm:text-xs font-extrabold bg-amber-400 text-red-950 shadow-2xs shrink-0"
                >
                    {{ $jumlahOutlet }} Outlet Aktif
                </span>
            </div>

            <p id="hero-revenue" class="relative mt-2.5 text-3xl sm:text-4xl font-extrabold tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </p>

            <div class="relative mt-4 pt-3 border-t border-white/15 flex items-center justify-between gap-4 text-xs sm:text-sm text-red-50 font-medium">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                    <span id="hero-transaksi">{{ number_format($totalTransaksi, 0, ',', '.') }} Transaksi</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                    </svg>
                    <span id="hero-outlet-info">{{ $jumlahOutlet }} Outlet Operasional</span>
                </span>
            </div>
        </section>

        {{-- 3 Quick Action Tiles (Kelola Stok, Kelola Menu & Harga, Export Laporan) --}}
        <section class="grid grid-cols-3 gap-2.5 sm:gap-4" aria-label="Aksi Cepat Owner">
            <a
                href="{{ route('stok.index') }}"
                class="bg-white rounded-2xl border border-stone-100 shadow-xs hover:shadow-sm p-3 sm:p-4 flex flex-col items-center justify-center text-center gap-2 transition-shadow min-h-[96px]"
            >
                <span class="h-10 w-10 rounded-full bg-red-50 text-[#DC2626] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </span>
                <span class="text-xs font-bold text-stone-800 leading-tight">Kelola<br class="sm:hidden"> Stok</span>
            </a>

            <a
                href="{{ route('menu.index') }}"
                class="bg-white rounded-2xl border border-stone-100 shadow-xs hover:shadow-sm p-3 sm:p-4 flex flex-col items-center justify-center text-center gap-2 transition-shadow min-h-[96px]"
            >
                <span class="h-10 w-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
                <span class="text-xs font-bold text-stone-800 leading-tight">Kelola Menu<br class="sm:hidden"> &amp; Harga</span>
            </a>

            <a
                id="btn-laporan-tile"
                href="{{ route('laporan.index') }}"
                class="bg-white rounded-2xl border border-stone-100 shadow-xs hover:shadow-sm p-3 sm:p-4 flex flex-col items-center justify-center text-center gap-2 transition-shadow min-h-[96px]"
            >
                <span class="h-10 w-10 rounded-full bg-rose-50 text-[#DC2626] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </span>
                <span class="text-xs font-bold text-stone-800 leading-tight">Laporan<br class="sm:hidden"> Penjualan</span>
            </a>
        </section>

        {{-- 2. Filter Compact (Bisa Collapse di Mobile, Selalu Terbuka di Tablet/Desktop) --}}
        <section class="bg-white rounded-2xl border border-stone-100 shadow-xs overflow-hidden" aria-label="Filter Dashboard">
            {{-- Header Bar Toggle Filter Mobile --}}
            <button
                type="button"
                id="mobile-filter-toggle-bar"
                class="w-full md:hidden px-4 py-3 flex items-center justify-between gap-2 text-left cursor-pointer min-h-[48px]"
                aria-controls="filter-collapse-panel"
                aria-expanded="false"
            >
                <div class="min-w-0">
                    <p class="text-xs font-bold text-stone-900">Filter Cabang &amp; Tanggal</p>
                    <p id="mobile-filter-summary" class="text-[11px] text-stone-500 truncate">
                        Semua Cabang • {{ $filter['start_date'] }} s/d {{ $filter['end_date'] }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-[#DC2626] shrink-0">
                    <span id="filter-toggle-label">Ubah</span>
                    <svg id="filter-chevron" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </button>

            {{-- Isi Form Filter --}}
            <div id="filter-collapse-panel" class="hidden md:block border-t md:border-t-0 border-stone-100 p-4">
                <form id="dashboard-filter-form" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-4">
                        <label for="filter-outlet" class="block text-xs font-semibold text-stone-600 mb-1">
                            Cabang / Outlet
                        </label>
                        <select
                            id="filter-outlet"
                            name="outlet_id"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm font-medium text-stone-800 focus:bg-white focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none min-h-[44px]"
                        >
                            <option value="">Semua Cabang</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(($filter['outlet_id'] ?? null) == $outlet->id)>
                                    {{ $outlet->nama_outlet }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 md:col-span-5">
                        <div>
                            <label for="filter-start-date" class="block text-xs font-semibold text-stone-600 mb-1">
                                Dari Tanggal
                            </label>
                            <input
                                type="date"
                                id="filter-start-date"
                                name="start_date"
                                value="{{ $filter['start_date'] }}"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm font-medium text-stone-800 focus:bg-white focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none min-h-[44px]"
                            >
                        </div>

                        <div>
                            <label for="filter-end-date" class="block text-xs font-semibold text-stone-600 mb-1">
                                Sampai Tanggal
                            </label>
                            <input
                                type="date"
                                id="filter-end-date"
                                name="end_date"
                                value="{{ $filter['end_date'] }}"
                                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm font-medium text-stone-800 focus:bg-white focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none min-h-[44px]"
                            >
                        </div>
                    </div>

                    <div class="md:col-span-3 flex gap-2">
                        <button
                            type="submit"
                            id="btn-apply-filter"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white font-bold text-sm shadow-2xs transition-colors cursor-pointer min-h-[44px]"
                        >
                            <svg id="filter-spinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="btn-apply-text">Terapkan</span>
                        </button>

                        <a
                            id="btn-export-inline"
                            href="{{ route('dashboard.export', array_filter([
                                'outlet_id' => $filter['outlet_id'] ?? null,
                                'start_date' => $filter['start_date'] ?? null,
                                'end_date' => $filter['end_date'] ?? null,
                            ])) }}"
                            class="md:hidden inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-red-950 font-bold text-sm transition-colors min-h-[44px]"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Export</span>
                        </a>
                    </div>
                </form>
            </div>
        </section>

        {{-- 3. 4 Kartu Metrik (Grid 2x2 di Mobile, 4 Kolom di Desktop) --}}
        <section id="metrics-grid" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 transition-opacity duration-200" aria-label="Metrik Ringkasan">
            {{-- Kartu 1: Total Revenue --}}
            <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-3.5 sm:p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold text-stone-500">Total Revenue</span>
                    <span class="h-8 w-8 rounded-xl bg-red-50 text-[#DC2626] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p id="metric-revenue" class="mt-2 text-base sm:text-xl font-extrabold text-stone-900 truncate">
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                </p>
                <p id="metric-revenue-sub" class="mt-1 text-[11px] text-stone-500 truncate">
                    Tunai {{ number_format((float) $tunaiData['total'], 0, ',', '.') }} • QRIS {{ number_format((float) $qrisData['total'], 0, ',', '.') }}
                </p>
            </article>

            {{-- Kartu 2: Total Transaksi --}}
            <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-3.5 sm:p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold text-stone-500">Total Transaksi</span>
                    <span class="h-8 w-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25 11.25 16.5 15 12m-9-7.5h12A2.25 2.25 0 0 1 20.25 6.75v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75A2.25 2.25 0 0 1 6 4.5Z" />
                        </svg>
                    </span>
                </div>
                <p id="metric-transaksi" class="mt-2 text-base sm:text-xl font-extrabold text-stone-900">
                    {{ number_format($totalTransaksi, 0, ',', '.') }}
                </p>
                <p id="metric-transaksi-sub" class="mt-1 text-[11px] text-stone-500 truncate">
                    {{ number_format((int) $tunaiData['jumlah_transaksi'], 0, ',', '.') }} Tunai • {{ number_format((int) $qrisData['jumlah_transaksi'], 0, ',', '.') }} QRIS
                </p>
            </article>

            {{-- Kartu 3: Total Pengeluaran Restock --}}
            <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-3.5 sm:p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold text-stone-500">Biaya Restock</span>
                    <span class="h-8 w-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25" />
                        </svg>
                    </span>
                </div>
                <p id="metric-restock" class="mt-2 text-base sm:text-xl font-extrabold text-stone-900 truncate">
                    Rp {{ number_format($totalRestock, 0, ',', '.') }}
                </p>
                <p id="metric-restock-sub" class="mt-1 text-[11px] text-stone-500 truncate">
                    {{ number_format($jumlahRestock, 0, ',', '.') }} kali nota restock
                </p>
            </article>

            {{-- Kartu 4: Jumlah Bahan Stok Menipis --}}
            <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-3.5 sm:p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] sm:text-xs font-bold text-stone-500">Stok Menipis</span>
                    <span id="metric-alert-icon" class="h-8 w-8 rounded-xl {{ $totalAlert > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </span>
                </div>
                <p id="metric-stok-alert" class="mt-2 text-base sm:text-xl font-extrabold {{ $totalAlert > 0 ? 'text-[#DC2626]' : 'text-emerald-600' }}">
                    {{ number_format($totalAlert, 0, ',', '.') }} Bahan
                </p>
                <p id="metric-stok-alert-sub" class="mt-1 text-[11px] text-stone-500 truncate">
                    dari {{ number_format($totalItemStok, 0, ',', '.') }} item bahan
                </p>
            </article>
        </section>

        {{-- 4. Grafik Tren Penjualan Harian (Chart.js CDN, Tinggi Tetap di Mobile) --}}
        <section class="bg-white rounded-2xl border border-stone-100 shadow-xs p-4 sm:p-5" aria-label="Grafik Tren Penjualan">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-stone-900">Tren Penjualan Harian</h2>
                    <p id="chart-period-label" class="text-xs text-stone-500">
                        {{ $filter['start_date'] }} s/d {{ $filter['end_date'] }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-50 text-[#DC2626]">
                    <span class="h-2 w-2 rounded-full bg-[#DC2626]"></span>
                    Omzet Lunas
                </span>
            </div>

            <div class="relative h-56 sm:h-68 w-full">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </section>

        {{-- 5. Status & Peringatan Bahan Stok Menipis (Kartu di Mobile, Tabel di Desktop) --}}
        <section aria-label="Status dan Alert Stok Bahan">
            <div class="flex items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm sm:text-base font-extrabold text-stone-900">Peringatan Stok Bahan</h2>
                    <span
                        id="alert-badge-count"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $totalAlert > 0 ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}"
                    >
                        {{ $totalAlert > 0 ? "{$totalAlert} Perlu Restock" : 'Stok Aman' }}
                    </span>
                </div>

                <button
                    type="button"
                    data-open-notif-sheet
                    class="text-xs font-bold text-[#DC2626] hover:underline cursor-pointer min-h-[36px] px-1"
                >
                    Lihat Semua
                </button>
            </div>

            {{-- Daftar Kartu di Mobile (<768px) --}}
            <div id="stok-alert-mobile" class="md:hidden space-y-2.5">
                @forelse ($stokAlerts as $bahan)
                    <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-4 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-stone-900 truncate">{{ $bahan->nama_bahan }}</p>
                            <p class="text-xs text-stone-500 truncate mt-0.5">{{ $bahan->outlet?->nama_outlet ?? '-' }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                    ! Perlu Restock
                                </span>
                                <span class="text-xs text-stone-600">
                                    Sisa <strong class="text-stone-900">{{ number_format((float) $bahan->stok, 2, ',', '.') }}</strong> / min {{ number_format((float) $bahan->stok_minimal, 2, ',', '.') }} {{ $bahan->satuan }}
                                </span>
                            </div>
                        </div>

                        <a
                            href="{{ route('stok.index', ['outlet_id' => $bahan->outlet_id]) }}"
                            class="shrink-0 inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-[#DC2626] font-bold text-xs transition-colors min-h-[44px]"
                        >
                            Restock
                        </a>
                    </article>
                @empty
                    <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-stone-900">Semua Cabang &amp; Bahan Baku</p>
                            <span class="inline-flex items-center gap-1 mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                &#10003; Stok Aman
                            </span>
                        </div>
                        <a
                            href="{{ route('stok.index') }}"
                            class="shrink-0 inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-stone-100 text-stone-700 font-bold text-xs min-h-[44px]"
                        >
                            Kelola Stok
                        </a>
                    </article>
                @endforelse
            </div>

            {{-- Tabel di Tablet/Desktop (>=768px) --}}
            <div class="hidden md:block bg-white rounded-2xl border border-stone-100 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-stone-50 border-b border-stone-100 text-[11px] font-bold uppercase tracking-wider text-stone-500">
                                <th class="px-5 py-3.5">Nama Bahan</th>
                                <th class="px-5 py-3.5 text-right">Stok Saat Ini</th>
                                <th class="px-5 py-3.5 text-right">Batas Minimum</th>
                                <th class="px-5 py-3.5">Satuan</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="stok-alert-tbody" class="divide-y divide-stone-100 text-sm">
                            @forelse ($stokAlerts as $bahan)
                                <tr class="hover:bg-stone-50/80 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-stone-900">{{ $bahan->nama_bahan }}</p>
                                        <p class="text-xs text-stone-500">{{ $bahan->outlet?->nama_outlet ?? '-' }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-[#DC2626]">
                                        {{ number_format((float) $bahan->stok, 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-stone-600">
                                        {{ number_format((float) $bahan->stok_minimal, 2, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-stone-600">{{ $bahan->satuan }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                            Perlu Restock
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a
                                            href="{{ route('stok.index', ['outlet_id' => $bahan->outlet_id]) }}"
                                            class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white font-bold text-xs transition-colors min-h-[38px]"
                                        >
                                            Restock
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            &#10003; Stok Aman — Tidak ada bahan di bawah batas minimum
                                        </span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection

{{-- Bottom Sheet Notifikasi Alert Stok --}}
@push('modals')
    @include('partials.notif-stok-sheet', [
        'totalAlert' => $totalAlert,
        'stokAlerts' => $stokAlerts,
        'riwayatRestock' => $riwayatRestock,
    ])
@endpush

@push('scripts')
    <script>
        (function () {
            const filterUrl = @json(route('dashboard.filter'));
            const exportBaseUrl = @json(route('dashboard.export'));
            const stokBaseUrl = @json(route('stok.index'));
            const totalOutletsCount = @json($jumlahOutlet);
            const initialTrend = @json($ringkasan_penjualan['tren_penjualan_harian'] ?? []);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const form = document.getElementById('dashboard-filter-form');
            const outletSelect = document.getElementById('filter-outlet');
            const startInput = document.getElementById('filter-start-date');
            const endInput = document.getElementById('filter-end-date');
            const applyBtn = document.getElementById('btn-apply-filter');
            const applyText = document.getElementById('btn-apply-text');
            const spinner = document.getElementById('filter-spinner');
            const metricsGrid = document.getElementById('metrics-grid');

            // Helper format angka & Rupiah (id-ID)
            function formatRupiah(value) {
                const num = Math.round(Number(value) || 0);
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
            }

            function formatAngka(value, decimals = 0) {
                const num = Number(value) || 0;
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                }).format(num);
            }

            function formatTanggalSingkat(isoDate) {
                if (!isoDate) return '';
                const parts = String(isoDate).split('-');
                if (parts.length !== 3) return isoDate;
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const idx = parseInt(parts[1], 10) - 1;
                return `${parseInt(parts[2], 10)} ${months[idx] || parts[1]}`;
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // ─── Toggle Collapse Filter di Mobile ─────────────────────────
            const filterPanel = document.getElementById('filter-collapse-panel');
            const toggleBtnBar = document.getElementById('mobile-filter-toggle-bar');
            const toggleBtnTop = document.getElementById('mobile-filter-toggle-top');
            const toggleLabel = document.getElementById('filter-toggle-label');
            const filterChevron = document.getElementById('filter-chevron');

            function toggleFilterPanel() {
                if (!filterPanel) return;
                const isHidden = filterPanel.classList.contains('hidden');
                filterPanel.classList.toggle('hidden', !isHidden);
                if (toggleBtnBar) toggleBtnBar.setAttribute('aria-expanded', String(isHidden));
                if (toggleBtnTop) toggleBtnTop.setAttribute('aria-expanded', String(isHidden));
                if (toggleLabel) toggleLabel.textContent = isHidden ? 'Tutup' : 'Ubah';
                if (filterChevron) filterChevron.classList.toggle('rotate-180', isHidden);
            }

            if (toggleBtnBar) toggleBtnBar.addEventListener('click', toggleFilterPanel);
            if (toggleBtnTop) toggleBtnTop.addEventListener('click', toggleFilterPanel);

            // ─── Bottom Sheet Notifikasi & Alert Stok ────────────────────
            const sheetRoot = document.getElementById('notif-sheet-root');
            const sheetBackdrop = document.getElementById('notif-sheet-backdrop');
            const sheetClose = document.getElementById('notif-sheet-close');
            const sheetMarkRead = document.getElementById('notif-mark-read');
            const openSheetButtons = document.querySelectorAll('[data-open-notif-sheet]');
            const tabButtons = document.querySelectorAll('.notif-tab-btn');
            let currentNotifTab = 'semua';

            function openNotifSheet() {
                if (!sheetRoot) return;
                sheetRoot.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeNotifSheet() {
                if (!sheetRoot) return;
                sheetRoot.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function applyNotifTabFilter(tabName) {
                currentNotifTab = tabName;
                tabButtons.forEach(btn => {
                    const active = btn.dataset.notifTab === tabName;
                    btn.className = active
                        ? 'notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#DC2626] text-white transition-colors cursor-pointer min-h-[36px] shrink-0'
                        : 'notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer min-h-[36px] shrink-0';
                });

                document.querySelectorAll('#notif-sheet-list .notif-item').forEach(el => {
                    const show = tabName === 'semua' || el.dataset.notifType === tabName;
                    el.classList.toggle('hidden', !show);
                });
            }

            openSheetButtons.forEach(btn => btn.addEventListener('click', openNotifSheet));
            if (sheetBackdrop) sheetBackdrop.addEventListener('click', closeNotifSheet);
            if (sheetClose) sheetClose.addEventListener('click', closeNotifSheet);
            tabButtons.forEach(btn => btn.addEventListener('click', () => applyNotifTabFilter(btn.dataset.notifTab)));

            if (sheetMarkRead) {
                sheetMarkRead.addEventListener('click', function () {
                    document.querySelectorAll('.notif-bell-dot').forEach(dot => dot.classList.add('hidden'));
                    closeNotifSheet();
                    window.showOwnerToast('Semua notifikasi stok ditandai sudah dibaca.', 'success');
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeNotifSheet();
            });

            // ─── Chart.js Tren Penjualan Harian ──────────────────────────
            let salesChart = null;
            const chartCanvas = document.getElementById('salesTrendChart');

            function buildChartData(tren) {
                return {
                    labels: tren.map(item => formatTanggalSingkat(item.tanggal)),
                    values: tren.map(item => Number(item.total_penjualan) || 0),
                    counts: tren.map(item => Number(item.jumlah_transaksi) || 0),
                };
            }

            if (chartCanvas && typeof Chart !== 'undefined') {
                const initial = buildChartData(initialTrend);
                salesChart = new Chart(chartCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: initial.labels,
                        datasets: [{
                            label: 'Omzet Harian (Rp)',
                            data: initial.values,
                            backgroundColor: 'rgba(220, 38, 38, 0.88)',
                            hoverBackgroundColor: 'rgba(245, 158, 11, 0.95)',
                            borderRadius: 8,
                            maxBarThickness: 36,
                            transactionCounts: initial.counts,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1c1917',
                                padding: 10,
                                cornerRadius: 10,
                                callbacks: {
                                    label: function (ctx) {
                                        const trx = ctx.dataset.transactionCounts?.[ctx.dataIndex] ?? 0;
                                        return [`Omzet: ${formatRupiah(ctx.parsed.y)}`, `Transaksi: ${formatAngka(trx)} trx`];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#78716c', font: { size: 11, weight: '600' } }
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: '#f5f5f4' },
                                ticks: {
                                    color: '#78716c',
                                    font: { size: 11 },
                                    callback: function (val) {
                                        if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'jt';
                                        if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'rb';
                                        return 'Rp ' + val;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ─── Sinkronisasi Tautan Export Laporan ──────────────────────
            function updateExportLinks(params) {
                const qs = params.toString();
                const url = qs ? `${exportBaseUrl}?${qs}` : exportBaseUrl;
                ['btn-export-header', 'btn-export-inline'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.href = url;
                });
            }

            // ─── Update Daftar Stok Menipis (Mobile Card, Desktop Table, Bottom Sheet) ───
            function updateStokViews(stok, restocks) {
                const alerts = stok.stok_alert || [];
                const total = Number(stok.total_alert) || 0;

                const badge = document.getElementById('alert-badge-count');
                if (badge) {
                    badge.textContent = total > 0 ? `${total} Perlu Restock` : 'Stok Aman';
                    badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold ${
                        total > 0 ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800'
                    }`;
                }

                // Mobile Cards
                const mobileEl = document.getElementById('stok-alert-mobile');
                if (mobileEl) {
                    if (alerts.length === 0) {
                        mobileEl.innerHTML = `
                            <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-stone-900">Semua Cabang &amp; Bahan Baku</p>
                                    <span class="inline-flex items-center gap-1 mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        &#10003; Stok Aman
                                    </span>
                                </div>
                                <a href="${stokBaseUrl}" class="shrink-0 inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-stone-100 text-stone-700 font-bold text-xs min-h-[44px]">
                                    Kelola Stok
                                </a>
                            </article>`;
                    } else {
                        mobileEl.innerHTML = alerts.map(item => {
                            const restockUrl = `${stokBaseUrl}?outlet_id=${encodeURIComponent(item.outlet_id || '')}`;
                            return `
                                <article class="bg-white rounded-2xl border border-stone-100 shadow-xs p-4 flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-stone-900 truncate">${escapeHtml(item.nama_bahan)}</p>
                                        <p class="text-xs text-stone-500 truncate mt-0.5">${escapeHtml(item.outlet?.nama_outlet || '-')}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                                ! Perlu Restock
                                            </span>
                                            <span class="text-xs text-stone-600">
                                                Sisa <strong class="text-stone-900">${formatAngka(item.stok, 2)}</strong> / min ${formatAngka(item.stok_minimal, 2)} ${escapeHtml(item.satuan)}
                                            </span>
                                        </div>
                                    </div>
                                    <a href="${restockUrl}" class="shrink-0 inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-[#DC2626] font-bold text-xs transition-colors min-h-[44px]">
                                        Restock
                                    </a>
                                </article>`;
                        }).join('');
                    }
                }

                // Desktop Table
                const tbody = document.getElementById('stok-alert-tbody');
                if (tbody) {
                    if (alerts.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        &#10003; Stok Aman — Tidak ada bahan di bawah batas minimum
                                    </span>
                                </td>
                            </tr>`;
                    } else {
                        tbody.innerHTML = alerts.map(item => {
                            const restockUrl = `${stokBaseUrl}?outlet_id=${encodeURIComponent(item.outlet_id || '')}`;
                            return `
                                <tr class="hover:bg-stone-50/80 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <p class="font-bold text-stone-900">${escapeHtml(item.nama_bahan)}</p>
                                        <p class="text-xs text-stone-500">${escapeHtml(item.outlet?.nama_outlet || '-')}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold text-[#DC2626]">${formatAngka(item.stok, 2)}</td>
                                    <td class="px-5 py-3.5 text-right text-stone-600">${formatAngka(item.stok_minimal, 2)}</td>
                                    <td class="px-5 py-3.5 text-stone-600">${escapeHtml(item.satuan)}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                            Perlu Restock
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="${restockUrl}" class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white font-bold text-xs transition-colors min-h-[38px]">
                                            Restock
                                        </a>
                                    </td>
                                </tr>`;
                        }).join('');
                    }
                }

                // Update Bottom Sheet Notifikasi
                const sheetBadge = document.getElementById('sheet-kritis-badge');
                const sheetTabKritis = document.getElementById('sheet-tab-kritis-count');
                const sheetTabInfo = document.getElementById('sheet-tab-info-count');
                const sheetList = document.getElementById('notif-sheet-list');
                const recentRestocks = (restocks || []).slice(0, 5);

                if (sheetBadge) {
                    sheetBadge.textContent = `${total} Kritis`;
                    sheetBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold shrink-0 ${
                        total > 0 ? 'bg-red-100 text-[#DC2626]' : 'bg-emerald-100 text-emerald-700'
                    }`;
                }
                if (sheetTabKritis) sheetTabKritis.textContent = total;
                if (sheetTabInfo) sheetTabInfo.textContent = recentRestocks.length;
                document.querySelectorAll('.notif-bell-dot').forEach(dot => dot.classList.toggle('hidden', total === 0));

                if (sheetList) {
                    const kritisHtml = alerts.map(item => {
                        const isHabis = (Number(item.stok) || 0) <= 0;
                        const restockUrl = `${stokBaseUrl}?outlet_id=${encodeURIComponent(item.outlet_id || '')}`;
                        return `
                            <div data-notif-type="kritis" class="notif-item rounded-2xl p-3.5 border-l-4 ${isHabis ? 'border-l-[#DC2626] bg-red-50/80' : 'border-l-amber-400 bg-amber-50/70'} flex items-start gap-3">
                                <div class="h-8 w-8 rounded-full ${isHabis ? 'bg-red-100 text-[#DC2626]' : 'bg-amber-100 text-amber-600'} flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-bold text-stone-900 leading-snug">${isHabis ? `Stok ${escapeHtml(item.nama_bahan)} Habis!` : `${escapeHtml(item.nama_bahan)} Menipis`}</p>
                                        <span class="text-[11px] font-semibold ${isHabis ? 'text-[#DC2626]' : 'text-amber-700'} shrink-0">${isHabis ? 'Habis' : 'Perlu Restock'}</span>
                                    </div>
                                    <p class="text-xs text-stone-600 mt-1">
                                        ${escapeHtml(item.outlet?.nama_outlet || 'Outlet')} tersisa <span class="font-bold text-stone-800">${formatAngka(item.stok, 2)} ${escapeHtml(item.satuan)}</span> (batas minimum ${formatAngka(item.stok_minimal, 2)} ${escapeHtml(item.satuan)}).
                                    </p>
                                    <a href="${restockUrl}" class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-[#DC2626] hover:underline">
                                        <span>Restock Sekarang</span><span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </div>
                            </div>`;
                    }).join('');

                    const infoHtml = recentRestocks.map(r => `
                        <div data-notif-type="info" class="notif-item rounded-2xl p-3.5 border border-stone-100 bg-white shadow-2xs flex items-start gap-3">
                            <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-bold text-stone-900 leading-snug">Restock ${escapeHtml(r.outlet?.nama_outlet || 'Outlet')}</p>
                                    <span class="text-[11px] text-stone-400 shrink-0">${formatTanggalSingkat(String(r.tanggal_restock || '').slice(0, 10))}</span>
                                </div>
                                <p class="text-xs text-stone-600 mt-1">
                                    Pengeluaran ${formatRupiah(r.total_biaya)} ke ${escapeHtml(r.supplier?.nama_supplier || 'Supplier')}.
                                </p>
                            </div>
                        </div>`).join('');

                    sheetList.innerHTML = (kritisHtml + infoHtml) || `
                        <div class="py-8 text-center text-sm text-stone-500">
                            <p class="font-semibold text-emerald-700">Semua stok aman &amp; belum ada notifikasi baru.</p>
                        </div>`;
                    applyNotifTabFilter(currentNotifTab);
                }
            }

            // ─── Render Ulang Seluruh Dashboard dari JSON Backend ─────────
            function renderDashboard(data) {
                const filter = data.filter || {};
                const ringkasan = data.ringkasan_penjualan || {};
                const metode = ringkasan.metode_pembayaran || {};
                const tunai = metode.TUNAI || { total: 0, jumlah_transaksi: 0 };
                const qris = metode.QRIS || { total: 0, jumlah_transaksi: 0 };
                const restock = data.laporan_pengeluaran_restock || { total_pengeluaran: 0, jumlah_restock: 0, riwayat_restock: [] };
                const stok = data.laporan_stok || { total_item_stok: 0, total_alert: 0, stok_alert: [] };

                const selectedOutletLabel = outletSelect.value
                    ? outletSelect.options[outletSelect.selectedIndex]?.text || 'Cabang Terpilih'
                    : 'Semua Cabang';

                // Hero Card
                document.getElementById('hero-period-label').textContent =
                    `Total Revenue (${filter.start_date || startInput.value} s/d ${filter.end_date || endInput.value})`;
                document.getElementById('hero-outlet-badge').textContent =
                    outletSelect.value ? selectedOutletLabel : `${totalOutletsCount} Outlet Aktif`;
                document.getElementById('hero-revenue').textContent = formatRupiah(ringkasan.total_revenue);
                document.getElementById('hero-transaksi').textContent = `${formatAngka(ringkasan.total_volume_transaksi)} Transaksi`;
                document.getElementById('hero-outlet-info').textContent =
                    outletSelect.value ? selectedOutletLabel : `${totalOutletsCount} Outlet Operasional`;

                // Summary Bar Filter Mobile
                const mobileSummary = document.getElementById('mobile-filter-summary');
                if (mobileSummary) {
                    mobileSummary.textContent = `${selectedOutletLabel} • ${filter.start_date || startInput.value} s/d ${filter.end_date || endInput.value}`;
                }

                // 4 Kartu Metrik
                document.getElementById('metric-revenue').textContent = formatRupiah(ringkasan.total_revenue);
                document.getElementById('metric-revenue-sub').textContent =
                    `Tunai ${formatAngka(tunai.total)} • QRIS ${formatAngka(qris.total)}`;

                document.getElementById('metric-transaksi').textContent = formatAngka(ringkasan.total_volume_transaksi);
                document.getElementById('metric-transaksi-sub').textContent =
                    `${formatAngka(tunai.jumlah_transaksi)} Tunai • ${formatAngka(qris.jumlah_transaksi)} QRIS`;

                document.getElementById('metric-restock').textContent = formatRupiah(restock.total_pengeluaran);
                document.getElementById('metric-restock-sub').textContent =
                    `${formatAngka(restock.jumlah_restock)} kali nota restock`;

                const totalAlert = Number(stok.total_alert) || 0;
                const metricAlert = document.getElementById('metric-stok-alert');
                const metricAlertIcon = document.getElementById('metric-alert-icon');
                metricAlert.textContent = `${formatAngka(totalAlert)} Bahan`;
                metricAlert.className = `mt-2 text-base sm:text-xl font-extrabold ${totalAlert > 0 ? 'text-[#DC2626]' : 'text-emerald-600'}`;
                if (metricAlertIcon) {
                    metricAlertIcon.className = `h-8 w-8 rounded-xl flex items-center justify-center shrink-0 ${
                        totalAlert > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'
                    }`;
                }
                document.getElementById('metric-stok-alert-sub').textContent =
                    `dari ${formatAngka(stok.total_item_stok)} item bahan`;

                // Grafik Chart.js
                document.getElementById('chart-period-label').textContent =
                    `${filter.start_date || startInput.value} s/d ${filter.end_date || endInput.value}`;

                if (salesChart) {
                    const next = buildChartData(ringkasan.tren_penjualan_harian || []);
                    salesChart.data.labels = next.labels;
                    salesChart.data.datasets[0].data = next.values;
                    salesChart.data.datasets[0].transactionCounts = next.counts;
                    salesChart.update();
                }

                // Daftar & Tabel Stok + Bottom Sheet
                updateStokViews(stok, restock.riwayat_restock);
            }

            // ─── Skeleton / Loading State ────────────────────────────────
            function setLoading(isLoading) {
                if (applyBtn) applyBtn.disabled = isLoading;
                if (spinner) spinner.classList.toggle('hidden', !isLoading);
                if (applyText) applyText.textContent = isLoading ? 'Memuat...' : 'Terapkan';
                if (metricsGrid) {
                    metricsGrid.classList.toggle('animate-pulse', isLoading);
                    metricsGrid.classList.toggle('opacity-60', isLoading);
                }
            }

            // ─── Fetch API ke GET /dashboard/filter ──────────────────────
            async function fetchDashboardData() {
                const params = new URLSearchParams();
                if (outletSelect.value) params.set('outlet_id', outletSelect.value);
                if (startInput.value) params.set('start_date', startInput.value);
                if (endInput.value) params.set('end_date', endInput.value);

                updateExportLinks(params);
                setLoading(true);

                try {
                    const response = await fetch(`${filterUrl}?${params.toString()}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });

                    const json = await response.json();

                    if (!response.ok || !json.success) {
                        const msg = json.errors
                            ? Object.values(json.errors).flat()[0]
                            : (json.message || 'Gagal memuat data filter dashboard.');
                        window.showOwnerToast(msg, 'error');
                        return;
                    }

                    renderDashboard(json.data);
                } catch (error) {
                    window.showOwnerToast('Terjadi gangguan koneksi saat mengambil data dashboard.', 'error');
                } finally {
                    setLoading(false);
                }
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    fetchDashboardData();
                });
            }

            if (outletSelect) {
                outletSelect.addEventListener('change', fetchDashboardData);
            }
        })();
    </script>
@endpush
