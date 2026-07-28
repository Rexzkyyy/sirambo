@extends('layouts.main')

@section('title', 'Data Fenomena PDRB')

@section('content')

{{-- Tampilkan error import --}}
@if(session('warning'))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">{{ session('warning') }}</p>
                @if(session('errors'))
                    <div class="mt-2 text-sm text-yellow-700">
                        <p class="font-medium">Detail Error:</p>
                        <ul class="list-disc list-inside">
                            @foreach(session('errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="x-circle" class="h-5 w-5 text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif

<div class="container-fluid px-6 py-8 bg-gray-50 min-h-screen" style="position: relative; z-index: 1;">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Data Fenomena PDRB</h1>
            <p class="text-gray-500 mt-1">Input dan tinjauan narasi ekonomi tahun {{ $tahun }} ({{ str_replace('_', ' ', $pendekatan) }})</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="load-from-rekonsiliasi" 
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium cursor-pointer shadow-md hover:shadow-lg"
                style="z-index: 1000; position: relative; pointer-events: auto;">
                <i data-lucide="database" class="w-5 h-5"></i>
                <span>Ambil dari Rekonsiliasi</span>
            </button>
            
            <!-- TOMBOL EXPORT -->
            <button type="button" id="export-fenomena" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium cursor-pointer shadow-md hover:shadow-lg"
                style="z-index: 1000; position: relative; pointer-events: auto;">
                <i data-lucide="download" class="w-5 h-5"></i>
                <span>Export ke Excel</span>
            </button>
            
            <!-- TOMBOL LIHAT RANKING -->
            <a href="{{ route('fenomena.ranking', ['mode' => request('mode', 'tahunan'), 'pendekatan' => $pendekatan, 'tahun' => $tahun]) }}" 
            class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium shadow-md hover:shadow-lg"
            style="z-index: 1000; position: relative;">
                <i data-lucide="trophy" class="w-5 h-5"></i>
                <span>Lihat Ranking</span>
            </a>
        </div>
    </div>

    <!-- FILTER WILAYAH UNTUK PROVINSI -->
    @if(isset($isProvinsi) && $isProvinsi)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8" style="position: relative; z-index: 100;">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700">Pilih Kabupaten/Kota:</label>
                <select id="wilayah-filter" name="id_wilayah" class="bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5 min-w-[250px]">
                    <option value="">-- Pilih Kabupaten/Kota --</option>
                    @foreach($wilayahList as $wilayah)
                        <option value="{{ $wilayah->id_wilayah }}" {{ isset($id_wilayah) && $wilayah->id_wilayah == $id_wilayah ? 'selected' : '' }}>
                            {{ $wilayah->nama_wilayah }}
                        </option>
                    @endforeach
                </select>
                
                <button type="button" id="load-wilayah" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 shadow-md hover:shadow-lg"
                    style="z-index: 1000; position: relative;">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Tampilkan
                </button>
            </div>
            
            @if(isset($id_wilayah) && $id_wilayah)
                <div class="flex items-center gap-3">


                    <div class="flex items-center gap-2 px-4 py-2 {{ isset($isLockedActual) && $isLockedActual ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }} rounded-lg">
                        <i data-lucide="{{ isset($isLockedActual) && $isLockedActual ? 'lock' : 'unlock' }}" class="w-5 h-5"></i>
                        <span class="font-medium">{{ isset($isLockedActual) && $isLockedActual ? 'Terkunci' : 'Terbuka' }}</span>
                    </div>
                    
                    @if($isProvinsi)
                        <button type="button" id="toggle-lock" 
                            class="{{ isset($isLockedActual) && $isLockedActual ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium shadow-md hover:shadow-lg"
                            style="z-index: 1000; position: relative;">
                            <i data-lucide="{{ isset($isLockedActual) && $isLockedActual ? 'unlock' : 'lock' }}" class="w-4 h-4"></i>
                            <span>{{ isset($isLockedActual) && $isLockedActual ? 'Buka Kunci' : 'Kunci Jawaban' }}</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- HIDDEN INPUTS UNTUK STATUS -->
    <input type="hidden" id="current-wilayah" value="{{ $id_wilayah ?? '' }}">
    <input type="hidden" id="current-tahun" value="{{ $tahun }}">
    <input type="hidden" id="current-pendekatan" value="{{ $pendekatan }}">
    <input type="hidden" id="current-mode" value="{{ request('mode', 'tahunan') }}">
    <input type="hidden" id="is-locked" value="{{ isset($isLocked) && $isLocked ? 'true' : 'false' }}">
    <input type="hidden" id="is-provinsi" value="{{ isset($isProvinsi) && $isProvinsi ? 'true' : 'false' }}">
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-wrap gap-6 items-end">
            
            <!-- FORM FILTER -->
            <form method="GET" class="flex flex-wrap gap-6 items-end">
                <!-- DROPDOWN MODE -->
                <div class="w-full md:w-40">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mode</label>
                    <select name="mode" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5" onchange="this.form.submit()">
                        <option value="tahunan" {{ request('mode', 'tahunan') == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                        <option value="triwulanan" {{ request('mode') == 'triwulanan' ? 'selected' : '' }}>Triwulanan</option>
                    </select>
                </div>
                
                <div class="w-full md:w-40">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tahun</label>
                    <select name="tahun" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5">
                        @foreach($tahunList as $th)
                            <option value="{{ $th }}" {{ $th == $tahun ? 'selected' : '' }}>{{ $th }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="w-full md:w-56">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Pendekatan</label>
                    <select name="pendekatan" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5">
                        <option value="lapangan_usaha" {{ $pendekatan == 'lapangan_usaha' ? 'selected' : '' }}>Lapangan Usaha</option>
                        <option value="pengeluaran" {{ $pendekatan == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                    </select>
                </div>

                @if(isset($isProvinsi) && $isProvinsi && isset($id_wilayah))
                    <input type="hidden" name="id_wilayah" value="{{ $id_wilayah }}">
                @endif

                <button type="submit" class="bg-gray-800 hover:bg-black text-white px-6 py-2.5 rounded-lg flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4"></i> Filter
                </button>
            </form>

            <!-- TOMBOL UPLOAD EXCEL -->
            <a href="{{ route('fenomena.create', ['mode' => request('mode', 'tahunan'), 'pendekatan' => $pendekatan, 'tahun' => $tahun, 'tab' => 'excel']) }}{{ isset($id_wilayah) ? '&id_wilayah='.$id_wilayah : '' }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium ml-auto">
                <i data-lucide="upload" class="w-4 h-4"></i> Upload Excel
            </a>
        </div>
    </div>

    @if(isset($kategoriData) && count($kategoriData) > 0)
        <form action="{{ route('fenomena.store') }}" method="POST" id="direct-input-form">
            @csrf
            <input type="hidden" name="mode" value="tahunan">
            <input type="hidden" name="pendekatan" value="{{ $pendekatan }}">
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="action" value="manual">
            <input type="hidden" name="manual_data" id="manual-data-input">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto" style="position: relative; z-index: 1; max-height: 70vh; overflow-y: auto;">
                    @if(isset($isProvinsi) && $isProvinsi && (!isset($id_wilayah) || !$id_wilayah))
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                            <div class="bg-yellow-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="map-pin" class="w-10 h-10 text-yellow-500"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Pilih Kabupaten/Kota</h3>
                            <p class="text-gray-500 mb-6">Silakan pilih kabupaten/kota dari dropdown di atas untuk melihat dan mengelola data fenomena.</p>
                        </div>
                    @elseif(isset($kategoriData) && count($kategoriData) > 0)
                        <table class="min-w-full border-collapse" style="table-layout: auto;">
                            <thead class="sticky-header" style="background-color: #1e88e5 !important;">
                                <tr class="bg-blue-600 text-white" style="background-color: #1e88e5 !important;">
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider border-r border-blue-400 sticky left-0" style="min-width: 300px; top: 0; background-color: #1e88e5 !important; background: #1e88e5 !important;">KATEGORI / SUBKATEGORI</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider border-r border-blue-400 sticky top-0" style="top: 0; background-color: #1e88e5 !important;">JENIS DATA</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider border-r border-blue-400 sticky top-0" style="top: 0; background-color: #1e88e5 !important;">NILAI (%)</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider border-r border-blue-400 sticky top-0" style="min-width: 1600px; top: 0; background-color: #1e88e5 !important;">FENOMENA</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider border-r border-blue-400 sticky top-0" style="min-width: 150px; top: 0; background-color: #1e88e5 !important;">RATING</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach($kategoriData as $kategori)
                                    @php
                                        $kode = $kategori->kode_kategori ?? '';
                                        $isLocked = isset($isLocked) ? $isLocked : false;
                                        // Skip kategori "Produk Domestik Regional Bruto Non Migas"
                                        if(strpos($kategori->nama_kategori, 'Non Migas') !== false || 
                                           $kategori->nama_kategori == 'Produk Domestik Regional Bruto Non Migas') {
                                            continue;
                                        }
                                    @endphp
                                    
                                    {{-- KATEGORI UTAMA - PERTUMBUHAN --}}
                                    <tr class="bg-blue-50/30 font-medium border-t-2 border-gray-300" 
                                        data-kategori-id="{{ $kategori->id_kategori }}" 
                                        data-level="1" 
                                        data-jenis="Pertumbuhan">
                                        <td class="px-4 py-3 border-r border-gray-300 sticky left-0 bg-blue-50/30 z-10">
                                            <div class="flex items-center gap-2">
                                                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">{{ $kode }}</span>
                                                <span class="text-sm font-bold text-gray-800">{{ $kategori->nama_kategori }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 border-r border-gray-300 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Pertumbuhan</span>
                                        </td>
                                        <td class="px-4 py-3 border-r border-gray-300">
                                            <input type="text" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                   placeholder="0,00" 
                                                   value="{{ isset($fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['nilai']) ? number_format($fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['nilai'], 2, ',', '.') : '' }}"
                                                   {{ $isLocked ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-4 py-3 border-r border-gray-300">
                                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                      rows="1"
                                                      placeholder="Deskripsi fenomena..."
                                                      data-autoresize="true"
                                                      {{ $isLocked ? 'disabled' : '' }}>{{ isset($fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['fenomena']) ? $fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['fenomena'] : '' }}</textarea>
                                        </td>
                                        <td class="px-4 py-3 border-r border-gray-300">
                                            @if($isProvinsi && !$isLocked)
                                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        name="rating[{{ $kategori->id_kategori }}][Pertumbuhan]">
                                                    <option value="">Pilih Rating</option>
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <option value="{{ $i }}" {{ isset($fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['rating']) && $fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['rating'] == $i ? 'selected' : '' }}>
                                                            {{ $i }} - 
                                                            @switch($i)
                                                                @case(1) Sangat Rendah @break
                                                                @case(2) Rendah @break
                                                                @case(3) Sedang @break
                                                                @case(4) Tinggi @break
                                                                @case(5) Sangat Tinggi @break
                                                            @endswitch
                                                        </option>
                                                    @endfor
                                                </select>
                                            @else
                                                @php
                                                    $ratingValue = isset($fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['rating']) ? $fenomenaMap[$kategori->id_kategori]['Pertumbuhan']['rating'] : null;
                                                    $ratingText = '';
                                                    if($ratingValue) {
                                                        switch($ratingValue) {
                                                            case 1: $ratingText = '1 - Sangat Rendah'; break;
                                                            case 2: $ratingText = '2 - Rendah'; break;
                                                            case 3: $ratingText = '3 - Sedang'; break;
                                                            case 4: $ratingText = '4 - Tinggi'; break;
                                                            case 5: $ratingText = '5 - Sangat Tinggi'; break;
                                                        }
                                                    }
                                                @endphp
                                                <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                    {{ $ratingValue ? $ratingText : 'Belum ada rating' }}
                                                </div>
                                                @if($ratingValue)
                                                    <input type="hidden" name="rating[{{ $kategori->id_kategori }}][Pertumbuhan]" value="{{ $ratingValue }}">
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    
                                    {{-- KATEGORI UTAMA - LAJU IMPLISIT --}}
                                    <tr class="bg-blue-50/30 border-b border-gray-200" 
                                        data-kategori-id="{{ $kategori->id_kategori }}" 
                                        data-level="1" 
                                        data-jenis="Laju Implisit">
                                        <td class="px-4 py-2 border-r border-gray-300 sticky left-0 bg-blue-50/30 z-10 pl-12">
                                            <span class="text-xs text-gray-600 italic"></span>
                                        </td>
                                        <td class="px-4 py-2 border-r border-gray-300 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">Laju Implisit</span>
                                        </td>
                                        <td class="px-4 py-2 border-r border-gray-300">
                                            <input type="text" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                   placeholder="0,00" 
                                                   value="{{ isset($fenomenaMap[$kategori->id_kategori]['Laju Implisit']['nilai']) ? number_format($fenomenaMap[$kategori->id_kategori]['Laju Implisit']['nilai'], 2, ',', '.') : '' }}"
                                                   {{ $isLocked ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-4 py-2 border-r border-gray-300">
                                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                      rows="1"
                                                      placeholder="Deskripsi fenomena..."
                                                      data-autoresize="true"
                                                      {{ $isLocked ? 'disabled' : '' }}>{{ isset($fenomenaMap[$kategori->id_kategori]['Laju Implisit']['fenomena']) ? $fenomenaMap[$kategori->id_kategori]['Laju Implisit']['fenomena'] : '' }}</textarea>
                                        </td>
                                        <td class="px-4 py-2 border-r border-gray-300">
                                            @if($isProvinsi && !$isLocked)
                                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        name="rating[{{ $kategori->id_kategori }}][Laju Implisit]">
                                                    <option value="">Pilih Rating</option>
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <option value="{{ $i }}" {{ isset($fenomenaMap[$kategori->id_kategori]['Laju Implisit']['rating']) && $fenomenaMap[$kategori->id_kategori]['Laju Implisit']['rating'] == $i ? 'selected' : '' }}>
                                                            {{ $i }} - 
                                                            @switch($i)
                                                                @case(1) Sangat Rendah @break
                                                                @case(2) Rendah @break
                                                                @case(3) Sedang @break
                                                                @case(4) Tinggi @break
                                                                @case(5) Sangat Tinggi @break
                                                            @endswitch
                                                        </option>
                                                    @endfor
                                                </select>
                                            @else
                                                @php
                                                    $ratingValue = isset($fenomenaMap[$kategori->id_kategori]['Laju Implisit']['rating']) ? $fenomenaMap[$kategori->id_kategori]['Laju Implisit']['rating'] : null;
                                                    $ratingText = '';
                                                    if($ratingValue) {
                                                        switch($ratingValue) {
                                                            case 1: $ratingText = '1 - Sangat Rendah'; break;
                                                            case 2: $ratingText = '2 - Rendah'; break;
                                                            case 3: $ratingText = '3 - Sedang'; break;
                                                            case 4: $ratingText = '4 - Tinggi'; break;
                                                            case 5: $ratingText = '5 - Sangat Tinggi'; break;
                                                        }
                                                    }
                                                @endphp
                                                <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                    {{ $ratingValue ? $ratingText : 'Belum ada rating' }}
                                                </div>
                                                @if($ratingValue)
                                                    <input type="hidden" name="rating[{{ $kategori->id_kategori }}][Laju Implisit]" value="{{ $ratingValue }}">
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    
                                    {{-- SUBKATEGORI --}}
                                    @if($kategori->subKategori && count($kategori->subKategori) > 0)
                                        @foreach($kategori->subKategori as $sub)
                                            @php
                                                // Skip subkategori yang terkait dengan Non Migas jika perlu
                                                if(strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                                                    continue;
                                                }
                                            @endphp
                                            {{-- SUBKATEGORI - PERTUMBUHAN --}}
                                            <tr class="hover:bg-gray-50" 
                                                data-kategori-id="{{ $kategori->id_kategori }}" 
                                                data-subkategori-id="{{ $sub->id_sub_kategori }}" 
                                                data-level="2" 
                                                data-jenis="Pertumbuhan">
                                                <td class="px-4 py-2 border-r border-gray-300 sticky left-0 bg-white z-10 pl-8">
                                                    <span class="text-sm text-gray-700">{{ $sub->nama_sub_kategori }}</span>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Pertumbuhan</span>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    <input type="text" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                           placeholder="0,00" 
                                                           value="{{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['nilai']) ? number_format($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['nilai'], 2, ',', '.') : '' }}"
                                                           {{ $isLocked ? 'disabled' : '' }}">
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                              rows="1"
                                                              placeholder="Deskripsi fenomena..."
                                                              data-autoresize="true"
                                                              {{ $isLocked ? 'disabled' : '' }}>{{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['fenomena']) ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['fenomena'] : '' }}</textarea>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    @if($isProvinsi && !$isLocked)
                                                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                name="rating[{{ $kategori->id_kategori }}][{{ $sub->id_sub_kategori }}][Pertumbuhan]">
                                                            <option value="">Pilih Rating</option>
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <option value="{{ $i }}" {{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['rating']) && $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['rating'] == $i ? 'selected' : '' }}>
                                                                    {{ $i }} - 
                                                                    @switch($i)
                                                                        @case(1) Sangat Rendah @break
                                                                        @case(2) Rendah @break
                                                                        @case(3) Sedang @break
                                                                        @case(4) Tinggi @break
                                                                        @case(5) Sangat Tinggi @break
                                                                    @endswitch
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    @else
                                                        @php
                                                            $ratingValue = isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['rating']) ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Pertumbuhan']['rating'] : null;
                                                            $ratingText = '';
                                                            if($ratingValue) {
                                                                switch($ratingValue) {
                                                                    case 1: $ratingText = '1 - Sangat Rendah'; break;
                                                                    case 2: $ratingText = '2 - Rendah'; break;
                                                                    case 3: $ratingText = '3 - Sedang'; break;
                                                                    case 4: $ratingText = '4 - Tinggi'; break;
                                                                    case 5: $ratingText = '5 - Sangat Tinggi'; break;
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                            {{ $ratingValue ? $ratingText : 'Belum ada rating' }}
                                                        </div>
                                                        @if($ratingValue)
                                                            <input type="hidden" name="rating[{{ $kategori->id_kategori }}][{{ $sub->id_sub_kategori }}][Pertumbuhan]" value="{{ $ratingValue }}">
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                            
                                            {{-- SUBKATEGORI - LAJU IMPLISIT --}}
                                            <tr class="hover:bg-gray-50/50 border-b border-gray-200" 
                                                data-kategori-id="{{ $kategori->id_kategori }}" 
                                                data-subkategori-id="{{ $sub->id_sub_kategori }}" 
                                                data-level="2" 
                                                data-jenis="Laju Implisit">
                                                <td class="px-4 py-2 border-r border-gray-300 sticky left-0 bg-white z-10 pl-12">
                                                    <span class="text-xs text-gray-500 italic"></span>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">Laju Implisit</span>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    <input type="text" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                           placeholder="0,00" 
                                                           value="{{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['nilai']) ? number_format($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['nilai'], 2, ',', '.') : '' }}"
                                                           {{ $isLocked ? 'disabled' : '' }}">
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                              rows="1"
                                                              placeholder="Deskripsi fenomena..."
                                                              data-autoresize="true"
                                                              {{ $isLocked ? 'disabled' : '' }}>{{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['fenomena']) ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['fenomena'] : '' }}</textarea>
                                                </td>
                                                <td class="px-4 py-2 border-r border-gray-300">
                                                    @if($isProvinsi && !$isLocked)
                                                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                name="rating[{{ $kategori->id_kategori }}][{{ $sub->id_sub_kategori }}][Laju Implisit]">
                                                            <option value="">Pilih Rating</option>
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <option value="{{ $i }}" {{ isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['rating']) && $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['rating'] == $i ? 'selected' : '' }}>
                                                                    {{ $i }} - 
                                                                    @switch($i)
                                                                        @case(1) Sangat Rendah @break
                                                                        @case(2) Rendah @break
                                                                        @case(3) Sedang @break
                                                                        @case(4) Tinggi @break
                                                                        @case(5) Sangat Tinggi @break
                                                                    @endswitch
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    @else
                                                        @php
                                                            $ratingValue = isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['rating']) ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori]['Laju Implisit']['rating'] : null;
                                                            $ratingText = '';
                                                            if($ratingValue) {
                                                                switch($ratingValue) {
                                                                    case 1: $ratingText = '1 - Sangat Rendah'; break;
                                                                    case 2: $ratingText = '2 - Rendah'; break;
                                                                    case 3: $ratingText = '3 - Sedang'; break;
                                                                    case 4: $ratingText = '4 - Tinggi'; break;
                                                                    case 5: $ratingText = '5 - Sangat Tinggi'; break;
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                            {{ $ratingValue ? $ratingText : 'Belum ada rating' }}
                                                        </div>
                                                        @if($ratingValue)
                                                            <input type="hidden" name="rating[{{ $kategori->id_kategori }}][{{ $sub->id_sub_kategori }}][Laju Implisit]" value="{{ $ratingValue }}">
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-wrap justify-between items-center gap-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span><span class="text-xs font-medium text-gray-600">Kategori Utama</span></div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-400"></span><span class="text-xs font-medium text-gray-600">Subkategori</span></div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-xs font-medium text-gray-600">Pertumbuhan</span></div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500"></span><span class="text-xs font-medium text-gray-600">Laju Implisit</span></div>
                    </div>
                    @if(!isset($isLocked) || !$isLocked)
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                        <i data-lucide="save" class="w-5 h-5"></i> Simpan Semua Perubahan
                    </button>
                    @else
                    <div class="bg-red-100 text-red-700 px-6 py-2.5 rounded-lg font-medium flex items-center gap-2">
                        <i data-lucide="lock" class="w-5 h-5"></i> Data Terkunci - Tidak Dapat Mengubah
                    </div>
                    @endif
                </div>
            </div>
        </form>
    @else
        <div class="bg-white border-2 border-dashed border-gray-200 rounded-3xl p-20 text-center shadow-sm">
            <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="database-zap" class="w-10 h-10 text-gray-300"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Data Kategori Tidak Ditemukan</h3>
            <p class="text-gray-500 mb-6">Tidak ada data kategori untuk pendekatan {{ str_replace('_', ' ', $pendekatan) }}.</p>
            <p class="text-gray-400 text-sm">Silakan hubungi administrator untuk menambahkan data kategori.</p>
        </div>
    @endif
</div>

<style>
    /* HANYA CSS UNTUK TABEL, JANGAN OVERRIDE BODY */
    .sticky-header {
        position: sticky;
        top: 0;
        z-index: 20;
        background-color: #1e88e5 !important;
    }

    .sticky-header th {
        position: sticky;
        top: 0;
        z-index: 20;
        background-color: #1e88e5 !important;
    }

    td:first-child, th:first-child {
        position: sticky;
        left: 0;
        z-index: 10;
        background-color: inherit;
    }

    textarea.fenomena {
        width: 100%;
        min-width: 1580px;
        max-width: 100%;
        min-height: 60px;
        height: auto;
        white-space: pre-wrap;
        word-break: break-word;
        resize: none;
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 0.8rem;
        box-sizing: border-box;
    }

    input.nilai {
        width: 100px !important;
        min-width: 80px;
        max-width: 120px;
        text-align: right;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 0.8rem;
    }

    select.rating {
        width: 140px;
        min-width: 130px;
        max-width: 160px;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 0.8rem;
    }

    .spinner {
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    select.rating option[value="1"] { background-color: #fee2e2; }
    select.rating option[value="2"] { background-color: #ffedd5; }
    select.rating option[value="3"] { background-color: #fef9c3; }
    select.rating option[value="4"] { background-color: #dbeafe; }
    select.rating option[value="5"] { background-color: #dcfce7; }
    
    input:disabled, textarea:disabled, select:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        console.log('DOM loaded - setting up event listeners');
        
        const isLocked = document.getElementById('is-locked')?.value === 'true';
        const isProvinsi = document.getElementById('is-provinsi')?.value === 'true';
        
        // =========================================================
        // AUTO-RESIZE TEXTAREA FUNCTION
        // =========================================================
        function autoResizeTextarea(textarea) {
            // Reset height to auto to get correct scrollHeight
            textarea.style.height = 'auto';
            // Set new height based on scrollHeight (min 60px)
            const newHeight = Math.max(textarea.scrollHeight, 60);
            textarea.style.height = newHeight + 'px';
        }
        
        // Inisialisasi semua textarea yang sudah ada
        function initAllTextareas() {
            document.querySelectorAll('textarea.fenomena').forEach(textarea => {
                // Hanya tambahkan event listener jika belum ada
                if (!textarea.hasAttribute('data-autoresize-initialized')) {
                    autoResizeTextarea(textarea);
                    textarea.addEventListener('input', function() {
                        autoResizeTextarea(this);
                    });
                    textarea.setAttribute('data-autoresize-initialized', 'true');
                } else {
                    // Jika sudah di-initialize, tetap resize ulang
                    autoResizeTextarea(textarea);
                }
            });
        }
        
        // Panggil sekali untuk inisialisasi awal
        initAllTextareas();
        
        // Gunakan MutationObserver dengan throttle untuk menghindari terlalu banyak panggilan
        let resizeTimeout = null;
        const observer = new MutationObserver(function(mutations) {
            // Throttle: hanya proses setiap 100ms
            if (resizeTimeout) return;
            resizeTimeout = setTimeout(() => {
                initAllTextareas();
                resizeTimeout = null;
            }, 100);
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        if (!isProvinsi) {
            document.querySelectorAll('select.rating').forEach(select => {
                select.disabled = true;
                select.classList.add('bg-gray-100', 'cursor-not-allowed');
            });
        }
        
        // Handle load wilayah button
        const loadWilayahBtn = document.getElementById('load-wilayah');
        if (loadWilayahBtn) {
            loadWilayahBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const wilayahId = document.getElementById('wilayah-filter').value;
                if (!wilayahId) {
                    alert('Silakan pilih kabupaten/kota terlebih dahulu');
                    return;
                }
                
                const url = new URL(window.location.href);
                url.searchParams.set('id_wilayah', wilayahId);
                window.location.href = url.toString();
            });
        }
        
        // Verifikasi elemen tombol
        const rekonsiliasiBtn = document.getElementById('load-from-rekonsiliasi');
        const toggleLockBtn = document.getElementById('toggle-lock');
        
        // Handle Ambil dari Rekonsiliasi button
        if (rekonsiliasiBtn) {
            console.log('Tombol Ambil dari Rekonsiliasi ditemukan');
            rekonsiliasiBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Tombol Ambil dari Rekonsiliasi diklik');
                
                // Cek apakah wilayah terkunci
                if (isLocked) {
                    alert('Wilayah ini sudah dikunci. Tidak dapat mengambil data dari rekonsiliasi.');
                    return;
                }
                
                const tahun = document.querySelector('select[name="tahun"]').value;
                const pendekatan = document.querySelector('select[name="pendekatan"]').value;
                const wilayahId = document.getElementById('current-wilayah').value;
                const mode = document.getElementById('current-mode').value;
                
                if (!wilayahId) {
                    alert('Silakan pilih kabupaten/kota terlebih dahulu');
                    return;
                }
                
                console.log('Tahun:', tahun, 'Pendekatan:', pendekatan, 'Wilayah:', wilayahId, 'Mode:', mode);
                
                const loadBtn = this;
                const originalText = loadBtn.innerHTML;
                loadBtn.innerHTML = '<span class="spinner mr-2"></span> Loading...';
                loadBtn.disabled = true;
                
                // Tentukan endpoint berdasarkan mode
                let url = '';
                if (mode === 'triwulanan') {
                    const triwulan = document.querySelector('select[name="triwulan"]')?.value || '1';
                    url = `/rekonsiliasi/get-nilai-fenomena-triwulanan?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}&triwulan=${triwulan}`;
                } else {
                    url = `/rekonsiliasi/get-nilai-fenomena?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}`;
                }
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            console.error('Error response:', text);
                            throw new Error(`HTTP error ${res.status}`);
                        });
                    }
                    return res.json();
                })
                .then(response => {
                    console.log('Response received:', response);
                    
                    if (response.warning) {
                        alert(response.warning);
                        var data = response.data || {};
                    } else if (response.error) {
                        alert('Error: ' + response.error);
                        return;
                    } else {
                        var data = response;
                    }
                    
                    let updatedCount = 0;
                    
                    if (mode === 'tahunan') {
                        // Update nilai Pertumbuhan untuk kategori
                        document.querySelectorAll('tr[data-jenis="Pertumbuhan"] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const kategoriId = row.getAttribute('data-kategori-id');
                            
                            if (data.pertumbuhan_kategori && data.pertumbuhan_kategori[kategoriId] !== undefined) {
                                const nilai = data.pertumbuhan_kategori[kategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Laju Implisit untuk kategori
                        document.querySelectorAll('tr[data-jenis="Laju Implisit"] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const kategoriId = row.getAttribute('data-kategori-id');
                            
                            if (data.laju_implisit_kategori && data.laju_implisit_kategori[kategoriId] !== undefined) {
                                const nilai = data.laju_implisit_kategori[kategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Pertumbuhan untuk subkategori
                        document.querySelectorAll('tr[data-jenis="Pertumbuhan"][data-subkategori-id] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const subkategoriId = row.getAttribute('data-subkategori-id');
                            
                            if (data.pertumbuhan_sub && data.pertumbuhan_sub[subkategoriId] !== undefined) {
                                const nilai = data.pertumbuhan_sub[subkategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Laju Implisit untuk subkategori
                        document.querySelectorAll('tr[data-jenis="Laju Implisit"][data-subkategori-id] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const subkategoriId = row.getAttribute('data-subkategori-id');
                            
                            if (data.laju_implisit_sub && data.laju_implisit_sub[subkategoriId] !== undefined) {
                                const nilai = data.laju_implisit_sub[subkategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                    } else {
                        // Mode triwulanan
                        // Update nilai Q-to-Q untuk kategori
                        document.querySelectorAll('tr[data-jenis="q-to-q"] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const kategoriId = row.getAttribute('data-kategori-id');
                            
                            if (data.q_to_q_kategori && data.q_to_q_kategori[kategoriId] !== undefined) {
                                const nilai = data.q_to_q_kategori[kategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Y-on-Y untuk kategori
                        document.querySelectorAll('tr[data-jenis="y-on-y"] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const kategoriId = row.getAttribute('data-kategori-id');
                            
                            if (data.y_on_y_kategori && data.y_on_y_kategori[kategoriId] !== undefined) {
                                const nilai = data.y_on_y_kategori[kategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Q-to-Q untuk subkategori
                        document.querySelectorAll('tr[data-jenis="q-to-q"][data-subkategori-id] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const subkategoriId = row.getAttribute('data-subkategori-id');
                            
                            if (data.q_to_q_sub && data.q_to_q_sub[subkategoriId] !== undefined) {
                                const nilai = data.q_to_q_sub[subkategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                        
                        // Update nilai Y-on-Y untuk subkategori
                        document.querySelectorAll('tr[data-jenis="y-on-y"][data-subkategori-id] input.nilai:not([disabled])').forEach(input => {
                            const row = input.closest('tr');
                            const subkategoriId = row.getAttribute('data-subkategori-id');
                            
                            if (data.y_on_y_sub && data.y_on_y_sub[subkategoriId] !== undefined) {
                                const nilai = data.y_on_y_sub[subkategoriId];
                                if (nilai !== 0 && nilai !== null) {
                                    input.value = nilai.toFixed(2).replace('.', ',');
                                    updatedCount++;
                                }
                            }
                        });
                    }
                    
                    // Setelah update nilai, resize ulang semua textarea
                    initAllTextareas();
                    
                    alert(`Berhasil memuat ${updatedCount} nilai dari rekonsiliasi`);
                    lucide.createIcons();
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Gagal memuat data dari rekonsiliasi: ' + err.message);
                })
                .finally(() => {
                    loadBtn.innerHTML = originalText;
                    loadBtn.disabled = false;
                });
            });
        } else {
            console.error('Tombol Ambil dari Rekonsiliasi tidak ditemukan');
        }
        
        // Toggle lock button
        const toggleLockBtn = document.getElementById('toggle-lock');
        if (toggleLockBtn && isProvinsi) {
            toggleLockBtn.addEventListener('click', function() {
                const wilayahId = document.getElementById('current-wilayah').value;
                const tahun = document.getElementById('current-tahun').value;
                const pendekatan = document.getElementById('current-pendekatan').value;
                const mode = document.getElementById('current-mode').value;
                
                if (!wilayahId) {
                    alert('Wilayah tidak valid');
                    return;
                }
                
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                this.disabled = true;
                
                fetch('{{ route("fenomena.toggle-lock") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_wilayah: wilayahId,
                        tahun: tahun,
                        pendekatan: pendekatan,
                        mode: mode
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Gagal mengubah status kunci: ' + (data.message || 'Unknown error'));
                        this.innerHTML = originalContent;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghubungi server');
                    this.innerHTML = originalContent;
                    this.disabled = false;
                });
            });
        } else {
            if (!toggleLockBtn) {
                console.log('Tombol Kunci Jawaban tidak ditemukan');
            } else if (!isProvinsi) {
                console.log('User bukan provinsi, tombol Kunci Jawaban tidak ditampilkan');
            }
        }
        
        // Form submit handler - kumpulkan semua data sebelum submit
        const form = document.getElementById('direct-input-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submit intercepted');
                
                // Cek apakah wilayah terkunci
                if (isLocked) {
                    alert('Wilayah ini sudah dikunci. Tidak dapat melakukan perubahan data.');
                    return;
                }
                
                const data = [];
                
                // Kumpulkan semua baris
                document.querySelectorAll('#direct-input-form tbody tr').forEach(row => {
                    const kategoriId = row.getAttribute('data-kategori-id');
                    const subkategoriId = row.getAttribute('data-subkategori-id') || null;
                    const jenisData = row.getAttribute('data-jenis');
                    const level = row.getAttribute('data-level');
                    
                    if (!kategoriId || !jenisData) return;
                    
                    // Ambil nilai dari input
                    const nilaiInput = row.querySelector('input.nilai');
                    const fenomenaTextarea = row.querySelector('textarea.fenomena');
                    const ratingSelect = row.querySelector('select.rating');
                    
                    if (!nilaiInput && !fenomenaTextarea && !ratingSelect) return;
                    
                    const nilai = nilaiInput ? parseNilaiIndonesia(nilaiInput.value) : null;
                    const fenomena = fenomenaTextarea ? fenomenaTextarea.value || null : null;
                    const rating = ratingSelect ? ratingSelect.value || null : null;
                    
                    // Hanya simpan jika ada nilai, fenomena, atau rating
                    if (nilai === null && !fenomena && !rating) return;
                    
                    // Ambil nama kategori dan subkategori
                    let namaKategori = '';
                    let namaSubkategori = null;
                    let kodeKategori = '';
                    
                    if (level == 1) {
                        // Kategori utama
                        const kodeSpan = row.querySelector('td:first-child .bg-blue-600');
                        if (kodeSpan) kodeKategori = kodeSpan.textContent.trim();
                        
                        const namaSpan = row.querySelector('td:first-child .text-sm.font-bold');
                        if (namaSpan) namaKategori = namaSpan.textContent.trim();
                    } else {
                        // Subkategori
                        const subSpan = row.querySelector('td:first-child .text-sm.text-gray-700');
                        if (subSpan) namaSubkategori = subSpan.textContent.trim();
                        
                        // Cari nama kategori dari baris sebelumnya (kategori utama)
                        let prevRow = row.previousElementSibling;
                        while (prevRow) {
                            if (prevRow.getAttribute('data-level') == 1) {
                                const kodeSpan = prevRow.querySelector('td:first-child .bg-blue-600');
                                if (kodeSpan) kodeKategori = kodeSpan.textContent.trim();
                                
                                const namaSpan = prevRow.querySelector('td:first-child .text-sm.font-bold');
                                if (namaSpan) namaKategori = namaSpan.textContent.trim();
                                break;
                            }
                            prevRow = prevRow.previousElementSibling;
                        }
                    }
                    
                    data.push({
                        id_kategori: parseInt(kategoriId),
                        id_sub_kategori: subkategoriId ? parseInt(subkategoriId) : null,
                        nama_kategori: namaKategori,
                        kode_kategori: kodeKategori,
                        nama_sub_kategori: namaSubkategori,
                        id_periode: null,
                        jenis_data: jenisData,
                        nilai: nilai,
                        fenomena: fenomena,
                        rating: rating ? parseInt(rating) : null,
                        level: parseInt(level || 1)
                    });
                });
                
                if (data.length === 0) {
                    alert('Tidak ada data untuk disimpan');
                    return;
                }
                
                // Tambahkan id_wilayah ke form
                const wilayahInput = document.createElement('input');
                wilayahInput.type = 'hidden';
                wilayahInput.name = 'id_wilayah';
                wilayahInput.value = document.getElementById('current-wilayah').value;
                this.appendChild(wilayahInput);
                
                document.getElementById('manual-data-input').value = JSON.stringify(data);
                this.submit();
            });
        }
    });
    
    function parseNilaiIndonesia(value) {
        if (!value || value.trim() === '') return null;
        
        // Ganti koma dengan titik untuk desimal
        let processed = value.trim().replace(',', '.');
        // Hapus karakter non-numerik kecuali minus dan titik
        processed = processed.replace(/[^\d.-]/g, '');
        
        const num = parseFloat(processed);
        return isNaN(num) ? null : num;
    }

    // Handle Export button
const exportBtn = document.getElementById('export-fenomena');
if (exportBtn) {
    console.log('Tombol Export ditemukan');
    exportBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Tombol Export diklik');
        
        const tahun = document.querySelector('select[name="tahun"]').value;
        const pendekatan = document.querySelector('select[name="pendekatan"]').value;
        const mode = document.getElementById('current-mode').value;
        const wilayahId = document.getElementById('current-wilayah').value;
        
        // Buat URL untuk export
        let exportUrl = `/fenomena/export?tahun=${tahun}&pendekatan=${pendekatan}&mode=${mode}`;
        
        if (wilayahId && wilayahId !== '') {
            exportUrl += `&id_wilayah=${wilayahId}`;
        }
        
        // Untuk mode triwulanan, ambil triwulan dari URL atau default
        if (mode === 'triwulanan') {
            // Coba ambil dari parameter URL
            const urlParams = new URLSearchParams(window.location.search);
            let triwulan = urlParams.get('triwulan');
            if (!triwulan) {
                triwulan = '1';
            }
            exportUrl += `&triwulan=${triwulan}`;
        }
        
        console.log('Export URL:', exportUrl);
        
        // Tampilkan loading
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<span class="spinner mr-2"></span> Loading...';
        exportBtn.disabled = true;
        
        // Download file
        window.location.href = exportUrl;
        
        // Kembalikan tombol setelah delay
        setTimeout(() => {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }, 2000);
    });
}
</script>
@endsection