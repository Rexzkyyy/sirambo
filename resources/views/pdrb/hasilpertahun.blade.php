<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil PDRB Per Tahun</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans flex">

@include('components.navbar')

<div class="flex-1 max-w-7xl mx-auto py-10">

    <h2 class="text-3xl font-bold text-center mb-8 text-blue-700">
        Hasil PDRB Per Tahun
    </h2>

    <!-- PILIH TAHUN -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <form method="GET" action="{{ route('pdrb.hasil.pertahun') }}"
              class="flex flex-col md:flex-row gap-4 items-end">

            <div class="flex-1">
                <label class="block mb-1 font-medium">Tahun</label>
                <select name="id_tahun"
                        class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Tahun --</option>
                    @foreach($tahun as $t)
                        <option value="{{ $t->id_tahun }}"
                            {{ request('id_tahun') == $t->id_tahun ? 'selected' : '' }}>
                            {{ $t->tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Tampilkan
            </button>
        </form>
    </div>

    @if($selectedTahun)
    <!-- TABEL -->
    <div class="bg-white shadow rounded-lg p-6 overflow-x-auto">
        <table class="min-w-full border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-3 py-2 w-48">Kategori / Sub</th>
                    @foreach($periode as $p)
                        <th class="border px-3 py-2 text-center w-24">
                            {{ $p->nama_periode }}
                        </th>
                    @endforeach
                    <th class="border px-3 py-2 text-center w-28">Total</th>
                </tr>
            </thead>

            <tbody>
            @foreach($kategori as $k)

                <!-- KATEGORI -->
                <tr class="bg-gray-50 font-semibold">
                    <td class="border px-3 py-2">{{ $k->nama_kategori }}</td>

                    @php $totalKategori = 0; @endphp

                    @foreach($periode as $p)
                        @php
                            $sum = $nilaiSub
                                ->where('id_periode', $p->id_periode)
                                ->whereIn('id_sub_kategori',
                                    $sub->where('id_kategori', $k->id_kategori)
                                        ->pluck('id_sub_kategori')
                                )->sum('nilai');

                            $totalKategori += $sum;
                        @endphp
                        <td class="border px-3 py-2 text-right">
                            {{ number_format($sum,1,',','.') }}
                        </td>
                    @endforeach

                    <td class="border px-3 py-2 text-right font-bold">
                        {{ number_format($totalKategori,1,',','.') }}
                    </td>
                </tr>

                <!-- SUB KATEGORI -->
                @foreach($sub->where('id_kategori', $k->id_kategori) as $s)
                <tr>
                    <td class="border px-3 py-2 pl-6 text-gray-700">
                        {{ $s->nama_sub_kategori }}
                    </td>

                    @php $totalSub = 0; @endphp

                    @foreach($periode as $p)
                        @php
                            $nilai = $nilaiSub
                                ->where('id_sub_kategori', $s->id_sub_kategori)
                                ->where('id_periode', $p->id_periode)
                                ->first()->nilai ?? 0;

                            $totalSub += $nilai;
                        @endphp
                        <td class="border px-3 py-2 text-right">
                            {{ number_format($nilai,1,',','.') }}
                        </td>
                    @endforeach

                    <td class="border px-3 py-2 text-right font-semibold">
                        {{ number_format($totalSub,1,',','.') }}
                    </td>
                </tr>
                @endforeach

            @endforeach
            </tbody>
        </table>
    </div>
    @else
        <p class="text-center text-gray-500 italic">
            Silakan pilih tahun untuk melihat hasil.
        </p>
    @endif

</div>
</body>
</html>
