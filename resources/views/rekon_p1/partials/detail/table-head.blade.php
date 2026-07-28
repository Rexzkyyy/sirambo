<thead class="bg-gray-50">
    <!-- BARIS 1: NAMA WILAYAH + KOLOM PER TAHUN & PERIODE -->
    <tr class="text-gray-900 text-center font-bold border-b border-gray-300 sticky-header-row">
        <th rowspan="4"
            class="border border-gray-300 px-3 py-2 text-left min-w-[200px] md:min-w-[250px] text-xs md:text-sm bg-white sticky-left">
            Provinsi / Kabupaten / Kota
        </th>
        @foreach ($tahunIdsDisplay as $idTahun)
            @php
                $tahunLabel = $tahunMap[$idTahun] ?? '-';
            @endphp
            <!-- PERIODE TRIWULAN -->
            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                <th colspan="16" class="border border-gray-300 px-1 py-2 text-[10px] md:text-xs bg-white whitespace-nowrap">
                    {{ $tahunLabel }} {{ $periode->nama_periode }}
                </th>
            @endforeach
            <!-- TOTAL PER TAHUN -->
            <th colspan="16" class="border border-gray-300 px-1 py-2 text-[10px] md:text-xs bg-yellow-50 whitespace-nowrap">
                TOTAL {{ $tahunLabel }}
            </th>
        @endforeach
    </tr>

    <!-- BARIS 2: BERLAKU & KONSTAN + PERTUMBUHAN -->
    <tr class="text-gray-900 text-center font-bold text-[10px] md:text-xs border-b border-gray-300 sticky-header-row">
        @foreach ($tahunIdsDisplay as $idTahun)
            <!-- Untuk setiap periode triwulan -->
            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                <th colspan="3" class="border border-gray-300 px-0.5 py-1 bg-white">Berlaku</th>
                <th colspan="3" class="border border-gray-300 px-0.5 py-1 bg-white">Konstan</th>
                <th colspan="10" class="border border-gray-300 px-0.5 py-1 bg-white">Pertumbuhan & Implisit</th>
            @endforeach
            <!-- Untuk TOTAL tahunan -->
            <th colspan="3" class="border border-gray-300 px-0.5 py-1 bg-yellow-50">Berlaku</th>
            <th colspan="3" class="border border-gray-300 px-0.5 py-1 bg-yellow-50">Konstan</th>
            <th colspan="10" class="border border-gray-300 px-0.5 py-1 bg-yellow-50">Pertumbuhan & Implisit</th>
        @endforeach
    </tr>

    <!-- BARIS 3: HEADER DETAIL KOLOM -->
    <tr
        class="text-gray-900 text-center font-semibold text-[9px] md:text-[10px] border-b border-gray-300 sticky-header-row">
        @foreach ($tahunIdsDisplay as $idTahun)
            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                <!-- BERLAKU -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white">Adj</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white">PDRB+Adj</th>

                <!-- KONSTAN -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white">Adj</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white">PDRB+Adj</th>

                <!-- PERTUMBUHAN DAN IMPLISIT -->
                <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">Pertumbuhan<br>Q-to-Q</th>
                <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">Pertumbuhan<br>Y-on-Y</th>
                <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">Pertumbuhan<br>C-to-C</th>
                <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">Indeks<br>Implisit</th>
                <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">Laju<br>Implisit</th>
            @endforeach
            <!-- HEADER DETAIL KOLOM UNTUK TOTAL TAHUNAN -->
            <!-- BERLAKU -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">Adj</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">PDRB+Adj</th>

            <!-- KONSTAN -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">Adj</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50">PDRB+Adj</th>

            <!-- PERTUMBUHAN DAN IMPLISIT -->
            <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">Pertumbuhan<br>Q-to-Q
            </th>
            <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">Pertumbuhan<br>Y-on-Y
            </th>
            <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">Pertumbuhan<br>C-to-C
            </th>
            <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">Indeks<br>Implisit
            </th>
            <th colspan="2" class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">Laju<br>Implisit</th>
        @endforeach
    </tr>

    <!-- BARIS 4: SUB-HEADER UNTUK PERTUMBUHAN -->
    <tr
        class="text-gray-900 text-center font-semibold text-[8px] md:text-[9px] border-b border-gray-300 sticky-header-row">
        @foreach ($tahunIdsDisplay as $idTahun)
            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                <!-- BERLAKU - 3 kolom -->
                <th colspan="3" class="bg-white"></th>

                <!-- KONSTAN - 3 kolom -->
                <th colspan="3" class="bg-white"></th>

                <!-- PERTUMBUHAN Q-to-Q - 2 kolom -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB+Adj</th>

                <!-- PERTUMBUHAN Y-on-Y - 2 kolom -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB+Adj</th>

                <!-- PERTUMBUHAN C-to-C - 2 kolom -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB+Adj</th>

                <!-- INDEKS IMPLISIT - 2 kolom -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB+Adj</th>

                <!-- LAJU IMPLISIT - 2 kolom -->
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB</th>
                <th class="border border-gray-300 px-0.5 py-1 bg-white whitespace-nowrap">PDRB+Adj</th>
            @endforeach
            <!-- SUB-HEADER UNTUK TOTAL TAHUNAN -->
            <!-- BERLAKU - 3 kolom -->
            <th colspan="3" class="bg-yellow-50"></th>

            <!-- KONSTAN - 3 kolom -->
            <th colspan="3" class="bg-yellow-50"></th>

            <!-- PERTUMBUHAN Q-to-Q - 2 kolom -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB+Adj</th>

            <!-- PERTUMBUHAN Y-on-Y - 2 kolom -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB+Adj</th>

            <!-- PERTUMBUHAN C-to-C - 2 kolom -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB+Adj</th>

            <!-- INDEKS IMPLISIT - 2 kolom -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB+Adj</th>

            <!-- LAJU IMPLISIT - 2 kolom -->
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB</th>
            <th class="border border-gray-300 px-0.5 py-1 bg-yellow-50 whitespace-nowrap">PDRB+Adj</th>
        @endforeach
    </tr>
</thead>