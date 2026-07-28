@php
    $tipePdrb = $tipePdrb ?? 'berlaku';
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
    <table class="w-full text-[10px] relative lk-sticky-table lk-dynamic-table" data-tipe-pdrb="{{ $tipePdrb }}">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="uppercase bg-slate-100 border-b border-slate-300">
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-8 text-center">No.</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[150px] text-left">Subkateg/Jenis Kegiatan</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-12 text-center text-[9px]">Satuan</th>
                <th colspan="5" class="border border-slate-300 px-1 py-1 text-center bg-blue-50/50">Populasi</th>
                <th colspan="3" class="border border-slate-300 px-1 py-1 text-center bg-orange-50/50">Pemotongan (Ekor)</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-20 text-center bg-blue-50">Kuantum /Total (8)+(11)</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-20 text-center bg-slate-50">Harga Produsen (Rp/Unit)</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-20 text-center bg-green-50">Nilai Produksi (12)x(13)</th>
                <th colspan="2" class="border border-slate-300 px-1 py-1 text-center bg-purple-50/50">Output Ikutan</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-20 text-center bg-blue-50">Output Total (14)+(16)</th>
                <th colspan="2" class="border border-slate-300 px-1 py-1 text-center bg-red-50/50">Konsumsi Antara</th>
                <th rowspan="2" class="border border-slate-300 px-1 py-2 w-20 text-center bg-green-100 font-bold">NTB</th>
            </tr>
            <tr class="uppercase bg-slate-100 text-[9px]">
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Pop Awal (5)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Pop Akhir (4)</th>
                <th class="border border-slate-300 px-1 py-1 w-12 text-center">Eks (6)</th>
                <th class="border border-slate-300 px-1 py-1 w-12 text-center">Imp (7)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center font-bold">Jml (5)-(4)+(6)-(7) (8)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Daging (Kg) (9)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Karkas (10)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center font-bold">Pot (9/10) (11)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Rasio (15)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Nilai (16)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Rasio (18)</th>
                <th class="border border-slate-300 px-1 py-1 w-16 text-center">Nilai (19)</th>
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}">
            @foreach($itemRows as $index => $item)
                @php
                    $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                @endphp
                <tr class="hover:bg-slate-50/40 relative" data-komoditas-id="{{ $item->komoditas_id }}">
                    <td class="border border-slate-200 px-1 py-1 text-center bg-slate-50/30">{{ $index + 1 }}</td>
                    <td class="border border-slate-200 px-2 py-1 font-semibold text-slate-800">{{ $item->komoditas_nama }}</td>
                    <td class="border border-slate-200 px-1 py-1 text-center text-slate-500 font-bold uppercase truncate">{{ $item->satuan_nama ?? 'UNIT' }}</td>
                    
                    {{-- POPULASI (META) --}}
                    {{-- (5) Pop Awal --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][populasi_awal]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['populasi_awal'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="populasi_awal" data-is-meta="true">
                    </td>
                    {{-- (4) Pop Akhir --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][populasi_akhir]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['populasi_akhir'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="populasi_akhir" data-is-meta="true">
                    </td>
                    {{-- (6) Eks --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][populasi_ekspor]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['populasi_ekspor'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="populasi_ekspor" data-is-meta="true">
                    </td>
                    {{-- (7) Imp --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][populasi_impor]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['populasi_impor'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="populasi_impor" data-is-meta="true">
                    </td>
                    {{-- (8) Jml: (5)-(4)+(6)-(7) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-blue-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][populasi_jumlah]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['populasi_jumlah'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right bg-transparent border-none font-bold" data-key="populasi_jumlah" data-is-meta="true" data-formula="(populasi_awal || 0) - (populasi_akhir || 0) + (populasi_ekspor || 0) - (populasi_impor || 0)">
                    </td>

                    {{-- PEMOTONGAN (META) --}}
                    {{-- (9) Daging --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][berat_daging]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['berat_daging'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="berat_daging" data-is-meta="true">
                    </td>
                    {{-- (10) Karkas --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][konversi_karkas]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['konversi_karkas'] ?? 1, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="konversi_karkas" data-is-meta="true">
                    </td>
                    {{-- (11) Pot: (9)/(10) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-orange-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][pemotongan]" value="{{ \App\Helpers\PdrbHelper::formatId($meta['pemotongan'] ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right bg-transparent border-none font-bold" data-key="pemotongan" data-is-meta="true" data-formula="(berat_daging || 0) / (konversi_karkas || 1)">
                    </td>

                    {{-- KUANTUM (12) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-blue-100/20">
                        <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]" value="{{ \App\Helpers\PdrbHelper::formatId($item->kuantum ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-blue-700" data-key="kuantum" data-formula="(populasi_jumlah || 0) + (pemotongan || 0)">
                    </td>

                    {{-- HARGA (13) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]" value="{{ \App\Helpers\PdrbHelper::formatId($item->harga_produsen ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="harga_produsen">
                    </td>

                    {{-- NILAI PRODUKSI (14) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-green-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_utama ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold" data-key="nilai_output_utama" data-formula="((kuantum || 0) * (harga_produsen || 0)) / 1000000">
                    </td>

                    {{-- OUTPUT IKUTAN (15, 16) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_output_ikut]" value="{{ \App\Helpers\PdrbHelper::formatId($item->rasio_output_ikut ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_output_ikut">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-purple-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_ikut]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_ikut ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="nilai_output_ikut" data-formula="(nilai_output_utama || 0) * (rasio_output_ikut || 0)">
                    </td>

                    {{-- OUTPUT TOTAL (17) --}}
                    <td class="border border-slate-200 px-1 py-1 bg-blue-50/50">
                        <input type="text" name="items[{{ $item->komoditas_id }}][output_adh]" value="{{ \App\Helpers\PdrbHelper::formatId($item->output_adh ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold" data-key="output_adh" data-formula="(nilai_output_utama || 0) + (nilai_output_ikut || 0)">
                    </td>

                    {{-- KONSUMSI ANTARA (18, 19) --}}
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][rasio_konsumsi_antara]" value="{{ \App\Helpers\PdrbHelper::formatId($item->rasio_konsumsi_antara ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="rasio_konsumsi_antara">
                    </td>
                    <td class="border border-slate-200 px-1 py-1 bg-red-50/30">
                        <input type="text" name="items[{{ $item->komoditas_id }}][konsumsi_antara]" value="{{ \App\Helpers\PdrbHelper::formatId($item->konsumsi_antara ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="konsumsi_antara" data-formula="(output_adh || 0) * (rasio_konsumsi_antara || 0)">
                    </td>

                    {{-- NTB --}}
                    <td class="border border-slate-200 px-1 py-1 bg-green-100/50">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_ntb ?? 0, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-green-700" data-key="nilai_ntb" data-formula="(output_adh || 0) - (konsumsi_antara || 0)">
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-slate-200 font-bold text-center text-[11px]">
                <td colspan="3" class="border border-slate-300 px-2 py-2 text-right">TOTAL (JUTA RP)</td>
                <td colspan="5" class="border border-slate-300"></td>
                <td colspan="3" class="border border-slate-300"></td>
                <td class="border border-slate-300"></td>
                <td class="border border-slate-300"></td>
                <td class="border border-slate-300 px-1 py-2 text-right text-blue-800" {{ $totalPrefix }}="nilai_output_utama">0</td>
                <td colspan="2" class="border border-slate-300"></td>
                <td class="border border-slate-300 px-1 py-2 text-right text-blue-800" {{ $totalPrefix }}="output_adh">0</td>
                <td colspan="2" class="border border-slate-300"></td>
                <td class="border border-slate-300 px-1 py-2 text-right text-green-800 font-bold" {{ $totalPrefix }}="nilai_ntb">0</td>
            </tr>
        </tfoot>
    </table>
</div>