@php
    $rowsId = $tipePdrb === 'berlaku' ? 'lk-rows-berlaku' : 'lk-rows-konstan';
    $totalPrefix = $tipePdrb === 'berlaku' ? 'data-total' : 'data-total-konstan';
@endphp

<div class="lk-table-scroll shadow-sm border border-slate-200 rounded-xl flex-1">
    <table class="w-full text-xs relative lk-sticky-table">
        <thead class="bg-slate-100 text-slate-700 sticky top-0 z-10 shadow-[0_1px_0_0_rgba(0,0,0,0.1)]">
            <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[250px] text-center">Subkategori/Jenis Kegiatan/Uraian Komoditi</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[180px] text-center">Nilai Produksi Utama ADHB Subkategori (Juta Rp)</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[150px] text-center">Rasio Jasa Pertanian Konstan</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[180px] text-center bg-blue-50/50">Nilai Jasa Pertanian (3) X (4)</th>
                <th colspan="2" class="border border-slate-300 px-2 py-2 text-center bg-orange-50/50">Konsumsi Antara</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[180px] text-center bg-slate-50">Nilai Tambah Bruto (Juta Rp) (5)-(7)</th>
                <th rowspan="2" class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
            </tr>
            <tr class="text-[11px] uppercase tracking-wide">
                <th class="border border-slate-300 px-2 py-2 min-w-[120px] text-center bg-orange-50/30">Rasio Tahun 2010</th>
                <th class="border border-slate-300 px-2 py-2 min-w-[150px] text-center bg-orange-50/30">Nilai (Juta Rp) (6)x(5)</th>
            </tr>
        </thead>
        <tbody id="{{ $rowsId }}" data-next-row="{{ $itemRows->count() }}">
            @forelse($itemRows as $index => $item)
                @php
                    $meta = is_string($item->data_tambahan) ? json_decode($item->data_tambahan, true) : ($item->data_tambahan ?? []);
                @endphp
                <tr class="hover:bg-orange-50/40" data-template="jasa_pertanian">
                    <td class="border border-slate-200 px-2 py-2 text-center">
                        {{ $index + 1 }}
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 font-semibold bg-yellow-50/10">
                        <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId(($item->nilai_output_utama ?? 0) / 1000000, 2) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="output_utama">
                    </td>
                    <td class="border border-slate-200 px-1 py-1">
                        <input type="text" name="items[{{ $item->komoditas_id }}][meta][rasio_jasa]" 
                            value="{{ \App\Helpers\PdrbHelper::formatId($meta['rasio_jasa'] ?? 0, 4) }}" 
                            class="lk-input w-full px-1 py-1 text-right border border-slate-100 rounded number-format" data-key="rasio_jasa">
                    </td>
                    <td class="border border-slate-200 px-2 py-1.5 text-right font-bold bg-blue-50/20" data-calc="nilai_jasa">0</td>
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
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][output_adh]" value="{{ $item->output_adh }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ $item->nilai_ntb }}">
                    <input type="hidden" name="items[{{ $item->komoditas_id }}][konsumsi_antara]" value="{{ $item->konsumsi_antara }}">
                </tr>
            @empty
                <tr><td colspan="9" class="border border-slate-200 px-3 py-6 text-center text-slate-500">Belum ada komoditas untuk wilayah ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-slate-100 font-semibold">
                <td colspan="4" class="border border-slate-300 px-2 py-2 text-right">Total</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40" {{ $totalPrefix }}="nilai_output_utama">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-orange-50/40" {{ $totalPrefix }}="konsumsi_antara">0</td>
                <td class="border border-slate-300 px-2 py-2 text-right bg-slate-50" {{ $totalPrefix }}="nilai_ntb">0</td>
                <td class="border border-slate-300 px-2 py-2"></td>
            </tr>
        </tfoot>
    </table>
</div>
