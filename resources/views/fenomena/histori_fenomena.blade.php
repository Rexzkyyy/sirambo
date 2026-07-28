@extends('layouts.main')

@section('title', 'Histori Upload Fenomena PDRB')

@section('content')
<div class="container-fluid px-6 py-8 bg-gray-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Histori Upload Fenomena PDRB</h1>
            <p class="text-gray-500 mt-1">Riwayat pengaploadan file Excel data fenomena ekonomi</p>
        </div>
        <div>
            <a href="{{ route('fenomena.index', ['mode' => $mode, 'tahun' => $tahun, 'pendekatan' => $pendekatan, 'id_wilayah' => $id_wilayah]) }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2.5 rounded-lg flex items-center gap-2 transition font-medium">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <!-- Statistik Card -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Upload</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['total_upload'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i data-lucide="upload" class="w-6 h-6 text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Total Data Import</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['total_data']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i data-lucide="database" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Berhasil</p>
                    <p class="text-2xl font-bold text-green-600">{{ $statistics['success_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Gagal / Partial</p>
                    <p class="text-2xl font-bold text-red-600">{{ $statistics['failed_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
        <form method="GET" action="{{ route('fenomena.histori') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                <select name="mode" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="tahunan" {{ $mode == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    <option value="triwulanan" {{ $mode == 'triwulanan' ? 'selected' : '' }}>Triwulanan</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                <select name="tahun" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @foreach($tahunList as $thn)
                        <option value="{{ $thn }}" {{ $tahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pendekatan</label>
                <select name="pendekatan" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="lapangan_usaha" {{ $pendekatan == 'lapangan_usaha' ? 'selected' : '' }}>Lapangan Usaha</option>
                    <option value="pengeluaran" {{ $pendekatan == 'pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                </select>
            </div>
            
            @if($isProvinsi)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Wilayah</label>
                <select name="id_wilayah" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Semua Wilayah --</option>
                    @foreach($wilayahList as $wilayah)
                        <option value="{{ $wilayah->id_wilayah }}" {{ $id_wilayah == $wilayah->id_wilayah ? 'selected' : '' }}>
                            {{ $wilayah->nama_wilayah ?? $wilayah->nama ?? $wilayah->nm_wilayah }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-medium transition w-full">
                    <i data-lucide="filter" class="w-4 h-4 inline mr-2"></i>
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Histori -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wilayah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama File</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploader</th>
                        <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th> -->
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($histori as $index => $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $histori->firstItem() + $index }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->uploaded_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $item->nama_wilayah }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                                <span class="truncate max-w-xs">{{ $item->nama_file }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $item->ukuran_file }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $item->mode == 'tahunan' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ ucfirst($item->mode) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->tahun }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($item->jumlah_data) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($item->status == 'success')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i data-lucide="check" class="w-3 h-3 inline mr-1"></i> Sukses
                                </span>
                            @elseif($item->status == 'partial')
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i> Partial
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i data-lucide="x" class="w-3 h-3 inline mr-1"></i> Gagal
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $item->user->name ?? 'Unknown' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada histori upload</p>
                            <p class="text-sm mt-1">Silakan upload file Excel terlebih dahulu</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $histori->links() }}
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Detail Histori Upload</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="p-6" id="detail-content">
            <!-- Content will be filled by JS -->
        </div>
    </div>
</div>

<script>
    function showDetail(id) {
        fetch(`/fenomena/histori/${id}/detail`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.data;
                    const modalContent = document.getElementById('detail-content');
                    modalContent.innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs text-gray-500">Tanggal Upload</label>
                                    <p class="text-sm font-medium">${item.uploaded_at}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Wilayah</label>
                                    <p class="text-sm font-medium">${item.nama_wilayah}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Nama File</label>
                                    <p class="text-sm font-medium break-all">${item.nama_file}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Ukuran File</label>
                                    <p class="text-sm font-medium">${item.ukuran_file}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Mode</label>
                                    <p class="text-sm font-medium">${item.mode}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Tahun</label>
                                    <p class="text-sm font-medium">${item.tahun}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Pendekatan</label>
                                    <p class="text-sm font-medium">${item.pendekatan.replace('_', ' ')}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Jumlah Data</label>
                                    <p class="text-sm font-medium">${item.jumlah_data.toLocaleString()}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Status</label>
                                    <p class="text-sm font-medium">${item.status}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Uploader</label>
                                    <p class="text-sm font-medium">${item.uploader_name}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">IP Address</label>
                                    <p class="text-sm font-medium">${item.ip_address || '-'}</p>
                                </div>
                            </div>
                            
                            ${item.keterangan ? `
                            <div>
                                <label class="text-xs text-gray-500">Keterangan</label>
                                <p class="text-sm mt-1 p-3 bg-gray-50 rounded-lg">${item.keterangan}</p>
                            </div>
                            ` : ''}
                            
                            ${item.error_message ? `
                            <div>
                                <label class="text-xs text-gray-500">Error Message</label>
                                <p class="text-sm mt-1 p-3 bg-red-50 text-red-700 rounded-lg">${item.error_message}</p>
                            </div>
                            ` : ''}
                        </div>
                    `;
                    document.getElementById('detail-modal').classList.remove('hidden');
                    document.getElementById('detail-modal').classList.add('flex');
                    lucide.createIcons();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mengambil detail histori');
            });
    }
    
    function closeModal() {
        const modal = document.getElementById('detail-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    document.getElementById('detail-modal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    
    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endsection