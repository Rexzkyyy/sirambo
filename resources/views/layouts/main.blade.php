@php
    $logoPath = null;
    $logoExists = false;
    $user = auth()->user();
    $w = $user ? $user->wilayah : null;

    if ($w && $w->id_wilayah) {
        $formats = [
            $w->id_wilayah . '.png',
            "wilayah {$w->id_wilayah}.png",
            "wilayah_{$w->id_wilayah}.png",
            $w->id_wilayah . '.PNG',
            $w->id_wilayah . '.jpg',
        ];

        foreach ($formats as $fileName) {
            $pathsToTry = [
                'assets/img/' . $fileName,
                'public/assets/img/' . $fileName
            ];
            foreach ($pathsToTry as $rel) {
                $checkPath = public_path($rel);
                $docRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/' . $rel : null;
                if (file_exists($checkPath) || ($docRootPath && file_exists($docRootPath))) {
                    $logoPath = $rel;
                    $logoExists = true;
                    break 2;
                }
            }
        }
    }

    $logoUrl = $logoExists ? asset($logoPath) : asset('assets/img/default.png');
    $userRole = $user->role ?? null;
    $isKabKota = in_array($userRole, ['kabupaten', 'kota'], true);

    $brandLogoCandidates = [
        'logosirambo1.png', 'logosirambo.png', 'logo_color.png', 'logo_bps.png'
    ];

    $brandLogoUrl = null;
    foreach ($brandLogoCandidates as $cName) {
        $cPaths = ['assets/img/' . $cName, 'public/assets/img/' . $cName];
        foreach ($cPaths as $cRel) {
            $cCheck = public_path($cRel);
            $cDocRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/' . $cRel : null;
            if (file_exists($cCheck) || ($cDocRoot && file_exists($cDocRoot))) {
                $brandLogoUrl = asset($cRel);
                break 2;
            }
        }
    }
    if (!$brandLogoUrl) $brandLogoUrl = $logoUrl;
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIRAMBO - Sistem Informasi Rekonsiliasi data dan Monitoring PDRB BPS Provinsi Sulawesi Tenggara.">
    <title>@yield('title', 'SIRAMBO')</title>

    <script>
        (function() {
            const saved = localStorage.getItem('sidebarCollapsed');
            if (saved === '1') {
                document.documentElement.classList.add('sidebar-is-collapsed');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=optional"
        rel="stylesheet">

    @yield('styles')

    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Reverb Dynamic Configuration -->
    <script>
        @if(config('broadcasting.default') === 'reverb')
        window.REVERB_APP_KEY = "{{ config('reverb.apps.apps.0.key') }}";
        window.REVERB_HOST    = "{{ config('reverb.apps.apps.0.options.host') }}";
        window.REVERB_PORT    = "{{ config('reverb.apps.apps.0.options.port') }}";
        window.REVERB_SCHEME  = "{{ config('reverb.apps.apps.0.options.scheme') }}";
        @else
        // Broadcast non-aktif ({{ config('broadcasting.default') }}) — WebSocket dimatikan
        window.REVERB_APP_KEY = null;
        window.REVERB_HOST    = null;
        window.REVERB_PORT    = null;
        window.REVERB_SCHEME  = null;
        @endif
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Sidebar collapse support before Alpine loads to prevent CLS */
        .sidebar-is-collapsed aside {
            width: 0 !important;
            opacity: 0 !important;
            pointer-events: none !important;
            transform: translateX(-100%) !important;
        }

        /* Tema Orange Sidebar */
        .sidebar-active {
            background: linear-gradient(to right, rgba(255, 255, 255, 0.15), transparent);
            color: #ffffff;
            border-left: 4px solid #fbbf24;
            /* Amber 400 */
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(251, 146, 60, 0.4);
            border-radius: 10px;
        }

        .logo-wilayah {
            width: 32px;
            height: 32px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px;
        }
    </style>
</head>

<body class="bg-[#fffcf9] text-slate-900" x-data="{
    sidebarOpen: false,
    sidebarCollapsed: document.documentElement.classList.contains('sidebar-is-collapsed'),
    init() {
        this.$watch('sidebarCollapsed', (value) => {
            localStorage.setItem('sidebarCollapsed', value ? '1' : '0');
            if (value) {
                document.documentElement.classList.add('sidebar-is-collapsed');
            } else {
                document.documentElement.classList.remove('sidebar-is-collapsed');
            }
        });
    }
}">

    <div class="flex h-screen overflow-hidden">

        {{-- ================= SIDEBAR (Orange Nuance) ================= --}}
        <aside :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            sidebarCollapsed ? 'md:-translate-x-full md:w-0 md:opacity-0 md:pointer-events-none' : 'md:translate-x-0 md:w-64'
        ]" class="fixed inset-y-0 left-0 bg-gradient-to-b from-[#ea580c] via-[#d946ef] to-[#c2410c] text-white border-r border-orange-400/20 transform transition-[transform,width,opacity] duration-300 ease-in-out will-change-transform z-50
                md:relative flex flex-col shadow-2xl md:shadow-none"
            style="background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);">

            <div class="h-16 flex items-center justify-between px-6 border-b border-white/10 bg-white/5">
                <div class="flex items-center gap-3">
                    {{-- Pengganti Icon dengan Logo PNG --}}
                    <div class="flex items-center justify-center">
                        <img src="{{ $brandLogoUrl }}" alt="Logo Sirambo"
                            width="32" height="32"
                            class="w-8 h-8 object-contain filter drop-shadow-sm"
                            onerror="this.onerror=null;this.src='{{ asset('public/assets/img/logo_bps.png') }}';">
                    </div>

                    <h1 class="text-lg font-black tracking-tighter uppercase text-white">
                        Sirambo<span class="text-orange-200">.</span>
                    </h1>
                </div>

                <button class="md:hidden p-1.5 hover:bg-white/10 rounded-lg" @click="sidebarOpen = false" aria-label="Tutup Sidebar">
                    <i data-lucide="x" class="w-5 h-5 text-white/80"></i>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 custom-scrollbar">
                {{-- Label Menu --}}
                <p class="px-4 text-[10px] font-black text-white/90 uppercase tracking-[0.2em] mb-3 mt-4">
                    Menu Utama
                </p>

                <div class="space-y-1">
                    <a href="{{ route('pdrb.dashboard') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-bold transition-all group hover:bg-white/10 text-white {{ request()->routeIs('pdrb.dashboard') ? 'sidebar-active bg-white/20' : '' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('penduduk.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-bold transition-all group hover:bg-white/10 text-white {{ request()->routeIs('penduduk.index') ? 'sidebar-active bg-white/20' : '' }}">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Data Penduduk</span>
                    </a>

                    <a href="{{ route('pdrb.dynamic') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-bold transition-all group hover:bg-white/10 text-white {{ request()->routeIs('pdrb.dynamic') ? 'sidebar-active bg-white/20' : '' }}">
                        <i data-lucide="table-2" class="w-4 h-4"></i>
                        <span>Tabel Dinamis</span>
                    </a>
                </div>

                 <p class="px-4 text-[10px] font-black text-white/60 uppercase tracking-[0.2em] mt-6 mb-2">Konfigurasi
                </p>

                <div x-data="{ open: {{ request()->routeIs('admin.*') || request()->routeIs('wilayah.*') ? 'true' : 'false' }} }"
                    class="space-y-1">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-3">
                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                            <span>Manajemen</span>
                        </div>
                        <i data-lucide="chevron-down" :class="open ? 'rotate-180' : ''"
                            class="w-3.5 h-3.5 transition-transform"></i>
                    </button>

                    <div x-show="open" x-cloak x-transition class="ml-4 pl-4 border-l border-white/20 space-y-1">
                         @unless($isKabKota)
                            <a href="{{ route('admin.dashboard') }}"
                                class="block px-4 py-2 text-xs font-bold {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">User
                                System</a>
                            <a href="{{ route('wilayah.index') }}"
                                class="block px-4 py-2 text-xs font-bold {{ request()->routeIs('wilayah.index') ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">Wilayah
                                Kerja</a>
                            <a href="{{ route('pdrb.import_logs') }}"
                                class="block px-4 py-2 text-xs font-bold {{ request()->routeIs('pdrb.import_logs') ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">Histori
                                Input PDRB</a>
                            <a href="{{ route('fenomena.histori') }}"
                                class="block px-4 py-2 text-xs font-bold {{ request()->routeIs('fenomena.histori') ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">Histori
                                Fenomena</a>
                        @endunless
                        <a href="{{ route('profile.password') }}"
                            class="block px-4 py-2 text-xs font-bold {{ request()->routeIs('profile.password') ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">Ubah Password</a>
                    </div>
                </div>

                <p class="px-4 text-[10px] font-black text-orange-100/70 uppercase tracking-[0.2em] mt-6 mb-2">Modul
                    PDRB</p>

                {{-- Lapangan Usaha --}}
                <div x-data="{ open: {{ request()->get('jenis') == 'lapangan_usaha' ? 'true' : 'false' }} }"
                    class="space-y-1">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-3">
                            <i data-lucide="factory" class="w-4 h-4"></i>
                            <span>Lapangan Usaha</span>
                        </div>
                        <i data-lucide="chevron-down" :class="open ? 'rotate-180' : ''"
                            class="w-3.5 h-3.5 transition-transform"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition class="ml-4 pl-4 border-l border-white/20 space-y-1">
                        @php
                            $items = [
                                ['Input Data', 'pdrb.data', []],
                                ['Data PDRB', 'pdrb.hasil', []],
                                ['Resume P0', 'rekonsiliasi.index', []],
                                ['Rekon P1', 'rekon_p1.index', []],
                                ['Resume P1', 'rekonsiliasi.index', ['resume' => 'p1']],
                                ['Cek Selisih', 'rekonsiliasi.cekSelisih', []],
                            ];
                        @endphp
                        @foreach($items as $item)
                            <a href="{{ route($item[1], array_merge(['jenis' => 'lapangan_usaha'], $item[2])) }}"
                                class="block px-4 py-2 text-xs font-bold text-orange-50/70 hover:text-white transition-all">{{ $item[0] }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Pengeluaran --}}
                <div x-data="{ open: {{ request()->get('jenis') == 'pengeluaran' ? 'true' : 'false' }} }"
                    class="space-y-1">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-3">
                            <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            <span>Pengeluaran</span>
                        </div>
                        <i data-lucide="chevron-down" :class="open ? 'rotate-180' : ''"
                            class="w-3.5 h-3.5 transition-transform"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition class="ml-4 pl-4 border-l border-white/20 space-y-1">
                        @foreach($items as $item)
                            <a href="{{ route($item[1], array_merge(['jenis' => 'pengeluaran'], $item[2])) }}"
                                class="block px-4 py-2 text-xs font-bold text-orange-50/70 hover:text-white transition-all">{{ $item[0] }}</a>
                        @endforeach
                    </div>
                </div>
                <div x-data="{ open: {{ request()->routeIs('lembar_kerja.*') ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-bold text-white hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-3">
                            <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            <span>Fenomena</span>
                        </div>
                        <i data-lucide="chevron-down" :class="open ? 'rotate-180' : ''"
                            class="w-3.5 h-3.5 transition-transform"></i>
                    </button>
                     <div x-show="open" x-cloak x-transition class="ml-4 pl-4 border-l border-white/20 space-y-1">
                        <a href="{{ route('fenomena.index', ['mode' => 'triwulanan', 'pendekatan' => 'lapangan_usaha', 'tahun' => request('tahun', date('Y'))]) }}"
                            class="block px-4 py-2 text-xs font-bold {{ request('pendekatan') == 'lapangan_usaha' && request('mode') == 'triwulanan' ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">
                            Lapangan Usaha
                        </a>
                        <a href="{{ route('fenomena.index', ['mode' => 'triwulanan', 'pendekatan' => 'pengeluaran', 'tahun' => request('tahun', date('Y'))]) }}"
                            class="block px-4 py-2 text-xs font-bold {{ request('pendekatan') == 'pengeluaran' && request('mode') == 'triwulanan' ? 'text-white' : 'text-white/60' }} hover:text-white transition-all">
                            Pengeluaran
                        </a>
                    </div>
                </div>
            </nav>



            <div class="p-4 border-t border-white/10">
                <div class="bg-black/10 rounded-xl p-3 flex items-center gap-3">
                    @if($logoExists)
                        <img src="{{ asset($logoPath) }}" alt="Logo Wilayah" width="32" height="32" class="logo-wilayah ring-1 ring-white/20">
                    @else
                        <div
                            class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white font-black text-[10px] ring-1 ring-white/30">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1 overflow-hidden">
                        <p class="text-[11px] font-black text-white truncate uppercase">
                            {{ $user->name ?? 'Administrator' }}
                        </p>
                        <p class="text-[9px] font-bold text-orange-200 truncate uppercase tracking-tighter">
                            @if($user && $user->wilayah && $user->wilayah->nama_wilayah)
                                {{ $user->wilayah->nama_wilayah }}
                            @else
                                Guest / Publik
                            @endif
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-[10px] font-semibold text-orange-200/60 text-center tracking-wide">
                    © BPS Sultra x Tim Neraca 2026
                </p>
            </div>
        </aside>

        {{-- ================= KONTEN UTAMA ================= --}}
        <div class="flex flex-col flex-1 min-w-0">

            <header
                class="h-16 flex items-center justify-between px-4 lg:px-8 bg-white border-b border-orange-100 sticky top-0 z-40 shadow-sm">
                <div class="flex items-center gap-4">
                    <button class="md:hidden p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                        @click="sidebarOpen = true" aria-label="Buka Sidebar">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <button
                        class="hidden md:inline-flex p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                        @click="sidebarCollapsed = !sidebarCollapsed" aria-label="Toggle Sidebar">
                        <i data-lucide="panel-left" class="w-5 h-5"></i>
                    </button>
                    <div class="flex items-center justify-center">
                        <img src="{{ $brandLogoUrl }}" alt="Logo Sirambo"
                            width="32" height="32"
                            class="w-8 h-8 object-contain filter drop-shadow-sm"
                            onerror="this.onerror=null;this.src='{{ asset('public/assets/img/logo_bps.png') }}';">
                    </div>
                    <div class="hidden md:block">
                        <h2
                            class="text-[10px] font-black text-orange-700 uppercase tracking-[0.2em] leading-none mb-1">
                            BPS Provinsi Sultra - SIRAMBO v1.0</h2>
                        <p class="text-xs font-bold text-slate-800 uppercase tracking-tight">@yield('title', 'Beranda')
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if($logoExists)
                        <div
                            class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-orange-50 rounded-lg border border-orange-100">
                            <img src="{{ asset($logoPath) }}" alt="Logo Wilayah" width="20" height="20" class="w-5 h-5 object-contain">
                            <span class="text-[10px] font-bold text-orange-700">
                                @if($user && $user->wilayah && $user->wilayah->nama_wilayah)
                                    {{ $user->wilayah->nama_wilayah }}
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="h-8 w-[1px] bg-slate-200 mx-1 hidden md:block"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            class="group flex items-center gap-2 px-4 py-1.5 border border-orange-200 bg-white hover:bg-orange-600 text-orange-600 hover:text-white rounded-lg text-[10px] font-black transition-all shadow-sm">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            KELUAR
                        </button>
                    </form>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#fffaf5]">
                <div class="w-full max-w-none px-2 py-2 md:px-4 md:py-4 lg:px-6 lg:py-6 2xl:px-10">
                    <div class="bg-white rounded-2xl border border-orange-100/50 shadow-sm min-h-[80vh] p-3 md:p-4 lg:p-6">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
    @yield('scripts')
</body>

</html>