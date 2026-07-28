@extends('layouts.main')

@section('title', 'Data Fenomena PDRB Triwulanan')

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

<div class="container-fluid px-6 py-8 bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Data Fenomena PDRB Triwulanan</h1>
            <p class="text-gray-500 mt-1">Input dan tinjauan narasi ekonomi triwulanan tahun {{ $tahun }} ({{ str_replace('_', ' ', $pendekatan) }})</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" id="load-from-rekonsiliasi" 
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium cursor-pointer shadow-md hover:shadow-lg">
                <i data-lucide="database" class="w-5 h-5"></i>
                <span>Ambil dari Rekonsiliasi</span>
            </button>
            
            <button type="button" id="export-fenomena" 
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium cursor-pointer shadow-md hover:shadow-lg">
                <i data-lucide="download" class="w-5 h-5"></i>
                <span>Export ke Excel</span>
            </button>
            
            <a href="{{ route('fenomena.ranking', ['mode' => request('mode', 'tahunan'), 'pendekatan' => $pendekatan, 'tahun' => $tahun]) }}" 
                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium shadow-md hover:shadow-lg">
                <i data-lucide="trophy" class="w-5 h-5"></i>
                <span>Lihat Ranking</span>
            </a>
        </div>
    </div>

    <!-- FILTER WILAYAH UNTUK PROVINSI -->
    @if(isset($isProvinsi) && $isProvinsi)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-gray-700">Pilih Kabupaten/Kota:</label>
                <select id="wilayah-filter" name="id_wilayah" class="bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5 min-w-[250px]">
                    <option value="">-- Pilih Kabupaten/Kota --</option>
                    @if(isset($wilayahList) && count($wilayahList) > 0)
                        @foreach($wilayahList as $wilayah)
                            <option value="{{ $wilayah->id_wilayah }}" {{ isset($id_wilayah) && $wilayah->id_wilayah == $id_wilayah ? 'selected' : '' }}>
                                {{ $wilayah->nama_wilayah ?? 'Wilayah ' . $wilayah->id_wilayah }}
                            </option>
                        @endforeach
                    @else
                        <option value="" disabled>Tidak ada data wilayah</option>
                    @endif
                </select>
                
                <button type="button" id="load-wilayah" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2">
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
                            class="{{ isset($isLockedActual) && $isLockedActual ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium shadow-md">
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
    <input type="hidden" id="current-mode" value="{{ request('mode', 'triwulanan') }}">
    <input type="hidden" id="current-triwulan" value="{{ $triwulan ?? 1 }}">
    <input type="hidden" id="is-locked" value="{{ isset($isLocked) && $isLocked ? 'true' : 'false' }}">
    <input type="hidden" id="is-provinsi" value="{{ isset($isProvinsi) && $isProvinsi ? 'true' : 'false' }}">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-wrap gap-6 items-end">
            <form method="GET" action="{{ route('fenomena.index') }}" class="flex flex-wrap gap-6 items-end">
                <div class="w-full md:w-40">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Mode</label>
                    <select name="mode" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2.5" onchange="this.form.submit()">
                        <option value="tahunan" {{ request('mode', 'triwulanan') == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                        <option value="triwulanan" {{ request('mode', 'triwulanan') == 'triwulanan' ? 'selected' : '' }}>Triwulanan</option>
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

            <a href="{{ route('fenomena.create', ['mode' => 'triwulanan', 'pendekatan' => $pendekatan, 'tahun' => $tahun, 'triwulan' => $triwulan ?? 1, 'tab' => 'excel']) }}{{ isset($id_wilayah) ? '&id_wilayah='.$id_wilayah : '' }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium ml-auto">
                <i data-lucide="upload" class="w-4 h-4"></i> Upload Excel
            </a>
        </div>
    </div>

    @if(isset($kategoriData) && count($kategoriData) > 0)
        @if(isset($isProvinsi) && $isProvinsi && (!isset($id_wilayah) || !$id_wilayah))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="bg-yellow-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="map-pin" class="w-10 h-10 text-yellow-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Pilih Kabupaten/Kota</h3>
                <p class="text-gray-500 mb-6">Silakan pilih kabupaten/kota dari dropdown di atas untuk melihat dan mengelola data fenomena.</p>
            </div>
        @else
            <form action="{{ route('fenomena.store') }}" method="POST" id="direct-input-form">
                @csrf
                <input type="hidden" name="mode" value="triwulanan">
                <input type="hidden" name="pendekatan" value="{{ $pendekatan }}">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="action" value="manual">
                <input type="hidden" name="manual_data" id="manual-data-input">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto" style="position: relative; z-index: 1; max-height: 70vh; overflow-y: auto;">
                        <table class="min-w-full border-collapse" style="table-layout: fixed; min-width: 6000px;">
                            <thead class="sticky-header">
                                {{-- BARIS PERTAMA HEADER --}}
                                <tr class="bg-blue-600 text-white">
                                    <th rowspan="2" class="sticky-left" style="width: 320px; min-width: 320px; background-color: #1e88e5 !important; vertical-align: middle; z-index: 30;">
                                        KATEGORI / SUBKATEGORI
                                    </th>
                                    @foreach(['I', 'II', 'III', 'IV'] as $triwulan)
                                        <th colspan="3" style="width: 1410px; min-width: 1410px; background-color: #1e88e5 !important; text-align: center; border-left: 1px solid #3b82f6;">
                                            TRIWULAN {{ $triwulan }}/{{ $tahun }}
                                        </th>
                                    @endforeach
                                </tr>
                                
                                {{-- BARIS KEDUA HEADER (sub header) --}}
                                <tr class="bg-blue-500 text-white">
                                    @for($i = 1; $i <= 4; $i++)
                                        <th style="width: 100px; min-width: 100px; background-color: #2563eb !important; text-align: center;">
                                            Nilai (%)
                                        </th>
                                        <th style="width: 1200px; min-width: 1200px; background-color: #2563eb !important; text-align: center;">
                                            FENOMENA
                                        </th>
                                        <th style="width: 110px; min-width: 110px; background-color: #2563eb !important; text-align: center;">
                                            RATING
                                        </th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @php
                                    $isPengeluaran = ($pendekatan == 'pengeluaran');
                                    $jenisDataList = ['q-to-q' => 'QtoQ', 'y-on-y' => 'YonY'];
                                    if($isPengeluaran) {
                                        $jenisDataList['c-to-c'] = 'CtoC';
                                    }
                                @endphp

                                @foreach($kategoriData as $kategori)
                                    @php
                                        $kode = $kategori->kode_kategori ?? '';
                                        $isLocked = isset($isLocked) ? $isLocked : false;
                                        if(strpos($kategori->nama_kategori, 'Non Migas') !== false || 
                                        $kategori->nama_kategori == 'Produk Domestik Regional Bruto Non Migas') {
                                            continue;
                                        }
                                    @endphp
                                    
                                    {{-- LOOP UNTUK SETIAP JENIS DATA --}}
                                    @foreach($jenisDataList as $jenisKey => $jenisLabel)
                                        @php
                                            $bgColor = ($jenisKey == 'q-to-q') ? '#eff6ff' : (($jenisKey == 'c-to-c') ? '#f0fdf4' : '#eff6ff');
                                            $rowClass = ($jenisKey == 'q-to-q') ? 'bg-blue-50/30 font-medium border-t-2 border-gray-300' : 'border-b border-gray-200 font-medium';
                                        @endphp
                                        <tr class="{{ $rowClass }}" 
                                            data-kategori-id="{{ $kategori->id_kategori }}" 
                                            data-level="1" 
                                            data-jenis="{{ $jenisKey }}">
                                            <td class="px-4 py-2 border-r border-gray-300 sticky-left" style="background-color: {{ $bgColor }} !important; z-index: 25;">
                                                <div class="flex items-center gap-2">
                                                    @if($jenisKey == 'q-to-q')
                                                        <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold shrink-0">{{ $kode }}</span>
                                                        <span class="text-sm font-bold text-gray-800">{{ $kategori->nama_kategori }} ({{ $jenisLabel }})</span>
                                                    @else
                                                        <span class="text-xs font-semibold {{ $jenisKey == 'c-to-c' ? 'text-green-700' : 'text-purple-700' }}">
                                                            {{ $kategori->nama_kategori }} ({{ $jenisLabel }})
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            {{-- LOOP UNTUK 4 TRIWULAN --}}
                                            @for($triwulan = 1; $triwulan <= 4; $triwulan++)
                                                @php
                                                    $nilai = isset($fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['nilai']) 
                                                        ? number_format($fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['nilai'], 2, ',', '.') 
                                                        : '';
                                                    $fenomena = isset($fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['fenomena']) 
                                                        ? $fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['fenomena'] 
                                                        : '';
                                                    $ratingValue = isset($fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['rating']) 
                                                        ? $fenomenaMap[$kategori->id_kategori][$jenisKey][$triwulan]['rating'] 
                                                        : null;
                                                @endphp
                                                
                                                <td class="px-3 py-2 border-r border-gray-300 text-center">
                                                    <input type="text" 
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                           placeholder="0,00" 
                                                           value="{{ $nilai }}"
                                                           data-kategori-id="{{ $kategori->id_kategori }}"
                                                           data-subkategori=""
                                                           data-jenis="{{ $jenisKey }}"
                                                           data-triwulan="{{ $triwulan }}"
                                                           {{ $isLocked ? 'disabled' : '' }}>
                                                </td>
                                                <td class="px-3 py-2 border-r border-gray-300">
                                                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena resize-y focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                              rows="2" 
                                                              placeholder="Deskripsi fenomena..."
                                                              data-kategori-id="{{ $kategori->id_kategori }}"
                                                              data-subkategori=""
                                                              data-jenis="{{ $jenisKey }}"
                                                              data-triwulan="{{ $triwulan }}"
                                                              {{ $isLocked ? 'disabled' : '' }}>{{ $fenomena }}</textarea>
                                                </td>
                                                <td class="px-3 py-2 border-r border-gray-300">
                                                    @if($isProvinsi && !$isLocked)
                                                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                data-kategori-id="{{ $kategori->id_kategori }}"
                                                                data-subkategori=""
                                                                data-jenis="{{ $jenisKey }}"
                                                                data-triwulan="{{ $triwulan }}">
                                                            <option value="">Pilih Rating</option>
                                                            @foreach([1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'] as $val => $label)
                                                                <option value="{{ $val }}" {{ $ratingValue == $val ? 'selected' : '' }}>
                                                                    {{ $val }} - {{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        @php
                                                            $ratingDisplay = $ratingValue ? $ratingValue . ' - ' . match($ratingValue) {
                                                                1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi', default => ''
                                                            } : 'Belum ada rating';
                                                        @endphp
                                                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm text-center {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                            {{ $ratingDisplay }}
                                                        </div>
                                                        @if($ratingValue)
                                                            <input type="hidden" 
                                                                data-kategori-id="{{ $kategori->id_kategori }}"
                                                                data-subkategori=""
                                                                data-jenis="{{ $jenisKey }}"
                                                                data-triwulan="{{ $triwulan }}"
                                                                value="{{ $ratingValue }}">
                                                        @endif
                                                    @endif
                                                </td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                    
                                    {{-- SUBKATEGORI --}}
                                    @if($kategori->subKategori && count($kategori->subKategori) > 0)
                                        @foreach($kategori->subKategori as $sub)
                                            @php
                                                if(strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                                                    continue;
                                                }
                                            @endphp
                                            
                                            @foreach($jenisDataList as $jenisKey => $jenisLabel)
                                                @php
                                                    $textClass = ($jenisKey == 'q-to-q') ? 'text-sm text-gray-700' : (($jenisKey == 'c-to-c') ? 'text-xs text-green-600' : 'text-xs text-purple-600');
                                                @endphp
                                                <tr class="hover:bg-gray-50 border-b border-gray-200" 
                                                    data-kategori-id="{{ $kategori->id_kategori }}" 
                                                    data-subkategori-id="{{ $sub->id_sub_kategori }}" 
                                                    data-level="2" 
                                                    data-jenis="{{ $jenisKey }}">
                                                    <td class="px-4 py-2 border-r border-gray-300 sticky-left" style="background-color: #ffffff !important; z-index: 25;">
                                                        <span class="{{ $textClass }}">{{ $sub->nama_sub_kategori }} ({{ $jenisLabel }})</span>
                                                    </td>
                                                    
                                                    @for($triwulan = 1; $triwulan <= 4; $triwulan++)
                                                        @php
                                                            $nilai = isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['nilai']) 
                                                                ? number_format($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['nilai'], 2, ',', '.') 
                                                                : '';
                                                            $fenomena = isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['fenomena']) 
                                                                ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['fenomena'] 
                                                                : '';
                                                            $ratingValue = isset($fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['rating']) 
                                                                ? $fenomenaMap[$kategori->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['rating'] 
                                                                : null;
                                                        @endphp
                                                        
                                                        <td class="px-3 py-2 border-r border-gray-300 text-center">
                                                            <input type="text" 
                                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm nilai text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                                   placeholder="0,00" 
                                                                   value="{{ $nilai }}"
                                                                   data-kategori-id="{{ $kategori->id_kategori }}"
                                                                   data-subkategori="{{ $sub->id_sub_kategori }}"
                                                                   data-jenis="{{ $jenisKey }}"
                                                                   data-triwulan="{{ $triwulan }}"
                                                                   {{ $isLocked ? 'disabled' : '' }}>
                                                        </td>
                                                        <td class="px-3 py-2 border-r border-gray-300">
                                                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm fenomena resize-y focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $isLocked ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                                                      rows="2" 
                                                                      placeholder="Deskripsi fenomena..."
                                                                      data-kategori-id="{{ $kategori->id_kategori }}"
                                                                      data-subkategori="{{ $sub->id_sub_kategori }}"
                                                                      data-jenis="{{ $jenisKey }}"
                                                                      data-triwulan="{{ $triwulan }}"
                                                                      {{ $isLocked ? 'disabled' : '' }}>{{ $fenomena }}</textarea>
                                                        </td>
                                                        <td class="px-3 py-2 border-r border-gray-300">
                                                            @if($isProvinsi && !$isLocked)
                                                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm rating focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                        data-kategori-id="{{ $kategori->id_kategori }}"
                                                                        data-subkategori="{{ $sub->id_sub_kategori }}"
                                                                        data-jenis="{{ $jenisKey }}"
                                                                        data-triwulan="{{ $triwulan }}">
                                                                    <option value="">Pilih Rating</option>
                                                                    @foreach([1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi'] as $val => $label)
                                                                        <option value="{{ $val }}" {{ $ratingValue == $val ? 'selected' : '' }}>
                                                                            {{ $val }} - {{ $label }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            @else
                                                                @php
                                                                    $ratingDisplay = $ratingValue ? $ratingValue . ' - ' . match($ratingValue) {
                                                                        1 => 'Sangat Rendah', 2 => 'Rendah', 3 => 'Sedang', 4 => 'Tinggi', 5 => 'Sangat Tinggi', default => ''
                                                                    } : 'Belum ada rating';
                                                                @endphp
                                                                <div class="px-3 py-2 bg-gray-50 rounded-lg text-sm text-center {{ $ratingValue ? 'font-medium' : 'text-gray-400' }}">
                                                                    {{ $ratingDisplay }}
                                                                </div>
                                                                @if($ratingValue)
                                                                    <input type="hidden" 
                                                                        data-kategori-id="{{ $kategori->id_kategori }}"
                                                                        data-subkategori="{{ $sub->id_sub_kategori }}"
                                                                        data-jenis="{{ $jenisKey }}"
                                                                        data-triwulan="{{ $triwulan }}"
                                                                        value="{{ $ratingValue }}">
                                                                @endif
                                                            @endif
                                                        </td>
                                                    @endfor
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-wrap justify-between items-center gap-4">
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span><span class="text-xs font-medium text-gray-600">Kategori Utama</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-400"></span><span class="text-xs font-medium text-gray-600">Subkategori</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-400"></span><span class="text-xs font-medium text-gray-600">QtoQ</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500"></span><span class="text-xs font-medium text-gray-600">YonY</span></div>
                            @if($isPengeluaran)
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-xs font-medium text-gray-600">CtoC</span></div>
                            @endif
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
        @endif
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
    /* HEADER STICKY VERTIKAL */
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
    
    /* KOLOM PERTAMA STICKY (KATEGORI/SUBKATEGORI) */
    /* Penting: untuk baris header (th) dan baris data (td) */
    .sticky-left {
        position: sticky !important;
        left: 0 !important;
        top: 0 !important;  /* Ini penting agar tetap di atas saat scroll vertikal */
        background-color: inherit;
        z-index: 25 !important;
    }
    
    /* Header kolom pertama harus lebih tinggi z-index-nya */
    thead .sticky-left {
        z-index: 30 !important;
    }
    
    /* Baris data (tbody) untuk kolom pertama */
    tbody .sticky-left {
        position: sticky !important;
        left: 0 !important;
        /* HAPUS top:0 pada tbody agar tidak ikut scroll vertikal */
        /* top: auto; */
        z-index: 15 !important;
    }
    
    /* Tapi untuk baris pertama tbody, biarkan saja */
    tbody tr:first-child .sticky-left {
        top: 0;
    }

    /* Warna background untuk kategori utama */
    tr[data-level="1"] .sticky-left {
        background-color: #eff6ff !important;
    }

    /* Warna background untuk subkategori */
    tr[data-level="2"] .sticky-left {
        background-color: #ffffff !important;
    }

    /* Textarea styling */
    textarea.fenomena {
        width: 100%;
        min-width: 1180px;
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

    /* Input nilai styling */
    input.nilai {
        width: 90px !important;
        min-width: 80px;
        max-width: 100px;
        text-align: right;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 0.8rem;
    }

    /* Select rating styling */
    select.rating {
        width: 140px;
        min-width: 130px;
        max-width: 160px;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 0.8rem;
    }

    /* Spinner animation */
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

    /* Warna option rating */
    select.rating option[value="1"] { background-color: #fee2e2; }
    select.rating option[value="2"] { background-color: #ffedd5; }
    select.rating option[value="3"] { background-color: #fef9c3; }
    select.rating option[value="4"] { background-color: #dbeafe; }
    select.rating option[value="5"] { background-color: #dcfce7; }

    /* Disabled styling */
    input:disabled, textarea:disabled, select:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    /* Tabel lebar minimum */
    .overflow-x-auto table {
        min-width: 6000px;
    }
    
    /* Perbaikan untuk container scroll */
    .overflow-x-auto {
        position: relative;
        overflow: auto;
        max-height: 70vh;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        const isLocked = document.getElementById('is-locked')?.value === 'true';
        const isProvinsi = document.getElementById('is-provinsi')?.value === 'true';
        
        // Auto-resize textarea function
        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            const newHeight = Math.max(textarea.scrollHeight, 60);
            textarea.style.height = newHeight + 'px';
        }
        
        function initAllTextareas() {
            document.querySelectorAll('textarea.fenomena').forEach(textarea => {
                if (!textarea.hasAttribute('data-autoresize-initialized')) {
                    autoResizeTextarea(textarea);
                    textarea.addEventListener('input', function() { autoResizeTextarea(this); });
                    textarea.setAttribute('data-autoresize-initialized', 'true');
                } else {
                    autoResizeTextarea(textarea);
                }
            });
        }
        
        initAllTextareas();
        
        let resizeTimeout = null;
        const observer = new MutationObserver(function() {
            if (resizeTimeout) return;
            resizeTimeout = setTimeout(() => {
                initAllTextareas();
                resizeTimeout = null;
            }, 100);
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Load wilayah button
        const loadWilayahBtn = document.getElementById('load-wilayah');
        if (loadWilayahBtn) {
            loadWilayahBtn.addEventListener('click', function() {
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
        }
        
        // Form submit handler
        const form = document.getElementById('direct-input-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (isLocked) {
                    alert('Wilayah ini sudah dikunci. Tidak dapat melakukan perubahan data.');
                    return;
                }
                
                const data = [];
                
                document.querySelectorAll('#direct-input-form tbody tr').forEach(row => {
                    const kategoriId = row.getAttribute('data-kategori-id');
                    const subkategoriId = row.getAttribute('data-subkategori-id') || null;
                    const jenisData = row.getAttribute('data-jenis');
                    const level = row.getAttribute('data-level');
                    
                    if (!kategoriId || !jenisData) return;
                    
                    const nilaiInput = row.querySelector('input.nilai');
                    const fenomenaTextarea = row.querySelector('textarea.fenomena');
                    const ratingSelect = row.querySelector('select.rating');
                    
                    if (!nilaiInput && !fenomenaTextarea && !ratingSelect) return;
                    
                    const nilai = nilaiInput ? parseNilaiIndonesia(nilaiInput.value) : null;
                    const fenomena = fenomenaTextarea ? fenomenaTextarea.value || null : null;
                    const rating = ratingSelect ? ratingSelect.value || null : null;
                    
                    if (nilai === null && !fenomena && !rating) return;
                    
                    let triwulan = null;
                    if (nilaiInput) triwulan = nilaiInput.getAttribute('data-triwulan');
                    else if (fenomenaTextarea) triwulan = fenomenaTextarea.getAttribute('data-triwulan');
                    else if (ratingSelect) triwulan = ratingSelect.getAttribute('data-triwulan');
                    
                    let namaKategori = '';
                    let namaSubkategori = null;
                    let kodeKategori = '';
                    
                    if (level == 1) {
                        const kodeSpan = row.querySelector('td:first-child .bg-blue-600');
                        if (kodeSpan) kodeKategori = kodeSpan.textContent.trim();
                        
                        let namaSpan = row.querySelector('td:first-child .text-sm.font-bold');
                        if (namaSpan) {
                            namaKategori = namaSpan.textContent.trim().replace(/\s*\([^)]*\)/, '');
                        } else {
                            let altSpan = row.querySelector('td:first-child .text-xs.font-semibold');
                            if (altSpan) {
                                namaKategori = altSpan.textContent.trim().replace(/\s*\([^)]*\)/, '');
                            }
                        }
                    } else {
                        const subSpan = row.querySelector('td:first-child span');
                        if (subSpan) {
                            namaSubkategori = subSpan.textContent.trim().replace(/\s*\([^)]*\)/, '');
                        }
                        
                        let prevRow = row.previousElementSibling;
                        while (prevRow) {
                            if (prevRow.getAttribute('data-level') == 1) {
                                const kodeSpan = prevRow.querySelector('td:first-child .bg-blue-600');
                                if (kodeSpan) kodeKategori = kodeSpan.textContent.trim();
                                
                                let namaSpan = prevRow.querySelector('td:first-child .text-sm.font-bold');
                                if (namaSpan) {
                                    namaKategori = namaSpan.textContent.trim().replace(/\s*\([^)]*\)/, '');
                                }
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
                        id_periode: triwulan ? parseInt(triwulan) : null,
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
                
                const wilayahInput = document.createElement('input');
                wilayahInput.type = 'hidden';
                wilayahInput.name = 'id_wilayah';
                wilayahInput.value = document.getElementById('current-wilayah').value;
                this.appendChild(wilayahInput);
                
                document.getElementById('manual-data-input').value = JSON.stringify(data);
                this.submit();
            });
        }
        
        // Export button
        const exportBtn = document.getElementById('export-fenomena');
        if (exportBtn) {
            exportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const tahun = document.querySelector('select[name="tahun"]').value;
                const pendekatan = document.querySelector('select[name="pendekatan"]').value;
                const mode = document.getElementById('current-mode').value;
                const wilayahId = document.getElementById('current-wilayah').value;
                
                let exportUrl = `/fenomena/export?tahun=${tahun}&pendekatan=${pendekatan}&mode=${mode}`;
                
                if (wilayahId && wilayahId !== '') {
                    exportUrl += `&id_wilayah=${wilayahId}`;
                }
                
                if (mode === 'triwulanan') {
                    const urlParams = new URLSearchParams(window.location.search);
                    let triwulan = urlParams.get('triwulan') || '1';
                    exportUrl += `&triwulan=${triwulan}`;
                }
                
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<span class="spinner mr-2"></span> Loading...';
                exportBtn.disabled = true;
                
                window.location.href = exportUrl;
                
                setTimeout(() => {
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                }, 2000);
            });
        }
        // =========================================================
// TOMBOL AMBIL DARI REKONSILIASI - PERBAIKAN
// =========================================================
const rekonsiliasiBtn = document.getElementById('load-from-rekonsiliasi');
if (rekonsiliasiBtn) {
    console.log('Rekonsiliasi button found, adding click handler');
    
    // Hapus event listener lama dengan clone dan replace
    const newBtn = rekonsiliasiBtn.cloneNode(true);
    rekonsiliasiBtn.parentNode.replaceChild(newBtn, rekonsiliasiBtn);
    
    newBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Rekonsiliasi button clicked!');
        
        // Cek apakah wilayah terkunci
        const isLocked = document.getElementById('is-locked')?.value === 'true';
        if (isLocked) {
            alert('Wilayah ini sudah dikunci. Tidak dapat mengambil data dari rekonsiliasi.');
            return;
        }
        
        const tahun = document.querySelector('select[name="tahun"]')?.value;
        const pendekatan = document.querySelector('select[name="pendekatan"]')?.value;
        const wilayahId = document.getElementById('current-wilayah')?.value;
        
        console.log('Tahun:', tahun, 'Pendekatan:', pendekatan, 'Wilayah:', wilayahId);
        
        if (!wilayahId) {
            alert('Silakan pilih kabupaten/kota terlebih dahulu');
            return;
        }
        
        const loadBtn = this;
        const originalText = loadBtn.innerHTML;
        loadBtn.innerHTML = '<span class="spinner mr-2"></span> Loading...';
        loadBtn.disabled = true;
        
        // Panggil API rekonsiliasi untuk semua triwulan (1-4)
        const promises = [
            fetch(`/rekonsiliasi/get-nilai-fenomena-triwulanan?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}&triwulan=1`).then(res => res.json()),
            fetch(`/rekonsiliasi/get-nilai-fenomena-triwulanan?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}&triwulan=2`).then(res => res.json()),
            fetch(`/rekonsiliasi/get-nilai-fenomena-triwulanan?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}&triwulan=3`).then(res => res.json()),
            fetch(`/rekonsiliasi/get-nilai-fenomena-triwulanan?tahun=${tahun}&pendekatan=${pendekatan}&id_wilayah=${wilayahId}&triwulan=4`).then(res => res.json())
        ];
        
        Promise.all(promises)
        .then(responses => {
            console.log('Responses received:', responses);
            
            let updatedCount = 0;
            
            responses.forEach((response, index) => {
                const triwulan = index + 1;
                
                if (response.error) {
                    console.error(`Error triwulan ${triwulan}:`, response.error);
                    return;
                }
                
                // Update nilai QtoQ untuk kategori
                document.querySelectorAll(`tr[data-jenis="q-to-q"] input.nilai:not([disabled])`).forEach(input => {
                    const kategoriId = input.getAttribute('data-kategori-id');
                    const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                    
                    if (inputTriwulan === triwulan && response.q_to_q_kategori && response.q_to_q_kategori[kategoriId] !== undefined) {
                        const nilai = response.q_to_q_kategori[kategoriId];
                        if (nilai !== 0 && nilai !== null) {
                            input.value = nilai.toFixed(2).replace('.', ',');
                            updatedCount++;
                        }
                    }
                });
                
                // Update nilai YonY untuk kategori
                document.querySelectorAll(`tr[data-jenis="y-on-y"] input.nilai:not([disabled])`).forEach(input => {
                    const kategoriId = input.getAttribute('data-kategori-id');
                    const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                    
                    if (inputTriwulan === triwulan && response.y_on_y_kategori && response.y_on_y_kategori[kategoriId] !== undefined) {
                        const nilai = response.y_on_y_kategori[kategoriId];
                        if (nilai !== 0 && nilai !== null) {
                            input.value = nilai.toFixed(2).replace('.', ',');
                            updatedCount++;
                        }
                    }
                });
                
                // Update nilai CtoC untuk kategori (jika ada)
                if (response.c_to_c_kategori) {
                    document.querySelectorAll(`tr[data-jenis="c-to-c"] input.nilai:not([disabled])`).forEach(input => {
                        const kategoriId = input.getAttribute('data-kategori-id');
                        const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                        
                        if (inputTriwulan === triwulan && response.c_to_c_kategori[kategoriId] !== undefined) {
                            const nilai = response.c_to_c_kategori[kategoriId];
                            if (nilai !== 0 && nilai !== null) {
                                input.value = nilai.toFixed(2).replace('.', ',');
                                updatedCount++;
                            }
                        }
                    });
                }
                
                // Update nilai QtoQ untuk subkategori
                document.querySelectorAll(`tr[data-jenis="q-to-q"][data-subkategori-id] input.nilai:not([disabled])`).forEach(input => {
                    const subkategoriId = input.getAttribute('data-subkategori');
                    const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                    
                    if (inputTriwulan === triwulan && response.q_to_q_sub && response.q_to_q_sub[subkategoriId] !== undefined) {
                        const nilai = response.q_to_q_sub[subkategoriId];
                        if (nilai !== 0 && nilai !== null) {
                            input.value = nilai.toFixed(2).replace('.', ',');
                            updatedCount++;
                        }
                    }
                });
                
                // Update nilai YonY untuk subkategori
                document.querySelectorAll(`tr[data-jenis="y-on-y"][data-subkategori-id] input.nilai:not([disabled])`).forEach(input => {
                    const subkategoriId = input.getAttribute('data-subkategori');
                    const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                    
                    if (inputTriwulan === triwulan && response.y_on_y_sub && response.y_on_y_sub[subkategoriId] !== undefined) {
                        const nilai = response.y_on_y_sub[subkategoriId];
                        if (nilai !== 0 && nilai !== null) {
                            input.value = nilai.toFixed(2).replace('.', ',');
                            updatedCount++;
                        }
                    }
                });
                
                // Update nilai CtoC untuk subkategori (jika ada)
                if (response.c_to_c_sub) {
                    document.querySelectorAll(`tr[data-jenis="c-to-c"][data-subkategori-id] input.nilai:not([disabled])`).forEach(input => {
                        const subkategoriId = input.getAttribute('data-subkategori');
                        const inputTriwulan = parseInt(input.getAttribute('data-triwulan'));
                        
                        if (inputTriwulan === triwulan && response.c_to_c_sub[subkategoriId] !== undefined) {
                            const nilai = response.c_to_c_sub[subkategoriId];
                            if (nilai !== 0 && nilai !== null) {
                                input.value = nilai.toFixed(2).replace('.', ',');
                                updatedCount++;
                            }
                        }
                    });
                }
            });
            
            // Trigger event change pada input untuk trigger auto-resize jika perlu
            document.querySelectorAll('input.nilai').forEach(input => {
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            });
            
            // Refresh textarea heights
            if (typeof initAllTextareas === 'function') {
                initAllTextareas();
            }
            
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
    console.error('Rekonsiliasi button not found! Check if element with id="load-from-rekonsiliasi" exists');
}
    });
    
    function parseNilaiIndonesia(value) {
        if (!value || value.trim() === '') return null;
        let processed = value.trim().replace(',', '.');
        processed = processed.replace(/[^\d.-]/g, '');
        const num = parseFloat(processed);
        return isNaN(num) ? null : num;
    }
</script>
@endsection