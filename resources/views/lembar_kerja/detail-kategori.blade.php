@extends('layouts.main')

@section('title', 'Lembar Kerja Kategori')

@section('content')
<div class="min-h-screen bg-slate-50 p-4 md:p-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lembar Kerja</p>
            <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                {{ $kategori->nama_kategori }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $jenisLabel }} - Kategori
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/lembar-kerja?jenis=' . $jenis) }}"
               class="px-4 py-2 rounded-lg text-xs font-bold border bg-white text-slate-600 border-slate-200 hover:border-slate-400">
                Kembali
            </a>
            <a href="{{ route('rekon_lk', [
                'jenis' => $jenis,
                'wilayah_id' => $selectedWilayahId ?? null,
                'item_key' => 'cat-' . ($kategori->id_kategori ?? '')
            ]) }}" target="_blank" rel="noopener"
               class="px-4 py-2 rounded-lg text-xs font-bold border bg-orange-600 text-white border-orange-600 hover:bg-orange-700">
                Rekap LK
            </a>
        </div>
    </div>

    <div class="bg-white border border-slate-200/70 rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-2.5 h-2.5 rounded-full bg-slate-900"></div>
            <h2 class="text-sm font-black uppercase tracking-widest text-slate-800">Detail Kategori</h2>
        </div>
        <p class="text-sm text-slate-600">
            Halaman ini disiapkan untuk ringkasan dan daftar sub kategori lembar kerja pada kategori ini.
            Beri tahu format yang diinginkan, nanti saya lengkapi.
        </p>
    </div>
</div>
@endsection
