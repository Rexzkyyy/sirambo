    @extends('layouts.main')

    @section('title', 'Cek Selisih P1 - P0')

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
        
        .sticky-col {
            position: sticky;
            left: 0;
            z-index: 40;
            background: white;
            border-right: 1px solid #e2e8f0 !important;
        }
        thead th.sticky-col { z-index: 50; background: #f8fafc; }
        /* Perbaikan UI Popover Modern */
    .popover {
        border: none;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border-radius: 16px;
        padding: 0;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .popover-body {
        padding: 12px 16px;
        color: #1e293b;
    }

    /* Garis dekoratif berdasarkan status di dalam popup */
    .status-indicator {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-right: 6px;
    }
    </style>


    <div class="p-4 md:p-8 space-y-6 w-full max-w-none mx-auto">

        {{-- ================= HEADER SECTION ================= --}}
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 md:p-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="space-y-1 text-center md:text-left">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Quality Control
                        </span>
                    </div>
                    
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-tight">
                        Cek Selisih <span class="text-blue-600">Data PDRB</span> (P1 - P0)
                    </h1>
                    
                    <p class="text-slate-500 text-xs md:text-sm font-medium">
                        Analisis hasil selisih perwilayah pendekatan:
                        <span class="text-slate-900 font-semibold">{{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- ================= FILTER PANEL ================= --}}
    <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
        <form method="GET" id="filterForm" class="flex flex-col lg:flex-row items-end gap-4">
            
            {{-- Grid disesuaikan menjadi 3 kolom untuk mengakomodasi Wilayah --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
                
                @if(auth()->user()->role === 'provinsi')
                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">
                        Wilayah Kab/Kota
                    </label>
                    <select name="id_wilayah"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-slate-400/20 outline-none transition-all">
                        @foreach($wilayahList as $w)
                            <option value="{{ $w->id_kabupaten }}"
                                {{ request('id_wilayah', $wilayah) == $w->id_kabupaten ? 'selected' : '' }}>
                                {{ $w->nama_kabupaten }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="space-y-1.5">
                    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Jenis Analisis</label>
                    <select name="jenis" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-slate-400/20 outline-none transition-all">
                        <option value="berlaku" {{ ($jenis ?? '') == 'berlaku' ? 'selected' : '' }}>Berlaku</option>
                        <option value="konstan" {{ ($jenis ?? '') == 'konstan' ? 'selected' : '' }}>Konstan</option>
                        <option value="qtoq" {{ ($jenis ?? '') == 'qtoq' ? 'selected' : '' }}>Q to Q</option>
                        <option value="yoy" {{ ($jenis ?? '') == 'yoy' ? 'selected' : '' }}>Y on Y</option>
                        <option value="ctoc" {{ ($jenis ?? '') == 'ctoc' ? 'selected' : '' }}>C to C</option>
                        <option value="indeks_implisit" {{ ($jenis ?? '') == 'indeks_implisit' ? 'selected' : '' }}>Indeks Implisit</option>
                        <option value="laju_implisit" {{ ($jenis ?? '') == 'laju_implisit' ? 'selected' : '' }}>laju Implisit</option>
                    </select>
                </div>

                <div class="space-y-1.5">
    <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pendekatan</label>

    <select name="pendekatan"
class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-slate-400/20 outline-none transition-all">

<option value="lapangan_usaha" {{ $pendekatan === 'lapangan_usaha' ? 'selected' : '' }}>
🏗️ Lapangan Usaha
</option>

<option value="pengeluaran" {{ $pendekatan === 'pengeluaran' ? 'selected' : '' }}>
🛍️ Pengeluaran
</option>

<option value="sektor_lapus" {{ $pendekatan === 'sektor_lapus' ? 'selected' : '' }}>
📊 Sektor (Lapangan Usaha)
</option>

<option value="sektor_pengeluaran" {{ $pendekatan === 'sektor_pengeluaran' ? 'selected' : '' }}>
📊 Sektor (Pengeluaran)
</option>

</select>
</div>

            </div>

            {{-- Tombol tetap konsisten --}}
            <button type="submit" class="w-full lg:w-auto px-8 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2 h-[46px] shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Tampilkan
            </button>
        </form>
    </div>
        {{-- ================= TABLE SECTION ================= --}}
        <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="custom-table-container overflow-auto max-h-[650px] relative">
                <table class="w-full border-separate border-spacing-0 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            {{-- 
                                KATEGORI / KOMPONEN 
                                Kita paksa tinggi container-nya agar pas dengan dua baris header
                            --}}
                            <th rowspan="2" class="sticky-col top-0 left-0 z-50 bg-slate-100 border-b border-r border-slate-200 p-0 text-left font-bold text-slate-700 min-w-[320px]">
                                <div class="flex items-center h-[97px] px-4"> {{-- Angka 97px ini adalah kunci penyama tinggi --}}
                                    KATEGORI / KOMPONEN
                                </div>
                            </th>

                            @php $groupTahun = collect($periodeTampil)->groupBy('tahun'); @endphp
                            @foreach($groupTahun as $tahunGroup => $items)
                                <th colspan="{{ count($items) }}" class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-3 py-3 text-center font-black text-slate-800 text-xs tracking-wider">
                                    {{ $tahunGroup }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($periodeTampil as $p)
                                {{-- Offset 48px adalah tinggi baris Tahun di atasnya --}}
                                <th class="sticky top-[48px] z-30 bg-slate-50 border-b border-r border-slate-200 px-3 py-2.5 text-center font-bold text-slate-500 text-[11px] min-w-[110px]">
                                    {{ $p['triwulan'] === 'total' ? 'TOTAL' : 'TW '.$p['triwulan'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($rows as $row)
                            @php
$isLevel1 = $row['level'] == 1;
$isPdrb = str_contains(strtolower($row['kategori']), 'produk domestik regional bruto');
@endphp
                            <tr class="hover:bg-slate-50 transition-colors {{ $isLevel1 ? 'bg-slate-50/50 font-bold' : 'bg-white' }}">
                                <td class="sticky-col left-0 z-20 px-4 py-3 border-r border-b border-slate-200 {{ $isLevel1 ? 'bg-slate-50/50' : 'bg-white' }}">
                                    <div class="flex items-center gap-2">
                                        {{-- Kode Warna Abu-abu Semua --}}
                                        <span class="text-[10px] font-bold text-slate-400 w-10 shrink-0 tracking-tighter">
                                            {{ $row['kode'] ?? '' }}
                                        </span>
                                        {{-- Teks lebih ke kiri (padding dikurangi) --}}
                                        <span class="flex-1 {{ $isLevel1 ? 'text-slate-900 uppercase' : 'text-slate-600 pl-1' }}">
                                            {{ $row['kategori'] }}
                                        </span>
                                    </div>
                                </td>

                                @foreach($periodeTampil as $p)
                                    @php
                                        $key  = $p['key'];
                                        $cell = $row[$key] ?? null;
                                        $nilai  = is_array($cell) ? ($cell['nilai'] ?? 0) : 0;
    $persen = is_array($cell) ? ($cell['persen'] ?? 0) : 0;
    $flag   = is_array($cell) ? ($cell['flag'] ?? 'OK') : 'OK';

    $bgClass = '';
    $textClass = 'text-slate-600';
    $title = '';

    // ================= DEFAULT =================
    $bgClass = '';
    $textClass = 'text-slate-600';
    $title = '';

    // ================= WARNING (REVISI BIASA) =================
    if ($flag === 'ADA SELISIH') {
        $bgClass = 'bg-amber-50';
        $textClass = 'text-amber-700 font-semibold';
    }

    // ================= EXTREME =================
    elseif ($flag === 'EXTREME') {
        $bgClass = 'bg-rose-50';
        $textClass = 'text-rose-600 font-bold';
    }

    // ================= BALIK ARAH =================
    elseif ($flag === 'BALIK ARAH') {
        $bgClass = 'bg-indigo-50';
        $textClass = 'text-indigo-600 font-bold';
    }

    // ================= EXTREME + BALIK =================
    elseif ($flag === 'EXTREME & BALIK ARAH') {
        $bgClass = 'bg-red-100';
        $textClass = 'text-red-700 font-black';
    }
    // ================= KHUSUS PDRB =================
if ($isPdrb && abs($nilai) > 0) {
    $bgClass = 'bg-red-200';
    $textClass = 'text-red-800 font-bold';
}
                                    @endphp

                                    <td class="px-3 py-3 text-right border-r border-b border-slate-200 tabular-nums {{ $bgClass }} {{ $textClass }}"
        @if(!$isPdrb && $flag !== 'OK')
            data-bs-toggle="popover"
            data-bs-html="true"
            data-bs-trigger="hover focus"
            data-bs-content="
                <div class='flex flex-col gap-2'>
                    <div class='flex items-center pb-2 border-b border-slate-100'>
                        <span class='status-indicator 
                            @if($flag == 'EXTREME & BALIK ARAH') bg-red-600
                            @elseif($flag == 'EXTREME') bg-rose-500
                            @elseif($flag == 'BALIK ARAH') bg-indigo-500
                            @endif'></span>
                        <span class='font-extrabold text-slate-800 uppercase text-[10px] tracking-wider'>Detail Analisis</span>
                    </div>
                    <div class='space-y-1 mt-1'>
                        <div class='flex justify-between gap-4 text-xs'>
                            <span class='text-slate-500'>Status:</span>
                            <span class='font-bold 
                                @if($flag == 'EXTREME & BALIK ARAH') text-red-700
                                @elseif($flag == 'EXTREME') text-rose-600
                                @elseif($flag == 'BALIK ARAH') text-indigo-600
                                @else text-slate-600
                                @endif'>{{ $flag }}</span>
                        </div>
                        <div class='flex justify-between gap-4 text-xs'>
                            <span class='text-slate-500'>Nilai Selisih:</span>
                            <span class='font-semibold text-slate-800'>{{ number_format($nilai, 2, ',', '.') }}</span>
                        </div>
                        <div class='flex justify-between gap-4 text-xs'>
                            <span class='text-slate-500'>Persentase:</span>
                            <span class='font-semibold {{ abs($persen) > 5 ? 'text-rose-600' : 'text-slate-800' }}'>{{ number_format($persen, 2) }}%</span>
                        </div>
                    </div>
                </div>
            "
        @endif>
        {{ number_format($nilai, 2, ',', '.') }}
    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100" class="text-center py-20 text-slate-400 font-medium bg-white">
                                    <div class="flex flex-col items-center gap-3">
                                        <i class="fa fa-folder-open text-3xl opacity-20"></i>
                                        <span>Data tidak tersedia untuk periode ini</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-slate-50 p-4 border-t border-slate-200 flex flex-wrap gap-6 items-center">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Keterangan:</span>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded bg-amber-50 border border-amber-200"></div>
                    <span class="text-[11px] text-slate-600 font-bold">Ada Revisi</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded bg-rose-50 border border-rose-200"></div>
                    <span class="text-[11px] text-slate-600 font-bold">Revisi Ekstrim (+-4,99% / +-0.02%)</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl, {
                container: 'body',
                placement: 'top',
                offset: [0, 10], // Memberi jarak antara cell dan popup
                delay: { "show": 100, "hide": 100 }
            })
        })
    })
    </script>
    @endsection
