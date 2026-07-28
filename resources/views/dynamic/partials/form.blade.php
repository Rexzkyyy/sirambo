<section class="bg-white rounded-xl shadow border border-slate-200 p-6">
    <form id="dynamic-form" method="GET" action="{{ route('pdrb.dynamic', ['jenis' => $pendekatan]) }}" class="grid gap-4 lg:grid-cols-2">
        <input type="hidden" name="jenis" value="{{ $pendekatan }}">

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-slate-900">1. Pilih indikator</h3>
            <p class="text-sm text-slate-500">Klik indikator tunggal (primary). Bisa tambahkan secondary dengan tombol di bawah.</p>
            <div class="space-y-2">
                <input type="text" id="indicatorSearch" placeholder="Cari judul tabel..." class="w-full border rounded-lg px-3 py-2 text-sm" />
                <div class="border border-slate-200 rounded-xl max-h-64 overflow-auto">
                    @foreach($kategori as $item)
                        <button type="button"
                            class="w-full text-left px-3 py-2 text-sm border-b last:border-b-0 hover:bg-blue-50 focus:outline-none indicator-row {{ (string)$subjek1 === (string)$item->id_kategori ? 'bg-blue-500 text-white' : 'text-slate-700' }}"
                            data-id="{{ $item->id_kategori }}">
                            {{ $item->nama_kategori }}
                        </button>
                    @endforeach
                </div>
            </div>
            <input type="hidden" name="subjek_1" id="primaryIndicator" value="{{ $subjek1 }}">
            <div class="grid gap-2">
                <label class="text-xs font-semibold text-slate-600 uppercase tracking-[0.2em]">Secondary (opsional)</label>
                <select name="subjek_2" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="">Tambahkan indikator</option>
                    @foreach($kategori as $item)
                        <option value="{{ $item->id_kategori }}" {{ (string)$subjek2 === (string)$item->id_kategori ? 'selected' : '' }}>
                            {{ $item->nama_kategori }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-slate-900 flex items-center justify-between">
                <span>2. Pilih tahun</span>
                <button type="button" id="selectAllYears" class="text-xs text-blue-600 hover:underline">Pilih semua</button>
            </h3>
            <p class="text-sm text-slate-500">Centang semua tahun yang ingin ditampilkan.</p>
            <div class="border border-slate-200 rounded-xl max-h-64 overflow-auto space-y-1 px-2 py-2 bg-slate-50">
                @foreach($tahunList as $tahun)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="tahun_ids[]" value="{{ $tahun->id_tahun }}" class="rounded border-slate-300" {{ request()->has('tahun_ids') ? (in_array($tahun->id_tahun, request('tahun_ids'), true) ? 'checked' : '') : ($selectedTahun == $tahun->id_tahun ? 'checked' : '') }}>
                        {{ $tahun->tahun }}
                    </label>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <select name="tahun_awal" class="w-full border rounded-lg px-2 py-2">
                    <option value="">Tahun awal</option>
                    @foreach($tahunList as $tahun)
                        <option value="{{ $tahun->tahun }}" {{ (string)$tahunAwal === (string)$tahun->tahun ? 'selected' : '' }}>{{ $tahun->tahun }}</option>
                    @endforeach
                </select>
                <select name="tahun_akhir" class="w-full border rounded-lg px-2 py-2">
                    <option value="">Tahun akhir</option>
                    @foreach($tahunList as $tahun)
                        <option value="{{ $tahun->tahun }}" {{ (string)$tahunAkhir === (string)$tahun->tahun ? 'selected' : '' }}>{{ $tahun->tahun }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-slate-900">3. Triwulan & total</h3>
            <p class="text-sm text-slate-500">Aktifkan turunan secara per-periode.</p>
            <div class="grid gap-2 sm:grid-cols-4">
                @foreach($periodeList as $periode)
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="periode_checks[]" value="{{ $periode->id_periode }}" class="rounded border-slate-300" {{ request('periode_checks') && in_array($periode->id_periode, request('periode_checks')) ? 'checked' : '' }}>
                        {{ $periode->nama_periode }}
                    </label>
                @endforeach
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" name="show_total" value="1" class="rounded border-slate-300" {{ request('show_total') ? 'checked' : '' }}>
                Sertakan kolom total tahunan
            </label>
        </div>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-slate-900">4. Parameter tambahan</h3>
            <div class="grid gap-2">
                <label class="text-xs font-semibold text-slate-600 uppercase tracking-[0.2em]">Tipe</label>
                <select name="tipe_pdrb" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="berlaku" {{ $tipePdrb === 'berlaku' ? 'selected' : '' }}>ADHB</option>
                    <option value="konstan" {{ $tipePdrb === 'konstan' ? 'selected' : '' }}>ADHK</option>
                </select>
            </div>
            <div class="grid gap-2">
                <label class="text-xs font-semibold text-slate-600 uppercase tracking-[0.2em]">Tahap Data</label>
                <select name="tahap_data" class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="awal" {{ $tahapData === 'awal' ? 'selected' : '' }}>Awal</option>
                    <option value="rekonsiliasi" {{ $tahapData === 'rekonsiliasi' ? 'selected' : '' }}>Rekonsiliasi</option>
                </select>
            </div>
            <div class="grid gap-2">
                <label class="text-xs font-semibold text-slate-600 uppercase tracking-[0.2em]">Wilayah</label>
                <select name="scope_wilayah" class="w-full border rounded-lg px-3 py-2 text-sm">
                    @foreach($allWilayahs as $wilayah)
                        <option value="{{ $wilayah->id_wilayah }}" {{ (string)$scopeWilayah === (string)$wilayah->id_wilayah ? 'selected' : '' }}>
                            {{ $wilayah->nama_wilayah }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-base font-semibold text-slate-900">5. Aksi</h3>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="px-5 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold">Generate Tabel</button>
                <a href="{{ route('pdrb.dynamic', ['jenis' => $pendekatan]) }}" class="px-5 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            </div>
                <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                    <button type="button" class="px-4 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold" id="exportCsvBtn" data-export="csv">Export CSV</button>
                    <button type="button" class="px-4 py-2 rounded-lg border border-green-600 text-green-600 font-semibold" id="exportXlsxBtn" data-export="xlsx">Export Excel</button>
                    <button type="button" class="px-4 py-2 rounded-lg border border-purple-600 text-purple-600 font-semibold" id="exportPdfBtn" data-export="pdf">Export PDF</button>
                </div>
            <p id="selectionSummary" class="text-xs italic text-slate-500"></p>
        </div>
    </form>
</section>
