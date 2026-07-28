@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
    <table class="w-full text-xs relative lk-sticky-table">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
                <th rowspan="4" class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Subkategori/Jenis Kegiatan/Uraian Komoditi</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Satuan/Unit</th>
                <th colspan="8" class="border border-slate-300 px-2 py-2 text-center bg-green-50/50">Populasi</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-blue-50">Kuantum/Total (8)+(11)</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[150px] text-center">Harga Produsen Thn Berjalan (Rp/Unit)</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[180px] text-center bg-blue-50">Nilai Produksi (Juta Rp) (12)x(13)</th>
                <th colspan="2" rowspan="3" class="border border-slate-300 px-2 py-2 text-center bg-green-50/50">Output Ikutan</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[150px] text-center bg-blue-100/50">Output/Nilai Produksi (Juta Rp) (14)+(16)</th>
                <th colspan="2" rowspan="3" class="border border-slate-300 px-2 py-2 text-center bg-orange-50/50">Konsumsi Antara</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 min-w-[180px] text-center bg-slate-50">Nilai Tambah Bruto (Juta Rp) (17)-(19)</th>
                <th rowspan="4" class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                <th colspan="5" class="border border-slate-300 px-2 py-2 text-center bg-green-50/30">Kenaikan Stok</th>
                <th colspan="3" class="border border-slate-300 px-2 py-2 text-center bg-orange-50/30">Pemotongan (Ekor)</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-green-50/30">Populasi Awal</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-green-50/30">Populasi Akhir</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-green-50/30">Ekspor</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-green-50/30">Impor</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-green-100/50">Jumlah [(5)-(4)+(6)-(7)]</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-orange-50/30">Berat Daging (Kg)</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-orange-50/30">Konv Karkas (Kg/unit)</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-orange-100/50">Pemotongan [9]/[10]</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Rasio Tahun Berjalan</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Nilai Produksi (Juta Rp) (14)x(15)</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Rasio Tahun Berjalan</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center">Nilai (Juta Rp) (17)x(18)</th>
            </tr>
            <tr class="bg-slate-200 text-slate-600 text-[10px] font-bold">
                @for ($i = 1; $i <= 21; $i++)
                    <td class="border border-slate-300 px-1 py-1 text-center">({{ $i }})</td>
                @endfor
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}" data-next-row="{{ $itemRows->count() }}">
            @forelse($itemRows as $index => $item)
                @php
                    $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                @endphp
                <tr class="hover:bg-orange-50/40" data-template="peternakan">
                    <td class="border border-slate-200 px-2 py-2 text-center">
                        {{ $index + 1 }}
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 font-semibold bg-yellow-50/10">
                        <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
                    </td>
                    <td class="border border-slate-200 px-2 py-2 text-center text-slate-500">{{ $item->satuan_nama ?? '-' }}</td>
                    
                    {{-- Populasi: Kenaikan Stok --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][pop_awal]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['pop_awal'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="pop_awal">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][pop_akhir]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['pop_akhir'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="pop_akhir">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][ekspor]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['ekspor'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="ekspor">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][impor]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['impor'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="impor">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-green-100/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][jumlah_stok]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['jumlah_stok'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" 
                            data-key="jumlah_stok" data-calc="jumlah_stok">
                    </td>

                    {{-- Populasi: Pemotongan --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][berat_daging]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['berat_daging'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="berat_daging">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][konversi_karkas]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['konversi_karkas'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="konversi_karkas">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-orange-100/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][pemotongan]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['pemotongan'] ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" 
                            data-key="pemotongan" data-calc="pemotongan">
                    </td>

                    {{-- Result Fields --}}
                    <td class="border border-slate-200 px-1 py-1 bg-slate-50">
                        <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->kuantum ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" 
                            data-key="kuantum" data-calc="kuantum_total">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->harga_produsen ?? 0, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="harga">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-blue-50/10">
                        <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_utama ?? 0, 2) }}">
                        <input type="text" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format font-bold" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_utama ?? 0, 2) }}"
                            data-key="nilai_produksi" data-calc="nilai_output_utama_display" data-unit="million">
                    </td>
                    
                    {{-- Output Ikutan --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][rasio_ikut]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['rasio_ikut'] ?? 0, 4) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ikut">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-green-50/10" data-calc="nilai_ikut">0</td>
                    
                    {{-- Output Total --}}
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-100/20" data-calc="output_total">0</td>

                    {{-- Konsumsi Antara --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][rasio_ka]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['rasio_ka'] ?? 0, 4) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ka">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-50/20" data-calc="nilai_ka">0</td>
                    
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="nilai_ntb">0</td>
                    
                    <td class="border border-slate-200 px-2 py-1.5 text-center">
                         <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600">Hapus</button>
                    </td>

                    {{-- Hidden outputs for DB saving --}}


                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_output_ikut]" value="{{ $item->nilai_output_ikut }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][output_adh]" value="{{ $item->output_adh }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ $item->nilai_ntb }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][konsumsi_antara]" value="{{ $item->konsumsi_antara }}">
                </tr>
            @empty
                <tr><td colspan="21" class="border border-slate-200 px-3 py-6 text-center text-slate-500">Belum ada komoditas untuk wilayah ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-semibold text-[10px] md:text-xs text-center">
                <td colspan="3" class="border border-slate-300 px-2 py-2 text-right">Total</td>
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Pop Awal --}}
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Pop Akhir --}}
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Ekspor --}}
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Impor --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40" {{ $totalPrefix }}="jumlah_stok">0</td>
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Berat Daging --}}
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Konversi --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40" {{ $totalPrefix }}="pemotongan">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40" {{ $totalPrefix }}="kuantum">0</td>
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Harga --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40" {{ $totalPrefix }}="nilai_output_utama">0</td> {{-- Nilai Produksi --}}
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Rasio Ikut --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-green-50/40" {{ $totalPrefix }}="nilai_output_ikut">0</td> {{-- Nilai Ikut --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-100/40" {{ $totalPrefix }}="output_adh">0</td>
                <td class="border border-slate-300 px-2 py-2"></td> {{-- Rasio KA --}}
                <td class="border border-slate-300 px-2 py-2 text-right bg-orange-50/40" {{ $totalPrefix }}="konsumsi_antara">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-slate-50" {{ $totalPrefix }}="nilai_ntb">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<template id="template-row-peternakan">
    <tr class="hover:bg-orange-50/40" data-template="peternakan">
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
        
        {{-- Populasi --}}
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][pop_awal]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="pop_awal">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][pop_akhir]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="pop_akhir">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][ekspor]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="ekspor">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][impor]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="impor">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-blue-50/20">
            <input type="text" name="new_rows[INDEX][meta][jumlah_stok]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="jumlah_stok" data-calc="jumlah_stok">
        </td>
        
        {{-- Pemotongan --}}
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][berat_daging]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="berat_daging">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][meta][konversi_karkas]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="konversi_karkas">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-blue-50/20">
            <input type="text" name="new_rows[INDEX][meta][pemotongan]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="pemotongan" data-calc="pemotongan">
        </td>
        
        <td class="border border-slate-200 px-1 py-1 bg-slate-50">
            <input type="text" name="new_rows[INDEX][kuantum]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="kuantum" data-calc="kuantum_total">
        </td>
        
        {{-- Harga --}}
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][harga_produsen]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="harga">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-blue-50/20">
            <input type="hidden" name="new_rows[INDEX][nilai_output_utama]">
            <input type="text" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format font-bold" 
                data-key="nilai_produksi" data-calc="nilai_output_utama_display" data-unit="million">
        </td>
        
        {{-- Output Ikutan --}}
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_output_ikut]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ikut">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-green-50/10" data-calc="nilai_ikut">0</td>
        
        {{-- Output Total --}}
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-100/20" data-calc="output_total">0</td>

        {{-- Konsumsi Antara --}}
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_konsumsi_antara]" class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_ka">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-orange-50/20" data-calc="nilai_ka">0</td>
        
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="nilai_ntb">0</td>
        
        <td class="border border-slate-200 px-2 py-1.5 text-center">
            <button type="button" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600" onclick="this.closest('tr').remove()">Hapus</button>
        </td>



        <input type="hidden" name="new_rows[INDEX][nilai_output_ikut]" value="0">
        <input type="hidden" name="new_rows[INDEX][output_adh]" value="0">
        <input type="hidden" name="new_rows[INDEX][nilai_ntb]" value="0">
        <input type="hidden" name="new_rows[INDEX][konsumsi_antara]" value="0">
    </tr>
</template>
