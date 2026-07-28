@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
    <table class="w-full text-xs relative lk-sticky-table">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Subkategori/Jenis Kegiatan/Uraian Komoditi</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Wujud/Kegiatan</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Satuan/Unit</th>
                <th colspan="3" class="border border-slate-300 px-2 py-2 text-center bg-blue-50/50">Output Utama</th>
                <th colspan="2" class="border border-slate-300 px-2 py-2 text-center bg-green-50/50">Output Ikutan</th>
                <th colspan="3" class="border border-slate-300 px-2 py-2 text-center bg-orange-50/50">CBR/WIP</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[150px] text-center bg-blue-100/50">Output ADH Produsen [(7)+(9)+(12)]</th>
                <th colspan="2" class="border border-slate-300 px-2 py-2 text-center bg-orange-100/50">Konsumsi Antara</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[150px] text-center bg-slate-50">Nilai Tambah Bruto (13)-(15)</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                {{-- Output Utama --}}
                <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Kuantum</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Harga Produsen</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-blue-50/30">Nilai (5)x(6)</th>
                {{-- Output Ikutan --}}
                <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Rasio</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-green-50/30">Nilai (8)x(7)</th>
                {{-- CBR/WIP --}}
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Biaya n</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Biaya n-1</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-orange-50/30">Nilai (10)-(11)</th>
                {{-- Konsumsi Antara --}}
                <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Rasio</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-orange-100/30">Nilai ((13)-(12))x(14)</th>
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}" data-next-row="{{ $itemRows->count() }}">
            @forelse($itemRows as $index => $item)
                @php
                    $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                @endphp
                <tr class="hover:bg-orange-50/40" data-template="perikanan">
                    <td class="border border-slate-200 px-2 py-2 text-center">
                        {{ $index + 1 }}
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 font-semibold bg-yellow-50/10">
                        <div class="flex items-center gap-2">
                            <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
                        </div>
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 font-semibold">
                        <input type="text" name="items[{{ $item->komoditas_id }}][wujud]" value="{{ $item->komoditas_wujud }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
                    </td>
                    <td class="border border-slate-200 px-2 py-2 text-center text-slate-500">{{ $item->satuan_nama ?? '-' }}</td>
                    
                    {{-- Output Utama --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->kuantum ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="kuantum">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->harga_produsen ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="harga">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/10" data-calc="nilai_utama">0</td>

                    {{-- Output Ikutan --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][rasio_ikut]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['rasio_ikut'] ?? 0, 4) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ikut">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-green-50/10" data-calc="nilai_ikut">0</td>

                    {{-- CBR/WIP --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][biaya_n]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['biaya_n'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="biaya_n">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][biaya_n_min_1]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['biaya_n_min_1'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="biaya_n_min_1">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-50/10" data-calc="nilai_wip">0</td>

                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-100/20" data-calc="output_adh">0</td>

                    {{-- Konsumsi Antara --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][rasio_ka]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['rasio_ka'] ?? 0, 4) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ka">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-100/20" data-calc="nilai_ka">0</td>

                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="nilai_ntb">0</td>

                    <td class="border border-slate-200 px-2 py-1.5 text-center">
                         <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600">Hapus</button>
                    </td>

                    {{-- Hidden outputs for DB saving --}}
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][kuantum]" value="{{ $item->kuantum }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" value="{{ $item->nilai_output_utama }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_output_ikut]" value="{{ $item->nilai_output_ikut }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][output_adh]" value="{{ $item->output_adh }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ $item->nilai_ntb }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][konsumsi_antara]" value="{{ $item->konsumsi_antara }}">
                </tr>
            @empty
                <tr><td colspan="17" class="border border-slate-200 px-3 py-6 text-center text-slate-500">Belum ada komoditas untuk wilayah ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-semibold">
                <td colspan="6" class="border border-slate-300 px-2 py-2 text-right">Total</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40">0</td> {{-- Nilai Utama --}}
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-green-50/40">0</td> {{-- Nilai Ikutan --}}
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-orange-50/40">0</td> {{-- Nilai WIP --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-100/40" {{ $totalPrefix }}="output_adh">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-orange-100/40" {{ $totalPrefix }}="konsumsi_antara">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-slate-50" {{ $totalPrefix }}="nilai_ntb">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<template id="template-row-perikanan">
    <tr class="hover:bg-orange-50/40" data-template="perikanan">
        <td class="border border-slate-200 px-2 py-2 text-center row-index">RANK_INDEX</td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][komoditas_nama]" class="lk-input w-full px-1 py-1 border border-slate-100 rounded focus:border-orange-400">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][wujud]" class="lk-input w-full px-1 py-1 border border-slate-100 rounded focus:border-orange-400">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][satuan_nama]" class="lk-input w-full px-1 py-1 border border-slate-100 rounded focus:border-orange-400">
        </td>

        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][kuantum]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="kuantum">
        </td>
        
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][harga_produsen]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="harga">
        </td>

        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/10" data-calc="nilai_utama">0</td>

        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_output_ikut]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ikut">
        </td>

        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-green-50/10" data-calc="nilai_ikut">0</td>
        
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][biaya_n]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="biaya_n">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][biaya_n_min_1]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="biaya_n_min_1">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-50/10" data-calc="nilai_wip">0</td>

        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-100/20" data-calc="output_adh">0</td>

        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_konsumsi_antara]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ka">
        </td>

        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-100/20" data-calc="nilai_ka">0</td>
        
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="nilai_ntb">0</td>
        
        <td class="border border-slate-200 px-2 py-1.5 text-center">
            <button type="button" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600" onclick="this.closest('tr').remove()">Hapus</button>
        </td>

        <input type="hidden" name="new_rows[INDEX][nilai_output_utama]" value="0">
        <input type="hidden" name="new_rows[INDEX][nilai_output_ikut]" value="0">
        <input type="hidden" name="new_rows[INDEX][output_adh]" value="0">
        <input type="hidden" name="new_rows[INDEX][nilai_ntb]" value="0">
        <input type="hidden" name="new_rows[INDEX][konsumsi_antara]" value="0">
    </tr>
</template>
