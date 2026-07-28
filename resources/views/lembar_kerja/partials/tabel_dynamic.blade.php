@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
    // Load schema JSON based on template name
    $schemaPath = config_path('lk_templates/' . $template . '.json');
    $schema = null;
    if (file_exists($schemaPath)) {
        $schema = json_decode(file_get_contents($schemaPath), true);
        
        // Filter columns by active tab (Berlaku/Konstan)
        $schema['columns'] = array_filter($schema['columns'], function($col) use ($tipePdrb) {
            if (!isset($col['visible_in'])) return true;
            return $col['visible_in'] === $tipePdrb;
        });

        // Resolve Year Placeholder in Labels
        foreach ($schema['columns'] as &$col) {
            $yearPlaceholder = ($tipePdrb === 'konstan') ? '2010' : 'tahun berjalan';
            $col['label'] = str_replace('{YEAR}', $yearPlaceholder, $col['label']);
            
            // Resolve formula if split by type
            if (isset($col['formula_' . $tipePdrb])) {
                $col['formula'] = $col['formula_' . $tipePdrb];
            }
        }
    }
@endphp

@if(!$schema)
    <div class="alert alert-danger bg-red-100 text-red-700 p-6 rounded-xl text-sm mb-4 shadow-sm border border-red-200">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Konfigurasi Schema JSON untuk template <b>{{ $template }}</b> tidak ditemukan.
    </div>
