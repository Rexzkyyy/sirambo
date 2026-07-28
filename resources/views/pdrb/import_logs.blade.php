@extends('layouts.main')

@section('title', 'Manajemen Input PDRB (Log History)')

@section('styles')
<style>
    /* Pagination Fix for Hosting */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
        padding: 1.5rem;
        background: rgba(249, 250, 251, 0.5);
        border-top: 1px solid #f3f4f6;
    }
    @media (min-width: 768px) {
        .pagination-container {
            flex-direction: row;
            justify-content: space-between;
        }
    }
    .page-link-custom {
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        color: #4b5563;
        font-size: 0.75rem;
        font-weight: 900;
        transition: all 0.2s;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .page-link-custom:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #2563eb;
    }
    .page-link-active {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: white !important;
        box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
    }
    .page-link-disabled {
        background: #f9fafb;
        border-color: #f3f4f6;
        color: #d1d5db;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
<div class="w-full px-2 md:px-6 pb-12">
    <!-- TOP HEADER -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-600 rounded-2xl shadow-lg shadow-blue-100">
                <i data-lucide="history" class="w-8 h-8 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-[#0b1f3a] tracking-tight">Log Aktivitas Import</h1>
                <p class="text-sm text-gray-500 font-medium mt-1">
                    Monitoring ketepatan waktu pengiriman data PDRB Kabupaten/Kota
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 bg-white p-1 rounded-xl border border-gray-100 shadow-sm">
            <span class="px-4 py-2 text-xs font-black text-blue-600 uppercase tracking-widest bg-blue-50 rounded-lg">
                Total: {{ number_format($logs->total(), 0, ',', '.') }} Record
            </span>
        </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden mb-8 transition-all hover:shadow-2xl hover:shadow-gray-200/70">
        <div class="bg-gradient-to-r from-gray-50 to-white p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-black text-[#0b1f3a] flex items-center gap-2 uppercase tracking-tighter text-sm">
                <i data-lucide="filter" class="w-4 h-4 text-blue-600"></i>
                Filter Pencarian Data
            </h3>
            <button type="button" @click="$refs.filterForm.reset()" class="text-xs font-bold text-gray-400 hover:text-blue-600 transition-colors uppercase tracking-widest">
                Clear Filters
            </button>
        </div>
        
        <form action="{{ route('pdrb.import_logs') }}" method="GET" class="p-6" x-ref="filterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- WILAYAH -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Wilayah Kerja</label>
                    <div class="relative">
                        <select name="id_wilayah" class="appearance-none w-full pl-4 pr-10 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                            <option value="">Semua Kabupaten/Kota</option>
                            @foreach($wilayahs as $w)
                                <option value="{{ $w->id_wilayah }}" {{ request('id_wilayah') == $w->id_wilayah ? 'selected' : '' }}>
                                    {{ $w->nama_wilayah }}
                                </option>
                            @endforeach
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3 top-3 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- TAHUN & PERIODE -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Waktu Data</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="relative">
                            <select name="id_tahun" class="appearance-none w-full pl-3 pr-8 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                <option value="">Tahun</option>
                                @foreach($tahuns as $t)
                                    <option value="{{ $t->id_tahun }}" {{ request('id_tahun') == $t->id_tahun ? 'selected' : '' }}>
                                        {{ $t->tahun }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="calendar" class="absolute right-2.5 top-3 w-4 h-4 text-gray-300 pointer-events-none"></i>
                        </div>
                        <div class="relative">
                            <select name="id_periode" class="appearance-none w-full pl-3 pr-8 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                                <option value="">Periode</option>
                                @foreach($periodes as $p)
                                    <option value="{{ $p->id_periode }}" {{ request('id_periode') == $p->id_periode ? 'selected' : '' }}>
                                        {{ $p->nama_periode }}
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="layers" class="absolute right-2.5 top-3 w-4 h-4 text-gray-300 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <!-- PENDEKATAN -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Pendekatan PDRB</label>
                    <div class="relative">
                        <select name="pendekatan" class="appearance-none w-full pl-4 pr-10 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                            <option value="">Semua Pendekatan</option>
                            <option value="lapangan_usaha" {{ request('pendekatan') == 'lapangan_usaha' ? 'selected' : '' }}>Lapangan Usaha (LU)</option>
                            <option value="pengeluaran" {{ request('pendekatan') == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran (EX)</option>
                        </select>
                        <i data-lucide="bar-chart-3" class="absolute right-3 top-3 w-4 h-4 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- RENTANG TANGGAL -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Rentang Tanggal Import</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                            class="w-full px-3 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            class="w-full px-3 py-3 bg-gray-50/50 border border-gray-200 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    </div>
                </div>
            </div>

            <div class="mt-8 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-gray-50 pt-6">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Filter Jam:</span>
                        <input type="time" name="start_time" value="{{ request('start_time') }}" class="px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none">
                        <span class="text-gray-400 text-xs">-</span>
                        <input type="time" name="end_time" value="{{ request('end_time') }}" class="px-2 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none">
                    </div>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <a href="{{ route('pdrb.import_logs') }}" class="flex-1 md:flex-none px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-500 text-xs font-black rounded-2xl transition-all uppercase tracking-widest text-center">
                        Reset
                    </a>
                    <button type="submit" class="flex-1 md:flex-none px-10 py-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-2xl transition-all shadow-lg shadow-blue-200 uppercase tracking-widest">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- DATA TABLE -->
    <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-separate border-spacing-0">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Waktu Import</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Kabupaten / Kota</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Pendekatan</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Tipe</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Detail Data</th>
                        <th class="px-6 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-50 rounded-xl text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                        <i data-lucide="clock" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-gray-900 text-sm tracking-tight">{{ $log->created_at->format('d M Y') }}</div>
                                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $log->created_at->format('H:i:s') }} WITA</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-red-400"></i>
                                    <span class="font-black text-[#0b1f3a] text-sm tracking-tight">
                                        {{ $log->wilayah ? $log->wilayah->nama_wilayah : 'Semua Wilayah' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                @if($log->pendekatan == 'lapangan_usaha')
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 border border-indigo-100">
                                        Lapangan Usaha
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                        Pengeluaran
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-center">
                                @if($log->tipe_import == 'single')
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 text-gray-500 rounded-xl border border-gray-100">
                                        <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Single</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-600 rounded-xl border border-purple-100">
                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Batch</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                @if($log->tipe_import == 'single')
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-[10px] font-black">{{ $log->tahun ? $log->tahun->tahun : '-' }}</span>
                                        <span class="text-xs font-bold text-gray-500">{{ $log->periode ? $log->periode->nama_periode : '-' }}</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1 text-gray-400 italic text-[10px] font-bold">
                                        <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                        MULTIPLE DATA
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-black text-gray-600 overflow-hidden border-2 border-white shadow-sm">
                                        {{ substr($log->user ? $log->user->name : '?', 0, 2) }}
                                    </div>
                                    <span class="text-xs font-black text-gray-700 tracking-tight">{{ $log->user ? $log->user->name : '-' }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center">
                                        <i data-lucide="database" class="w-10 h-10 text-gray-200"></i>
                                    </div>
                                    <div>
                                        <p class="text-lg font-black text-gray-900 tracking-tight">Tidak Ada Data</p>
                                        <p class="text-sm text-gray-400 font-medium">Coba sesuaikan filter pencarian Anda.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="pagination-container">
            {{-- Info Status --}}
            <div class="order-2 md:order-1">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    Menampilkan <span class="text-blue-600">{{ $logs->firstItem() }}</span> - <span class="text-blue-600">{{ $logs->lastItem() }}</span> 
                    Dari <span class="text-slate-900">{{ $logs->total() }}</span> Data
                </p>
            </div>

            {{-- Kontrol Navigasi --}}
            <div class="order-1 md:order-2 flex items-center gap-2">
                {{-- Tombol Sebelumnya --}}
                @if ($logs->onFirstPage())
                    <div class="page-link-custom page-link-disabled">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </div>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="page-link-custom active:scale-95 group">
                        <i data-lucide="chevron-left" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                    </a>
                @endif

                {{-- List Halaman --}}
                <div class="flex items-center gap-1.5 px-2">
                    @php
                        $start = max($logs->currentPage() - 2, 1);
                        $end = min($start + 4, $logs->lastPage());
                        if ($end - $start < 4) {
                            $start = max($end - 4, 1);
                        }
                    @endphp

                    @if($start > 1)
                        <a href="{{ $logs->url(1) }}" class="page-link-custom">1</a>
                        @if($start > 2)
                            <span class="text-gray-300 text-xs font-black px-1">...</span>
                        @endif
                    @endif

                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i == $logs->currentPage())
                            <div class="page-link-custom page-link-active">
                                {{ $i }}
                            </div>
                        @else
                            <a href="{{ $logs->url($i) }}" class="page-link-custom">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    @if($end < $logs->lastPage())
                        @if($end < $logs->lastPage() - 1)
                            <span class="text-gray-300 text-xs font-black px-1">...</span>
                        @endif
                        <a href="{{ $logs->url($logs->lastPage()) }}" class="page-link-custom">{{ $logs->lastPage() }}</a>
                    @endif
                </div>

                {{-- Tombol Berikutnya --}}
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="page-link-custom active:scale-95 group">
                        <i data-lucide="chevron-right" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                    </a>
                @else
                    <div class="page-link-custom page-link-disabled">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

