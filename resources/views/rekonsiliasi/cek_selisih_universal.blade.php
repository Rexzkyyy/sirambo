@extends('layouts.main')

@section('title','Cek Selisih P0 - P1')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .table-scroll::-webkit-scrollbar { height:6px; width:6px; }
    .table-scroll::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:6px; }
</style>

<div class="p-4 md:p-8 space-y-6">

    {{-- HEADER --}}
    <div class="bg-white p-5 rounded-2xl border shadow-sm">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-xl font-black text-slate-800">
                    Cek Selisih <span class="text-blue-600">Resume P0 - P1</span>
                </h1>
                <p class="text-xs text-slate-500">
                    Nilai = Resume P0 dikurangi Resume P1
                </p>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="bg-white p-5 rounded-2xl border shadow-sm">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

            {{-- Tahun --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase">Tahun</label>
                <select name="tahun" class="form-input w-full">
                    @foreach($tahunList as $t)
                        <option value="{{ $t->id_tahun }}" {{ $tahun==$t->id_tahun?'selected':'' }}>
                            {{ $t->tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Periode --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase">Periode</label>
                <select name="triwulan" class="form-input w-full">
                    <option value="all" {{ $triwulan=='all'?'selected':'' }}>Semua Periode</option>
                    @foreach($periodeList as $p)
                        <option value="{{ $p->id_periode }}" {{ $triwulan==$p->id_periode?'selected':'' }}>
                            {{ $p->nama_periode }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Wilayah --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase">Wilayah</label>
                <select name="wilayah" class="form-input w-full">
                    <option value="provinsi">Provinsi</option>
                    @foreach($kabkota as $k)
                        <option value="{{ $k->id_wilayah }}">
                            {{ $k->nama_kabupaten }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pendekatan --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase">Pendekatan</label>
                <select name="pendekatan" class="form-input w-full">
                    <option value="lapangan_usaha" {{ $pendekatan=='lapangan_usaha'?'selected':'' }}>
                        Lapangan Usaha
                    </option>
                    <option value="pengeluaran" {{ $pendekatan=='pengeluaran'?'selected':'' }}>
                        Pengeluaran
                    </option>
                </select>
            </div>

            {{-- Tipe PDRB --}}
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase">Tipe PDRB</label>
                <select name="tipe_pdrb" class="form-input w-full">
                    <option value="berlaku">ADHB (Berlaku)</option>
                    <option value="konstan">ADHK (Konstan)</option>
                    <option value="qtoq">Q to Q</option>
                    <option value="ytoy">Y on Y</option>
                    <option value="ctoc">C to C</option>
                    <option value="indeks_implisit">Indeks Implisit</option>
                    <option value="laju_implisit">Laju Implisit</option>
                    <option value="struktur_dalam">Struktur Dalam</option>
                    <option value="struktur_antar">Struktur Antar</option>
                </select>
            </div>

            {{-- Button --}}
            <div>
                <button class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow">
                    Tampilkan
                </button>
            </div>

        </form>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
        <div class="table-scroll overflow-auto max-h-[650px]">
            <table class="w-full text-sm border-collapse">

                <thead class="bg-slate-100 sticky top-0 z-20">
                    <tr>
                        <th rowspan="2" class="sticky left-0 z-30 bg-slate-100 px-4 py-3 text-left border">
                            Kategori
                        </th>
                        <th colspan="4" class="px-4 py-3 text-center border">
                            {{ $tahun }}
                        </th>
                        <th rowspan="2" class="px-4 py-3 text-center border">
                            Total
                        </th>
                    </tr>
                    <tr>
                        <th class="px-4 py-2 border text-center">Triwulan I</th>
                        <th class="px-4 py-2 border text-center">Triwulan II</th>
                        <th class="px-4 py-2 border text-center">Triwulan III</th>
                        <th class="px-4 py-2 border text-center">Triwulan IV</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rekonsiliasi as $row)
                        <tr class="{{ $row['level']==1?'bg-slate-50 font-bold':'' }} hover:bg-blue-50/50">
                            <td class="sticky left-0 z-10 bg-white px-4 py-2 border">
                                {{ $row['kategori'] }}
                            </td>

                            <td class="px-4 py-2 text-right border">
                                {{ number_format($row['tw1'] ?? 0,2,',','.') }}
                            </td>
                            <td class="px-4 py-2 text-right border">
                                {{ number_format($row['tw2'] ?? 0,2,',','.') }}
                            </td>
                            <td class="px-4 py-2 text-right border">
                                {{ number_format($row['tw3'] ?? 0,2,',','.') }}
                            </td>
                            <td class="px-4 py-2 text-right border">
                                {{ number_format($row['tw4'] ?? 0,2,',','.') }}
                            </td>
                            <td class="px-4 py-2 text-right border font-semibold">
                                {{ number_format($row['total'] ?? 0,2,',','.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">
                                Data tidak tersedia
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>
@endsection