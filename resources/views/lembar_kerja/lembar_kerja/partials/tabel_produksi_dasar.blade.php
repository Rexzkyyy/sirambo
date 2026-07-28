@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
    @if(!$lembarKerja)
        <style>
            .lk-input {
                pointer-events: none;
                background: #f3f4f6;
            }
        </style>
    @endif

    <table class="w-full text-xs relative lk-sticky-table">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[400px] text-center">
                    Komoditas
                </th>
                <th colspan="5" class="border border-slate-300 px-2 py-2 text-center">Output Utama</th>
                <th colspan="2" class="border border-slate-300 px-2 py-2 text-center">Output Ikutan</th>
                <th colspan="3" class="border border-slate-300 px-2 py-2 text-center">WIP</th>
                <th rowspan="2"
                    class="border border-slate-300 px-2 py-2 min-w-[280px] text-center bg-slate-50">
                    Output ADH<br>Produsen (Juta Rp)
                </th>
                <th colspan="2" class="border border-slate-300 px-2 py-2 text-center">Konsumsi Antara
                </th>
                <th rowspan="2"
                    class="border border-slate-300 px-2 py-2 min-w-[280px] text-center bg-slate-50">
                    Nilai Tambah Bruto<br>(Juta Rp)
                </th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center">Wujud/Kegiatan
                </th>
                <th class="border border-slate-300 px-2 py-2 min-w-[180px] text-center">Satuan/Unit</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Kuantum</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center">Harga Produsen
                    (Rp/Juta)</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center bg-blue-50/30">
                    Nilai Output Utama<br>(Juta Rp)</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Rasio Output
                    Ikut</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center bg-blue-50/30">
                    Nilai Output Ikut<br>(Juta Rp)</th>

                @if($tipePdrb === 'berlaku')
                    <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Biaya Perawatan</th>
                    <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Biaya Sebelumnya</th>
                    <th class="border border-slate-300 px-2 py-2 min-w-[220px] text-center bg-blue-50/30">Nilai WIP (Juta Rp)</th>
                @else
                    <th class="border border-slate-300 px-2 py-2 min-w-[160px] text-center">Deflator</th>
                    <th class="border border-slate-300 px-2 py-2 min-w-[220px] text-center bg-blue-50/30">WIP Berlaku (Juta Rp)</th>
                    <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center bg-blue-50/30">Nilai WIP<br>(Juta Rp)</th>
                @endif

                <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Rasio Tahun
                    Berjalan</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[250px] text-center bg-blue-50/30">
                    Nilai Konsumsi Antara<br>(Juta Rp)</th>
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}" data-next-row="{{ $itemRows->count() }}">
            @forelse($itemRows as $index => $item)
                <tr class="hover:bg-orange-50/40">
                    <td class="border border-slate-200 px-2 py-2 text-center">
                        {{ $index + 1 }}
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 ">
                        <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]"
                            value="{{ $item->komoditas_nama ?? '' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded focus:outline-none focus:border-orange-400 font-semibold text-slate-800"
                            data-row="{{ $index }}" data-col="2">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5">
                        <input type="text" name="items[{{ $item->komoditas_id }}][wujud]"
                            value="{{ $item->komoditas_wujud ?? '' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded focus:outline-none focus:border-orange-400"
                            data-row="{{ $index }}" data-col="1">
                    </td>
                    <td class="border border-slate-200 px-2 py-2 text-slate-700">
                        {{ $item->satuan_nama ?? '-' }}
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5">
                        <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]"
                            value="{{ $item->kuantum !== null ? number_format((float) $item->kuantum, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                            maxlength="16" data-row="{{ $index }}" data-col="3"
                            data-raw-value="{{ $item->kuantum ?? 0 }}">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5">
                        <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]"
                            value="{{ $item->harga_produsen !== null ? number_format((float) $item->harga_produsen, 0, ',', '.') : '0' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format {{ $tipePdrb === 'konstan' && !($isBaseYear ?? true) ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                            {{ $tipePdrb === 'konstan' && !($isBaseYear ?? true) ? 'readonly tabindex="-1"' : '' }}
                            maxlength="16" data-row="{{ $index }}" data-col="4"
                            data-raw-value="{{ $item->harga_produsen ?? 0 }}">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_utama]"
                            value="{{ $item->nilai_output_utama !== null ? number_format(((float) $item->nilai_output_utama) / 1000000, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                            readonly data-row="{{ $index }}" data-col="5"
                            data-raw-value="{{ $item->nilai_output_utama !== null ? ((float) $item->nilai_output_utama) / 1000000 : 0 }}">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_output_ikut]"
                            value="{{ rtrim(rtrim(number_format((float) ($item->rasio_output_ikut ?? 0), 15, ',', '.'), '0'), ',') }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format {{ !($isBaseYear ?? true) ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                            {{ !($isBaseYear ?? true) ? 'readonly tabindex="-1"' : '' }}
                            data-row="{{ $index }}" data-col="6"
                            data-raw-value="{{ $item->rasio_output_ikut ?? 0 }}">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_ikut]"
                            value="{{ $item->nilai_output_ikut !== null ? number_format(((float) $item->nilai_output_ikut) / 1000000, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                            readonly data-row="{{ $index }}" data-col="7">
                    </td>

                    @if($tipePdrb === 'berlaku')
                        <td class="border border-slate-200 px-2 py-1.5">
                            <input type="text" name="items[{{ $item->komoditas_id }}][biaya_perawatan]"
                                value="{{ $item->biaya_perawatan !== null ? number_format((float) $item->biaya_perawatan, 0, ',', '.') : '0' }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                                maxlength="16" data-row="{{ $index }}" data-col="8">
                        </td>
                        <td class="border border-slate-200 px-2 py-1.5">
                            <input type="text" name="items[{{ $item->komoditas_id }}][biaya_sebelumnya]"
                                value="{{ $item->biaya_sebelumnya !== null ? number_format((float) $item->biaya_sebelumnya, 0, ',', '.') : '0' }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                                maxlength="16" data-row="{{ $index }}" data-col="9">
                        </td>
                        <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                            <input type="text" name="items[{{ $item->komoditas_id }}][wip_berlaku]"
                                value="{{ $item->wip_berlaku !== null ? number_format(((float) $item->wip_berlaku) / 1000000, 2, ',', '.') : '0,00' }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                                readonly data-row="{{ $index }}" data-col="10">
                        </td>
                    @else
                        <td class="border border-slate-200 px-2 py-1.5">
                            <input type="text" name="items[{{ $item->komoditas_id }}][deflator]"
                                value="{{ rtrim(rtrim(number_format((float) ($item->deflator ?? 0), 15, ',', '.'), '0'), ',') }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                                data-row="{{ $index }}" data-col="8"
                                data-raw-value="{{ $item->deflator ?? 0 }}">
                        </td>
                        <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                            <input type="text" name="items[{{ $item->komoditas_id }}][wip_berlaku]"
                                value="{{ $item->wip_berlaku !== null ? number_format(((float) $item->wip_berlaku) / 1000000, 2, ',', '.') : '0,00' }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                                readonly data-row="{{ $index }}" data-col="9">
                        </td>
                        <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                            <input type="text" name="items[{{ $item->komoditas_id }}][wip]"
                                value="{{ $item->wip !== null ? number_format(((float) $item->wip) / 1000000, 2, ',', '.') : '0,00' }}"
                                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                                readonly data-row="{{ $index }}" data-col="10">
                        </td>
                    @endif

                    <td class="border border-slate-200 px-2 py-1.5 bg-slate-50">
                        <input type="text" name="items[{{ $item->komoditas_id }}][output_adh]"
                            value="{{ $item->output_adh !== null ? number_format(((float) $item->output_adh) / 1000000, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 font-bold bg-slate-50 number-format"
                            readonly data-row="{{ $index }}" data-col="11">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_konsumsi_antara]"
                            value="{{ rtrim(rtrim(number_format((float) ($item->rasio_konsumsi_antara ?? 0), 15, ',', '.'), '0'), ',') }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format {{ !($isBaseYear ?? true) ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : '' }}"
                            {{ !($isBaseYear ?? true) ? 'readonly tabindex="-1"' : '' }}
                            data-row="{{ $index }}" data-col="12"
                            data-raw-value="{{ $item->rasio_konsumsi_antara ?? 0 }}">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 bg-blue-50/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][konsumsi_antara]"
                            value="{{ $item->konsumsi_antara !== null ? number_format(((float) $item->konsumsi_antara) / 1000000, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 bg-blue-50/30 font-semibold number-format"
                            readonly data-row="{{ $index }}" data-col="13">
                    </td>
                    <td class="border border-slate-200 px-2 py-2 bg-slate-50">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_ntb]"
                            value="{{ $item->nilai_ntb !== null ? number_format(((float) $item->nilai_ntb) / 1000000, 2, ',', '.') : '0,00' }}"
                            class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 font-bold bg-slate-50 number-format"
                            readonly data-row="{{ $index }}" data-col="14">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-center">
                        <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}"
                            class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600">
                            Hapus
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="border border-slate-200 px-3 py-6 text-center text-slate-500">
                        Belum ada komoditas untuk wilayah ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-semibold">
                <td colspan="2" class="border border-slate-300 px-2 py-2 text-right">Total</td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40"
                    {{ $totalPrefix }}="nilai_output_utama">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40 text-[10px]" {{ $totalPrefix }}="rasio_output_ikut">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40"
                    {{ $totalPrefix }}="nilai_output_ikut">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-slate-50"
                    {{ $totalPrefix }}="output_adh">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40 text-[10px]" {{ $totalPrefix }}="rasio_konsumsi_antara">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40"
                    {{ $totalPrefix }}="konsumsi_antara">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-slate-50"
                    {{ $totalPrefix }}="nilai_ntb">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<template id="template-row-lembar-kerja">
    <tr class="hover:bg-orange-50/40">
        <td class="border border-slate-200 px-2 py-2 text-center row-index">RANK_INDEX</td>
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][komoditas_nama]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded focus:outline-none focus:border-orange-400 font-semibold text-slate-800">
        </td>
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][wujud]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded focus:outline-none focus:border-orange-400">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 ">
            <input type="text" name="new_rows[INDEX][satuan_nama]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded focus:outline-none focus:border-orange-400">
        </td>
        {{-- Kuantum --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][kuantum]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="kuantum">
        </td>
        {{-- Harga --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][harga_produsen]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="harga">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/20"
            data-calc="nilai_output_utama">0</td>
        {{-- Rasio Output Ikutan --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][rasio_output_ikut]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="rasio_output_ikut">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/20"
            data-calc="nilai_output_ikut">0</td>
        {{-- Biaya Perawatan --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][biaya_perawatan]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="biaya_perawatan">
        </td>
        {{-- Biaya Sebelumnya --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][biaya_sebelumnya]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="biaya_sebelumnya">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/20" data-calc="wip">0</td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="output_adh">
            0</td>
        {{-- Rasio Konsumsi Antara --}}
        <td class="border border-slate-200 px-2 py-1.5">
            <input type="text" name="new_rows[INDEX][rasio_konsumsi_antara]"
                class="lk-input w-full px-2 py-1 border border-slate-200 rounded text-right focus:outline-none focus:border-orange-400 number-format"
                data-key="rasio_konsumsi_antara">
        </td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/20"
            data-calc="konsumsi_antara">0</td>
        <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-slate-50" data-calc="nilai_ntb">
            0</td>
        <td class="border border-slate-200 px-2 py-1.5 text-center">
            <button type="button"
                class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600"
                onclick="this.closest('tr').remove()">Hapus</button>
        </td>
    </tr>
</template>
