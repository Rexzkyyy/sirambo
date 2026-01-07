<!-- resources/views/components/navbar.blade.php -->
<div class="flex h-screen bg-gray-100">

    <!-- Sidebar / Navbar -->
    <aside class="w-64 bg-white shadow-md">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-blue-600 mb-6">SIRAMBO</h1>
        </div>
        <nav class="flex flex-col px-4 space-y-2">
            <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                DASHBOARD
            </a>
            <a href="{{ route('pdrb.hasil') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                MANAJEMEN USER
            </a>
            <a href="{{ route('pdrb.hasil') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                MANAJEMEN WILAYAH
            </a>
            <a href="{{ route('pdrb.hasil') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                TAHUN
            </a>
            <a href="{{ route('pdrb.data') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                FORM INPUT DATA POKOK
            </a>
            <a href="{{ route('pdrb.hasil') }}" class="block px-4 py-2 rounded hover:bg-blue-100 hover:text-blue-700 transition">
                HASIL
            </a>
        </nav>
    </aside>

    <!-- Konten Utama -->
    <main class="flex-1 p-8">
        @yield('content')
    </main>

</div>
