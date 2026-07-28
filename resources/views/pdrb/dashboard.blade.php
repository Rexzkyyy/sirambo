@extends('layouts.main')

@section('title', 'Dashboard SIRAMBO')

@section('content')
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .custom-scrollbar::-webkit-scrollbar {
            height: 4px;
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .stat-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-ring {
            transition: stroke-dashoffset 0.5s ease;
        }
    </style>

    <div class="p-4 md:p-8 space-y-6 w-full max-w-none mx-auto">

        {{-- ================= HEADER SECTION ================= --}}
        <div class="relative overflow-hidden bg-slate-950 rounded-3xl shadow-2xl p-6 md:p-10 text-white">
            <div class="absolute top-0 right-0 w-80 h-80 bg-orange-500/15 rounded-full blur-[100px] -mr-40 -mt-40"></div>
            <div class="absolute bottom-0 left-0 w-60 h-60 bg-blue-500/10 rounded-full blur-[80px] -ml-30 -mb-30"></div>

            <div class="relative z-10 flex flex-col lg:flex-row justify-between items-center gap-8">
                <div class="space-y-4 text-center lg:text-left">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-orange-500/10 border border-orange-500/20 rounded-full">
                        <span class="relative flex h-2 w-2">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-orange-200">Periode Aktif:
                            {{ $tahunSekarang }}</span>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-2">
                            Dashboard <span
                                class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-200">SIRAMBO</span>
                        </h1>
                        <p class="text-slate-400 text-sm md:text-base font-medium max-w-xl leading-relaxed">
                            Sistem Monitoring & Pelaporan Data PDRB Wilayah <span
                                class="text-white border-b border-orange-500/50">{{ $namaWilayah }}</span>.
                        </p>
                    </div>
                </div>

                {{-- Digital Clock Widget --}}
                <div class="flex items-center gap-6 glass-effect p-5 md:p-6 rounded-2xl shadow-2xl min-w-[240px]">
                    <div class="text-right">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-orange-400 mb-1">
                            {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                        <p id="clock" class="text-3xl md:text-4xl font-black tracking-tighter tabular-nums"></p>
                    </div>
                    <div class="h-12 w-[1px] bg-white/10"></div>
                    <div
                        class="bg-gradient-to-br from-orange-500 to-orange-600 p-3 rounded-xl shadow-lg shadow-orange-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= MODE INFO ================= --}}
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
                <div>
                    <p class="text-sm font-semibold text-blue-800">
                        Mode Tampilan: <span class="uppercase">{{ $currentMode }}</span>
                        @if($currentMode == 'triwulanan')
                            @php
                                $periodeName = '';
                                foreach ($periode as $p) {
                                    if ($p->id_periode == $idPeriode) {
                                        $periodeName = $p->nama_periode;
                                        break;
                                    }
                                }
                            @endphp
                            - {{ $periodeName }}
                        @endif
                    </p>
                    <p class="text-xs text-blue-600">
                        @if($currentMode == 'triwulanan')
                            Menampilkan data fenomena untuk 1 triwulan (2 jenis data: QtoQ dan YonY)
                        @else
                            Menampilkan data fenomena untuk 2 jenis data per tahun (Pertumbuhan dan Laju Implisit)
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- ================= QUICK STATS ================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $stats = [
                    ['label' => 'Lapangan Usaha', 'title' => 'Kategori', 'value' => $totalKategori, 'color' => 'orange'],
                    ['label' => 'Lapangan Usaha', 'title' => 'Sub Kategori', 'value' => $totalSubKategori, 'color' => 'orange'],
                    ['label' => 'Pengeluaran', 'title' => 'Komponen', 'value' => $totalKomponen, 'color' => 'blue'],
                    ['label' => 'Pengeluaran', 'title' => 'Sub Komponen', 'value' => $totalSubKomponen, 'color' => 'blue'],
                ];
            @endphp

            @foreach($stats as $s)
                <div class="stat-card bg-white p-6 rounded-2xl border border-slate-200/60 flex items-center justify-between">
                    <div>
                        <span
                            class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1">{{ $s['label'] }}</span>
                        <h3 class="text-xs font-bold text-slate-600 mb-2 uppercase">{{ $s['title'] }}</h3>
                        <p class="text-3xl font-black text-slate-900 tracking-tight">{{ $s['value'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center {{ $s['color'] == 'orange' ? 'bg-orange-50 text-orange-500' : 'bg-blue-50 text-blue-600' }}">
                        @if(Str::contains($s['title'], 'Sub'))
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ================= FILTER PANEL ================= --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm">
            <form method="GET" class="flex flex-col md:flex-row items-end gap-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Mode</label>
                        <select name="mode"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                            onchange="this.form.submit()">
                            <option value="tahunan" {{ $currentMode == 'tahunan' ? 'selected' : '' }}>📊 Tahunan</option>
                            <option value="triwulanan" {{ $currentMode == 'triwulanan' ? 'selected' : '' }}>📈 Triwulanan
                            </option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Periode Tahun</label>
                        <select name="id_tahun"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                            onchange="this.form.submit()">
                            @foreach($tahun as $t)
                                <option value="{{ $t->id_tahun }}" {{ $idTahun == $t->id_tahun ? 'selected' : '' }}>🗓️ Tahun
                                    {{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-extrabold text-slate-500 uppercase ml-1">Triwulan</label>
                        <select name="id_periode"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                            onchange="this.form.submit()">
                            @foreach($periode as $p)
                                <option value="{{ $p->id_periode }}" {{ $idPeriode == $p->id_periode ? 'selected' : '' }}>📈
                                    {{ $p->nama_periode }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        {{-- ================= RINGKASAN REKONSILIASI ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- HARGA BERLAKU --}}
            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <h3 class="text-xs font-black uppercase tracking-widest text-orange-600 mb-4">
                    Rekonsiliasi PDRB — Harga Berlaku
                </h3>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-400 font-semibold">Lapangan Usaha</p>
                        <p class="text-xl font-black text-slate-900">
                            Rp {{ number_format($totalLapusBerlaku, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-400 font-semibold">Pengeluaran</p>
                        <p class="text-xl font-black text-slate-900">
                            Rp {{ number_format($totalPengBerlaku, 0, ',', '.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-400 font-semibold">Diskrepansi</p>
                        <p
                            class="text-xl font-black {{ abs($diskrepansiBerlaku) <= 1 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rp {{ number_format($diskrepansiBerlaku, 0, ',', '.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-400 font-semibold">Persentase</p>
                        <p class="text-xl font-black {{ $persenDiskBerlaku <= 1 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $persenDiskBerlaku }}%
                        </p>
                    </div>
                </div>
            </div>

            {{-- HARGA KONSTAN --}}
            <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                <h3 class="text-xs font-black uppercase tracking-widest text-blue-600 mb-4">
                    Rekonsiliasi PDRB — Harga Konstan
                </h3>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-400 font-semibold">Lapangan Usaha</p>
                        <p class="text-xl font-black text-slate-900">
                            Rp {{ number_format($totalLapusKonstan, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-slate-400 font-semibold">Pengeluaran</p>
                        <p class="text-xl font-black text-slate-900">
                            Rp {{ number_format($totalPengKonstan, 0, ',', '.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-400 font-semibold">Diskrepansi</p>
                        <p
                            class="text-xl font-black {{ abs($diskrepansiKonstan) <= 1 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rp {{ number_format($diskrepansiKonstan, 0, ',', '.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-400 font-semibold">Persentase</p>
                        <p class="text-xl font-black {{ $persenDiskKonstan <= 1 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $persenDiskKonstan }}%
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ================= CHARTS GRID ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @php
                $charts = [
                    ['id' => 'chartLapusBerlaku', 'title' => 'Lapangan Usaha (Harga Berlaku)', 'color' => '#f97316'],
                    ['id' => 'chartLapusKonstan', 'title' => 'Lapangan Usaha (Harga Konstan)', 'color' => '#64748b'],
                    ['id' => 'chartPengBerlaku', 'title' => 'Pengeluaran (Harga Berlaku)', 'color' => '#10b981'],
                    ['id' => 'chartPengKonstan', 'title' => 'Pengeluaran (Harga Konstan)', 'color' => '#3b82f6'],
                ];
            @endphp

            @foreach($charts as $c)
                <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-1.5 h-5 rounded-full" style="background-color: {{ $c['color'] }}"></div>
                        <h2 class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">{{ $c['title'] }}</h2>
                    </div>
                    <div class="h-[280px] w-full">
                        <canvas id="{{ $c['id'] }}"></canvas>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ================= MONITORING DATA PDRB ================= --}}
        <div class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-slate-200/60 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span> Monitoring Kelengkapan Data
                        PDRB
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Status entri data PDRB per wilayah</p>
                </div>

                <div class="flex items-center gap-4 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-orange-500 rounded-full"></span> BERLAKU
                    </div>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-blue-500 rounded-full"></span> KONSTAN
                    </div>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-slate-900 rounded-full"></span> BELUM ISI
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                <div class="space-y-4">
                    <h3 class="text-[15px] font-black uppercase text-orange-600 border-b border-orange-100 pb-2">Lapangan
                        Usaha</h3>
                    <div class="h-[400px]">
                        <canvas id="monitoringChart"></canvas>
                    </div>
                </div>
                <div class="space-y-4">
                    <h3 class="text-[15px] font-black uppercase text-blue-600 border-b border-blue-100 pb-2">Pengeluaran
                    </h3>
                    <div class="h-[400px]">
                        <canvas id="monitoringChartPengeluaran"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= MONITORING FENOMENA LAPANGAN USAHA ================= --}}
        <div class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-slate-200/60 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></span> Monitoring Kelengkapan
                        Fenomena
                    </h2>
                    <p class="text-xs text-slate-400 mt-1 font-medium">Status entri data fenomena (nilai & rating) per
                        wilayah</p>
                </div>

                <div class="flex items-center gap-4 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-emerald-500 rounded-full"></span> SUDAH DIISI
                    </div>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-slate-300 rounded-full"></span> BELUM DIISI
                    </div>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-600">
                        <span class="w-3 h-3 bg-purple-500 rounded-full"></span> PERSENTASE
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                {{-- Stacked Bar Chart Fenomena --}}
                <div class="space-y-4">
                    <h3 class="text-[15px] font-black uppercase text-purple-600 border-b border-purple-100 pb-2">Kelengkapan
                        Data Fenomena (Lapangan Usaha)</h3>
                    <div class="h-[400px]">
                        <canvas id="fenomenaChart"></canvas>
                    </div>
                </div>

                {{-- Tabel Persentase Kelengkapan --}}
                {{-- Tabel Persentase Kelengkapan --}}
                <div class="space-y-4">
                    <h3 class="text-[15px] font-black uppercase text-purple-600 border-b border-purple-100 pb-2">Persentase
                        Kelengkapan per Wilayah</h3>
                    <div class="overflow-x-auto max-h-[400px] custom-scrollbar">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-white">
                                <tr class="border-b border-slate-200">
                                    <th class="text-left py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Wilayah
                                    </th>
                                    <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Terisi
                                    </th>
                                    <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Total
                                    </th>
                                    <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">
                                        Persentase</th>
                                    <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fenomenaLabels as $index => $label)
                                    @php
                                        $terisi = $fenomenaData[$index] ?? 0;
                                        $total = $fenomenaTotal[$index] ?? 0;
                                        $persen = $total > 0 ? round(($terisi / $total) * 100, 1) : 0;

                                        // PERBAIKAN: Status berdasarkan persentase
                                        if ($persen == 0) {
                                            $status = 'Belum';
                                            $statusColor = 'text-red-600 bg-red-50';
                                        } elseif ($persen >= 100) {
                                            $status = 'Lengkap';
                                            $statusColor = 'text-emerald-600 bg-emerald-50';
                                        } else {
                                            $status = 'Kurang';
                                            $statusColor = 'text-amber-600 bg-amber-50';
                                        }
                                    @endphp
                                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                        <td class="py-3 px-2 font-semibold text-slate-700">{{ $label }}</td>
                                        <td class="text-center py-3 px-2 font-mono font-bold text-slate-800">
                                            {{ number_format($terisi) }}</td>
                                        <td class="text-center py-3 px-2 font-mono font-bold text-slate-800">
                                            {{ number_format($total) }}</td>
                                        <td class="text-center py-3 px-2">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                                    <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ $persen }}%">
                                                    </div>
                                                </div>
                                                <span class="text-xs font-bold text-purple-600">{{ $persen }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center py-3 px-2">
                                            <span
                                                class="text-[9px] font-bold px-2 py-1 rounded-full {{ $statusColor }}">{{ $status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Progress Ring Summary --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8 pt-6 border-t border-slate-100">
                @php
                    $totalDataFenomena = array_sum($fenomenaTotal);
                    $totalTerisiFenomena = array_sum($fenomenaData);
                    $persenKeseluruhan = $totalDataFenomena > 0 ? round(($totalTerisiFenomena / $totalDataFenomena) * 100, 1) : 0;
                    $targetTercapai = $persenKeseluruhan >= 80;
                    $jumlahWilayah = count($fenomenaLabels);
                    $totalPerWilayah = $fenomenaTotal[0] ?? 0;
                @endphp

                <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-4 text-center">
                    <p class="text-[10px] font-bold text-purple-500 uppercase tracking-wider">Total Data Fenomena</p>
                    <p class="text-2xl font-black text-purple-700 mt-1">{{ number_format($totalDataFenomena) }}</p>
                    <p class="text-xs text-slate-500">{{ $jumlahWilayah }} wilayah × {{ number_format($totalPerWilayah) }}
                        data</p>
                    <p class="text-xs text-purple-500 mt-1">Mode: {{ ucfirst($currentMode) }}
                        @if($currentMode == 'triwulanan')
                            (Triwulan {{ $idPeriode }})
                        @endif
                    </p>
                </div>

                <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl p-4 text-center">
                    <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Data Terisi</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($totalTerisiFenomena) }}</p>
                    <p class="text-xs text-slate-500">sudah memiliki nilai & rating</p>
                </div>

                <div class="bg-gradient-to-br from-slate-100 to-white rounded-xl p-4 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kelengkapan Keseluruhan</p>
                    <p class="text-2xl font-black {{ $targetTercapai ? 'text-emerald-600' : 'text-amber-600' }} mt-1">
                        {{ $persenKeseluruhan }}%</p>
                    <p class="text-xs text-slate-500">{{ $targetTercapai ? 'Target tercapai (≥80%)' : 'Target 80%' }}</p>
                </div>
            </div>
        </div>

        {{-- ================= MONITORING FENOMENA PENGELUARAN ================= --}}
        @if(isset($fenomenaLabelsPeng) && count($fenomenaLabelsPeng) > 0 && ($fenomenaTotalPeng[0] ?? 0) > 0)
            <div class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-slate-200/60 shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse"></span> Monitoring Fenomena -
                            Pengeluaran
                        </h2>
                        <p class="text-xs text-slate-400 mt-1 font-medium">Status entri data fenomena pengeluaran per wilayah
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <h3 class="text-[15px] font-black uppercase text-cyan-600 border-b border-cyan-100 pb-2">Kelengkapan
                            Data Fenomena (Pengeluaran)</h3>
                        <div class="h-[400px]">
                            <canvas id="fenomenaChartPengeluaran"></canvas>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-[15px] font-black uppercase text-cyan-600 border-b border-cyan-100 pb-2">Persentase
                            Kelengkapan per Wilayah</h3>
                        <div class="overflow-x-auto max-h-[400px] custom-scrollbar">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-white">
                                    <tr class="border-b border-slate-200">
                                        <th class="text-left py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Wilayah
                                        </th>
                                        <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Terisi
                                        </th>
                                        <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Total
                                        </th>
                                        <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">
                                            Persentase</th>
                                        <th class="text-center py-3 px-2 text-[10px] font-black text-slate-500 uppercase">Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fenomenaLabelsPeng as $index => $label)
                                        @php
                                            $terisi = $fenomenaDataPeng[$index] ?? 0;
                                            $total = $fenomenaTotalPeng[$index] ?? 0;
                                            $persen = $total > 0 ? round(($terisi / $total) * 100, 1) : 0;

                                            // PERBAIKAN: Status berdasarkan persentase
                                            if ($persen == 0) {
                                                $status = 'Belum';
                                                $statusColor = 'text-red-600 bg-red-50';
                                            } elseif ($persen >= 100) {
                                                $status = 'Lengkap';
                                                $statusColor = 'text-emerald-600 bg-emerald-50';
                                            } else {
                                                $status = 'Kurang';
                                                $statusColor = 'text-amber-600 bg-amber-50';
                                            }
                                        @endphp
                                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                            <td class="py-3 px-2 font-semibold text-slate-700">{{ $label }}</td>
                                            <td class="text-center py-3 px-2 font-mono font-bold text-slate-800">
                                                {{ number_format($terisi) }}</td>
                                            <td class="text-center py-3 px-2 font-mono font-bold text-slate-800">
                                                {{ number_format($total) }}</td>
                                            <td class="text-center py-3 px-2">
                                                <div class="flex items-center justify-center gap-2">
                                                    <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                                        <div class="bg-cyan-500 h-1.5 rounded-full" style="width: {{ $persen }}%">
                                                        </div>
                                                    </div>
                                                    <span class="text-xs font-bold text-cyan-600">{{ $persen }}%</span>
                                                </div>
                                            </td>
                                            <td class="text-center py-3 px-2">
                                                <span
                                                    class="text-[9px] font-bold px-2 py-1 rounded-full {{ $statusColor }}">{{ $status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Progress Ring Summary Pengeluaran --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8 pt-6 border-t border-slate-100">
                    @php
                        $totalDataFenomenaPeng = array_sum($fenomenaTotalPeng);
                        $totalTerisiFenomenaPeng = array_sum($fenomenaDataPeng);
                        $persenKeseluruhanPeng = $totalDataFenomenaPeng > 0 ? round(($totalTerisiFenomenaPeng / $totalDataFenomenaPeng) * 100, 1) : 0;
                        $targetTercapaiPeng = $persenKeseluruhanPeng >= 80;
                        $jumlahWilayahPeng = count($fenomenaLabelsPeng);
                        $totalPerWilayahPeng = $fenomenaTotalPeng[0] ?? 0;
                    @endphp

                    <div class="bg-gradient-to-br from-cyan-50 to-white rounded-xl p-4 text-center">
                        <p class="text-[10px] font-bold text-cyan-500 uppercase tracking-wider">Total Data Fenomena</p>
                        <p class="text-2xl font-black text-cyan-700 mt-1">{{ number_format($totalDataFenomenaPeng) }}</p>
                        <p class="text-xs text-slate-500">{{ $jumlahWilayahPeng }} wilayah ×
                            {{ number_format($totalPerWilayahPeng) }} data</p>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl p-4 text-center">
                        <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Data Terisi</p>
                        <p class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($totalTerisiFenomenaPeng) }}</p>
                        <p class="text-xs text-slate-500">sudah memiliki nilai & rating</p>
                    </div>

                    <div class="bg-gradient-to-br from-slate-100 to-white rounded-xl p-4 text-center">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kelengkapan Keseluruhan</p>
                        <p class="text-2xl font-black {{ $targetTercapaiPeng ? 'text-emerald-600' : 'text-amber-600' }} mt-1">
                            {{ $persenKeseluruhanPeng }}%</p>
                        <p class="text-xs text-slate-500">{{ $targetTercapaiPeng ? 'Target tercapai (≥80%)' : 'Target 80%' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <script>
        // Clock Function
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        }
        setInterval(updateClock, 1000); updateClock();

        // Chart Global Defaults
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.font.weight = '600';

        const formatNumber = (v) => new Intl.NumberFormat('id-ID').format(v);

        // Generic Bar Chart Builder
        function initBarChart(id, labels, codes, data, color) {
            return new Chart(document.getElementById(id), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: color,
                        borderRadius: 8,
                        barThickness: 20,
                        hoverBackgroundColor: color + 'CC'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            callbacks: {
                                title: (ctx) =>
                                    (codes[ctx[0].dataIndex] ?? '-') +
                                    ' - ' +
                                    labels[ctx[0].dataIndex],
                                label: (ctx) =>
                                    ' Rp ' + formatNumber(ctx.raw)
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: '#f1f5f9', drawBorder: false },
                            ticks: { font: { size: 10 }, callback: v => formatNumber(v) }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 10, weight: '800' },
                                callback: (v, i) => codes[i] ?? '-'
                            }
                        }
                    }
                }
            });
        }

        // Initialize Trends
        initBarChart(
            'chartLapusBerlaku',
            @json($labelsLapus),
            @json($kodeLapus),
            @json($dataLapusBerlaku),
            '#f97316'
        );

        initBarChart(
            'chartLapusKonstan',
            @json($labelsLapus),
            @json($kodeLapus),
            @json($dataLapusKonstan),
            '#64748b'
        );

        initBarChart(
            'chartPengBerlaku',
            @json($labelsPengeluaran),
            @json($kodePengeluaran),
            @json($dataPengBerlaku),
            '#10b981'
        );

        initBarChart(
            'chartPengKonstan',
            @json($labelsPengeluaran),
            @json($kodePengeluaran),
            @json($dataPengKonstan),
            '#3b82f6'
        );

        // Monitoring Horizontal Charts for PDRB
        const monitoringOptions = {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10, weight: '700' }, padding: 20 } }
            },
            scales: {
                x: { display: false, max: 1 },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: '700' }, color: '#475569' }
                }
            }
        };

        new Chart(document.getElementById('monitoringChart'), {
            type: 'bar',
            data: {
                labels: @json($labelsMonitoring),
                datasets: [
                    { label: 'Berlaku', data: @json($dataMonitoringBerlaku), backgroundColor: @json($warnaMonitoringBerlaku), borderRadius: 4, barThickness: 12 },
                    { label: 'Konstan', data: @json($dataMonitoringKonstan), backgroundColor: @json($warnaMonitoringKonstan), borderRadius: 4, barThickness: 12 }
                ]
            },
            options: monitoringOptions
        });

        new Chart(document.getElementById('monitoringChartPengeluaran'), {
            type: 'bar',
            data: {
                labels: @json($labelsMonitoringPengeluaran),
                datasets: [
                    { label: 'Berlaku', data: @json($dataMonitoringPengBerlaku), backgroundColor: @json($warnaMonitoringPengBerlaku), borderRadius: 4, barThickness: 12 },
                    { label: 'Konstan', data: @json($dataMonitoringPengKonstan), backgroundColor: @json($warnaMonitoringPengKonstan), borderRadius: 4, barThickness: 12 }
                ]
            },
            options: monitoringOptions
        });

        // Fenomena Chart (Stacked Bar) - Lapangan Usaha
        @if(isset($fenomenaLabels) && count($fenomenaLabels) > 0)
            {
                @php
                    $fenomenaBelumDiisi = [];
                    foreach ($fenomenaTotal as $index => $total) {
                        $fenomenaBelumDiisi[] = $total - ($fenomenaData[$index] ?? 0);
                    }
                @endphp

                new Chart(document.getElementById('fenomenaChart'), {
                    type: 'bar',
                    data: {
                        labels: @json($fenomenaLabels),
                        datasets: [
                            {
                                label: 'Sudah Diisi',
                                data: @json($fenomenaData),
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'Belum Diisi',
                                data: @json($fenomenaBelumDiisi),
                                backgroundColor: '#cbd5e1',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, weight: '700' },
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                callbacks: {
                                    label: function (context) {
                                        const label = context.dataset.label || '';
                                        const value = context.raw;
                                        const total = @json($fenomenaTotal)[context.dataIndex];
                                        const persen = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} data (${persen}%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { size: 9, weight: '700' },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            },
                            y: {
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    stepSize: 50,
                                    callback: function (value) {
                                        return value + ' data';
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah Data',
                                    font: { size: 10, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }
        @endif

        // Fenomena Chart for Pengeluaran
        @if(isset($fenomenaLabelsPeng) && count($fenomenaLabelsPeng) > 0 && ($fenomenaTotalPeng[0] ?? 0) > 0)
            {
                @php
                    $fenomenaBelumDiisiPeng = [];
                    foreach ($fenomenaTotalPeng as $index => $total) {
                        $fenomenaBelumDiisiPeng[] = $total - ($fenomenaDataPeng[$index] ?? 0);
                    }
                @endphp

                new Chart(document.getElementById('fenomenaChartPengeluaran'), {
                    type: 'bar',
                    data: {
                        labels: @json($fenomenaLabelsPeng),
                        datasets: [
                            {
                                label: 'Sudah Diisi',
                                data: @json($fenomenaDataPeng),
                                backgroundColor: '#10b981',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            },
                            {
                                label: 'Belum Diisi',
                                data: @json($fenomenaBelumDiisiPeng),
                                backgroundColor: '#cbd5e1',
                                borderRadius: 4,
                                barPercentage: 0.6,
                                categoryPercentage: 0.8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: { size: 10, weight: '700' },
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                padding: 12,
                                callbacks: {
                                    label: function (context) {
                                        const label = context.dataset.label || '';
                                        const value = context.raw;
                                        const total = @json($fenomenaTotalPeng)[context.dataIndex];
                                        const persen = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} data (${persen}%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    font: { size: 9, weight: '700' },
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            },
                            y: {
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    stepSize: 50,
                                    callback: function (value) {
                                        return value + ' data';
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Jumlah Data',
                                    font: { size: 10, weight: 'bold' }
                                }
                            }
                        }
                    }
                });
            }
        @endif

        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
@endsection