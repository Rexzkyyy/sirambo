{{-- Tampilkan flash message jika ada --}}
@if(session('warning'))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    {{ session('warning') }}
                </p>
            </div>
        </div>
    </div>
@endif

@php
    $pendekatanLabel = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran'
        ? 'Pengeluaran'
        : 'Lapangan Usaha';
@endphp

<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <h2 class="text-2xl font-bold text-gray-800">
        Hasil Input Nilai PDRB - {{ $pendekatanLabel }}
    </h2>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" id="rentangTahunBtn"
            class="flex items-center text-sm bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-lg transition duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Rentang Tahun
        </button>
        <a href="{{ route('pdrb.hasil.export', array_merge(request()->query(), ['jenis' => $pendekatan ?? 'lapangan_usaha'])) }}"
           class="flex items-center text-sm bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16l4-4m0 0l-4-4m4 4H8m12 6a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h7"/>
            </svg>
            Export
        </a>
        <a href="{{ route('pdrb.hasil', ['jenis' => $pendekatan ?? 'lapangan_usaha']) }}"
           class="flex items-center text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg transition duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Reset Filter
        </a>
        <a href="{{ route('pdrb.hasil', ['jenis' => $pendekatan ?? 'lapangan_usaha']) }}"
           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition duration-200">Kembali</a>
    </div>
</div>
