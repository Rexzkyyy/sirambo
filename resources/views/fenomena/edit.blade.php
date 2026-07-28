@extends('layouts.main')

@section('title', 'Edit Fenomena')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Fenomena</h1>
        <a href="{{ route('fenomena.index', ['tahun' => $fenomena->tahun, 'pendekatan' => $fenomena->pendekatan]) }}" class="text-gray-600 hover:text-gray-900 flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-4xl mx-auto">
        <form action="{{ route('fenomena.update', $fenomena->id_fenomena) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <input type="text" value="{{ $fenomena->tahun }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pendekatan</label>
                    <input type="text" value="{{ $fenomena->pendekatan == 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Kategori</label>
                    <input type="text" value="{{ $fenomena->kode_kategori ?? '-' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <input type="text" value="{{ $fenomena->nama_kategori }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                </div>
            </div>
            
            @if($fenomena->level > 1)
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Subkategori</label>
                <input type="text" value="{{ $fenomena->nama_sub_kategori ?? '-' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
            </div>
            @endif
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Data</label>
                <input type="text" value="{{ $fenomena->jenis_data }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai (%)</label>
                <input type="text" step="0.01" name="nilai" value="{{ $fenomena->nilai !== null ? number_format($fenomena->nilai, 2, ',', '.') : '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Gunakan koma (,) untuk desimal, contoh: 11,08</p>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fenomena</label>
                <textarea name="fenomena" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ $fenomena->fenomena }}</textarea>
            </div>
            
            <div class="flex justify-end gap-2">
                <a href="{{ route('fenomena.index', ['tahun' => $fenomena->tahun, 'pendekatan' => $fenomena->pendekatan]) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Batal
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    Update Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>
@endsection