{{-- Bottom Sheet Notifikasi & Alert Stok (Mobile Bottom Sheet / Desktop Dialog) --}}
<div
    id="notif-sheet-root"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="notif-sheet-title"
>
    {{-- Backdrop --}}
    <div id="notif-sheet-backdrop" class="fixed inset-0 bg-stone-950/50 backdrop-blur-[1px] transition-opacity"></div>

    {{-- Wrapper Posisi: Bottom Sheet di HP, Modal Tengah di Tablet/Desktop --}}
    <div class="fixed inset-x-0 bottom-0 md:inset-0 flex items-end md:items-center justify-center md:p-4 pointer-events-none">
        <div
            class="pointer-events-auto w-full md:max-w-lg bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-stone-100 flex flex-col max-h-[85vh] overflow-hidden"
            style="padding-bottom: env(safe-area-inset-bottom);"
        >
            {{-- Drag Handle Mobile --}}
            <div class="flex justify-center pt-2.5 pb-1 md:hidden">
                <span class="h-1 w-10 rounded-full bg-stone-300"></span>
            </div>

            {{-- Header Sheet --}}
            <div class="px-5 py-3.5 border-b border-stone-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <h2 id="notif-sheet-title" class="text-base font-extrabold text-stone-900 truncate">
                        Notifikasi &amp; Alert Stok
                    </h2>
                    <span
                        id="sheet-kritis-badge"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold shrink-0 {{ ($totalAlert ?? 0) > 0 ? 'bg-red-100 text-[#DC2626]' : 'bg-emerald-100 text-emerald-700' }}"
                    >
                        {{ $totalAlert ?? 0 }} Kritis
                    </span>
                </div>

                <button
                    type="button"
                    id="notif-sheet-close"
                    class="inline-flex items-center justify-center h-11 w-11 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors cursor-pointer shrink-0"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Tab Filter Notifikasi --}}
            <div class="px-5 pt-3 pb-2 flex items-center gap-2 overflow-x-auto">
                <button
                    type="button"
                    data-notif-tab="semua"
                    class="notif-tab-btn px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#DC2626] text-white transition-colors cursor-pointer min-h-[36px] shrink-0"
                >
                    Semua
                </button>
                <button
                    type="button"
                    data-notif-tab="kritis"
                    class="notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer min-h-[36px] shrink-0"
                >
                    <span>Kritis</span>
                    <span id="sheet-tab-kritis-count" class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-[#DC2626]">
                        {{ $totalAlert ?? 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    data-notif-tab="info"
                    class="notif-tab-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-stone-100 text-stone-700 hover:bg-stone-200 transition-colors cursor-pointer min-h-[36px] shrink-0"
                >
                    <span>Info</span>
                    <span id="sheet-tab-info-count" class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-200 text-stone-700">
                        {{ ($riwayatRestock ?? collect())->take(5)->count() }}
                    </span>
                </button>
            </div>

            {{-- Daftar Notifikasi --}}
            <div id="notif-sheet-list" class="flex-1 overflow-y-auto px-5 py-3 space-y-3">
                @foreach ($stokAlerts ?? [] as $bahan)
                    @php
                        $isHabis = (float) $bahan->stok <= 0;
                    @endphp
                    <div
                        data-notif-type="kritis"
                        class="notif-item rounded-2xl p-3.5 border-l-4 {{ $isHabis ? 'border-l-[#DC2626] bg-red-50/80' : 'border-l-amber-400 bg-amber-50/70' }} flex items-start gap-3"
                    >
                        <div class="h-8 w-8 rounded-full {{ $isHabis ? 'bg-red-100 text-[#DC2626]' : 'bg-amber-100 text-amber-600' }} flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-bold text-stone-900 leading-snug">
                                    {{ $isHabis ? "Stok {$bahan->nama_bahan} Habis!" : "{$bahan->nama_bahan} Menipis" }}
                                </p>
                                <span class="text-[11px] font-semibold {{ $isHabis ? 'text-[#DC2626]' : 'text-amber-700' }} shrink-0">
                                    {{ $isHabis ? 'Habis' : 'Perlu Restock' }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-600 mt-1">
                                {{ $bahan->outlet?->nama_outlet ?? 'Outlet' }} tersisa
                                <span class="font-bold text-stone-800">{{ number_format((float) $bahan->stok, 2, ',', '.') }} {{ $bahan->satuan }}</span>
                                (batas minimum {{ number_format((float) $bahan->stok_minimal, 2, ',', '.') }} {{ $bahan->satuan }}).
                            </p>
                            <a
                                href="{{ route('stok.index', ['outlet_id' => $bahan->outlet_id]) }}"
                                class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-[#DC2626] hover:underline"
                            >
                                <span>Restock Sekarang</span>
                                <span aria-hidden="true">&rsaquo;</span>
                            </a>
                        </div>
                    </div>
                @endforeach

                @foreach (($riwayatRestock ?? collect())->take(5) as $restock)
                    <div
                        data-notif-type="info"
                        class="notif-item rounded-2xl p-3.5 border border-stone-100 bg-white shadow-2xs flex items-start gap-3"
                    >
                        <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-bold text-stone-900 leading-snug">
                                    Restock {{ $restock->outlet?->nama_outlet ?? 'Outlet' }}
                                </p>
                                <span class="text-[11px] text-stone-400 shrink-0">
                                    {{ optional($restock->tanggal_restock)->format('d M Y') }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-600 mt-1">
                                Pengeluaran Rp {{ number_format((float) $restock->total_biaya, 0, ',', '.') }}
                                ke {{ $restock->supplier?->nama_supplier ?? 'Supplier' }}.
                            </p>
                        </div>
                    </div>
                @endforeach

                @if (($stokAlerts ?? collect())->isEmpty() && ($riwayatRestock ?? collect())->isEmpty())
                    <div class="py-8 text-center text-sm text-stone-500">
                        <p class="font-semibold text-emerald-700">Semua stok aman &amp; belum ada notifikasi baru.</p>
                    </div>
                @endif
            </div>

            {{-- Footer Action Sheet --}}
            <div class="px-5 py-3 border-t border-stone-100 bg-stone-50/70 text-center">
                <button
                    type="button"
                    id="notif-mark-read"
                    class="w-full py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:text-[#DC2626] hover:bg-stone-100 transition-colors cursor-pointer min-h-[44px]"
                >
                    Tandai Semua Sudah Dibaca
                </button>
            </div>
        </div>
    </div>
</div>
