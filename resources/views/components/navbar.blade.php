<div class="flex h-screen bg-[#f4f6fb]">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gradient-to-b from-[#0b1f3a] via-[#0c2240] to-[#071425] text-white shadow-xl">
        <div class="h-16 flex items-center gap-3 px-6 border-b border-white/10 bg-gradient-to-r from-white/10 via-white/5 to-transparent">
            <div class="w-8 h-8 rounded-lg bg-white/15 ring-1 ring-white/20 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h8v8H3V3zm10 0h8v5h-8V3zM3 13h5v8H3v-8zm7 0h11v8H10v-8z"/>
                </svg>
            </div>
            <h1 class="text-lg font-black tracking-tight uppercase">Sirambo<span class="text-blue-200">.</span></h1>
        </div>

        <nav class="px-3 py-4 space-y-1 text-sm font-semibold">
            <a href="{{ route('pdrb.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Dashboard
            </a>

            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Manajemen User
            </a>

            <a href="{{ route('pdrb.hasil') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Manajemen Wilayah
            </a>



            <a href="{{ route('pdrb.hasil') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Tahun
            </a>

            <a href="{{ route('pdrb.data') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Form Input Data Pokok
            </a>

            <a href="{{ route('pdrb.hasil') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-blue-100/85 hover:bg-white/10 hover:text-white transition">
                <span class="inline-block h-2 w-2 rounded-full bg-blue-200"></span>
                Hasil
            </a>
        </nav>

        <div class="mt-auto p-4 border-t border-white/10">
            <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/15 ring-1 ring-white/20 flex items-center justify-center text-[10px] font-black uppercase">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="flex-1 overflow-hidden">
                    <p class="text-[11px] font-black text-white truncate uppercase">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-[9px] font-bold text-blue-100/70 truncate uppercase tracking-tighter">Admin Pusat</p>
                </div>
            </div>
            <p class="mt-3 text-[10px] font-semibold text-blue-100/70 text-center tracking-wide">
                Copyright © BPS Provinsi Sultra x Tim Neraca 2026
            </p>
        </div>
    </aside>

    <!-- KONTEN KANAN -->
    <div class="flex flex-col flex-1 min-w-0">
        <!-- TOP BAR -->
        <header class="h-16 flex items-center justify-between px-6 bg-gradient-to-r from-[#0b1f3a] via-[#0c2240] to-[#071425] border-b border-white/10 sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <div class="hidden md:block">
                    <p class="text-[10px] font-black text-blue-100/70 uppercase tracking-[0.2em] leading-none mb-1">BPS - SIRAMBO v2.0</p>
                    <h2 class="text-sm font-bold text-white uppercase tracking-tight">Sistem Informasi Rilis Angka PDRB</h2>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-blue-900">
                    <svg class="w-6 h-6 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5.121 17.804A9 9 0 1118.364 6.64M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ auth()->user()->name ?? 'User' }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-[#2f6adf] to-[#2257c5] rounded-lg hover:from-[#245ecf] hover:to-[#1e4fb3] transition">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>
</div>
