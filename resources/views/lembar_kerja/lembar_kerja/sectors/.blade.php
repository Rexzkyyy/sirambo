<!-- Sector:  -->
<x-lk-sector-table :itemRows="$itemRows" :tipePdrb="$tipePdrb">
    <x-slot name="headerSlot">
        <tr class="text-[11px] uppercase tracking-wide bg-slate-100 border-b border-slate-300">
            <th class="border border-slate-300 px-2 py-2 w-10 text-center">No.</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[200px] text-center">Uraian Komoditi</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center">Satuan/Unit</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50">Kuantum</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50">Harga Produsen</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50">Nilai Output Utama</th>
            <th class="border border-slate-300 px-2 py-2 min-w-[100px] text-center bg-slate-50 text-blue-700">NTB</th>
            <th class="border border-slate-300 px-2 py-2 w-16 text-center">Aksi</th>
        </tr>
    </x-slot>

    @foreach($itemRows as $index => $item)
        <tr class="hover:bg-slate-50/40">
            <td class="border border-slate-200 px-2 py-2 text-center">{{ $index + 1 }}</td>
            <td class="border border-slate-200 px-2 py-1.5 font-semibold">
                <input type="text" name="items[{{ $item->komoditas_id }}][komoditas_nama]" value="{{ $item->komoditas_nama }}" class="lk-input w-full border-none focus:ring-0 p-0 bg-transparent">
            </td>
            <td class="border border-slate-200 px-2 py-2 text-center text-slate-500">{{ $item->satuan_nama ?? 'UNIT' }}</td>
            <td class="border border-slate-200 px-1 py-1">
                <input type="text" name="items[{{ $item->komoditas_id }}][kuantum]" value="{{ \App\Helpers\PdrbHelper::formatId($item->kuantum, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="kuantum">
            </td>
             <td class="border border-slate-200 px-1 py-1">
                <input type="text" name="items[{{ $item->komoditas_id }}][harga_produsen]" value="{{ \App\Helpers\PdrbHelper::formatId($item->harga_produsen, 4) }}" class="lk-input lk-dynamic-input w-full text-right" data-key="harga_produsen">
            </td>
            <td class="border border-slate-200 px-1 py-1 bg-slate-50">
                <input type="text" name="items[{{ $item->komoditas_id }}][nilai_output_utama]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_output_utama, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold" data-key="nilai_output_utama" data-formula="([kuantum]*[harga_produsen])" readonly tabindex="-1">
            </td>
            <td class="border border-slate-200 px-1 py-1 bg-blue-50">
                <input type="text" name="items[{{ $item->komoditas_id }}][nilai_ntb]" value="{{ \App\Helpers\PdrbHelper::formatId($item->nilai_ntb, 4) }}" class="lk-input lk-dynamic-input w-full text-right font-bold text-blue-800" data-key="nilai_ntb" data-formula="[nilai_output_utama]" readonly tabindex="-1">
            </td>
            <td class="border border-slate-200 px-2 py-1.5 text-center">
                <button type="button" data-delete-komoditas="{{ $item->komoditas_id }}" class="px-2 py-1 text-[10px] font-bold rounded bg-red-500 text-white hover:bg-red-600">Hapus</button>
            </td>
        </tr>
    @endforeach

    <x-slot name="footerSlot">
        <tr class="bg-slate-100 font-semibold text-xs text-center">
            <td colspan="3" class="border border-slate-300 px-2 py-2 text-right">Total</td>
            <td class="border border-slate-300 px-2 py-2 text-right text-transparent" data-total="kuantum">0</td>
            <td class="border border-slate-300 px-2 py-2 text-right text-transparent" data-total="harga_produsen">0</td>
            <td class="border border-slate-300 px-2 py-2 text-right bg-blue-50/40 text-blue-800" data-total="nilai_output_utama">0</td>
            <td class="border border-slate-300 px-2 py-2 text-right bg-blue-100 text-blue-900 font-bold" data-total="nilai_ntb">0</td>
            <td class="border border-slate-300 px-2 py-2"></td>
        </tr>
    </x-slot>
</x-lk-sector-table>