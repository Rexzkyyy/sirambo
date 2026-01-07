<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Input Nilai PDRB</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans flex">

    @include('components.navbar')

    <div class="flex-1 max-w-6xl mx-auto py-10">

        <h2 class="text-3xl font-bold text-center mb-8 text-blue-700">Hasil Input Nilai PDRB</h2>

<div class="bg-white shadow-md rounded-lg p-6 mb-8">

    <form method="GET" action="{{ route('pdrb.hasil') }}"
          class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">
        <div>
            <label class="block mb-1 font-medium">Tahun</label>
            <select name="id_tahun"
                class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500">
                <option value="">-- Pilih Tahun --</option>
                @foreach($tahun as $t)
                    <option value="{{ $t->id_tahun }}"
                        {{ request('id_tahun') == $t->id_tahun ? 'selected' : '' }}>
                        {{ $t->tahun }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1 font-medium">Periode</label>
            <select name="id_periode"
                class="border border-gray-300 rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500">
                <option value="">-- Pilih Periode --</option>
                @foreach($periode as $p)
                    <option value="{{ $p->id_periode }}"
                        {{ request('id_periode') == $p->id_periode ? 'selected' : '' }}>
                        {{ $p->nama_periode }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                Tampilkan
            </button>
        </div>

        <div></div>
    </form>

    <div class="flex justify-end">
        <a href="{{ route('pdrb.hasil.pertahun', ['id_tahun' => request('id_tahun')]) }}"
           class="inline-block bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 transition">
            Lihat Hasil Per Tahun
        </a>
    </div>

</div>

        @if($selectedTahun && $selectedPeriode)

<div class="bg-white shadow-md rounded-lg p-4 overflow-x-auto">
    <table class="w-full border border-gray-200 text-sm table-fixed">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 border-b text-left w-1/4">
                    Kategori
                </th>
                <th class="px-3 py-2 border-b text-left w-1/4">
                    Sub Kategori
                </th>
                <th class="px-3 py-2 border-b text-right w-1/2">
                    Nilai
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($kategori as $k)
                @php
                    $nk = $nilaiKategori->where('id_kategori', $k->id_kategori)
                                         ->where('id_tahun', $selectedTahun)
                                         ->where('id_periode', $selectedPeriode)
                                         ->first();

                    $subs = $sub->where('id_kategori', $k->id_kategori);
                @endphp

                <tr class="bg-gray-50 font-semibold">
                    <td class="px-3 py-1.5 border-b">
                        {{ $k->nama_kategori }}
                    </td>
                    <td class="px-3 py-1.5 border-b text-gray-400 text-center">
                        —
                    </td>
                    <td class="px-3 py-1.5 border-b text-right whitespace-nowrap">
                        {{ number_format($nk->nilai ?? 0, 1, ',', '.') }}
                    </td>
                </tr>

                @foreach($subs as $s)
                    @php
                        $ns = $nilaiSub->where('id_sub_kategori', $s->id_sub_kategori)
                                       ->where('id_tahun', $selectedTahun)
                                       ->where('id_periode', $selectedPeriode)
                                       ->first();
                    @endphp
                    <tr>
                        <td class="px-3 py-1.5 border-b"></td>
                        <td class="px-3 py-1.5 border-b pl-4">
                            {{ $s->nama_sub_kategori }}
                        </td>
                        <td class="px-3 py-1.5 border-b text-right whitespace-nowrap">
                            {{ number_format($ns->nilai ?? 0, 1, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

        @else
            <p class="text-center text-gray-500 italic">Pilih Tahun dan Periode untuk menampilkan hasil.</p>
        @endif
    </div>
</body>
</html>
