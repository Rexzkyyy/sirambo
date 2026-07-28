@extends('layouts.main')

@section('title', isset($user) ? 'Edit User' : 'Tambah User')

@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">
            {{ isset($user) ? 'Edit Profil Pengguna' : 'Tambah Pengguna Baru' }}
        </h1>
        <p class="text-sm text-gray-500">Silakan lengkapi formulir di bawah ini dengan data yang valid.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" 
              method="POST" class="p-8 space-y-6">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Nama Lengkap</label>
                    <input type="text" name="name" required
                           value="{{ $user->name ?? old('name') }}"
                           placeholder="Masukkan nama lengkap"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                </div>

                <div class="md:col-span-2">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Alamat Email</label>
                    <input type="email" name="email" required
                           value="{{ $user->email ?? old('email') }}"
                           placeholder="contoh@domain.com"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Password</label>
                    <div class="relative">
                        <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                               placeholder="********"
                               class="w-full px-4 py-2.5 pr-11 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                               data-password-input>
                        <button type="button"
                                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-700"
                                data-password-toggle
                                aria-label="Tampilkan password">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                    @if(isset($user))
                        <p class="mt-1.5 text-xs text-gray-400 italic font-light">
                            * Biarkan kosong jika tidak ingin diubah
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Konfirmasi Password</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" {{ isset($user) ? '' : 'required' }}
                               placeholder="********"
                               class="w-full px-4 py-2.5 pr-11 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                               data-password-input>
                        <button type="button"
                                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-700"
                                data-password-toggle
                                aria-label="Tampilkan password">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Role Akses</label>
                    <div class="relative">
                        <select name="role" id="role" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                            <option value="">-- Pilih Role --</option>
                            <option value="provinsi" {{ (isset($user) && $user->role=='provinsi') ? 'selected' : '' }}>Provinsi</option>
                            <option value="kabupaten" {{ (isset($user) && $user->role=='kabupaten') ? 'selected' : '' }}>Kabupaten</option>
                            <option value="kota" {{ (isset($user) && $user->role=='kota') ? 'selected' : '' }}>Kota</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <div id="wilayah-div">
                    <label class="block mb-2 text-sm font-semibold text-gray-700">Wilayah Kerja</label>
                    <div class="relative">
                        <select name="id_wilayah" id="id_wilayah" required 
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                            <option value="">-- Pilih Wilayah --</option>
                            @foreach($wilayah as $w)
                                <option value="{{ $w->id_wilayah }}"
                                    data-tipe="{{ $w->tipe }}"
                                    {{ (isset($user) && $user->id_wilayah == $w->id_wilayah) ? 'selected' : '' }}>
                                    @if($w->tipe == 'provinsi')
                                        [Provinsi] {{ $w->provinsi->nama_provinsi ?? '-' }}
                                    @elseif($w->tipe == 'kota')
                                        [Kota] {{ $w->kabupaten->nama_kabupaten ?? '-' }}
                                    @else
                                        [Kabupaten] {{ $w->kabupaten->nama_kabupaten ?? '-' }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('users.index') }}"
                   class="px-6 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                    Batal
                </a>
                <button type="submit"
                        class="px-8 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow-md shadow-indigo-200 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                    {{ isset($user) ? 'Simpan Perubahan' : 'Daftarkan User' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const wilayahSelect = document.getElementById('id_wilayah');
    const wilayahOptions = Array.from(wilayahSelect.options);

    function filterWilayah() {
        const selectedRole = roleSelect.value;
        const currentSelectedValue = wilayahSelect.value;
        
        // Bersihkan dropdown wilayah kecuali option pertama
        wilayahSelect.innerHTML = '';
        wilayahSelect.appendChild(wilayahOptions[0]); 

        let hasMatch = false;
        wilayahOptions.forEach((option, index) => {
            if (index === 0) return;
            
            if (!selectedRole || option.getAttribute('data-tipe') === selectedRole) {
                wilayahSelect.appendChild(option);
                if (option.value === currentSelectedValue) {
                    option.selected = true;
                    hasMatch = true;
                }
            }
        });

        if (!hasMatch && selectedRole) {
            wilayahSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', filterWilayah);
    
    // Jalankan saat pertama kali load (untuk mode Edit)
    if (roleSelect.value) {
        filterWilayah();
    }

    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const wrapper = btn.closest('.relative');
            const input = wrapper?.querySelector('[data-password-input]');
            if (!input) return;

            const isHidden = input.getAttribute('type') === 'password';
            input.setAttribute('type', isHidden ? 'text' : 'password');
            btn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');

            const icon = btn.querySelector('i');
            if (icon) {
                icon.setAttribute('data-lucide', isHidden ? 'eye-off' : 'eye');
            }
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    });
});
</script>
@endsection
