@extends('layouts.main')

@section('title', 'Manajemen User')

@section('content')
    <div class="w-full max-w-none mx-auto mt-8 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Manajemen Users</h1>
                <p class="text-sm text-gray-600">Kelola hak akses dan informasi pengguna sistem Anda.</p>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Search Form -->
                <form method="GET" action="{{ route('users.index') }}" class="flex-1 md:flex-none">
                    <div class="relative flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari user..."
                            class="w-full md:w-64 pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 w-5 h-5 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>

                        <!-- Tombol Reset Pencarian -->
                        @if(request('search'))
                            <a href="{{ route('users.index') }}"
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

                <a href="{{ route('users.create') }}"
                    class="inline-flex items-center px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-all duration-200 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Tambah User
                </a>
            </div>
        </div>

        <!-- Info Jumlah Data -->
        @if(request('search'))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg flex justify-between items-center">
                <p class="text-sm text-blue-700">
                    Menampilkan hasil pencarian untuk "<span class="font-semibold">{{ request('search') }}</span>"
                    @if($users->count() > 0)
                        ({{ $users->total() }} hasil ditemukan)
                    @endif
                </p>
                <a href="{{ route('users.index') }}"
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
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700">Informasi
                                User</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700">Role</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700">Wilayah</th>
                            <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-700 text-center">
                                Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                                        <tr class="hover:bg-gray-50/80 transition-colors duration-150">
                                            <td class="px-6 py-4 text-center">
                                                <span
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-900 font-bold text-sm">
                                                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                            <span class="text-indigo-600 font-bold">
                                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-bold text-gray-900 block">{{ $user->name }}</span>
                                                        <span class="text-xs text-gray-600">{{ $user->email }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                                    {{ $user->role == 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' :
                            ($user->role == 'provinsi' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                                ($user->role == 'kota' ? 'bg-green-100 text-green-800 border border-green-200' :
                                    'bg-orange-100 text-orange-800 border border-orange-200')) }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($user->wilayah)
                                                    <div class="text-sm text-gray-900">
                                                        {{ $user->wilayah->nama_wilayah ?? '-' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        @if($user->wilayah->provinsi)
                                                            {{ $user->wilayah->provinsi->nama_provinsi ?? '' }}
                                                            @if($user->wilayah->kabupaten)
                                                                / {{ $user->wilayah->kabupaten->nama_kabupaten ?? '' }}
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-sm text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex justify-center items-center space-x-2">
                                                    <a href="{{ route('users.edit', $user->id) }}"
                                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 border border-blue-200 transition-all duration-200"
                                                        title="Edit User">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline"
                                                        onsubmit="return confirmDelete()">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 border border-red-200 transition-all duration-200"
                                                            title="Hapus User">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1.5" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-300 mb-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="text-gray-500 font-medium text-lg mb-2">Tidak ada data user</span>
                                        <p class="text-gray-400 text-sm mb-4">
                                            @if(request('search'))
                                                Coba gunakan kata kunci lain atau
                                                <a href="{{ route('users.index') }}"
                                                    class="text-indigo-600 hover:text-indigo-800 font-medium">reset pencarian</a>
                                            @else
                                                Tambah user baru untuk memulai
                                            @endif
                                        </p>
                                        @if(!request('search'))
                                            <a href="{{ route('users.create') }}"
                                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                                Tambah User Pertama
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                        <div class="text-sm text-gray-700">
                            Menampilkan
                            <span class="font-semibold">{{ $users->firstItem() }}</span>
                            sampai
                            <span class="font-semibold">{{ $users->lastItem() }}</span>
                            dari
                            <span class="font-semibold">{{ $users->total() }}</span>
                            data
                        </div>

                        <div class="flex items-center space-x-1">
                            <!-- Previous Page Link -->
                            @if($users->onFirstPage())
                                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </span>
                            @else
                                <a href="{{ $users->previousPageUrl() }}"
                                    class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            @endif

                            <!-- Page Numbers -->
                            @foreach($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                                @if($page == $users->currentPage())
                                    <span class="px-3 py-1.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}"
                                        class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            <!-- Next Page Link -->
                            @if($users->hasMorePages())
                                <a href="{{ $users->nextPageUrl() }}"
                                    class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @else
                                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function confirmDelete() {
            return confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.');
        }
    </script>
@endsection