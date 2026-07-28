@extends('layouts.main')
@section('title','Tambah Kategori')
@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow-md">
    <h1 class="text-2xl font-bold mb-4">Tambah Kategori</h1>
    <form action="{{ route('kategori.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="block font-medium">Kode Kategori</label>
            <input type="text" name="kode_kategori" class="w-full border px-3 py-2 rounded" value="{{ old('kode_kategori') }}">
            @error('kode_kategori') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label class="block font-medium">Nama Kategori</label>
            <input type="text" name="nama_kategori" class="w-full border px-3 py-2 rounded" value="{{ old('nama_kategori') }}">
            @error('nama_kategori') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        <a href="{{ route('kategori.index') }}" class="ml-2 px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
    </form>
</div>
@endsection
