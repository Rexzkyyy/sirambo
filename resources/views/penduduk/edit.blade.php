@extends('layouts.main')

@section('title', 'Edit Data Penduduk')

@section('content')
<div class="max-w-4xl mx-auto mt-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-xl font-extrabold text-gray-900">Edit Data Penduduk</h1>
            <p class="text-sm text-gray-600">Perbarui data penduduk per wilayah dan tahun.</p>
        </div>
        <form method="POST" action="{{ route('penduduk.update', $penduduk) }}" class="px-6 py-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Wilayah</label>
                <select name="id_wilayah" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                    <option value="">Pilih Wilayah</option>
                    @foreach($wilayah as $w)
                        <option value="{{ $w->id_wilayah }}" {{ old('id_wilayah', $penduduk->id_wilayah) == $w->id_wilayah ? 'selected' : '' }}>
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
                <input type="number" name="tahun" value="{{ old('tahun', $penduduk->tahun) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="2025">
                @error('tahun')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Penduduk</label>
                <input type="number" name="jumlah" value="{{ old('jumlah', $penduduk->jumlah) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm" placeholder="Masukkan jumlah penduduk">
                @error('jumlah')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('penduduk.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-800">Kembali</a>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
