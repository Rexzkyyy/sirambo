@extends('layouts.main')

@section('title', 'Rekonsiliasi P1')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-4 md:p-6">

    <!-- HEADER SECTION -->
    @php
        $pendekatanLabel = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran'
            ? 'Pengeluaran'
            : 'Lapangan Usaha';
        $isPengeluaran = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran';
    @endphp

    <div class="mb-8 text-center md:text-left">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">
            Rekonsiliasi <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">P1</span>
        </h1>
        <p class="text-gray-600 max-w-2xl">
            Pilih kategori atau sub kategori PDRB ({{ $pendekatanLabel }}) di bawah ini untuk melanjutkan proses rekonsiliasi
        </p>

        <div class="mt-4 inline-flex items-center gap-2">
            <a href="{{ route('rekon_p1.index', ['jenis' => 'lapangan_usaha']) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition
                      {{ $isPengeluaran ? 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                Lapangan Usaha
            </a>
            <a href="{{ route('rekon_p1.index', ['jenis' => 'pengeluaran']) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition
                      {{ $isPengeluaran ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50' }}">
                Pengeluaran
            </a>
        </div>
        
        <!-- COUNTER -->
        <div class="mt-4 inline-flex items-center space-x-4">
            <div class="px-4 py-2 bg-white rounded-full shadow-sm">
                <span class="text-sm text-gray-600">Total Item:</span>
                <span class="ml-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                    {{ $totalItems ?? 0 }}
                </span>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="bg-white rounded-2xl shadow-xl p-4 md:p-8 border border-gray-100">
        
        <!-- SECTION TITLE -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Data Rekonsiliasi PDRB</h2>
                <p class="text-gray-500 text-sm mt-1">Pilih item untuk melanjutkan proses rekonsiliasi</p>
            </div>
            
            <!-- FILTER/SORT OPTIONS -->
            <div class="hidden md:flex space-x-2">
                <button class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition text-gray-700">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path>
                        </svg>
                        Urutkan
                    </span>
                </button>
            </div>
        </div>

        <!-- GRID GABUNGAN -->
        @if(isset($gabunganData) && $gabunganData->count() > 0)
        <div class="grid 
            grid-cols-1 
            sm:grid-cols-2 
            md:grid-cols-3 
            lg:grid-cols-4 
            xl:grid-cols-5 
            gap-6">
            
            @foreach ($gabunganData as $item)
                @php
                    // Tentukan gradient berdasarkan type atau kategori_id
                    $gradients = [
                        ['from-blue-500', 'to-cyan-400'],
                        ['from-indigo-500', 'to-blue-400'],
                        ['from-purple-500', 'to-indigo-400'],
                        ['from-emerald-500', 'to-teal-400'],
                        ['from-amber-500', 'to-orange-400'],
                        ['from-rose-500', 'to-pink-400'],
                        ['from-violet-500', 'to-purple-400'],
                        ['from-sky-500', 'to-blue-400'],
                        ['from-lime-500', 'to-emerald-400'],
                        ['from-red-500', 'to-rose-400'],
                    ];
                    
                    // Tentukan warna berdasarkan type
                    if ($item['type'] == 'subkategori') {
                        // Untuk subkategori, gunakan kategori_id jika ada
                        $gradientIndex = $item['kategori_id'] ? 
                            $item['kategori_id'] % count($gradients) : 
                            $loop->iteration % count($gradients);
                    } else {
                        // Untuk kategori, gunakan loop iteration
                        $gradientIndex = $loop->iteration % count($gradients);
                    }
                    
                    $gradientFrom = $gradients[$gradientIndex][0];
                    $gradientTo = $gradients[$gradientIndex][1];
                    
                    // Tentukan icon berdasarkan type
                    $icon = $item['type'] == 'subkategori' ? 
                        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' :
                        'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10';
                @endphp
                
                <a href="{{ $item['route'] }}"
                   class="group relative overflow-hidden bg-white rounded-xl shadow-md 
                          border border-gray-200 hover:border-transparent
                          hover:shadow-2xl transition-all duration-300 
                          hover:-translate-y-2 transform">

                    <!-- Card Header with Gradient -->
                    <div class="relative h-3 w-full bg-gradient-to-r {{ $gradientFrom }} {{ $gradientTo }}"></div>

                    <!-- Card Content -->
                    <div class="p-5">
                        <!-- Number Badge (Reset ulang) -->
                        <div class="absolute top-3 right-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full 
                                        bg-gradient-to-r {{ $gradientFrom }} {{ $gradientTo }} 
                                        text-white font-bold shadow-md">
                                {{ $loop->iteration }}
                            </div>
                        </div>

                        <!-- Icon Container -->
                        <div class="mb-4 flex items-center justify-center">
                            <div class="p-3 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 
                                        group-hover:from-blue-50 group-hover:to-indigo-50 transition">
                                <svg class="w-8 h-8 text-gray-600 group-hover:text-blue-600 transition" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Type Badge -->
                        <div class="text-center mb-2">
                            <span class="inline-block px-2 py-1 
                                       {{ $item['type'] == 'subkategori' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' }} 
                                       rounded-full text-xs font-medium">
                                {{ $item['type'] == 'subkategori' ? 'Sub Kategori' : 'Kategori' }}
                            </span>
                        </div>

                        <!-- Parent Kategori Badge (hanya untuk subkategori) -->
                        @if($item['type'] == 'subkategori' && $item['kategori_nama'])
                        <div class="text-center mb-2">
                            <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 
                                       rounded-full text-xs font-medium">
                                {{ $item['kategori_nama'] }}
                            </span>
                        </div>
                        @endif

                        <!-- Title -->
                        <h3 class="text-center font-bold text-gray-800 mb-2 text-lg 
                                   group-hover:text-transparent group-hover:bg-clip-text 
                                   group-hover:bg-gradient-to-r {{ $gradientFrom }} {{ $gradientTo }} transition">
                            {{ $item['nama'] }}
                        </h3>

                        <!-- Additional Info -->
                        <div class="mt-3 text-center">
                           
                            <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 
                                       rounded-full text-sm font-medium">
                                Detail Item
                            </span>
                            
                        </div>

                        <!-- Indicator -->
                        <div class="mt-4 flex items-center justify-center">
                            <div class="flex items-center text-sm text-gray-500">
                                <span class="mr-2">
                                  
                                    Klik untuk detail
                              
                                </span>
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Hover Effect Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-5 
                                {{ $gradientFrom }} {{ $gradientTo }} transition-opacity duration-300"></div>
                </a>
            @endforeach
        </div>
        @else
        <!-- Message jika tidak ada data -->
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Tidak ada data</h3>
            <p class="mt-1 text-gray-500">Belum ada data yang tersedia untuk rekonsiliasi.</p>
        </div>
        @endif

    </div>
</div>
@endsection
