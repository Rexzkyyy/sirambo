@extends('layouts.main')

@section('title', 'Import PDRB')

@section('content')
<div class="container mx-auto px-4">
    <h1 class="text-2xl font-bold mb-6">Import Data PDRB Menurut Pengeluaran</h1>

    {{-- Pesan --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 mb-6 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('success_full'))
        <div class="bg-green-100 text-green-700 p-3 mb-6 rounded whitespace-pre-line">
            {!! session('success_full') !!}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ===================== --}}
        {{-- CARD 1 : SINGLE --}}
        {{-- ===================== --}}
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-bold mb-4 text-blue-600">
                <i class="fas fa-file-import mr-2"></i>Import PDRB Pengerluaran (Single Tahun)
            </h2>

            <a href="{{ route('pdrb.template') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4 transition">
                <i class="fas fa-download mr-2"></i> Download Template Single
            </a>

            <form action="{{ route('pdrb.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block mb-1 font-medium">Tahun</label>
                        <select name="id_tahun" class="w-full border p-2 rounded" required>
                            <option value="">-- Pilih Tahun --</option>
                            @foreach($tahun as $t)
                                <option value="{{ $t->id_tahun }}">{{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">Periode</label>
                        <select name="id_periode" class="w-full border p-2 rounded" required>
                            <option value="">-- Pilih Periode --</option>
                            @foreach($periode as $p)
                                <option value="{{ $p->id_periode }}">{{ $p->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">File Excel</label>
                        <input type="file" name="file" class="w-full border p-2 rounded" required>
                    </div>

                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded font-medium">
                        <i class="fas fa-upload mr-2"></i> Upload & Import Single
                    </button>
                </div>
            </form>
        </div>

        {{-- ===================== --}}
        {{-- CARD 2 : MULTI TAHUN --}}
        {{-- ===================== --}}
        @if($canImportMulti)
            <div class="bg-white p-6 rounded shadow">
                <h2 class="text-xl font-bold mb-4 text-purple-600">
                    <i class="fas fa-layer-group mr-2"></i>Import PDRB (Multi Tahun)
                </h2>

                <a href="{{ route('pdrb.template.full') }}"
                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 mb-4 transition">
                    <i class="fas fa-download mr-2"></i> Download Template Multi Tahun
                </a>

                <div class="bg-purple-50 border border-purple-200 p-4 rounded text-sm text-purple-800 mb-4">
                    <p class="font-semibold mb-2">📌 Ketentuan File Excel:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li><b>1 File Excel</b></li>
                        <li><b>2 Sheet WAJIB</b>: Berlaku & Konstan</li>
                        <li>Mendukung <b>multi tahun & semua triwulan</b></li>
                    </ul>
                </div>

                <form action="{{ route('pdrb.import.full') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 font-medium">Wilayah Tujuan</label>
                          <select name="id_wilayah" class="w-full border p-2 rounded" required>
    <option value="">-- Pilih Wilayah --</option>
    @foreach($wilayah as $w)
        <option value="{{ $w->id_wilayah }}">
            {{ $w->nama_wilayah }}
        </option>
    @endforeach
</select>

                        </div>

                        <div>
                            <label class="block mb-1 font-medium">File Excel</label>
                            <input type="file" name="file" class="w-full border p-2 rounded" required>
                        </div>

                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded font-medium">
                            <i class="fas fa-upload mr-2"></i> Upload & Import Multi Tahun
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endsection
