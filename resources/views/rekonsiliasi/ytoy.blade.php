@extends('layouts.main')

@section('title', 'Pertumbuhan Y on Y')

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

@php
    $isLapanganUsaha = ($pendekatan ?? 'lapangan_usaha') === 'lapangan_usaha';
@endphp

<div class="p-4 md:p-8 space-y-6 w-full max-w-none mx-auto">

    {{-- ================= HEADER SECTION ================= --}}
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 md:p-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="space-y-1 text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg mb-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        {{ ($resume ?? null) === 'p1' ? 'Resume P1' : 'Resume P0' }}
                    </span>
                </div>
                
                <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-tight">
                    Pertumbuhan <span class="text-blue-600">Y on Y</span> {{ $isLapanganUsaha ? 'Lapangan Usaha' : 'Pengeluaran' }}
                </h1>
                
                <p class="text-slate-500 text-xs md:text-sm font-medium">
                    Pertumbuhan Year-on-Year PDRB Kabupaten/Kota Provinsi berdasarkan
                    <span class="text-slate-900 font-semibold">{{ $isLapanganUsaha ? 'Lapangan Usaha' : 'Pengeluaran' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('rekonsiliasi.export','yony') }}?{{ http_build_query(request()->all()) }}"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2 active:scale-95 shadow-sm">
                    <i class="fa fa-file-excel"></i>
                    Export Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ================= FILTER PANEL ================= --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <form method="GET" id="filterForm" class="flex flex-col lg:flex-row items-end gap-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                {{-- Tahun --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Tahun</label>
                    <select name="tahun" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                        @foreach($tahunList as $t)
                            <option value="{{ $t->id_tahun }}" {{ $tahun == $t->id_tahun ? 'selected' : '' }}>🗓️ Tahun {{ $t->tahun }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Triwulan --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Triwulan</label>
                    <select name="triwulan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                        <option value="all" {{ $triwulan === 'all' ? 'selected' : '' }}>📊 Total</option>
                        @foreach($periodeList as $p)
                            <option value="{{ $p->id_periode }}" {{ $triwulan == $p->id_periode ? 'selected' : '' }}>📈 {{ $p->nama_periode }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pendekatan --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pendekatan</label>
                    <select name="pendekatan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                        <option value="lapangan_usaha" {{ ($pendekatan ?? 'lapangan_usaha') == 'lapangan_usaha' ? 'selected' : '' }}>🏗️ Lapangan Usaha</option>
                        <option value="pengeluaran" {{ ($pendekatan ?? '') == 'pengeluaran' ? 'selected' : '' }}>🛍️ Pengeluaran</option>
                    </select>
                </div>

                {{-- Pilih Data --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pilih Data Laporan</label>
                    <select id="pilihPdrb" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all">
                        <option value="{{ route('rekonsiliasi.index', ['tahun'=>$tahun,'triwulan'=>$triwulan,'tipe_pdrb'=>'berlaku']) }}" {{ $tipePdrb == 'berlaku' ? 'selected' : '' }}>Berlaku</option>
                        <option value="{{ route('rekonsiliasi.index', ['tahun'=>$tahun,'triwulan'=>$triwulan,'tipe_pdrb'=>'konstan']) }}" {{ $tipePdrb == 'konstan' ? 'selected' : '' }}>Konstan</option>
                        <option value="{{ route('rekonsiliasi.qtoq', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.qtoq') ? 'selected' : '' }}>Q to Q</option>
                        <option value="{{ route('rekonsiliasi.ytoy', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.ytoy') ? 'selected' : '' }}>Y on Y</option>
                        <option value="{{ route('rekonsiliasi.ctoc', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.ctoc') ? 'selected' : '' }}>C to C</option>
                        <option value="{{ route('rekonsiliasi.indeksImplisit', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.indeksImplisit') ? 'selected' : '' }}>Indeks Implisit</option>
                        <option value="{{ route('rekonsiliasi.lajuImplisit', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.lajuImplisit') ? 'selected' : '' }}>Laju Implisit</option>
                        <option value="{{ route('rekonsiliasi.stukturDalam', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.stkturDalam') ? 'selected' : '' }}>Struktur Dalam</option>
                        <option value="{{ route('rekonsiliasi.stukturAntar', ['tahun'=>$tahun,'triwulan'=>$triwulan]) }}" {{ request()->routeIs('rekonsiliasi.stkturAntar') ? 'selected' : '' }}>Struktur Antar</option>
                    </select>
                </div>
            </div>

            <button type="button" onclick="goPdrb()" class="w-full lg:w-auto px-8 py-2.5 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2 h-[46px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Tampilkan
            </button>
        </form>
    </div>

    {{-- ================= INSIGHT SECTION ================= --}}
    @php
        $luRowInsight = collect($rekonsiliasi)->first(fn($r) => empty($r['is_sub']) && strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO');
        $lapusRowInsight = collect($rekonsiliasi)->first(fn($r) => empty($r['is_sub']) && strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS');
        
        $regionsWithDiscrepancy = [];
        if($luRowInsight && $lapusRowInsight){
            foreach($allKabkota as $kab){
                $val = round(($luRowInsight['kab'][$kab->id_wilayah] ?? 0) - ($lapusRowInsight['kab'][$kab->id_wilayah] ?? 0), 9);
                if(abs($val) > 1e-9){
                    $regionsWithDiscrepancy[] = [
                        'name' => $kab->nama_kabupaten,
                        'val' => $val
                    ];
                }
            }
        }
    @endphp

    @if(count($regionsWithDiscrepancy) > 0)
        <div class="mb-6 p-5 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="w-12 h-12 bg-rose-600 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-rose-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-black text-rose-900 uppercase tracking-wider mb-1">Insight Selisih Wilayah</h3>
                <p class="text-xs text-rose-700 leading-relaxed mb-3">Terdeteksi perbedaan antara PDRB Pengeluaran dan Lapangan Usaha (Nilai > 0). Segera lakukan pengecekan pada wilayah berikut:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach($regionsWithDiscrepancy as $rd)
                        <div class="bg-white border border-rose-100 p-3 rounded-xl flex flex-col shadow-sm">
                            <span class="text-[9px] font-extrabold text-slate-400 uppercase mb-1">{{ $rd['name'] }}</span>
                            <span class="text-sm font-black text-rose-600">{{ fmt($rd['val'], 9) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="mb-6 p-5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-600 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-200">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-emerald-900 uppercase tracking-wider mb-0.5">Semua Wilayah Konsisten</h3>
                <p class="text-xs text-emerald-700">Tidak ditemukan selisih antara PDRB Pengeluaran dan Lapangan Usaha di seluruh Kabupaten/Kota.</p>
            </div>
        </div>
    @endif

    {{-- ================= TABLE SECTION ================= --}}
    <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="custom-table-container overflow-auto max-h-[650px] relative">
            <table id="main-table" class="w-full border-separate border-spacing-0 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="sticky top-0 left-0 z-50 bg-slate-100 border-b border-r border-slate-200 px-6 py-4 text-left font-bold text-slate-700 min-w-[320px]">
                            {{ $isLapanganUsaha ? 'KATEGORI' : 'KOMPONEN' }}
                        </th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[120px]">CEK</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[100px]">SELISIH</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[130px]">PROVINSI</th>
                        <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[130px]">TOTAL KAB/KOTA</th>
                        @foreach($allKabkota as $kab)
                            <th class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-600 min-w-[140px]">
                                <div class="line-clamp-2 uppercase text-[11px] leading-tight">{{ $kab->nama_kabupaten }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($rekonsiliasi as $r)
                        @php
                            // LOGIKA CEK ASLI TETAP DIJAGA (TIDAK BERUBAH)
                            $prov = $r['provinsi'] ?? null;
                            $total = $r['total_kab'] ?? null;
                            $selisih = ($prov !== null && $total !== null) ? $total - $prov : null;

                            if($prov === null || $total === null){
                                $cek = ''; $warna = '';
                            } elseif(($prov > 0 && $total < 0) || ($prov < 0 && $total > 0)){
                                $cek = 'BEDA ARAH'; $warna = 'text-red-600 font-bold';
                            } elseif($selisih > 10 || $selisih < -10){
                                $cek = 'SELISIH > 10 %'; $warna = 'text-red-600 font-bold';
                            } elseif(($selisih > 2 && $selisih <= 5) || ($selisih < -2 && $selisih >= -5)){
                                $cek = 'SELISIH 2 - 5 %'; $warna = 'text-yellow-500 font-semibold';
                            } elseif(($selisih > 5 && $selisih <= 10) || ($selisih < -5 && $selisih >= -10)){
                                $cek = 'SELISIH 5 - 10 %'; $warna = 'text-red-500 font-semibold';
                            } else {
                                $cek = '-'; $warna = 'text-green-600';
                            }
                        @endphp

                        <tr class="hover:bg-blue-50/50 transition-colors {{ empty($r['is_sub']) ? 'bg-slate-50/80 font-bold' : 'bg-white' }}">
                            <td class="sticky left-0 z-20 px-6 py-3 border-r border-b border-slate-200 {{ empty($r['is_sub']) ? 'bg-slate-50' : 'bg-white' }}">
                                <div class="flex items-start gap-3">
                                    <span class="text-[10px] font-black text-slate-400 w-12 shrink-0 mt-1">{{ $r['kode'] ?? '' }}</span>
                                    <span class="flex-1 {{ empty($r['is_sub']) ? 'text-slate-900 uppercase' : 'pl-2 text-slate-700' }}">
                                        {{ $r['kategori'] }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-[10px] border-r border-b border-slate-200 {{ $warna }} tracking-wider">{{ $cek }}</td>
                            <td class="px-4 py-3 text-right border-r border-b border-slate-200 tabular-nums {{ $warna }}">
                                {{ number_format($selisih ?? 0, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right border-r border-b border-slate-200 font-semibold text-slate-900 tabular-nums">{{ number_format($prov ?? 0, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right border-r border-b border-slate-200 font-semibold text-slate-900 tabular-nums">{{ number_format($total ?? 0, 2, ',', '.') }}</td>

                            @foreach($filterKabkota as $w)
                                <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-slate-600 tabular-nums">
                                    {{ number_format($r['kab'][$w->id_wilayah] ?? 0, 2, ',', '.') }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($filterKabkota) }}" class="px-6 py-12 text-center text-slate-400 italic">Data tidak tersedia</td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- ================= FOOTER SELISIH ================= --}}
                @php
                    $luRow = collect($rekonsiliasi)->first(fn($r) => empty($r['is_sub']) && strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO');
                    $lapusRow = collect($rekonsiliasi)->first(fn($r) => empty($r['is_sub']) && strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS');
                @endphp

                @if($luRow && $lapusRow)
                <tfoot class="bg-slate-100 font-black">
                    <tr class="border-t-2 border-slate-300">
                        <td class="sticky left-0 z-40 bg-slate-100 border-r border-slate-200 px-6 py-4 text-slate-800 uppercase">
                            <div class="flex items-center gap-3">
                                <span class="w-12 italic text-slate-400 text-[10px]">#</span>
                                <span>SELISIH</span>
                            </div>
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-center text-slate-400">-</td>
                        @php
                            $provSel = round(($luRow['provinsi'] ?? 0) - ($lapusRow['provinsi'] ?? 0), 9);
                            $totalSel = round(($luRow['total_kab'] ?? 0) - ($lapusRow['total_kab'] ?? 0), 9);
                        @endphp
                        <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($totalSel) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ fmt($totalSel, 9) }}
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($provSel) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ fmt($provSel, 9) }}
                        </td>
                        <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($totalSel) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ fmt($totalSel, 9) }}
                        </td>
                        @foreach($allKabkota as $kab)
                            @php $val = round(($luRow['kab'][$kab->id_wilayah] ?? 0) - ($lapusRow['kab'][$kab->id_wilayah] ?? 0), 9); @endphp
                            <td class="border-r border-slate-200 px-4 py-4 text-right {{ abs($val) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ fmt($val, 9) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
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