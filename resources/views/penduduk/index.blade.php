@extends('layouts.main')

@section('title', 'Manajemen Penduduk')

@section('content')
    <div class="w-full max-w-none mx-auto mt-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Manajemen Penduduk</h1>
                <p class="text-sm text-gray-600">Kelola data penduduk per wilayah dan tahun.</p>
            </div>

            <div class="flex items-center space-x-3">
                <form method="GET" action="{{ route('penduduk.index') }}" class="flex-1 md:flex-none">
                    <div class="relative flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari wilayah / tahun..."
                            class="w-full md:w-64 pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 w-5 h-5 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>

                        @if(request('search'))
                            <a href="{{ route('penduduk.index') }}"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 transition-colors"
                                title="Reset pencarian">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </form>

                <a href="{{ route('penduduk.create') }}"
                    class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-all duration-200 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tambah Data
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        @if(request('search'))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex justify-between items-center">
                <p class="text-sm text-blue-700">
                    Menampilkan hasil pencarian untuk "<span class="font-semibold">{{ request('search') }}</span>"
                    @if($penduduk->count() > 0)
                        ({{ $penduduk->total() }} hasil ditemukan)
                    @endif
                </p>
                <a href="{{ route('penduduk.index') }}"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Reset Pencarian
                </a>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th
                                class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700 text-center w-16">
                                No</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700">Wilayah</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700">Tahun</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700 text-right">
                                Jumlah</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700 text-center">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($penduduk as $index => $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    {{ $penduduk->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $row->wilayah?->nama_wilayah ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 uppercase">
                                        {{ $row->wilayah?->tipe ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $row->tahun }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-right">
                                    {{ number_format($row->jumlah, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('penduduk.edit', $row) }}"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-amber-500 rounded-md hover:bg-amber-600">Edit</a>
                                        <form method="POST" action="{{ route('penduduk.destroy', $row) }}"
                                            onsubmit="return confirm('Hapus data penduduk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-red-500 rounded-md hover:bg-red-600">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada data penduduk.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4">
                {{ $penduduk->links() }}
            </div>
        </div>
    </div>
@endsection