@else
    <div class="lk-table-scroll">
        <table class="w-full text-xs relative lk-sticky-table lk-dynamic-table" data-tipe-pdrb="{{ $tipePdrb }}">
            <thead class="text-[11px] bg-slate-100/80">
                {{-- Row 1: Main Groups --}}
                <tr class="text-slate-700 font-black border-b border-slate-200">
                    <th rowspan="2" class="px-2 py-3 text-center border-r border-slate-200" style="width: 60px;">No.</th>
                    <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-200" style="width: 350px;">Subkategori/Jenis Kegiatan/Uraian Komoditi</th>
                    <th colspan="5" class="px-2 py-2 text-center border-r border-slate-200 text-orange-700">Output Utama</th>
                    <th colspan="2" class="px-2 py-2 text-center border-r border-slate-200 text-orange-700">Output Ikutan</th>
                    <th colspan="3" class="px-2 py-2 text-center border-r border-slate-200 text-orange-700">WIP</th>
                    <th rowspan="2" class="px-4 py-3 text-center border-r border-slate-200 text-orange-700" style="width: 180px;">
                        Output ADH Produsen (Juta Rp)<br>[(7)+(9)+(12)]
                    </th>
                    <th colspan="2" class="px-2 py-2 text-center border-r border-slate-200 text-orange-700">Konsumsi Antara</th>
                    <th rowspan="2" class="px-4 py-3 text-center border-r border-slate-200 text-orange-700" style="width: 180px;">
                        Nilai Tambah Bruto (Juta Rp) (13)-(15)
                    </th>
                    <th rowspan="2" class="px-2 py-3 text-center" style="width: 100px;">Aksi</th>
                </tr>

                {{-- Row 2: Sub-columns --}}
                <tr class="text-slate-600 font-bold border-b border-slate-200">
                    {{-- Output Utama --}}
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 120px;">Wujud/<br>Kegiatan</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 80px;">Satuan/<br>Unit</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 150px;">Kuantum</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 200px;">
                        Harga Produsen {{ $tipePdrb === 'konstan' ? '2010' : 'tahun berjalan' }} (Rp/Unit)
                    </th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 220px;">Nilai (Juta Rp) (5)x(6)</th>
                    
                    {{-- Output Ikutan --}}
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 140px;">Rasio Tahun 2010</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 220px;">Nilai (Juta Rp) (8)x(7)</th>
                    
                    {{-- WIP (Standardized widths for consistency) --}}
                    @if($tipePdrb === 'konstan')
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 200px;">Deflator</th>
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 200px;">WIP Berlaku (Juta Rp)</th>
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 220px;">Nilai (Juta Rp) (11)/(10)x100</th>
                    @else
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 200px;">Total Biaya Perawatan Triwulan I (Juta Rp)</th>
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 200px;">Total Biaya Perawatan Sebelumnya (Juta Rp)</th>
                        <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 220px;">Nilai (Juta Rp) (10)-(11)</th>
                    @endif

                    {{-- Konsumsi Antara --}}
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 140px;">Rasio {{ $tipePdrb === 'konstan' ? 'Tahun 2010' : 'tahun berjalan' }}</th>
                    <th class="px-2 py-3 text-center border-r border-slate-200" style="width: 220px;">Nilai (Juta Rp) ((13)-(12))x(14)</th>
                </tr>

                {{-- Row 3: Numbering (1)-(16) --}}
                <tr class="bg-slate-200/50 text-[10px] text-slate-500 font-bold border-b border-slate-300">
                    @for($i = 1; $i <= 16; $i++)
                        <th class="text-center py-1 border-r border-slate-300">({{ $i }})</th>
                    @endfor
                    <th class="text-center py-1"></th>
                </tr>
            </thead>
            <tbody id="{{ $rowsId }}" data-next-row="{{ collect($itemRows ?? [])->count() }}">
                @forelse($itemRows as $index => $item)
                    @php
                        $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                    @endphp
                    <tr data-template="false" data-komoditas-id="{{ $item->komoditas_id }}">
                        <td class="text-center text-slate-400 font-medium">{{ $index + 1 }}</td>
                        <td class="px-4 py-2">
                            <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" 
                                class="w-full border-none focus:ring-0 p-0 bg-transparent font-semibold text-slate-700 lk-dynamic-input" 
                                data-key="komoditas_nama" data-type="text" placeholder="Nama Komoditas...">
                        </td>
                        
                        @foreach($schema['columns'] as $col)
                            @php
                                $visible = !isset($col['visible_in']) || $col['visible_in'] === $tipePdrb;
                                @php
                                    $val = 0;
                                    if ($col['is_meta']) {
                                        $val = $meta[$col['key']] ?? 0;
                                    } else {
                                        $val = $item->{$col['key']} ?? 0;
                                    }
                                    
                                    // Scale values that are stored as Raw Rp in DB (Multiplied by 1M)
                                    // These fields are always shown as Juta Rp in UI
                                    $scaleKeys = ['nilai_output_utama', 'nilai_output_ikut', 'biaya_perawatan', 'biaya_sebelumnya', 
                                                 'wip', 'output_adh', 'konsumsi_antara', 'nilai_ntb', 'wip_berlaku',
                                                 'biaya_perawatan_skr', 'biaya_perawatan_sbl'];
                                    
                                    if (in_array($col['key'], $scaleKeys) || (isset($col['group']) && in_array($col['group'], ['WIP', 'Konsumsi Antara']) && $col['type'] === 'numeric')) {
                                        if (is_numeric($val) && (float)$val != 0) {
                                            $val = bcdiv((string)$val, '1000000', 15);
                                        }
                                    }

                                    $formula = $col['formula'] ?? null;
                                    if (!$formula) {
                                        $formula = ($tipePdrb === 'konstan') 
                                            ? ($col['formula_konstan'] ?? null) 
                                            : ($col['formula_berlaku'] ?? null);
                                    }
                                    $isFormula = !empty($formula);
                                    
                                    // Logic for readonly: Formulas are always readonly.
                                    // In Konstan tab, physical fields (Kuantum, Ratios) follow Berlaku and are readonly.
                                    $isSyncedField = ($tipePdrb === 'konstan' && in_array($col['key'], ['kuantum', 'rasio_output_ikut', 'rasio_konsumsi_antara']));
                                    $isReadonly = $isFormula || $isSyncedField;
                                @endphp
                                <input type="text" 
                                    name="items[{{ $item->komoditas_id }}]{{ $col['is_meta'] ? '[meta]' : '' }}[{{ $col['key'] }}]" 
                                    value="{{ $col['type'] === 'text' ? ($val ?: '-') : \App\Helpers\PdrbHelper::formatId($val, 4) }}" 
                                    class="lk-input lk-dynamic-input w-full {{ $col['type'] === 'text' ? 'text-left' : 'text-right' }} @if($isReadonly) bg-slate-50/50 text-slate-500 @endif" 
                                    data-key="{{ $col['key'] }}"
                                    data-is-meta="{{ $col['is_meta'] ? 'true' : 'false' }}"
                                    data-type="{{ $col['type'] }}"
                                    data-raw-value="{{ $val }}"
                                    @if($isFormula) data-formula="{{ $formula }}" @endif
                                    @if($isReadonly) readonly @endif>
                            </td>
                        @endforeach
                        
                        <td class="text-center">
                            <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" 
                                class="w-7 h-7 flex items-center justify-center rounded-full text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fas fa-trash-alt text-[10px]"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($schema['columns']) + 4 }}" class="px-3 py-12 text-center text-slate-400 italic">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="fas fa-folder-open text-3xl text-slate-200"></i>
                            <span>Belum ada data komoditas.</span>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-black border-t-2 border-slate-300">
                <tr class="text-[11px] text-slate-800">
                    <td colspan="4" class="px-4 py-3 text-right bg-slate-100/50 border-r border-slate-200">RINGKASAN TOTAL</td>
                    @foreach($schema['columns'] as $col)
                        @php 
                            $visible = !isset($col['visible_in']) || $col['visible_in'] === $tipePdrb;
                            // Skip wujud and satuan because they are inside colspan="4"
                            if (in_array($col['key'], ['wujud', 'satuan'])) continue;
                            if (!$visible) continue;
                            
                            $isNoTotal = in_array($col['key'], ['harga_produsen']);
                        @endphp
                        <td class="px-2 py-3 text-right border-r border-slate-200" 
                            {{ $totalPrefix }}="{{ $col['key'] }}">
                            {{ $isNoTotal ? '-' : '0' }}
                        </td>
                    @endforeach
                    <td class="bg-slate-100/30"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- New Row Template -->
    <template id="template-row-{{ $template }}">
        <tr data-template="true">
            <td class="text-center text-slate-400 font-medium row-index">#</td>
            <td class="px-4 py-2">
                <input type="text" name="new_rows[INDEX][komoditas_nama]" 
                    class="w-full border-none focus:ring-0 p-0 bg-transparent font-semibold text-slate-700 lk-dynamic-input" 
                    data-key="komoditas_nama" data-type="text" placeholder="Nama Komoditas Baru...">
            </td>
            
            @foreach($schema['columns'] as $col)
                @php
                    $visible = !isset($col['visible_in']) || $col['visible_in'] === $tipePdrb;
                    if (!$visible) continue;

                    $formula = $col['formula'] ?? null;
                    if (!$formula) {
                        $formula = ($tipePdrb === 'konstan') 
                            ? ($col['formula_konstan'] ?? null) 
                            : ($col['formula_berlaku'] ?? null);
                    }
                    $isFormula = !empty($formula);
                @endphp
                <td class="px-1 py-1 border-r border-slate-100">
                    <input type="text" 
                        name="new_rows[INDEX]{{ $col['is_meta'] ? '[meta]' : '' }}[{{ $col['key'] }}]" 
                        class="lk-input lk-dynamic-input w-full {{ $col['type'] === 'text' ? 'text-left' : 'text-right' }}" 
                        data-key="{{ $col['key'] }}"
                        data-is-meta="{{ $col['is_meta'] ? 'true' : 'false' }}"
                        data-type="{{ $col['type'] }}"
                        data-raw-value="0"
                        @if($isFormula) data-formula="{{ $formula }}" readonly @endif>
                </td>
            @endforeach
            
            <td class="px-2 py-2 text-center border-l border-slate-100/50">
                <button type="button" class="w-7 h-7 flex items-center justify-center rounded-full text-red-300 hover:text-red-500 hover:bg-red-50 transition-all" onclick="this.closest('tr').remove()">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </td>
        </tr>
    </template>
@endif
