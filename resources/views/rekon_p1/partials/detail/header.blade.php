<!-- HEADER -->
<div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
    <div>
        @php
            $pendekatanLabel = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran'
                ? 'Pengeluaran'
                : 'Lapangan Usaha';
        @endphp
        <h1 class="text-xl font-bold text-gray-800 md:text-2xl">
            Rekonsiliasi {{ strtoupper($subKategori->nama_sub_kategori) }}
        </h1>
        <p class="text-xs text-gray-500 md:text-sm">
            Nilai PDRB {{ $pendekatanLabel }}
        </p>
        @if(!empty($hasNoData))
            <p class="text-xs text-red-600 md:text-sm">
                Data awal untuk kategori ini belum tersedia.
            </p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <div id="sync-indicator"
            class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/80 px-2.5 py-1 text-[10px] font-semibold text-gray-600 shadow-sm">
            <span class="h-2 w-2 rounded-full bg-gray-400" data-sync-dot></span>
            <span data-sync-text>Syncing...</span>
        </div>
        @if(!empty($isProvinsi))
            <button id="lock-toggle-btn" data-locked="{{ !empty($isLocked) ? '1' : '0' }}"
                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[10px] font-semibold text-white shadow-sm transition {{ !empty($isLocked) ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                <span data-lock-label>{{ !empty($isLocked) ? 'Buka Input' : 'Kunci Input' }}</span>
            </button>
        @else
            <div id="lock-status"
                class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[10px] font-semibold shadow-sm {{ !empty($isLocked) ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' }}">
                <span data-lock-text>{{ !empty($isLocked) ? 'Terkunci' : 'Terbuka' }}</span>
            </div>
        @endif
        <a href="{{ route('rekon_p1.index', ['jenis' => $pendekatan ?? 'lapangan_usaha']) }}"
            class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition shadow md:px-4 md:py-2 md:text-sm">
            Kembali
        </a>
    </div>
</div>