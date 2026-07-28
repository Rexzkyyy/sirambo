@extends('layouts.main')

@section('title', 'Ranking Fenomena PDRB')

@section('content')

<div class="container-fluid px-4 py-5 bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Ranking Fenomena PDRB</h1>
            <p class="text-gray-500 text-sm mt-0.5">
                Perankingan wilayah berdasarkan total rating fenomena ekonomi 
                @if($mode == 'triwulanan')
                    @if($triwulan && $triwulan != 'all')
                        {{ $triwulanList[$triwulan] ?? 'Triwulan ' . $triwulan }}
                    @else
                        Seluruh Triwulan
                    @endif
                    tahun {{ $tahun }}
                @else
                    tahun {{ $tahun }}
                @endif
                ({{ str_replace('_', ' ', $pendekatan) }}) - Mode: {{ ucfirst($mode) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('fenomena.index', ['mode' => $mode, 'pendekatan' => $pendekatan, 'tahun' => $tahun]) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg flex items-center gap-1.5 transition font-medium text-sm">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- FILTER -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="w-full md:w-36">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Mode</label>
                <select name="mode" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2" onchange="this.form.submit()">
                    <option value="tahunan" {{ $mode == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    <option value="triwulanan" {{ $mode == 'triwulanan' ? 'selected' : '' }}>Triwulanan</option>
                </select>
            </div>
            
            <div class="w-full md:w-36">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun</label>
                <select name="tahun" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2" onchange="this.form.submit()">
                    @foreach($tahunList as $th)
                        <option value="{{ $th }}" {{ $th == $tahun ? 'selected' : '' }}>{{ $th }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="w-full md:w-48">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Pendekatan</label>
                <select name="pendekatan" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2" onchange="this.form.submit()">
                    <option value="lapangan_usaha" {{ $pendekatan == 'lapangan_usaha' ? 'selected' : '' }}>Lapangan Usaha</option>
                    <option value="pengeluaran" {{ $pendekatan == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>

            @if($mode == 'triwulanan')
            <div class="w-full md:w-40">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Triwulan</label>
                <select name="triwulan" class="w-full bg-gray-50 border border-gray-300 text-sm rounded-lg p-2" onchange="this.form.submit()">
                    @foreach($triwulanList as $key => $label)
                        <option value="{{ $key }}" {{ ($triwulan ?? 'all') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-1.5 text-sm">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
            </button>
        </form>
    </div>

    <!-- TABEL RANKING -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <tr>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider border-r border-blue-400 w-14">RANK</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider border-r border-blue-400">KABUPATEN/KOTA</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider border-r border-blue-400 w-28">TOTAL RATING</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider border-r border-blue-400 w-24">MAKSIMAL</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider border-r border-blue-400 w-28">PERSENTASE</th>
                        @if($mode == 'triwulanan')
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider border-r border-blue-400 w-64">RATING PER TRIWULAN</th>
                        @endif
                        <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wider w-28">STATUS</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($rankingData as $data)
                        @php
                            $percentage = $data['percentage'];
                            if ($percentage >= 80) {
                                $status = 'Sangat Baik';
                                $statusColor = 'bg-green-100 text-green-700';
                                $icon = 'award';
                            } elseif ($percentage >= 60) {
                                $status = 'Baik';
                                $statusColor = 'bg-blue-100 text-blue-700';
                                $icon = 'thumbs-up';
                            } elseif ($percentage >= 40) {
                                $status = 'Cukup';
                                $statusColor = 'bg-yellow-100 text-yellow-700';
                                $icon = 'minus';
                            } elseif ($percentage >= 20) {
                                $status = 'Kurang';
                                $statusColor = 'bg-orange-100 text-orange-700';
                                $icon = 'thumbs-down';
                            } else {
                                $status = 'Sangat Kurang';
                                $statusColor = 'bg-red-100 text-red-700';
                                $icon = 'alert-circle';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-4 py-2.5 text-center font-bold">
                                @if($data['rank'] == 1)
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-full">
                                        <i data-lucide="crown" class="w-4 h-4 text-yellow-600"></i>
                                    </span>
                                @elseif($data['rank'] == 2)
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full">
                                        <i data-lucide="medal" class="w-4 h-4 text-gray-500"></i>
                                    </span>
                                @elseif($data['rank'] == 3)
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 rounded-full">
                                        <i data-lucide="medal" class="w-4 h-4 text-amber-600"></i>
                                    </span>
                                @else
                                    <span class="text-gray-500 font-semibold text-sm">#{{ $data['rank'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i data-lucide="building-2" class="w-3.5 h-3.5 text-blue-600"></i>
                                    </div>
                                    <span class="font-medium text-gray-800 text-sm">{{ $data['nama_wilayah'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="text-xl font-bold text-blue-600">{{ number_format($data['total_rating']) }}</span>
                                <span class="text-gray-400 text-xs"> poin</span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-500 text-sm">
                                {{ number_format($data['max_rating']) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold {{ $percentage >= 60 ? 'text-green-600' : ($percentage >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ $percentage }}%
                                    </span>
                                </div>
                            </td>
                            @if($mode == 'triwulanan')
                            <td class="px-4 py-2.5">
                                <div class="flex justify-center gap-2 flex-wrap">
                                    @for($q = 1; $q <= 4; $q++)
                                        @php
                                            $quarterRating = $data['ratings_by_quarter'][$q] ?? 0;
                                            $quarterMax = $data['max_rating'] / 4;
                                            $quarterPercent = $quarterMax > 0 ? round(($quarterRating / $quarterMax) * 100) : 0;
                                            
                                            if ($quarterPercent >= 80) $barColor = 'bg-green-500';
                                            elseif ($quarterPercent >= 60) $barColor = 'bg-blue-500';
                                            elseif ($quarterPercent >= 40) $barColor = 'bg-yellow-500';
                                            elseif ($quarterPercent >= 20) $barColor = 'bg-orange-500';
                                            else $barColor = 'bg-red-500';
                                        @endphp
                                        <div class="text-center" style="width: 55px;">
                                            <div class="text-xs font-medium text-gray-500 mb-0.5">TW{{ $q }}</div>
                                            <div class="relative pt-0.5">
                                                <div class="overflow-hidden h-1.5 text-xs flex rounded bg-gray-200">
                                                    <div class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $barColor }}" style="width: {{ $quarterPercent }}%"></div>
                                                </div>
                                            </div>
                                            <div class="text-xs font-bold {{ $quarterRating > 0 ? 'text-blue-600' : 'text-gray-400' }} mt-0.5">
                                                {{ number_format($quarterRating) }}
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </td>
                            @endif
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                    <i data-lucide="{{ $icon }}" class="w-3 h-3"></i>
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mode == 'triwulanan' ? 7 : 6 }}" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="bar-chart-3" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-400 text-sm">Belum ada data rating untuk periode ini</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(count($rankingData) > 0)
            <div class="bg-gray-50 px-4 py-2.5 border-t border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-2">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            <span class="text-xs text-gray-500">Sangat Baik (≥80%)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span class="text-xs text-gray-500">Baik (60-79%)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                            <span class="text-xs text-gray-500">Cukup (40-59%)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span class="text-xs text-gray-500">Kurang (20-39%)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            <span class="text-xs text-gray-500">Sangat Kurang ({"<"}20%)</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- STATISTIK RINGKASAN -->
    @if(count($rankingData) > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="trophy" class="w-4.5 h-4.5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Juara 1</p>
                    <p class="font-semibold text-gray-800 text-sm">{{ $rankingData[0]['nama_wilayah'] ?? '-' }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="bar-chart-2" class="w-4.5 h-4.5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Rata-rata Total Rating</p>
                    <p class="font-semibold text-gray-800 text-sm">{{ number_format(collect($rankingData)->avg('total_rating'), 0) }} poin</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="percent" class="w-4.5 h-4.5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Rata-rata Persentase</p>
                    <p class="font-semibold text-gray-800 text-sm">{{ number_format(collect($rankingData)->avg('percentage'), 1) }}%</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="users" class="w-4.5 h-4.5 text-orange-600"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Total Wilayah</p>
                    <p class="font-semibold text-gray-800 text-sm">{{ count($rankingData) }} Kab/Kota</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    
    .transition-all {
        transition: all 0.3s ease;
    }
    
    table {
        border-collapse: collapse;
        width: 100%;
    }
    
    th, td {
        border: 1px solid #e5e7eb;
    }
    
    .hover\:bg-gray-50:hover {
        background-color: #f9fafb;
    }
    
    /* Animasi untuk progress bar */
    @keyframes growWidth {
        from { width: 0%; }
        to { width: var(--target-width); }
    }
    
    .bg-blue-600.h-2 {
        animation: growWidth 0.8s ease-out;
    }
    
    .w-4\.5 {
        width: 1.125rem;
    }
    .h-4\.5 {
        height: 1.125rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Animasi progress bar
        document.querySelectorAll('.bg-blue-600.h-2').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    });
</script>

@endsection