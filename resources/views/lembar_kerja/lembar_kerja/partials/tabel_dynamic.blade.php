@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
    // Load schema JSON based on template name
    $schemaPath = config_path('lk_templates/' . $template . '.json');
    $schema = null;
    if (file_exists($schemaPath)) {
        $schema = json_decode(file_get_contents($schemaPath), true);
    }
@endphp

@if(!$schema)
    <div class="alert alert-danger bg-red-100 text-red-700 p-4 rounded text-sm mb-4">
        Konfigurasi Schema JSON untuk template <b>{{ $template }}</b> tidak ditemukan di <code>config/lk_templates/{{ $template }}.json</code>.
    </div>
@else
    <div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
        <table class="w-full text-xs relative lk-sticky-table lk-dynamic-table" data-tipe-pdrb="{{ $tipePdrb }}">
            <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
                <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
                    <th rowspan="2" class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
                    <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Subkategori/Jenis Kegiatan/Uraian Komoditi</th>
                    <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Satuan/Unit</th>
                    
                    @php
                        $groups = [];
                        foreach ($schema['columns'] as $col) {
                            $groupName = $col['group'] ?? 'none';
                            if (!isset($groups[$groupName])) {
                                $groups[$groupName] = 0;
                            }
                            $groups[$groupName]++;
                        }
                    @endphp

                    @foreach($schema['columns'] as $col)
                        @if(!isset($col['group']))
                            <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50">{{ $col['label'] }}</th>
                        @else
                            @if($groups[$col['group']] > 0)
                                <th colspan="{{ $groups[$col['group']] }}" class="border border-slate-300 px-2 py-2 text-center bg-slate-100">{{ $col['group'] }}</th>
                                @php $groups[$col['group']] = 0; @endphp
                            @endif
                        @endif
                    @endforeach
                    <th rowspan="2" class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
                </tr>
                <tr class="text-[11px] uppercase tracking-wide">
                    @foreach($schema['columns'] as $col)
                        @if(isset($col['group']))
                            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50">{{ $col['label'] }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody id="{{ $rowsId }}" data-next-row="{{ collect($itemRows ?? [])->count() }}">
                @forelse($itemRows as $index => $item)
                    @php
                        $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                    @endphp
                    <tr class="hover:bg-slate-50/40" data-template="{{ $template }}" data-komoditas-id="{{ $item->komoditas_id }}">
                        <td class="border border-slate-200 px-2 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="border border-slate-200 px-2 py-1.5 font-semibold bg-yellow-50/10">
                            <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
                        </td>
                        <td class="border border-slate-200 px-2 py-2 text-center text-slate-500">{{ $item->satuan_nama ?? '-' }}</td>
                        
                        @foreach($schema['columns'] as $col)
                            @php
                                $val = 0;
                                if ($col['is_meta']) {
                                    $val = $meta[$col['key']] ?? 0;
                                } else {
                                    $val = $item->{$col['key']} ?? 0;
                                }
                                $isFormula = $col['type'] === 'formula';
                            @endphp
                            <td class="border border-slate-200 px-1 py-1 {{ $isFormula ? 'bg-slate-50' : '' }}">
                                <input type="text" 
                                    name="items[{{ $item->komoditas_id }}]{{ $col['is_meta'] ? '[meta]' : '' }}[{{ $col['key'] }}]" 
                                    value="{{ \App\Helpers\PdrbHelper::formatId($val, 4) }}" 
                                    class="lk-input lk-dynamic-input w-full text-right {{ $isFormula ? 'font-bold bg-slate-50/50' : '' }}" 
                                    data-key="{{ $col['key'] }}"
                                    data-is-meta="{{ $col['is_meta'] ? 'true' : 'false' }}"
                                    @if($isFormula) data-formula="{{ $col['formula'] }}" @endif>
                            </td>
                        @endforeach
                        
                        <td class="border border-slate-200 px-2 py-1.5 text-center">
                            <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($schema['columns']) + 4 }}" class="border border-slate-200 px-3 py-6 text-center text-slate-500">Belum ada komoditas untuk wilayah ini.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-slate-100 font-semibold text-[10px] md:text-xs text-center">
                    <td colspan="3" class="border border-slate-300 px-2 py-2 text-right">Total</td>
                    @foreach($schema['columns'] as $col)
                        <td class="border border-slate-300 px-2 py-2 text-right {{ in_array($col['key'], array_keys(array_filter($schema['display'] ?? []))) ? 'bg-blue-50/40 text-blue-800' : 'text-transparent' }}" {{ $totalPrefix }}="{{ $col['key'] }}">0</td>
                    @endforeach
                    <td class="border border-slate-300 px-2 py-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Template Baris Baru -->
    <template id="template-row-{{ $template }}">
        <tr class="hover:bg-slate-50/40" data-template="{{ $template }}">
            <td class="border border-slate-200 px-2 py-2 text-center row-index">RANK_INDEX</td>
            <td class="border border-slate-200 px-1 py-1">
                <input type="text" name="new_rows[INDEX][komoditas_nama]" class="lk-input w-full px-1 py-1 border border-slate-100 rounded focus:border-orange-400">
            </td>
            <td class="border border-slate-200 px-1 py-1">
                <input type="text" name="new_rows[INDEX][satuan_nama]" class="lk-input w-full px-1 py-1 border border-slate-100 rounded focus:border-orange-400" placeholder="Satuan">
            </td>
            
            @foreach($schema['columns'] as $col)
                @php $isFormula = $col['type'] === 'formula'; @endphp
                <td class="border border-slate-200 px-1 py-1 {{ $isFormula ? 'bg-slate-50' : '' }}">
                    <input type="text" 
                        name="new_rows[INDEX]{{ $col['is_meta'] ? '[meta]' : '' }}[{{ $col['key'] }}]" 
                        class="lk-input lk-dynamic-input w-full text-right {{ $isFormula ? 'font-bold bg-slate-50/50' : '' }}" 
                        data-key="{{ $col['key'] }}"
                        data-is-meta="{{ $col['is_meta'] ? 'true' : 'false' }}"
                        @if($isFormula) data-formula="{{ $col['formula'] }}" @endif>
                </td>
            @endforeach
            
            <td class="border border-slate-200 px-2 py-1.5 text-center">
                <button type="button" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600" onclick="this.closest('tr').remove()">Hapus</button>
            </td>
        </tr>
    </template>
@endif
