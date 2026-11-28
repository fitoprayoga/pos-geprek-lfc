@extends('layouts.owner')

@section('title', 'Laporan Penjualan')
@section('page_title', 'Laporan Penjualan')
@section('page_subtitle', 'Ringkasan revenue terfilter, performa cabang & menu terlaris')

@push('head')
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            aside,
            header,
            nav,
            footer,
            #toast-container,
            .no-print,
            .fixed,
            [style*="position: fixed"],
            [style*="position:fixed"],
            body > *:not(:has(#area-laporan-print)) {
                display: none !important;
            }

            html,
            body {
                background: #ffffff !important;
                color: #111827 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            #area-laporan-print,
            #area-laporan-print * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            main {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #area-laporan-print {
                display: block !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            #area-laporan-print .shadow-sm,
            #area-laporan-print .shadow-md,
            #area-laporan-print .shadow-xs,
            #area-laporan-print .shadow-2xs {
                box-shadow: none !important;
            }

            #area-laporan-print .print-card-clean {
                border-color: #e7e5e4 !important;
            }

            .print-break-inside-avoid {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
@endpush

@php
    $formatShortRupiah = function (float $value): string {
        if ($value <= 0) {
            return '0';
        }
        if ($value >= 1000000) {
            $jt = $value / 1000000;
            return rtrim(rtrim(number_format($jt, 1, ',', ''), '0'), ',') . 'jt';
        }
        if ($value >= 1000) {
            $rb = $value / 1000;
            return rtrim(rtrim(number_format($rb, 0, ',', ''), '0'), ',') . 'rb';
        }
        return number_format($value, 0, ',', '.');
    };

    $shortOutletName = function (string $name): string {
        $cleaned = preg_replace('/^(LFC\s+Cabang\s+|LFC\s+|Cabang\s+)/i', '', trim($name));
        return $cleaned !== '' ? $cleaned : $name;
    };

    $maxOutletRevenue = (float) ($performaOutlet->max('total_revenue') ?? 0);
    $exportUrl = route('dashboard.export', array_filter([
        'outlet_id' => $filter['outlet_id'] ?? null,
        'start_date' => $filter['start_date'] ?? null,
        'end_date' => $filter['end_date'] ?? null,
    ]));
@endphp

@section('content')
    <div id="area-laporan-print" class="max-w-5xl mx-auto space-y-4 sm:space-y-5">
        {{-- Header Halaman (Khusus Mobile, Desktop sudah ada di Topbar Layout) --}}
        <div class="flex items-center justify-between gap-3 no-print md:hidden">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-white border border-stone-200 text-stone-700 hover:bg-red-50 hover:text-[#DC2626] hover:border-red-200 transition-colors shrink-0"
                    aria-label="Kembali ke Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-900 truncate">Laporan Penjualan</h1>
            </div>
            <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-stone-100 text-stone-600">
                {{ \Carbon\Carbon::parse($filter['start_date'])->translatedFormat('d M Y') }}
                @if ($filter['start_date'] !== $filter['end_date'])
                    — {{ \Carbon\Carbon::parse($filter['end_date'])->translatedFormat('d M Y') }}
                @endif
            </span>
        </div>

        {{-- Header Khusus Print --}}
        <div class="hidden print:block border-b border-stone-300 pb-3 mb-4">
            <h1 class="text-xl font-extrabold text-stone-900">Laporan Penjualan — Geprek LFC</h1>
            <p class="text-xs text-stone-600 mt-1">
                Outlet: {{ $selectedOutlet?->nama_outlet ?? 'Semua Outlet' }} &bull;
                Periode: {{ \Carbon\Carbon::parse($filter['start_date'])->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($filter['end_date'])->translatedFormat('d M Y') }}
            </p>
        </div>

        {{-- 2 Tombol Filter Pill Sejajar --}}
        <form id="laporan-quick-filter-form" method="GET" action="{{ route('laporan.index') }}"
            class="grid grid-cols-2 gap-3 no-print" aria-label="Filter Laporan Penjualan">
            <input type="hidden" name="start_date" value="{{ $filter['start_date'] }}">
            <input type="hidden" name="end_date" value="{{ $filter['end_date'] }}">

            {{-- Pill 1: Dropdown Outlet --}}
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-[#DC2626]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" />
                    </svg>
                </span>
                <select name="outlet_id" id="laporan-outlet-select" onchange="this.form.submit()"
                    aria-label="Filter Outlet"
                    class="w-full appearance-none rounded-full bg-white border border-stone-200 hover:border-red-300 focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none pl-9 pr-8 py-2.5 text-xs sm:text-sm font-semibold text-stone-800 shadow-2xs cursor-pointer truncate min-h-[44px]">
                    <option value="">Semua Outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(($filter['outlet_id'] ?? null) == $outlet->id)>
                            {{ $outlet->nama_outlet }}
                        </option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </div>

            {{-- Pill 2: Tombol Buka Bottom Sheet Rentang Tanggal --}}
            <button type="button" id="btn-open-date-sheet"
                class="w-full flex items-center justify-between gap-2 rounded-full bg-white border border-stone-200 hover:border-red-300 focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-stone-800 shadow-2xs cursor-pointer min-h-[44px]">
                <span class="flex items-center gap-2 min-w-0">
                    <svg class="w-4 h-4 text-[#DC2626] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span class="truncate">{{ $dateRangeLabel }}</span>
                </span>
                <svg class="w-4 h-4 text-stone-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        </form>

        {{-- Kartu Hero Merah --}}
        <section
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-600 to-red-700 text-white p-5 shadow-md print:shadow-none print:border print:border-red-700 print-break-inside-avoid"
            aria-label="Ringkasan Total Revenue Terfilter">
            <div class="pointer-events-none absolute -top-10 -right-10 h-36 w-36 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-12 right-12 h-24 w-24 rounded-full bg-white/10"></div>

            <p class="relative text-[11px] font-bold uppercase tracking-wider text-red-100">
                TOTAL REVENUE TERFILTER
            </p>
            <p class="relative mt-1.5 text-3xl sm:text-4xl font-extrabold tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </p>

            <div class="relative mt-4 pt-3 border-t border-white/20 flex items-center justify-between gap-3 text-xs sm:text-sm font-medium text-red-50">
                <span>{{ number_format($totalTransaksi, 0, ',', '.') }} Transaksi</span>
                <span>Restock: Rp {{ number_format($totalRestock, 0, ',', '.') }}</span>
            </div>
        </section>

        {{-- Grid Responsif: Grafik Performa 5 Outlet & Menu Terlaris --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5 items-start">
            {{-- Kartu Performa Revenue 5 Outlet --}}
            <section
                class="lg:col-span-5 bg-white rounded-2xl p-5 shadow-sm border border-stone-100 print-card-clean print:shadow-none print:border-stone-200 print-break-inside-avoid"
                aria-label="Performa Revenue 5 Outlet">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h2 class="text-sm sm:text-base font-extrabold text-stone-900">
                        Performa Revenue {{ $performaOutlet->count() }} Outlet
                    </h2>
                    @if ($selectedOutlet)
                        <span class="text-[11px] font-bold text-[#DC2626] bg-red-50 px-2.5 py-0.5 rounded-full">
                            {{ $shortOutletName($selectedOutlet->nama_outlet) }}
                        </span>
                    @endif
                </div>

                @if ($performaOutlet->isEmpty())
                    <div class="py-10 text-center text-xs text-stone-400">
                        Belum ada data outlet terdaftar.
                    </div>
                @else
                    <div class="flex items-end justify-between gap-2 sm:gap-3 pt-2">
                        @foreach ($performaOutlet as $index => $outlet)
                            @php
                                $rev = (float) $outlet['total_revenue'];
                                $isHighlighted = $maxOutletRevenue > 0 && (
                                    ($filter['outlet_id'] && (int) $filter['outlet_id'] === (int) $outlet['id']) ||
                                    (!$filter['outlet_id'] && $index === 0)
                                );
                                $heightPercent = $maxOutletRevenue > 0
                                    ? ($rev > 0 ? max(10, (int) round(($rev / $maxOutletRevenue) * 100)) : 4)
                                    : 6;
                            @endphp
                            <div class="flex-1 min-w-0 flex flex-col items-center">
                                <span class="text-[11px] font-bold mb-1.5 {{ $rev <= 0 ? 'text-stone-400' : ($isHighlighted ? 'text-red-600' : 'text-amber-600') }}">
                                    {{ $formatShortRupiah($rev) }}
                                </span>

                                <div class="w-full max-w-[44px] h-36 flex items-end justify-center" style="height: 144px;">
                                    <div
                                        class="w-full rounded-t-lg transition-all duration-300 border {{ $maxOutletRevenue <= 0 || $rev <= 0 ? 'bg-stone-200 border-stone-300' : ($isHighlighted ? 'bg-red-600 border-red-700' : 'bg-amber-500 border-amber-600') }}"
                                        style="height: {{ $heightPercent }}%; min-height: 6px;"
                                        title="{{ $outlet['nama_outlet'] }}: Rp {{ number_format($rev, 0, ',', '.') }}">
                                    </div>
                                </div>

                                <span class="mt-2 w-full text-center text-[11px] font-medium text-stone-600 truncate"
                                    title="{{ $outlet['nama_outlet'] }}">
                                    {{ $shortOutletName($outlet['nama_outlet']) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Bagian Menu Terlaris --}}
            <section class="lg:col-span-7 space-y-3" aria-label="Menu Terlaris">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm sm:text-base font-extrabold text-stone-900">Menu Terlaris</h2>
                    @if ($menuTerlaris->isNotEmpty())
                        <span class="text-xs font-medium text-stone-500">Top {{ $menuTerlaris->count() }} menu</span>
                    @endif
                </div>

                @if ($menuTerlaris->isEmpty())
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-stone-100 print-card-clean print:shadow-none print:border-stone-200 text-center">
                        <div class="mx-auto h-12 w-12 rounded-2xl bg-stone-100 text-stone-400 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5.75a3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-3 0 3.354 3.354 0 0 0-3 0 3.354 3.354 0 0 1-3 0L3 16.5m15-3.379a48.474 48.474 0 0 0-6-.371c-2.032 0-4.034.126-6 .371m12 0c.39.049.777.102 1.163.16 1.07.16 1.837 1.094 1.837 2.175v5.169c0 .621-.504 1.125-1.125 1.125H4.125A1.125 1.125 0 0 1 3 20.625v-5.17c0-1.08.768-2.014 1.837-2.174A47.78 47.78 0 0 1 6 13.12M12.265 3.11a.375.375 0 1 1-.53 0L12 2.845l.265.265Z" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-stone-700">Belum ada penjualan</p>
                        <p class="text-xs text-stone-500 mt-1">
                            Tidak ada transaksi sukses untuk filter outlet &amp; rentang tanggal ini.
                        </p>
                    </div>
                @else
                    <div class="space-y-2.5">
                        @foreach ($menuTerlaris as $idx => $menu)
                            @php
                                $rank = $idx + 1;
                                $badgeClass = match ($rank) {
                                    1 => 'bg-amber-400 text-amber-950',
                                    2 => 'bg-stone-300 text-stone-800',
                                    3 => 'bg-orange-400 text-white',
                                    default => 'bg-stone-200 text-stone-700',
                                };
                            @endphp
                            <div class="bg-white rounded-2xl p-3.5 shadow-sm border border-stone-100 print-card-clean print:shadow-none print:border-stone-200 flex items-center justify-between gap-3 print-break-inside-avoid">
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Thumbnail Foto atau Fallback Ikon Api --}}
                                    <div class="h-12 w-12 rounded-xl overflow-hidden shrink-0
                                        {{ $menu->foto_url ? '' : 'bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-100 flex items-center justify-center' }}">
                                        @if ($menu->foto_url)
                                            <img
                                                src="{{ $menu->foto_url }}"
                                                alt="{{ $menu->nama_menu }}"
                                                class="w-full h-full object-cover"
                                                loading="lazy"
                                                style="-webkit-print-color-adjust: exact; print-color-adjust: exact;"
                                            >
                                        @else
                                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-stone-900 truncate">{{ $menu->nama_menu }}</p>
                                        <p class="text-xs text-stone-500 mt-0.5">
                                            {{ number_format((int) $menu->total_porsi, 0, ',', '.') }} Porsi terjual
                                        </p>
                                    </div>
                                </div>

                                <p class="text-sm font-extrabold text-[#DC2626] shrink-0">
                                    Rp {{ number_format((float) $menu->total_penjualan, 0, ',', '.') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- Tombol Bawah Unduh Laporan (PDF / Excel) --}}
        <div class="pt-1 no-print">
            <button type="button" id="btn-open-export-sheet"
                class="w-full rounded-2xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-sm sm:text-base py-3.5 px-5 shadow-md flex items-center justify-center gap-2.5 transition-colors cursor-pointer min-h-[48px]">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Unduh Laporan (PDF / Excel)</span>
            </button>
        </div>
    </div>
@endsection

@push('modals')
    {{-- Bottom Sheet: Pilih Rentang Tanggal --}}
    <div id="date-sheet-backdrop"
        class="fixed inset-0 z-50 hidden items-end md:items-center justify-center bg-stone-900/50 backdrop-blur-[1px] no-print"
        role="dialog" aria-modal="true" aria-labelledby="date-sheet-title">
        <div id="date-sheet-panel"
            class="w-full md:max-w-md md:mx-auto bg-white rounded-t-3xl md:rounded-2xl shadow-2xl p-5 pb-6 space-y-4">
            {{-- Drag handle mobile --}}
            <div class="mx-auto h-1 w-10 rounded-full bg-stone-200 md:hidden"></div>

            <div class="flex items-center justify-between gap-3">
                <h2 id="date-sheet-title" class="text-base font-extrabold text-stone-900">Pilih Rentang Tanggal</h2>
                <button type="button" id="btn-close-date-sheet"
                    class="inline-flex items-center justify-center h-9 w-9 rounded-full text-stone-400 hover:bg-stone-100 hover:text-stone-700 transition-colors cursor-pointer"
                    aria-label="Tutup pilih rentang tanggal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Pill Preset Cepat --}}
            <div class="flex flex-wrap gap-2" role="group" aria-label="Preset Rentang Tanggal">
                @php
                    $presets = [
                        'today' => 'Hari Ini',
                        '7d' => '7 Hari Terakhir',
                        '30d' => '30 Hari Terakhir',
                        'month' => 'Bulan Ini',
                        'custom' => 'Rentang Kustom',
                    ];
                @endphp
                @foreach ($presets as $key => $label)
                    <button type="button" data-preset="{{ $key }}"
                        class="preset-pill px-3.5 py-2 rounded-full text-xs font-bold border transition-colors cursor-pointer {{ $activePreset === $key ? 'bg-[#DC2626] text-white border-[#DC2626]' : 'bg-stone-100 text-stone-700 border-stone-200 hover:bg-stone-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Form Filter Tanggal --}}
            <form id="date-sheet-form" method="GET" action="{{ route('laporan.index') }}" class="space-y-4">
                @if ($filter['outlet_id'])
                    <input type="hidden" name="outlet_id" value="{{ $filter['outlet_id'] }}">
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="sheet-start-date" class="block text-[11px] font-semibold text-stone-500 mb-1">
                            Dari
                        </label>
                        <input type="date" id="sheet-start-date" name="start_date"
                            value="{{ $filter['start_date'] }}" required
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-xs sm:text-sm font-bold text-stone-800 focus:bg-white focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none min-h-[44px]">
                    </div>
                    <div>
                        <label for="sheet-end-date" class="block text-[11px] font-semibold text-stone-500 mb-1">
                            Sampai
                        </label>
                        <input type="date" id="sheet-end-date" name="end_date"
                            value="{{ $filter['end_date'] }}" required
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-xs sm:text-sm font-bold text-stone-800 focus:bg-white focus:border-[#DC2626] focus:ring-2 focus:ring-red-100 focus:outline-none min-h-[44px]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <button type="button" id="btn-cancel-date-sheet"
                        class="w-full rounded-xl border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 font-bold text-sm py-3 transition-colors cursor-pointer min-h-[44px]">
                        Batal
                    </button>
                    <button type="submit"
                        class="w-full rounded-xl bg-[#DC2626] hover:bg-[#B91C1C] text-white font-bold text-sm py-3 shadow-sm transition-colors cursor-pointer min-h-[44px]">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Mini Action Sheet: Pilih Format Unduh Laporan --}}
    <div id="export-sheet-backdrop"
        class="fixed inset-0 z-50 hidden items-end md:items-center justify-center bg-stone-900/50 backdrop-blur-[1px] no-print"
        role="dialog" aria-modal="true" aria-labelledby="export-sheet-title">
        <div class="w-full md:max-w-sm md:mx-auto bg-white rounded-t-3xl md:rounded-2xl shadow-2xl p-5 pb-6 space-y-3">
            <div class="mx-auto h-1 w-10 rounded-full bg-stone-200 md:hidden"></div>

            <div class="flex items-center justify-between gap-3">
                <h2 id="export-sheet-title" class="text-base font-extrabold text-stone-900">Pilih Format Laporan</h2>
                <button type="button" id="btn-close-export-sheet"
                    class="inline-flex items-center justify-center h-9 w-9 rounded-full text-stone-400 hover:bg-stone-100 hover:text-stone-700 transition-colors cursor-pointer"
                    aria-label="Tutup pilihan format">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <a href="{{ $exportUrl }}" id="btn-export-csv"
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-stone-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition-colors">
                <span class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </span>
                <div class="text-left min-w-0">
                    <p class="text-sm font-bold text-stone-900">Unduh Excel / CSV</p>
                    <p class="text-xs text-stone-500">File rekap transaksi &amp; ringkasan terfilter</p>
                </div>
            </a>

            <button type="button" id="btn-print-pdf"
                class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-stone-200 hover:border-red-300 hover:bg-red-50/50 transition-colors cursor-pointer">
                <span class="h-10 w-10 rounded-xl bg-red-100 text-[#DC2626] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                </span>
                <div class="text-left min-w-0">
                    <p class="text-sm font-bold text-stone-900">Cetak / Simpan PDF</p>
                    <p class="text-xs text-stone-500">Cetak ringkasan, grafik &amp; menu terlaris</p>
                </div>
            </button>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        (function () {
            const dateBackdrop = document.getElementById('date-sheet-backdrop');
            const openDateBtn = document.getElementById('btn-open-date-sheet');
            const closeDateBtn = document.getElementById('btn-close-date-sheet');
            const cancelDateBtn = document.getElementById('btn-cancel-date-sheet');
            const startInput = document.getElementById('sheet-start-date');
            const endInput = document.getElementById('sheet-end-date');
            const presetButtons = document.querySelectorAll('.preset-pill');

            const exportBackdrop = document.getElementById('export-sheet-backdrop');
            const openExportBtn = document.getElementById('btn-open-export-sheet');
            const closeExportBtn = document.getElementById('btn-close-export-sheet');
            const printPdfBtn = document.getElementById('btn-print-pdf');

            const todayStr = @json(now('Asia/Jakarta')->toDateString());

            function toIsoDate(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function parseLocalIso(iso) {
                const [y, m, d] = iso.split('-').map(Number);
                return new Date(y, m - 1, d);
            }

            function setActivePreset(key) {
                presetButtons.forEach((btn) => {
                    const isActive = btn.dataset.preset === key;
                    btn.classList.toggle('bg-[#DC2626]', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('border-[#DC2626]', isActive);
                    btn.classList.toggle('bg-stone-100', !isActive);
                    btn.classList.toggle('text-stone-700', !isActive);
                    btn.classList.toggle('border-stone-200', !isActive);
                });
            }

            function toggleModal(el, show) {
                if (!el) return;
                if (show) {
                    el.classList.remove('hidden');
                    el.classList.add('flex');
                } else {
                    el.classList.add('hidden');
                    el.classList.remove('flex');
                }
            }

            openDateBtn?.addEventListener('click', () => toggleModal(dateBackdrop, true));
            closeDateBtn?.addEventListener('click', () => toggleModal(dateBackdrop, false));
            cancelDateBtn?.addEventListener('click', () => toggleModal(dateBackdrop, false));
            dateBackdrop?.addEventListener('click', (e) => {
                if (e.target === dateBackdrop) toggleModal(dateBackdrop, false);
            });

            presetButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const preset = btn.dataset.preset;
                    setActivePreset(preset);

                    const base = parseLocalIso(todayStr);
                    if (preset === 'today') {
                        startInput.value = todayStr;
                        endInput.value = todayStr;
                    } else if (preset === '7d') {
                        const start = new Date(base);
                        start.setDate(start.getDate() - 6);
                        startInput.value = toIsoDate(start);
                        endInput.value = todayStr;
                    } else if (preset === '30d') {
                        const start = new Date(base);
                        start.setDate(start.getDate() - 29);
                        startInput.value = toIsoDate(start);
                        endInput.value = todayStr;
                    } else if (preset === 'month') {
                        const start = new Date(base.getFullYear(), base.getMonth(), 1);
                        startInput.value = toIsoDate(start);
                        endInput.value = todayStr;
                    } else if (preset === 'custom') {
                        startInput?.focus();
                    }
                });
            });

            [startInput, endInput].forEach((input) => {
                input?.addEventListener('change', () => {
                    setActivePreset('custom');
                    if (startInput.value && endInput.value && endInput.value < startInput.value) {
                        endInput.value = startInput.value;
                    }
                });
            });

            openExportBtn?.addEventListener('click', () => toggleModal(exportBackdrop, true));
            closeExportBtn?.addEventListener('click', () => toggleModal(exportBackdrop, false));
            exportBackdrop?.addEventListener('click', (e) => {
                if (e.target === exportBackdrop) toggleModal(exportBackdrop, false);
            });

            printPdfBtn?.addEventListener('click', () => {
                toggleModal(exportBackdrop, false);
                setTimeout(() => window.print(), 120);
            });
        })();
    </script>
@endpush
