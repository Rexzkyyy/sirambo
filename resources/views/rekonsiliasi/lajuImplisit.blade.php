@extends('layouts.main')

@section('title', 'Laju Implisit')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: #f8fafc; 
    }
    .custom-table-container::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-table-container::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-table-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="p-4 md:p-8 space-y-6 w-full max-w-none mx-auto">

    {{-- ================= HEADER SECTION ================= --}}
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 md:p-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="space-y-1 text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-lg mb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">
                        {{ ($resume ?? null) === 'p1' ? 'Resume P1' : 'Resume P0' }}
                    </span>
                </div>
                
                <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-tight">
                    Laju <span class="text-indigo-600">Implisit</span> {{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}
                </h1>
                
                <p class="text-slate-500 text-xs md:text-sm font-medium">
                    Laju Implisit Kabupaten/Kota Provinsi berdasarkan 
                    <span class="text-slate-900 font-semibold">{{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('rekonsiliasi.export','laju') }}?{{ http_build_query(request()->all()) }}"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2 active:scale-95 shadow-sm">
                    <i class="fa fa-file-excel"></i>
                    Export Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ================= FILTER PANEL ================= --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <form method="GET" class="flex flex-col lg:flex-row items-end gap-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                {{-- Tahun --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Tahun</label>
                    <select name="tahun" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        @foreach($tahunList as $t)
                            <option value="{{ $t->id_tahun }}" {{ $selectedTahun == $t->id_tahun ? 'selected' : '' }}>🗓️ {{ $t->tahun }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Triwulan --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Triwulan</label>
                    <select name="triwulan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option value="all" {{ $triwulan === 'all' ? 'selected' : '' }}>📊 Total</option>
                        @foreach($periodeList as $p)
                            <option value="{{ $p->id_periode }}" {{ $triwulan == $p->id_periode ? 'selected' : '' }}>📈 {{ $p->nama_periode }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pendekatan --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pendekatan</label>
                    <select name="pendekatan" id="pendekatan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option value="lapangan_usaha" {{ request()->pendekatan == 'lapangan_usaha' ? 'selected' : '' }}>🏗️ Lapangan Usaha</option>
                        <option value="pengeluaran" {{ request()->pendekatan == 'pengeluaran' ? 'selected' : '' }}>🛍️ Pengeluaran</option>
                    </select>
                </div>

                {{-- Pilih Data --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pilih Laporan</label>
                    <select id="pilihPdrb" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all">
                        <option value="{{ route('rekonsiliasi.index', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode,'tipe_pdrb'=>'berlaku']) }}" {{ ($tipePdrb ?? 'berlaku') == 'berlaku' ? 'selected' : '' }}>Berlaku</option>
                        <option value="{{ route('rekonsiliasi.index', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode,'tipe_pdrb'=>'konstan']) }}" {{ ($tipePdrb ?? '') == 'konstan' ? 'selected' : '' }}>Konstan</option>
                        <option value="{{ route('rekonsiliasi.qtoq', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.qtoq') ? 'selected' : '' }}>Q to Q</option>
                        <option value="{{ route('rekonsiliasi.ytoy', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.ytoy') ? 'selected' : '' }}>Y on Y</option>
                        <option value="{{ route('rekonsiliasi.ctoc', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.ctoc') ? 'selected' : '' }}>C to C</option>
                        <option value="{{ route('rekonsiliasi.indeksImplisit', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.indeksImplisit') ? 'selected' : '' }}>Indeks Implisit</option>
                        <option value="{{ route('rekonsiliasi.lajuImplisit', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.lajuImplisit') ? 'selected' : '' }}>Laju Implisit</option>
                        <option value="{{ route('rekonsiliasi.stukturDalam', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.stukturDalam') ? 'selected' : '' }}>Struktur Dalam</option>
                        <option value="{{ route('rekonsiliasi.stukturAntar', ['tahun'=>$selectedTahun,'triwulan'=>$selectedPeriode]) }}" {{ request()->routeIs('rekonsiliasi.stukturAntar') ? 'selected' : '' }}>Struktur Antar</option>
                    </select>
                </div>
            </div>

            <button type="button" onclick="goPdrb()" class="w-full lg:w-auto px-8 py-2.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2 h-[46px]">
                Tampilkan
            </button>
        </form>
    </div>

    {{-- ================= DISCREPANCY INSIGHT ================= --}}
    @if(($pendekatan ?? 'lapangan_usaha') === 'pengeluaran')
        @php
            $insightKab = [];
            $luRow = collect($kategoriList)->firstWhere('nama_kategori','PRODUK DOMESTIK REGIONAL BRUTO');
            $lapusRow = collect($kategoriList)->firstWhere('nama_kategori','PRODUK DOMESTIK REGIONAL BRUTO LAPUS');

            if ($luRow && $lapusRow && $selectedTahun) {
                foreach($selectedWilayahs->whereIn('tipe', ['kabupaten', 'kota']) as $kab) {
                    $valLu = $hasilKategori[$luRow->id_kategori][$kab->id_wilayah] ?? 0;
                    $valLapus = $hasilKategori[$lapusRow->id_kategori][$kab->id_wilayah] ?? 0;
                    $diff = round($valLu - $valLapus, 9);
                    if (abs($diff) > 1e-9) {
                        $insightKab[] = ['nama' => str_replace(['Kabupaten ', 'Kota '], '', $kab->nama_kabupaten ?? $kab->nama), 'val' => $diff];
                    }
                }
            }
        @endphp

        @if(count($insightKab) > 0)
            <div class="bg-rose-50 border-l-4 border-rose-500 p-6 rounded-2xl shadow-sm animate-pulse-subtle">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-rose-500 rounded-xl text-white shadow-lg shadow-rose-200">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-black text-rose-900 mb-1">Insight Selisih Wilayah</h3>
                        <p class="text-rose-700 text-sm font-medium mb-4">Ditemukan selisih (Pengeluaran - Lapus) pada laju pertumbuhan wilayah berikut:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            @foreach($insightKab as $item)
                                <div class="bg-white/60 border border-rose-200 rounded-xl p-3 flex flex-col items-center justify-center text-center">
                                    <span class="text-[10px] font-bold text-rose-400 uppercase tracking-tighter">{{ $item['nama'] }}</span>
                                    <span class="text-sm font-black text-rose-600">{{ fmt($item['val'], 9) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-6 rounded-2xl shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500 rounded-xl text-white shadow-lg shadow-emerald-200">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-emerald-900">Data Wilayah Seimbang</h3>
                        <p class="text-emerald-700 text-sm font-medium">Semua Kabupaten/Kota memiliki selisih laju di bawah ambang batas (1e-9).</p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- ================= TABEL DATA ================= --}}
    @if($selectedTahun && $selectedWilayahs->count())
    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="custom-table-container overflow-auto max-h-[600px] relative">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="sticky top-0 left-0 z-50 bg-slate-100 border-b border-r border-slate-200 px-6 py-4 text-left font-bold text-slate-700 min-w-[320px]">
                            {{ ($pendekatan ?? 'lapangan_usaha') == 'pengeluaran' ? 'KOMPONEN' : 'KATEGORI' }}
                        </th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[125px]">CEK</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[100px]">SELISIH</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[130px]">PROVINSI</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[140px]">TOTAL KAB/KOTA</th>

                        @foreach($selectedWilayahs->whereIn('tipe',['kabupaten','kota']) as $kab)
                            <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-600 min-w-[140px]">
                                <div class="line-clamp-2 uppercase text-[11px] leading-tight">{{ $kab->kabupaten->nama_kabupaten ?? $kab->nama }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
@foreach($kategoriList as $k)
    @php
        $provId = $selectedWilayahs->firstWhere('tipe','provinsi')->id_wilayah ?? null;
        $prov = $hasilKategori[$k->id_kategori][$provId] ?? null;
        $total = $hasilTotalKabKota[$k->id_kategori] ?? null;
        $kabDataValues = collect($hasilKategori[$k->id_kategori] ?? [])->values();
        $selisih = ($prov !== null && $total !== null) ? $total - $prov : null;

        if ($prov === null || $total === null) {
            $cek = '-'; 
            $warna = '';
        } else {

            $threshold = 3; // ambang beda arah (%)

$positifCount = $kabDataValues->filter(fn($v) => $v > 0)->count();
$negatifCount = $kabDataValues->filter(fn($v) => $v < 0)->count();

$isBedaArah = false;

if (
    $selisih !== null &&
    abs($selisih) >= $threshold &&
    (
        ($prov > 0 && $negatifCount > $positifCount) ||
        ($prov < 0 && $positifCount > $negatifCount)
    )
) {
    $isBedaArah = true;
}

            if ($isBedaArah) {
                $cek = 'BEDA ARAH';
                $warna = 'text-red-600 font-bold';
            } else {
                if ($selisih > 10 || $selisih < -10) {
                    $cek = 'SELISIH > 10 %';
                    $warna = 'text-red-600 font-bold';
                }
                elseif (($selisih > 5 && $selisih <= 10) || ($selisih < -5 && $selisih >= -10)) {
                    $cek = 'SELISIH 5 - 10 %';
                    $warna = 'text-yellow-500 font-semibold';
                }
                else {
                    $cek = '-';
                    $warna = 'text-green-600';
                }
            }
        }
    @endphp

    {{-- Baris Kategori Utama --}}
    <tr class="bg-slate-50/80 font-bold hover:bg-blue-50/50 transition-colors">
        <td class="sticky left-0 z-20 px-6 py-4 border-r border-b border-slate-200 bg-slate-50 uppercase text-slate-900 text-sm">
            <div class="flex items-center gap-3 text-left"> {{-- Tambahkan text-left di sini --}}
                <span class="text-[10px] font-black text-slate-400 w-8 shrink-0">{{ $k->kode }}</span>
                <span class="flex-1 whitespace-normal break-words">{{ $k->nama_kategori }}</span>
            </div>
        </td>
        <td class="px-4 py-4 text-center text-xs border-r border-b border-slate-200 {{ $warna }} tracking-wider">{{ $cek }}</td>
        <td class="px-4 py-4 text-right border-r border-b border-slate-200 tabular-nums {{ $warna }} font-extrabold text-sm">
            {{ $selisih !== null ? number_format($selisih, 2, ',', '.') : '-' }}
        </td>
        <td class="px-4 py-4 text-right border-r border-b border-slate-200 text-slate-900 tabular-nums text-sm">
            {{ $prov === null ? '-' : number_format($prov, 2, ',', '.') }}
        </td>
        <td class="px-4 py-4 text-right border-r border-b border-slate-200 text-slate-900 tabular-nums text-sm">
            {{ $total === null ? '-' : number_format($total, 2, ',', '.') }}
        </td>
        @foreach($selectedWilayahs->whereIn('tipe',['kabupaten','kota']) as $kab)
            <td class="px-4 py-4 text-right border-r border-b border-slate-200 text-slate-700 tabular-nums text-sm">
                {{ number_format($hasilKategori[$k->id_kategori][$kab->id_wilayah] ?? 0, 2, ',', '.') }}
            </td>
        @endforeach
    </tr>

    {{-- Baris Sub-Kategori --}}
    @foreach($subList->where('id_kategori',$k->id_kategori) as $s)
        @php
            $provSub = $hasilSub[$s->id_sub_kategori][$provId] ?? null;
            $totalSub = $hasilTotalKabKota['sub_'.$s->id_sub_kategori] ?? null;
            $selisihSub = ($provSub !== null && $totalSub !== null) ? $totalSub - $provSub : null;
            $kabSubValues = collect($hasilSub[$s->id_sub_kategori] ?? [])->values();

            if($provSub === null || $totalSub === null){
                $cekSub = '-'; 
                $warnaSub = '';
            } else {

                $threshold = 3;

$positifCount = $kabSubValues->filter(fn($v) => $v > 0)->count();
$negatifCount = $kabSubValues->filter(fn($v) => $v < 0)->count();

$isBedaArah = false;

if (
    $selisihSub !== null &&
    abs($selisihSub) >= $threshold &&
    (
        ($provSub > 0 && $negatifCount > $positifCount) ||
        ($provSub < 0 && $positifCount > $negatifCount)
    )
) {
    $isBedaArah = true;
}
                if ($isBedaArah) {
                    $cekSub = 'BEDA ARAH';
                    $warnaSub = 'text-red-600 font-bold';
                } else {
                    if ($selisihSub > 10 || $selisihSub < -10) {
                        $cekSub = 'SELISIH > 10 %';
                        $warnaSub = 'text-red-600 font-bold';
                    }
                    elseif (($selisihSub > 5 && $selisihSub <= 10) || ($selisihSub < -5 && $selisihSub >= -10)) {
                        $cekSub = 'SELISIH 5 - 10 %';
                        $warnaSub = 'text-yellow-500 font-semibold';
                    }
                    else {
                        $cekSub = '-';
                        $warnaSub = 'text-green-600';
                    }
                }
            }
        @endphp
        <tr class="bg-white hover:bg-blue-50/30 transition-colors">
            <td class="sticky left-0 z-20 px-6 py-3 border-r border-b border-slate-200 bg-white text-sm">
                <div class="flex items-center gap-3 text-left"> {{-- Tambahkan text-left di sini --}}
                    <span class="text-[10px] font-bold text-slate-300 w-8 shrink-0 text-left">{{ $s->kode }}</span>
                    <span class="flex-1 text-slate-600 leading-tight whitespace-normal">{{ $s->nama_sub_kategori }}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-center text-xs border-r border-b border-slate-200 {{ $warnaSub }}">{{ $cekSub }}</td>
            <td class="px-4 py-3 text-right border-r border-b border-slate-200 tabular-nums {{ $warnaSub }} font-bold text-sm">
                {{ $selisihSub !== null ? number_format($selisihSub, 2, ',', '.') : '-' }}
            </td>
            <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-slate-600 tabular-nums text-sm">
                {{ number_format($provSub ?? 0, 2, ',', '.') }}
            </td>
            <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-slate-600 tabular-nums text-sm">
                {{ number_format($totalSub ?? 0, 2, ',', '.') }}
            </td>
            @foreach($selectedWilayahs->whereIn('tipe',['kabupaten','kota']) as $kab)
                <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-slate-500 tabular-nums text-sm">
                    {{ number_format($hasilSub[$s->id_sub_kategori][$kab->id_wilayah] ?? 0, 2, ',', '.') }}
                </td>
            @endforeach
        </tr>
    @endforeach
@endforeach
</tbody>

                {{-- ================= FOOTER SELISIH PDRB ================= --}}
                @php
                    $luRow = collect($kategoriList)->firstWhere('nama_kategori','PRODUK DOMESTIK REGIONAL BRUTO');
                    $lapusRow = collect($kategoriList)->firstWhere('nama_kategori','PRODUK DOMESTIK REGIONAL BRUTO LAPUS');
                @endphp

                @if($luRow && $lapusRow)
                <tfoot class="bg-slate-100 font-black">
                    <tr class="border-t-2 border-slate-300">
                        <td class="sticky left-0 z-40 bg-slate-100 border-r border-slate-200 px-6 py-4 text-slate-800 italic">
                            SELISIH PRODUK DOMESTIK
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-center text-slate-400">-</td>
                        @php
                            $provSel = round(($hasilKategori[$luRow->id_kategori][$provId] ?? 0) - ($hasilKategori[$lapusRow->id_kategori][$provId] ?? 0), 9);
                            $totalSel = round(($hasilTotalKabKota[$luRow->id_kategori] ?? 0) - ($hasilTotalKabKota[$lapusRow->id_kategori] ?? 0), 9);
                        @endphp
                        <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($totalSel) > 1e-9 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ fmt($totalSel, 9) }}
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($provSel) > 1e-9 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ fmt($provSel, 9) }}
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-right font-bold text-indigo-700">
                            {{ fmt($totalSel, 9) }}
                        </td>
                        @foreach($selectedWilayahs->whereIn('tipe',['kabupaten','kota']) as $kab)
                            @php 
                                $val = round(($hasilKategori[$luRow->id_kategori][$kab->id_wilayah] ?? 0) - ($hasilKategori[$lapusRow->id_kategori][$kab->id_wilayah] ?? 0), 9); 
                            @endphp
                            <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($val) > 1e-9 ? 'text-red-600' : 'text-slate-900' }}">
                                {{ fmt($val, 9) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    function goPdrb() {
        const route = document.getElementById('pilihPdrb').value;
        const tahun = document.querySelector('select[name="tahun"]').value;
        const triwulan = document.querySelector('select[name="triwulan"]').value;
        const pendekatan = document.querySelector('select[name="pendekatan"]').value;

        const currentParams = new URLSearchParams(window.location.search);
        const resume = currentParams.get('resume');

        const url = new URL(route, window.location.origin);
        url.searchParams.set('tahun', tahun);
        url.searchParams.set('triwulan', triwulan);
        url.searchParams.set('pendekatan', pendekatan);

        if (resume) {
            url.searchParams.set('resume', resume);
        }

        window.location.href = url.toString();
    } 
</script>
@endsection