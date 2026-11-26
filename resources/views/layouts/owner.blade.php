<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#DC2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Owner') — Geprek LFC</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    @stack('head')
</head>

<body class="bg-stone-50 text-stone-800 font-sans antialiased min-h-screen overflow-x-hidden">
    @php
        $isDashboard = request()->routeIs('dashboard');
        $isStok = request()->routeIs('stok.*');
        $isMenu = request()->routeIs('menu.*');
        $ownerName = auth()->user()->nama_lengkap ?? 'Owner LFC';
        $notifBadgeCount = (int) trim($__env->yieldContent('notif_badge_count', '0'));
    @endphp

    <div class="min-h-screen flex flex-col md:flex-row">
        {{-- Sidebar Kiri Ringkas Khusus Desktop/Tablet (>=768px) --}}
        <aside
            class="hidden md:flex md:w-56 lg:w-60 shrink-0 bg-white border-r border-stone-200 flex-col justify-between sticky top-0 h-screen"
            aria-label="Navigasi Owner Desktop">
            <div>
                {{-- Brand Header Sidebar --}}
                <div class="px-4 py-4 border-b border-stone-100 flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-10 w-10 rounded-full bg-gradient-to-br from-[#DC2626] to-[#B91C1C] text-white flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-stone-900 truncate leading-tight">Geprek LFC</p>
                            <p class="text-[11px] font-medium text-stone-500 truncate">Lana Fried Chicken</p>
                        </div>
                    </a>
                </div>

                {{-- Menu Navigasi Utama --}}
                <nav class="p-3 space-y-1" aria-label="Menu Sidebar Owner">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors min-h-[44px] {{ $isDashboard ? 'bg-red-50 text-[#DC2626] font-bold border border-red-100' : 'text-stone-600 font-medium hover:bg-stone-100 hover:text-stone-900' }}">
                        <svg class="w-5 h-5 shrink-0 {{ $isDashboard ? 'text-[#DC2626]' : 'text-stone-400' }}"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('stok.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors min-h-[44px] {{ $isStok ? 'bg-red-50 text-[#DC2626] font-bold border border-red-100' : 'text-stone-600 font-medium hover:bg-stone-100 hover:text-stone-900' }}">
                        <svg class="w-5 h-5 shrink-0 {{ $isStok ? 'text-[#DC2626]' : 'text-stone-400' }}" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                        <span>Kelola Stok</span>
                    </a>

                    <a href="{{ route('menu.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors min-h-[44px] {{ $isMenu ? 'bg-red-50 text-[#DC2626] font-bold border border-red-100' : 'text-stone-600 font-medium hover:bg-stone-100 hover:text-stone-900' }}">
                        <svg class="w-5 h-5 shrink-0 {{ $isMenu ? 'text-[#DC2626]' : 'text-stone-400' }}"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span>Kelola Menu</span>
                    </a>
                </nav>
            </div>

            {{-- Akun & Logout Desktop --}}
            <div class="p-3 border-t border-stone-100 space-y-2">
                <div class="px-3 py-2 rounded-xl bg-stone-50 border border-stone-100">
                    <p class="text-xs font-bold text-stone-800 truncate">{{ $ownerName }}</p>
                    <p class="text-[11px] text-stone-500 truncate">{{ auth()->user()->role ?? 'OWNER' }}</p>
                </div>

                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-stone-600 hover:bg-red-50 hover:text-[#DC2626] transition-colors cursor-pointer min-h-[44px]">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Area Konten Utama --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Header Atas Ringkas Khusus Mobile (<768px) --}}
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-stone-100 md:hidden">
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="h-10 w-10 rounded-full bg-gradient-to-br from-[#DC2626] to-[#B91C1C] text-white flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-stone-900 truncate leading-tight">Halo,
                                {{ $ownerName }}</p>
                            <p class="text-xs text-stone-500 truncate">Lana Fried Chicken</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @yield('mobile_header_actions')

                        {{-- Tombol Lonceng Notifikasi Stok Menipis --}}
                        <button type="button" data-open-notif-sheet
                            class="relative inline-flex items-center justify-center h-11 w-11 rounded-full bg-stone-50 hover:bg-red-50 text-stone-700 hover:text-[#DC2626] border border-stone-200 transition-colors cursor-pointer"
                            aria-label="Buka notifikasi stok menipis">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span id="mobile-bell-dot"
                                class="notif-bell-dot {{ ($notifBadgeCount ?? 0) > 0 ? '' : 'hidden' }} absolute top-2 right-2.5 h-2.5 w-2.5 rounded-full bg-[#DC2626] ring-2 ring-white"></span>
                        </button>
                    </div>
                </div>
            </header>

            {{-- Header Desktop/Tablet (>=768px) --}}
            <header
                class="hidden md:flex sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-stone-200 px-6 py-3.5 items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-lg font-extrabold text-stone-900 truncate">
                        @yield('page_title', 'Dashboard Owner')
                    </h1>
                    @hasSection('page_subtitle')
                        <p class="text-xs text-stone-500 truncate">@yield('page_subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    @yield('header_actions')

                    <button type="button" data-open-notif-sheet
                        class="relative inline-flex items-center justify-center h-11 w-11 rounded-xl bg-stone-50 hover:bg-red-50 text-stone-700 hover:text-[#DC2626] border border-stone-200 transition-colors cursor-pointer"
                        aria-label="Notifikasi stok menipis" title="Notifikasi & Alert Stok">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        <span id="desktop-bell-dot"
                            class="notif-bell-dot {{ ($notifBadgeCount ?? 0) > 0 ? '' : 'hidden' }} absolute top-2 right-2.5 h-2.5 w-2.5 rounded-full bg-[#DC2626] ring-2 ring-white"></span>
                    </button>
                </div>
            </header>

            {{-- Konten Halaman (Compact Max-Width & Safe Bottom Padding di Mobile) --}}
            <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-4 md:py-6 pb-24 md:pb-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Bottom Navigation Bar Fixed Khusus Mobile (<768px) --}}
    <nav class="fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur-md border-t border-stone-200 shadow-[0_-4px_16px_rgba(0,0,0,0.05)] md:hidden"
        style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Navigasi Bawah Owner">
        <div class="grid grid-cols-4 items-stretch">
            <a href="{{ route('dashboard') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 px-1 min-h-[56px] text-[11px] transition-colors {{ $isDashboard ? 'text-[#DC2626] font-bold' : 'text-stone-500 font-medium hover:text-stone-800' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                    stroke-width="{{ $isDashboard ? '2.4' : '1.9' }}" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span class="truncate">Dashboard</span>
            </a>

            <a href="{{ route('stok.index') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 px-1 min-h-[56px] text-[11px] transition-colors {{ $isStok ? 'text-[#DC2626] font-bold' : 'text-stone-500 font-medium hover:text-stone-800' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                    stroke-width="{{ $isStok ? '2.4' : '1.9' }}" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span class="truncate">Kelola Stok</span>
            </a>

            <a href="{{ route('menu.index') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 px-1 min-h-[56px] text-[11px] transition-colors {{ $isMenu ? 'text-[#DC2626] font-bold' : 'text-stone-500 font-medium hover:text-stone-800' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                    stroke-width="{{ $isMenu ? '2.4' : '1.9' }}" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <span class="truncate">Kelola Menu</span>
            </a>

            <form method="POST" action="{{ route('auth.logout') }}" class="flex">
                @csrf
                <button type="submit"
                    class="w-full flex flex-col items-center justify-center gap-1 py-2 px-1 min-h-[56px] text-[11px] font-medium text-stone-500 hover:text-[#DC2626] transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.9"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    <span class="truncate">Logout</span>
                </button>
            </form>
        </div>
    </nav>

    {{-- Toast Notification Container --}}
    <div id="toast-container"
        class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-xs sm:max-w-sm pointer-events-none"
        aria-live="polite"></div>

    {{-- Slot Modal / Bottom Sheet --}}
    @stack('modals')

    <script>
        // Helper Toast Global untuk notifikasi error / info Fetch API
        window.showOwnerToast = function(message, type = 'error') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const isError = type === 'error';
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-start gap-2.5 px-4 py-3 rounded-2xl shadow-lg border text-xs sm:text-sm font-medium transition-all duration-200 ${
                isError
                    ? 'bg-red-50 text-red-800 border-red-200'
                    : 'bg-emerald-50 text-emerald-800 border-emerald-200'
            }`;
            toast.setAttribute('role', 'alert');

            const icon = isError ?
                '<svg class="w-5 h-5 text-[#DC2626] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>' :
                '<svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';

            const textSpan = document.createElement('span');
            textSpan.textContent = message;

            toast.innerHTML = icon;
            toast.appendChild(textSpan);
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-1');
                setTimeout(() => toast.remove(), 220);
            }, 3800);
        };
    </script>

    @stack('scripts')
</body>

</html>
