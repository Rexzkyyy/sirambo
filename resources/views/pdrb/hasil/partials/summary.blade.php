@if(request('id_tahun') || request('id_periode') || request('scope_wilayah') || request('rentang_tahun'))
    <div class="bg-white shadow rounded-xl px-4 py-3 w-full xl:w-72 shrink-0">
        <h3 class="font-semibold text-sm md:text-base text-gray-800 leading-tight">
            @if($selectedTahun && !request('rentang_tahun'))
                Tahun {{ optional($tahun->firstWhere('id_tahun', $selectedTahun))->tahun ?? $selectedTahun }}
            @elseif(request('rentang_tahun'))
                @php
                    $rentangArray = explode('-', request('rentang_tahun'));
                    $tahunAwal = $rentangArray[0] ?? '';
                    $tahunAkhir = $rentangArray[1] ?? '';
                @endphp
                Tahun {{ $tahunAwal }} - {{ $tahunAkhir }}
            @else
                Semua Tahun
            @endif
            @if($selectedPeriode)
                - {{ optional($periode->firstWhere('id_periode', $selectedPeriode))->nama_periode ?? $selectedPeriode }}
            @else
                - Semua Periode
            @endif
        </h3>
        <p class="text-xs text-gray-600 leading-tight">
            @php
                $typeLabels = [
                    'berlaku' => 'ADHB (Berlaku)',
                    'konstan' => 'ADHK (Konstan)',
                    'y-on-y' => 'Y-on-Y',
                    'q-to-q' => 'Q-to-Q',
                    'c-to-c' => 'C-to-C',
                    'laju' => 'Laju Implisit',
                    'indeks' => 'Indeks Implisit',
                    'distribusi' => 'Distribusi',
                ];
                $typeLabel = $typeLabels[$typePdrb] ?? 'ADHB (Berlaku)';
            @endphp
            {{ $typeLabel }}
            @if($selectedWilayahs->count() > 0)
                ({{ $selectedWilayahs->first()->nama_wilayah ?? 'Wilayah' }})
            @endif
        </p>
    </div>
@endif
