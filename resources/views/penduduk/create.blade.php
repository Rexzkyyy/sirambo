@extends('layouts.main')

@section('title', 'Tambah Data Penduduk')

@section('content')
<div class="w-full max-w-none mx-auto mt-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Tambah Data Penduduk</h1>
        <p class="text-sm text-gray-600">Input per tahun atau import data multi tahun lewat template.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Tambah Data (Per Tahun)</h2>
                <p class="text-sm text-gray-600">Input manual per wilayah dan tahun.</p>
            </div>
            <form method="POST" action="{{ route('penduduk.store') }}" class="px-6 py-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Wilayah</label>
                    <select name="id_wilayah" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                        <option value="">Pilih Wilayah</option>
                        @foreach($wilayah as $w)
                            <option value="{{ $w->id_wilayah }}" {{ old('id_wilayah') == $w->id_wilayah ? 'selected' : '' }}>
                                {{ strtoupper($w->tipe) }} - {{ $w->nama_wilayah }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_wilayah')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun</label>
                    <input type="number" name="tahun" value="{{ old('tahun') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="2025">
                    @error('tahun')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Penduduk</label>
                    <input type="number" name="jumlah" value="{{ old('jumlah') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="Masukkan jumlah penduduk">
                    @error('jumlah')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ route('penduduk.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-800">Kembali</a>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-indigo-50 via-white to-white">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m4-6V7a2 2 0 00-2-2H7a2 2 0 00-2 2v4m14 0a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2v-5a2 2 0 012-2m12 0H5"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Import Data (Multi Tahun)</h2>
                        <p class="text-sm text-gray-600">Gunakan template untuk memasukkan banyak tahun sekaligus.</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 space-y-4">
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 text-sm text-indigo-900">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-200/60 text-indigo-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </span>
                        <div>
                            <p class="font-semibold mb-1">Template Excel</p>
                            <p class="text-xs text-indigo-700/80 mb-3">Isi kolom `id_wilayah`, `wilayah`, dan kolom tahun (mis. 2021, 2022, dst).</p>
                            <a href="{{ route('penduduk.template') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"></path>
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>
                </div>

                <form action="{{ route('penduduk.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="file" class="w-full text-xs border border-indigo-200 rounded-md px-3 py-2 bg-white" accept=".xlsx,.xls" required>
                    @error('file')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Upload & Import
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
