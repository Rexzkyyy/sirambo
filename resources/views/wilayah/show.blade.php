@extends('layouts.main')

@section('title', 'Detail Wilayah')

@section('content')
<div class="bg-white p-6 rounded shadow">

    <h1 class="text-xl font-bold text-gray-700 mb-2">
        {{ $wilayah->nama_wilayah }}
    </h1>

    <p class="text-sm text-gray-500 mb-6">
        {{ ucfirst($wilayah->tipe) }}
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Hasil PDRB --}}
        <a href="{{ route('pdrb.hasil', ['scope_wilayah' => $wilayah->id_wilayah]) }}"
           class="p-5 border rounded-lg hover:bg-blue-50 transition">
            <h2 class="font-semibold text-blue-600">
                📊 Hasil Data PDRB
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Lihat hasil PDRB {{ $wilayah->tipe }}
            </p>
        </a>

        {{-- PDRB Per Tahun --}}
        <a href="{{ route('pdrb.hasil.pertahun', ['scope_wilayah' => $wilayah->id_wilayah]) }}"
           class="p-5 border rounded-lg hover:bg-green-50 transition">
            <h2 class="font-semibold text-green-600">
                📈 PDRB Per Tahun
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Data PDRB berdasarkan tahun
            </p>
        </a>

        {{-- Placeholder --}}
        <div class="p-5 border rounded-lg bg-gray-100">
            <h2 class="font-semibold text-gray-400">
                ➕ Fitur Lainnya
            </h2>
            <p class="text-sm text-gray-400 mt-1">
                Segera hadir
            </p>
        </div>

    </div>
</div>
@endsection
