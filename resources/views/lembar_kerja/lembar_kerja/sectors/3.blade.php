@php
    $tipePdrb = $tipePdrb ?? 'berlaku';
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = 'data-total'; 
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1 custom-scrollbar w-full overflow-x-auto">
    <table class="w-full text-[11px] relative lk-sticky-table lk-dynamic-table" data-tipe-pdrb="{{ $tipePdrb }}">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="uppercase bg-slate-100 border-b border-slate-300 font-bold text-[10px]">
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-10 text-center">No.</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-1 min-w-[180px] text-left">Subkategori / Komoditi</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-16 text-center">Satuan</th>
                <th colspan="3" class="border border-slate-300 px-1 py-1 text-center bg-blue-50 text-blue-900">Output Utama</th>
                <th colspan="2" class="border border-slate-300 px-1 py-1 text-center bg-purple-50 text-purple-900 font-bold">Output Ikutan</th>
                <th colspan="3" class="border border-slate-300 px-1 py-1 text-center bg-amber-50 text-amber-900 font-bold">WIP (Standing Crops)</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-28 text-center bg-sky-50 text-sky-900 font-black">Output ADH Produsen<br><span class="text-[9px] text-sky-600">(Juta Rp) (13)</span></th>
                <th colspan="2" class="border border-slate-300 px-1 py-1 text-center bg-rose-50 text-rose-900">Konsumsi Antara</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-28 text-center bg-emerald-600 text-white font-black">Nilai Tambah Bruto<br><span class="text-[9px] text-emerald-100">(Juta Rp) (16)</span></th>
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-10 text-center">Aksi</th>
            </tr>
            <tr class="uppercase bg-slate-50 text-[9px] text-slate-500">
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Kuantum (4)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center">Harga (5)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center bg-blue-100/30 font-bold">Nilai (7)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Rasio (8)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center bg-purple-100/30">Nilai (9)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center">Rawat Brj (10)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center">Rawat Sblm (11)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center bg-amber-100/30 font-bold">WIP (12)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Rasio (14)</th>
                <th class="border border-slate-300 px-1 py-1 w-24 text-center bg-rose-100/30">Nilai (15)</th>
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}">
            @foreach($itemRows as $index => $item)
                <tr class="hover:bg-amber-50/50" data-template="hortikultura" data-komoditas-id="{{ $item->komoditas_id }}">
                    <td class="border border-slate-200 px-1 py-1 text-center text-slate-400 bg-slate-50/50 font-mono">{{ $index + 1 }}</td>
                    <td class="border border-slate-200 px-2 py-1 font-bold text-slate-700 uppercase">
                        {{ $item->komoditas_nama }}
                        <input type="hidden" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 text-center text-slate-500 font-bold italic">{{ $item->satuan_nama ?? 'UNIT' }}</td>

                    {{-- OUTPUT UTAMA (4-7) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]" value="{{ \App\Helpers\PdrbHelper::formatId($item->kuantum ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="kuantum">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]" value="{{ \App\Helpers\PdrbHelper::formatId($item->harga_produsen ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="harga_produsen">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-blue-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_utama ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-blue-700" data-key="nilai_output_utama" data-formula="((kuantum || 0) * (harga_produsen || 0)) / 1000000" readonly>
                    </td>

                    {{-- OUTPUT IKUTAN (8-9) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_output_ikut]" value="{{ \App\Helpers\PdrbHelper::formatId($item->rasio_output_ikut ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_output_ikut">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-purple-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_ikut]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_ikut ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-purple-700" data-key="nilai_output_ikut" data-formula="(nilai_output_utama || 0) * (rasio_output_ikut || 0)" readonly>
                    </td>

                    {{-- WIP (10-12) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][biaya_perawatan]" value="{{ \App\Helpers\PdrbHelper::formatId($item->biaya_perawatan ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="biaya_perawatan" placeholder="Berjalan (10)">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][biaya_sebelumnya]" value="{{ \App\Helpers\PdrbHelper::formatId($item->biaya_sebelumnya ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="biaya_sebelumnya" placeholder="Sebelumnya (11)">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-amber-50/40">
                        <input type="text" name="items[{{ $item->komoditas_id }}][wip]" value="{{ \App\Helpers\PdrbHelper::formatId($item->wip ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-amber-700" data-key="wip" data-formula="(biaya_perawatan || 0) - (biaya_sebelumnya || 0)" readonly>
                    </td>

                    {{-- OUTPUT ADH (13) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-sky-50/40">
                        <input type="text" name="items[{{ $item->komoditas_id }}][output_adh]" value="{{ \App\Helpers\PdrbHelper::formatId($item->output_adh ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-black text-sky-900" data-key="output_adh" data-formula="(nilai_output_utama || 0) + (nilai_output_ikut || 0) + (wip || 0)" readonly>
                    </td>

                    {{-- KONSUMSI ANTARA (14-15) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_konsumsi_antara]" value="{{ \App\Helpers\PdrbHelper::formatId($item->rasio_konsumsi_antara ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_konsumsi_antara">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-rose-50/40">
                        <input type="text" name="items[{{ $item->komoditas_id }}][konsumsi_antara]" value="{{ \App\Helpers\PdrbHelper::formatId($item->konsumsi_antara ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-rose-800" data-key="konsumsi_antara" data-formula="((output_adh || 0) - (wip || 0)) * (rasio_konsumsi_antara || 0)" readonly>
                    </td>

                    {{-- NTB (16) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-emerald-600/10">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_ntb ?? 0, 2) }}" class="lk-input lk-dynamic-input w-full text-right font-black text-emerald-900 text-[12px]" data-key="nilai_ntb" data-formula="(output_adh || 0) - (konsumsi_antara || 0)" readonly>
                    </td>

                    <td class="border border-slate-200 px-1 py-1 text-center">
                        <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" class="text-slate-300 hover:text-red-500 transition-all p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="sticky bottom-0 z-10 shadow-[0_-2px_4px_rgba(0,0,0,0.1)]">
            <tr class="bg-slate-800 text-white font-bold text-[10px] uppercase">
                <td colspan="3" class="border border-slate-700 px-2 py-3 text-right text-slate-300 font-normal">TOTAL (JUTA RP)</td>
                <td class="border border-slate-700 bg-slate-700/50 text-right px-2" {{ $totalPrefix }}="kuantum">0</td>
                <td class="border border-slate-700 bg-slate-700/50"></td>
                <td class="border border-slate-700 bg-blue-900/40 text-blue-300 px-2 text-right" {{ $totalPrefix }}="nilai_output_utama">0</td>
                <td class="border border-slate-700 bg-slate-700/50"></td>
                <td class="border border-slate-700 bg-purple-900/40 text-purple-300 px-2 text-right" {{ $totalPrefix }}="nilai_output_ikut">0</td>
                <td class="border border-slate-700 bg-slate-700/50"></td>
                <td class="border border-slate-700 bg-slate-700/50"></td>
                <td class="border border-slate-700 bg-amber-900/40 text-amber-300 px-2 text-right" {{ $totalPrefix }}="wip">0</td>
                <td class="border border-slate-700 bg-sky-900/40 text-sky-300 px-2 text-right font-black" {{ $totalPrefix }}="output_adh">0</td>
                <td class="border border-slate-700 bg-slate-700/50"></td>
                <td class="border border-slate-700 bg-rose-900/40 text-rose-300 px-2 text-right" {{ $totalPrefix }}="konsumsi_antara">0</td>
                <td class="border border-slate-700 bg-emerald-500 text-slate-900 px-2 py-3 text-right font-black text-[12px]" {{ $totalPrefix }}="nilai_ntb">0</td>
                <td class="border border-slate-700 bg-slate-900"></td>
            </tr>
        </tfoot>
    </table>
</div>

<template id="template-row-hortikultura">
    <tr class="hover:bg-amber-50/50 pointer-events-auto" data-template="hortikultura">
        <td class="border border-slate-200 px-1 py-1 text-center bg-slate-50/50 text-slate-400 row-index">#</td>
        <td class="border border-slate-200 px-2 py-1 font-bold text-slate-700">
            <input type="text" name="new_rows[INDEX][komoditas_nama]" class="lk-input w-full p-1 border-none bg-transparent hover:bg-white focus:bg-white focus:ring-1 focus:ring-blue-100 italic uppercase" placeholder="Komoditi Baru...">
        </td>
        <td class="border border-slate-200 px-1 py-1 text-center text-slate-500 font-bold italic uppercase">UNIT</td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][kuantum]" class="lk-input lk-dynamic-input w-full text-right" data-key="kuantum">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][harga_produsen]" class="lk-input lk-dynamic-input w-full text-right" data-key="harga_produsen">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-blue-50/30">
            <input type="text" name="new_rows[INDEX][nilai_output_utama]" class="lk-input lk-dynamic-input w-full text-right font-bold text-blue-700 bg-transparent" data-key="nilai_output_utama" data-formula="((kuantum || 0) * (harga_produsen || 0)) / 1000000" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_output_ikut]" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_output_ikut">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-purple-50/30">
            <input type="text" name="new_rows[INDEX][nilai_output_ikut]" class="lk-input lk-dynamic-input w-full text-right font-bold text-purple-800 bg-transparent" data-key="nilai_output_ikut" data-formula="(nilai_output_utama || 0) * (rasio_output_ikut || 0)" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][biaya_perawatan]" class="lk-input lk-dynamic-input w-full text-right" data-key="biaya_perawatan">
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][biaya_sebelumnya]" class="lk-input lk-dynamic-input w-full text-right" data-key="biaya_sebelumnya">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-amber-50/40">
            <input type="text" name="new_rows[INDEX][wip]" class="lk-input lk-dynamic-input w-full text-right font-bold text-amber-700 bg-transparent" data-key="wip" data-formula="(biaya_perawatan || 0) - (biaya_sebelumnya || 0)" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-sky-50/40">
            <input type="text" name="new_rows[INDEX][output_adh]" class="lk-input lk-dynamic-input w-full text-right font-black text-sky-900 bg-transparent" data-key="output_adh" data-formula="(nilai_output_utama || 0) + (nilai_output_ikut || 0) + (wip || 0)" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-1">
            <input type="text" name="new_rows[INDEX][rasio_konsumsi_antara]" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_konsumsi_antara">
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-rose-50/40">
            <input type="text" name="new_rows[INDEX][konsumsi_antara]" class="lk-input lk-dynamic-input w-full text-right font-bold text-rose-800 bg-transparent" data-key="konsumsi_antara" data-formula="((output_adh || 0) - (wip || 0)) * (rasio_konsumsi_antara || 0)" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-1 bg-emerald-600/10">
            <input type="text" name="new_rows[INDEX][nilai_ntb]" class="lk-input lk-dynamic-input w-full text-right font-black text-emerald-900 bg-transparent" data-key="nilai_ntb" data-formula="(output_adh || 0) - (konsumsi_antara || 0)" readonly>
        </td>
        <td class="border border-slate-200 px-1 py-2 text-center bg-slate-50/50">
            <button type="button" onclick="this.closest('tr').remove()" class="p-1 text-slate-300 hover:text-red-500 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </td>
    </tr>
</template>