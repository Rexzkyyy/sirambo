{{-- FILTER SECTION --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <form method="GET" action="{{ route('pdrb.hasil', ['jenis' => $pendekatan ?? 'lapangan_usaha']) }}" 
            class="grid grid-cols-1 {{ auth()->user() && in_array(auth()->user()->role, ['kabupaten', 'kota']) ? 'md:grid-cols-4' : 'md:grid-cols-5' }} gap-3">
            <input type="hidden" name="jenis" value="{{ $pendekatan ?? 'lapangan_usaha' }}">

            @if(auth()->user() && in_array(auth()->user()->role, ['kabupaten', 'kota']))
                <input type="hidden" name="scope_wilayah" value="{{ auth()->user()->id_wilayah }}">
            @endif

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-0.5">Tahun</label>
                <div class="flex space-x-2">
                    <select name="id_tahun" id="id_tahun_select" 
                        class="w-full text-sm border border-gray-300 rounded-lg p-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Semua Tahun</option>
                        @foreach(($tahunFilter ?? $tahun) as $t)
                            <option value="{{ $t->id_tahun }}" {{ request('id_tahun') == $t->id_tahun?'selected':'' }}>{{ $t->tahun }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="rentang_tahun" id="rentang_tahun_input" value="{{ request('rentang_tahun') }}">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-0.5">Periode</label>
                <select name="id_periode" class="w-full text-sm border border-gray-300 rounded-lg p-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Semua Periode</option>
                    @foreach(($periodeFilter ?? $periode) as $p)
                        <option value="{{ $p->id_periode }}" {{ request('id_periode') == $p->id_periode?'selected':'' }}>{{ $p->nama_periode }}</option>
                    @endforeach
                </select>
            </div>
            @if(!auth()->user() || !in_array(auth()->user()->role, ['kabupaten', 'kota']))
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-0.5">Wilayah</label>
                <select name="scope_wilayah" class="w-full text-sm border border-gray-300 rounded-lg p-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    @foreach($allWilayahs as $w)
                        @php
                            $namaWilayah = '';
                            if ($w->tipe == 'provinsi' && $w->provinsi) {
                                $namaWilayah = $w->provinsi->nama_provinsi;
                            } elseif (($w->tipe == 'kabupaten' || $w->tipe == 'kota') && $w->kabupaten) {
                                $namaWilayah = $w->kabupaten->nama_kabupaten;
                            }
                        @endphp
                        <option value="{{ $w->id_wilayah }}" 
                            {{ request('scope_wilayah') == $w->id_wilayah || (!request('scope_wilayah') && $w->id_wilayah == auth()->user()->id_wilayah) ? 'selected' : '' }}>
                            {{ $namaWilayah }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-0.5">Tipe PDRB</label>
                <select name="tipe_pdrb" id="tipe-pdrb-select" class="w-full text-sm border border-gray-300 rounded-lg p-1.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="berlaku" {{ request('tipe_pdrb') == 'berlaku' ? 'selected' : '' }}>
                        ADHB (Berlaku)
                    </option>
                    <option value="konstan" {{ request('tipe_pdrb') == 'konstan' ? 'selected' : '' }}>
                        ADHK (Konstan)
                    </option>
                    <option value="y-on-y" {{ request('tipe_pdrb') == 'y-on-y' ? 'selected' : '' }}>
                        Y-on-Y
                    </option>
                    <option value="q-to-q" {{ request('tipe_pdrb') == 'q-to-q' ? 'selected' : '' }}>
                        Q-to-Q
                    </option>
                    <option value="c-to-c" {{ request('tipe_pdrb') == 'c-to-c' ? 'selected' : '' }}>
                        C-to-C
                    </option>
                    <option value="laju" {{ request('tipe_pdrb') == 'laju' ? 'selected' : '' }}>
                        Laju Implisit
                    </option>
                    <option value="indeks" {{ request('tipe_pdrb') == 'indeks' ? 'selected' : '' }}>
                        Indeks Implisit
                    </option>
                    <option value="distribusi" {{ request('tipe_pdrb') == 'distribusi' ? 'selected' : '' }}>
                        Distribusi
                    </option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full text-sm bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3 rounded-lg transition duration-200">
                    Tampilkan
                </button>
            </div>
        </form>
        
        {{-- Tampilkan info rentang tahun jika dipilih --}}
        @if(request('rentang_tahun'))
            @php
                $rentangArray = explode('-', request('rentang_tahun'));
                $tahunAwal = $rentangArray[0] ?? '';
                $tahunAkhir = $rentangArray[1] ?? '';
            @endphp
            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-800">
                    <span class="font-semibold">Rentang Tahun:</span> 
                    {{ $tahunAwal }} - {{ $tahunAkhir }}
                    <button type="button" id="clearRentangBtn" class="ml-2 text-blue-600 hover:text-blue-800 text-[11px]">
                        [Hapus]
                    </button>
                </p>
            </div>
        @endif
    </div>

    {{-- MODAL RENTANG TAHUN --}}
    <div id="rentangTahunModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Rentang Tahun</h3>
                
                <div id="rentangTahunForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Awal</label>
                        <select id="tahun_awal" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Pilih Tahun Awal</option>
                            @foreach(($tahunFilter ?? $tahun) as $t)
                                <option value="{{ $t->tahun }}">{{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Akhir</label>
                        <select id="tahun_akhir" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Pilih Tahun Akhir</option>
                            @foreach(($tahunFilter ?? $tahun) as $t)
                                <option value="{{ $t->tahun }}">{{ $t->tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancelRentangBtn" 
                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                            Batal
                        </button>
                        <button type="button" id="applyRentangBtn" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    
