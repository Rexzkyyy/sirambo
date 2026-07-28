@extends('layouts.main')

@section('title', 'Diskrepansi PDRB')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .custom-table-container::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .custom-table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>

    <div class="p-4 md:p-8 space-y-6 w-full max-w-none mx-auto">

        {{-- ================= HEADER SECTION ================= --}}
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 md:p-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="space-y-1 text-center md:text-left">
                    <div
                        class="inline-flex items-center gap-2 px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            {{ ($resume ?? null) === 'p1' ? 'Resume P1' : 'Resume P0' }}
                        </span>
                    </div>

                    <h1 class="text-xl md:text-2xl font-black text-slate-900 leading-tight">
                        Diskrepansi <span class="text-blue-600">PDRB</span>
                    </h1>

                    <p class="text-slate-500 text-xs md:text-sm font-medium">
                        Analisis selisih data Provinsi & Kab/Kota:
                        <span class="text-slate-900 font-semibold">
                            {{ $pendekatan === 'lapangan_usaha' ? 'Lapangan Usaha' : 'Pengeluaran' }}
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('rekonsiliasi.export', 'resume') }}?{{ http_build_query(request()->all()) }}"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2 active:scale-95 shadow-sm">
                        <i class="fa fa-file-excel"></i>
                        Export Excel
                    </a>

                    @if(($resume ?? null) === 'p1' && auth()->user()->wilayah?->tipe === 'provinsi')
                        <button onclick="rilisData()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2">
                            <i class="fa fa-paper-plane"></i>
                            Rilis P1
                        </button>
                    @endif

                    @php $resume = request('resume') ?? 'p0'; @endphp
                    @if($resume === 'p0' && auth()->user()->wilayah->tipe === 'provinsi')
                        <button id="btnReset"
                            class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2">
                            🔄 Reset
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================= FILTER PANEL ================= --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
            <form method="GET" id="filterForm" class="flex flex-col lg:flex-row items-end gap-4">
                @if($resume) <input type="hidden" name="resume" value="{{ $resume }}"> @endif

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Tahun</label>
                        <select name="tahun"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold">
                            @foreach($tahunList as $t)
                                <option value="{{ $t->id_tahun }}" {{ $tahun == $t->id_tahun ? 'selected' : '' }}>
                                    🗓️ Tahun {{ $t->tahun }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Triwulan</label>
                        <select name="triwulan"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold">
                            <option value="all" {{ $triwulan === 'all' ? 'selected' : '' }}>📊 Total</option>
                            @foreach($periodeList as $p)
                                <option value="{{ $p->id_periode }}" {{ $triwulan == $p->id_periode ? 'selected' : '' }}>
                                    📈 {{ $p->nama_periode }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pendekatan</label>
                        <select name="pendekatan"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold">
                            <option value="lapangan_usaha" {{ $pendekatan === 'lapangan_usaha' ? 'selected' : '' }}>
                                🏗️ Lapangan Usaha
                            </option>
                            <option value="pengeluaran" {{ $pendekatan === 'pengeluaran' ? 'selected' : '' }}>
                                🛍️ Pengeluaran
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Pilih Data</label>
                        <select id="pilihPdrb"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold">
                            <option
                                value="{{ route('rekonsiliasi.index', ['tahun' => $tahun, 'triwulan' => $triwulan, 'tipe_pdrb' => 'berlaku']) }}">
                                Berlaku</option>
                            <option
                                value="{{ route('rekonsiliasi.index', ['tahun' => $tahun, 'triwulan' => $triwulan, 'tipe_pdrb' => 'konstan']) }}">
                                Konstan</option>
                            <option value="{{ route('rekonsiliasi.qtoq', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">Q to Q
                            </option>
                            <option value="{{ route('rekonsiliasi.ytoy', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">Y on Y
                            </option>
                            <option value="{{ route('rekonsiliasi.ctoc', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">C to C
                            </option>
                            <option
                                value="{{ route('rekonsiliasi.indeksImplisit', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">
                                Indeks Implisit</option>
                            <option
                                value="{{ route('rekonsiliasi.lajuImplisit', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">
                                Laju Implisit</option>
                            <option
                                value="{{ route('rekonsiliasi.stukturDalam', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">
                                Struktur Dalam</option>
                            <option
                                value="{{ route('rekonsiliasi.stukturAntar', ['tahun' => $tahun, 'triwulan' => $triwulan]) }}">
                                Struktur Antar</option>
                        </select>
                    </div>
                </div>

                <button type="button" onclick="goPdrb()"
                    class="w-full lg:w-auto px-8 py-2.5 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2 h-[46px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Tampilkan
                </button>
            </form>
        </div>
        {{-- ================= TABLE SECTION ================= --}}
        <div class="bg-white rounded-[1.5rem] border border-slate-200 shadow-sm overflow-hidden">
            <div class="custom-table-container overflow-auto max-h-[650px] relative">
                <table id="main-table" class="w-full border-separate border-spacing-0 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            {{-- Header Kiri (Sticky) --}}
                            <th
                                class="sticky top-0 left-0 z-50 bg-slate-100 border-b border-r border-slate-200 px-6 py-4 text-left font-bold text-slate-700 min-w-[320px]">
                                {{ $pendekatan === 'pengeluaran' ? 'KOMPONEN' : 'KATEGORI' }}
                            </th>
                            <th
                                class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[120px]">
                                CEK</th>
                            <th
                                class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[100px]">
                                SELISIH (%)</th>
                            <th
                                class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-700 min-w-[110px]">
                                SELISIH</th>
                            <th
                                class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[130px]">
                                PROVINSI</th>
                            <th
                                class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-right font-bold text-slate-700 min-w-[130px]">
                                TOTAL KAB/KOTA</th>
                            @foreach($kabkota as $w)
                                <th
                                    class="sticky top-0 z-30 bg-slate-100 border-b border-r border-slate-200 px-4 py-4 text-center font-bold text-slate-600 min-w-[140px]">
                                    <div class="line-clamp-2 uppercase text-[11px] leading-tight">{{ $w->nama_kabupaten }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($rekonsiliasi as $row)
                            @php
                                $absPersen = abs($row['persen']);
                                if ($absPersen < 2) {
                                    $cekSelisih = 'DISK < 2 %';
                                    $warna = 'text-emerald-600';
                                } elseif ($absPersen >= 2 && $absPersen <= 5) {
                                    $cekSelisih = 'DISK 2 - 5 %';
                                    $warna = 'text-orange-600';
                                } else {
                                    $cekSelisih = 'DISK > 5 %';
                                    $warna = 'text-rose-600';
                                }
                            @endphp

                            <tr
                                class="hover:bg-blue-50/50 transition-colors {{ $row['level'] == 1 ? 'bg-slate-50/80 font-bold' : '' }}">
                                {{-- Kolom Kategori (Sticky) --}}
                                <td
                                    class="sticky left-0 z-20 px-6 py-3 border-r border-b border-slate-200 {{ $row['level'] == 1 ? 'bg-slate-50' : 'bg-white' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="text-[10px] font-black text-slate-400 w-8 mt-1">{{ $row['kode'] }}</span>
                                        <span
                                            class="flex-1 {{ $row['level'] == 2 ? 'pl-2 text-slate-700' : ($row['level'] == 3 ? 'pl-4 text-slate-500 italic' : 'text-slate-900') }}">
                                            {{ $row['kategori'] }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Kolom Data dengan Border Kanan --}}
                                <td
                                    class="px-4 py-3 text-center text-[10px] border-r border-b border-slate-200 {{ $warna }} font-bold tracking-wider">
                                    {{ $cekSelisih }}</td>
                                <td
                                    class="px-4 py-3 text-right border-r border-b border-slate-200 font-bold {{ $warna }} tabular-nums">
                                    {{ fmt($row['persen']) }} %</td>
                                <td
                                    class="px-4 py-3 text-right border-r border-b border-slate-200 font-bold {{ $warna }} tabular-nums">
                                    {{ fmt($row['selisih']) }}</td>
                                <td
                                    class="px-4 py-3 text-right border-r border-b border-slate-200 font-semibold text-slate-900 tabular-nums">
                                    {{ fmt($row['provinsi']) }}</td>
                                <td
                                    class="px-4 py-3 text-right border-r border-b border-slate-200 font-semibold text-slate-900 tabular-nums">
                                    {{ fmt($row['total']) }}</td>

                                @foreach($kabkota as $w)
                                    <td class="px-4 py-3 text-right border-r border-b border-slate-200 text-slate-600 tabular-nums">
                                        {{ fmt($row['kabkota'][$w->nama_kabupaten] ?? 0) }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + $kabkota->count() }}" class="text-center py-10 text-slate-400 italic">Data
                                    tidak tersedia</td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Footer Selisih --}}
                    {{-- ================= FOOTER SELISIH ================= --}}
                    @if(count($rekonsiliasi) && $pendekatan === 'pengeluaran')
                        @php
                            // 1. Inisialisasi awal agar variabel selalu ada (mencegah error Undefined Variable)
                            $selProvinsi = 0;
                            $selTotalKab = 0;
                            $selPerKab = [];
                            foreach ($kabkota as $w) {
                                $selPerKab[$w->nama_kabupaten] = 0;
                            }

                            // 2. Cari baris Produk Domestik Regional Bruto (Pengeluaran)
                            $pdrbLuRow = collect($rekonsiliasi)->first(function ($r) {
                                return ($r['level'] == 1) &&
                                    (strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO' ||
                                        strtoupper(trim($r['kategori'])) === 'PDRB');
                            });

                            // 3. Cari baris Produk Domestik Regional Bruto Lapus (untuk pembanding)
                            $pdrbLapusRow = collect($rekonsiliasi)->first(function ($r) {
                                return strtoupper(trim($r['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS';
                            });

                            // 4. Hitung selisih jika kedua data ditemukan
                            if ($pdrbLuRow && $pdrbLapusRow) {
                                $selProvinsi = round($pdrbLuRow['provinsi'] - $pdrbLapusRow['provinsi'], 9);
                                $selTotalKab = round($pdrbLuRow['total'] - $pdrbLapusRow['total'], 9);
                                foreach ($kabkota as $w) {
                                    $a = $pdrbLuRow['kabkota'][$w->nama_kabupaten] ?? 0;
                                    $b = $pdrbLapusRow['kabkota'][$w->nama_kabupaten] ?? 0;
                                    $selPerKab[$w->nama_kabupaten] = round($a - $b, 9);
                                }
                            }
                        @endphp

                        <tfoot class="bg-slate-100 font-black">
                            <tr class="border-t-2 border-slate-300">
                                <td class="sticky left-0 z-40 bg-slate-100 border-r border-slate-200 px-6 py-4 text-slate-800">
                                    SELISIH (PENGELUARAN - LAPUS)</td>
                                <td class="border-r border-slate-200 px-4 py-4 text-center text-slate-400">-</td>
                                <td class="border-r border-slate-200 px-4 py-4 text-right text-slate-400">-</td>
                                <td class="border-r border-slate-200 px-4 py-4 text-right text-slate-400">-</td>
                                <td
                                    class="border-r border-slate-200 px-4 py-4 text-right {{ abs($selProvinsi) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                                    {{ fmt($selProvinsi, 9) }}</td>
                                <td
                                    class="border-r border-slate-200 px-4 py-4 text-right {{ abs($selTotalKab) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
                                    {{ fmt($selTotalKab, 9) }}</td>
                                @foreach ($kabkota as $w)
                                    @php $val = $selPerKab[$w->nama_kabupaten] ?? 0; @endphp
                                    <td
                                        class="border-r border-slate-200 px-4 py-4 text-right {{ abs($val) > 1e-9 ? 'text-rose-600' : 'text-slate-900' }}">
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

            const resumeInput = document.querySelector('input[name="resume"]');
            const resume = resumeInput ? resumeInput.value : null;

            const url = new URL(route, window.location.origin);
            url.searchParams.set('tahun', tahun);
            url.searchParams.set('triwulan', triwulan);
            url.searchParams.set('pendekatan', pendekatan);

            if (resume) {
                url.searchParams.set('resume', resume);
            }

            window.location.href = url.toString();
        }

        function rilisData() {
            if (!confirm('Yakin ingin merilis data P1 ke P0?')) return;

            const tahun = document.querySelector('select[name="tahun"]').value;
            const triwulan = document.querySelector('select[name="triwulan"]').value;
            const pendekatan = document.querySelector('select[name="pendekatan"]').value;

            // 🔥 AMBIL TIPE PDRB DARI URL SAAT INI
            const urlParams = new URLSearchParams(window.location.search);
            const tipePdrb = urlParams.get('tipe_pdrb') ?? 'berlaku';

            const url = new URL("{{ route('rekonsiliasi.release') }}", window.location.origin);
            url.searchParams.set('tahun', tahun);
            url.searchParams.set('triwulan', triwulan);
            url.searchParams.set('pendekatan', pendekatan);
            url.searchParams.set('tipe_pdrb', tipePdrb); // 🔥 FIX UTAMA

            window.location.href = url.toString();
        }

        const btnReset = document.getElementById('btnReset');
        if (btnReset) {
            btnReset.addEventListener('click', function () {

                if (!confirm("Yakin ingin mengembalikan data ke kondisi sebelum rilis?")) return;

                const tahun = document.querySelector('select[name="tahun"]').value;
                const triwulan = document.querySelector('select[name="triwulan"]').value;
                const pendekatan = document.querySelector('select[name="pendekatan"]').value;

                const url = new URL("{{ route('rekonsiliasi.reset') }}", window.location.origin);
                url.searchParams.set('tahun', tahun);
                url.searchParams.set('triwulan', triwulan);
                url.searchParams.set('pendekatan', pendekatan);

                window.location.href = url.toString();
            });
        }
    </script>
@endsection