@extends('layouts.main')

@section('title','Cek Selisih')

@section('content')
<div class="min-h-screen bg-slate-50 p-4 md:p-6">

    <div class="bg-white border rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-col md:flex-row justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold text-slate-700">Cek Selisih P0 - P1</h1>
                <p class="text-sm text-slate-500">
                    {{ strtoupper($request->get('jenis','pdrb')) }}
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 border text-left">Kategori</th>
                    <th class="p-3 border text-right">Provinsi</th>
                    <th class="p-3 border text-right">Total Kab/Kota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hasil as $row)
                <tr class="hover:bg-slate-50">
                    <td class="p-3 border">{{ $row['kategori'] }}</td>
                    <td class="p-3 border text-right">
                        {{ number_format($row['provinsi'],2,',','.') }}
                    </td>
                    <td class="p-3 border text-right">
                        {{ number_format($row['total'] ?? $row['total_kab'],2,',','.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection