@extends('layouts.main')

@section('title', 'Direktori Wilayah - Sirambo')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sirambo-primary: #003366;
            --sirambo-secondary: #00A3E0;
            --sirambo-bg: #F4F7FA;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--sirambo-bg);
        }

        .card-wilayah {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .card-wilayah:hover {
            transform: translateY(-8px);
            border-color: var(--sirambo-secondary);
            box-shadow: 0 20px 25px -5px rgba(0, 51, 102, 0.1), 0 10px 10px -5px rgba(0, 51, 102, 0.04);
        }

        .search-container focus-within {
            border-color: var(--sirambo-primary);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }
    </style>

    <div class="p-4 md:p-8 space-y-8">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-200 pb-8">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span
                        class="px-2 py-0.5 bg-blue-100 text-[#003366] text-[10px] font-bold uppercase tracking-widest rounded">Master
                        Data</span>
                    <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                    <span class="text-xs font-medium text-slate-400">Regional Directory</span>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                    Direktori Wilayah Kerja
                </h1>
                <p class="text-slate-500 mt-2 max-w-2xl">Akses portal data spesifik untuk setiap Kabupaten dan Kota di
                    wilayah kerja Provinsi Sulawesi Tenggara.</p>
            </div>

            <div class="relative w-full md:w-96 group">
                <i data-lucide="search"
                    class="w-5 h-5 absolute left-4 top-3.5 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                <input type="text" id="wilayahSearch" placeholder="Cari kabupaten atau kota..."
                    class="w-full pl-12 pr-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm">
            </div>
        </div>

        {{-- GRID WILAYAH --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="wilayahGrid">
            @foreach($wilayah as $w)
                @php
                    $formats = [
                        $w->id_wilayah . '.png',
                        'wilayah ' . $w->id_wilayah . '.png',
                        'wilayah_' . $w->id_wilayah . '.png',
                        'wilayah ' . $w->id_wilayah . '.PNG',
                        $w->id_wilayah . '.PNG',
                        $w->id_wilayah . '.jpg',
                        $w->id_wilayah . '.JPG',
                    ];
                    $logoUrl = null;
                    $logoExists = false;
                    foreach ($formats as $fileName) {
                        $pathsToTry = [
                            'assets/img/' . $fileName,
                            'public/assets/img/' . $fileName
                        ];

                        foreach ($pathsToTry as $rel) {
                            $checkPath = public_path($rel);
                            $docRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] . '/' . $rel : null;

                            if (file_exists($checkPath) || ($docRootPath && file_exists($docRootPath))) {
                                $logoUrl = asset($rel);
                                $logoExists = true;
                                break 2;
                            }
                        }
                    }
                @endphp

                <a href="{{ route('pdrb.hasil', ['scope_wilayah' => $w->id_wilayah]) }}"
                    class="card-wilayah group flex flex-col h-full relative wilayah-item"
                    data-name="{{ strtolower($w->nama_wilayah) }}">

                    <div class="p-6 flex-1">
                        {{-- TOP ROW: TIPE & LOGO --}}
                        <div class="flex justify-between items-start mb-6">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                            {{ $w->tipe == 'provinsi' ? 'bg-navy-900 text-white' :
                ($w->tipe == 'kota' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}"
                                style="{{ $w->tipe == 'provinsi' ? 'background-color: #003366;' : '' }}">
                                {{ $w->tipe }}
                            </span>

                            <div
                                class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 p-1 group-hover:bg-white transition-colors">
                                @if($logoExists)
                                    <img src="{{ $logoUrl }}" alt="{{ $w->nama_wilayah }}"
                                        class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 transition-all duration-500">
                                @else
                                    <div
                                        class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 font-bold text-xl">
                                        {{ substr($w->nama_wilayah, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- WILAYAH INFO --}}
                        <div class="space-y-1">
                            <h2
                                class="text-lg font-bold text-slate-800 group-hover:text-[#003366] transition-colors leading-tight">
                                {{ $w->nama_wilayah }}
                            </h2>
                            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                                <i data-lucide="database" class="w-3 h-3"></i>
                                Repository Terintegrasi
                            </div>
                        </div>

                        {{-- HOVER ACTION INDICATOR --}}
                        <div
                            class="mt-8 flex items-center gap-2 text-sm font-bold text-[#003366] opacity-0 group-hover:opacity-100 transition-all transform translate-y-2 group-hover:translate-y-0">
                            <span>Tampilan Data</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </div>
                    </div>

                    {{-- DECORATIVE BAR --}}
                    <div class="h-1.5 w-0 group-hover:w-full bg-[#00A3E0] transition-all duration-500"></div>
                </a>
            @endforeach
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();

        // Logic Pencarian Wilayah Sederhana
        document.getElementById('wilayahSearch').addEventListener('input', function (e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.wilayah-item').forEach(item => {
                const name = item.getAttribute('data-name');
                item.style.display = name.includes(term) ? 'block' : 'none';
            });
        });
    </script>
@endsection