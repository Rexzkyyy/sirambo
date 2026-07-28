@extends('layouts.main')

@section('title', 'Preview Import Lembar Kerja')

@section('content')
<div class="p-4">
    <h1 class="text-xl font-bold mb-2">Preview Import</h1>

    @if(session('import_error'))
        <div class="mb-2 text-red-700">{{ session('import_error') }}</div>
    @endif
    @if(session('import_success'))
        <div class="mb-2 text-green-700">{{ session('import_success') }}</div>
    @endif

    <div class="mb-4">
        <strong>Sub Kategori:</strong> {{ $meta['sub_kategori_id'] ?? $sub_kategori_id ?? '' }}
        &nbsp; <strong>Tahun:</strong> {{ $tahun }}
        &nbsp; <strong>Periode:</strong> {{ $periode }}
    </div>

    <form method="post" action="{{ route('lembar_kerja.import.confirm', $sub_kategori_id) }}">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="periode" value="{{ $periode }}">
        <input type="hidden" name="jenis" value="{{ request('jenis') }}">

        <div class="overflow-auto border rounded p-2 mb-4 max-h-[60vh]">
            <table class="w-full text-sm table-auto border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border px-2 py-1">No</th>
                        @if(!empty($preview) && is_array($preview[0]))
                            @foreach(array_keys($preview[0]) as $h)
                                <th class="border px-2 py-1">{{ $h }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $i => $row)
                        <tr class="even:bg-slate-50">
                            <td class="border px-2 py-1 text-center">{{ $i + 1 }}</td>
                            @foreach($row as $col)
                                <td class="border px-2 py-1">{{ $col }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('lembar_kerja.detail', ['id_sub_kategori' => $sub_kategori_id, 'jenis' => request('jenis')]) }}" class="px-3 py-2 bg-white border rounded">Batal</a>
            <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded">Konfirmasi dan Simpan</button>
        </div>
    </form>
</div>
@endsection
