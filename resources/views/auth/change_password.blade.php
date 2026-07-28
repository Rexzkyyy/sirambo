@extends('layouts.main')

@section('title', 'Ganti Password')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-orange-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-orange-50 bg-orange-50/30">
            <h1 class="text-xl font-bold text-gray-900">Ganti Password</h1>
            <p class="text-sm text-gray-600 mt-1">Pastikan password Anda kuat dan sulit ditebak.</p>
        </div>

        <form action="{{ route('profile.password.update') }}" method="POST" class="p-8 space-y-6">
            @csrf

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-100 text-green-700 rounded-xl text-sm font-medium flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2">Password Baru</label>
                    <div class="relative group">
                        <input type="password" name="password" id="password" required
                            class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none">
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3 text-gray-400 hover:text-orange-600 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4" id="eye-password"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-600 mt-2 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
                    <div class="relative group">
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all outline-none">
                        <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-3 text-gray-400 hover:text-orange-600 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4" id="eye-password_confirmation"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-xl shadow-lg shadow-orange-200 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    SIMPAN PERUBAHAN
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById('eye-' + id);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        
        // Refresh Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
</script>
@endsection
