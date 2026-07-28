@extends('layouts.main')

@section('title', 'Lembar Kerja')

@section('content')
<div class="min-h-screen bg-slate-50 p-4 md:p-6">

    @php
        $pendekatanLabel = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran'
            ? 'Pengeluaran'
            : 'Lapangan Usaha';
        $isPengeluaran = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran';
    @endphp

    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-black text-slate-900">
            Lembar Kerja {{ $pendekatanLabel }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Pilih kategori atau sub kategori untuk membuka detail.
        </p>
        <div class="mt-3 inline-flex items-center gap-2">
            <a href="{{ route('lembar_kerja.index', ['jenis' => 'lapangan_usaha']) }}"
               class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $isPengeluaran ? 'bg-white text-slate-600 border-slate-200' : 'bg-slate-900 text-white border-slate-900' }}">
                Lapangan Usaha
            </a>
            <a href="{{ route('lembar_kerja.index', ['jenis' => 'pengeluaran']) }}"
               class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $isPengeluaran ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200' }}">
                Pengeluaran
            </a>
            <span class="ml-2 text-xs text-slate-500">
                Total item: <span class="font-semibold text-slate-700">{{ $totalItems ?? 0 }}</span>
            </span>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 md:p-6 border border-slate-200">

        <div class="mb-5">
            <h2 class="text-base font-bold text-slate-800">Daftar Lembar Kerja</h2>
            <p class="text-xs text-slate-500 mt-1">Tampilan minimal untuk akses cepat</p>
        </div>

        @if(isset($gabunganData) && $gabunganData->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-2">

            @foreach ($gabunganData as $item)
                @php
                    $isSub = $item['type'] === 'subkategori';
                    $cardGradient = $isSub ? 'from-orange-50/80 to-white' : 'from-orange-100/70 to-white';
                    $accentGradient = $isSub ? 'from-orange-400 to-orange-600' : 'from-orange-500 to-amber-500';
                    $badgeGradient = $isSub ? 'from-orange-600 to-orange-700' : 'from-orange-700 to-amber-700';

                    $name = strtolower($item['nama'] ?? '');
                    $icon = null;
                    $iconRules = [
                        'pertanian' => 'leaf',
                        'tanaman' => 'leaf',
                        'pangan' => 'leaf',
                        'perkebunan' => 'tree-pine',
                        'kehutanan' => 'tree-pine',
                        'perikanan' => 'fish',
                        'peternakan' => 'paw-print',
                        'pertambangan' => 'hammer',
                        'migas' => 'flame',
                        'listrik' => 'zap',
                        'gas' => 'flame',
                        'air' => 'droplets',
                        'konstruksi' => 'hammer',
                        'industri' => 'factory',
                        'perdagangan' => 'shopping-bag',
                        'transport' => 'truck',
                        'angkutan' => 'truck',
                        'akomodasi' => 'bed',
                        'makan' => 'utensils',
                        'minum' => 'utensils',
                        'informasi' => 'wifi',
                        'komunikasi' => 'radio',
                        'keuangan' => 'banknote',
                        'asuransi' => 'shield',
                        'real estate' => 'home',
                        'jasa' => 'briefcase',
                        'pendidikan' => 'graduation-cap',
                        'kesehatan' => 'heart-pulse',
                        'pemerintahan' => 'landmark',
                        'administrasi' => 'landmark',
                        'keamanan' => 'shield',
                        'sosial' => 'users',
                        'ekspor' => 'ship',
                        'impor' => 'ship',
                        'konsumsi' => 'shopping-cart',
                        'investasi' => 'line-chart',
                        'persediaan' => 'boxes',
                        'pajak' => 'receipt',
                    ];

                    foreach ($iconRules as $keyword => $iconName) {
                        if (str_contains($name, $keyword)) {
                            $icon = $iconName;
                            break;
                        }
                    }

                    if (!$icon) {
                        $poolSub = ['circle', 'square', 'tag', 'bookmark', 'hash', 'list-check', 'file-text', 'check-square', 'clipboard-list'];
                        $poolKategori = ['layers', 'grid-2x2', 'layout-grid', 'folder', 'box', 'pie-chart', 'bar-chart-3', 'activity', 'briefcase', 'package'];
                        $pool = $isSub ? $poolSub : $poolKategori;
                        $icon = $pool[$loop->iteration % count($pool)];
                    }
                @endphp
                <a href="{{ $item['route'] }}"
                   class="group relative rounded-xl border border-slate-200 px-2.5 py-2.5 transition
                          bg-gradient-to-br {{ $cardGradient }}
                          shadow-[0_6px_12px_rgba(15,23,42,0.08)]
                          hover:shadow-[0_10px_18px_rgba(15,23,42,0.16)]
                          hover:-translate-y-1 hover:border-slate-400 min-h-[120px]">
                    <div class="absolute inset-x-0 top-0 h-1 rounded-t-xl bg-gradient-to-r {{ $accentGradient }}"></div>
                    <div class="absolute -top-2 -left-2">
                        <div class="h-6 w-6 rounded-full bg-gradient-to-br {{ $badgeGradient }} text-white text-[10px] font-bold flex items-center justify-center
                                    shadow-[0_4px_8px_rgba(15,23,42,0.25)]">
                            {{ $loop->iteration }}
                        </div>
                    </div>
                    <div class="absolute top-2 right-2">
                        <div class="h-6 w-6 rounded-md bg-white/85 border border-slate-200 flex items-center justify-center">
                            <i data-lucide="{{ $icon }}" class="w-4 h-4 text-orange-600"></i>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 pt-2">
                        <div class="text-xs font-semibold text-slate-900 line-clamp-2">
                            {{ $item['nama'] }}
                        </div>
                        <div class="text-[10px] text-slate-500 leading-tight">
                            Klik detail untuk membuka
                        </div>
                        <div class="text-[10px] text-slate-500 leading-tight">
                            lembar kerja ini
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Tidak ada data</h3>
            <p class="mt-1 text-gray-500">Belum ada data yang tersedia untuk lembar kerja.</p>
        </div>
        @endif

    </div>
</div>
@endsection
