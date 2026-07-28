<tbody>
                    @php
                        // Helper PHP closure untuk merender popup status (sama seperti fungsi JS)
                        $renderStatusPopup = function($status) {
                            return '';
                        };
                        
                        // Helper untuk menentukan apakah total kab/kota adalah extreme
                        $isTotalKabExtreme = function($type, $kabValue, $provValue, $rank, $idTahun) {
                            $kab = floatval($kabValue ?? 0);
                            $prov = floatval($provValue ?? 0);
                            if ($kab == 0 || $prov == 0) return false;
                            $diff = abs($kab - $prov);
                            return $diff >= 5;
                        };
                        
                        // Helper untuk label status
                        $statusLabel = function($category) {
                            if (!$category) return '';
                            if ($category === 'extreme_beda_arah') return 'Extreme Beda Arah';
                            if ($category === 'extreme') return 'Extreme';
                            if ($category === 'beda_arah') return 'Beda Arah';
                            return '';
                        };
                        
                        // Threshold diff default
                        $diffThreshold = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran' ? 4 : 5;
                    @endphp
                    <!-- DISKREPANSI -->
                    @if ($provinsi && $totalKabKota)
                    <tr class="font-bold border-b border-gray-300 bg-gray-50">
                        <td class="border border-gray-300 px-3 py-2 sticky left-0 sticky-left bg-gray-50 z-10 pl-8 text-xs md:text-sm">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                                </svg>
                                <span class="truncate">DISKREPANSI</span>
                            </div>
                        </td>
                        @foreach ($tahunIdsDisplay as $idTahun)
                            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                                @php
                                    $periodeNama = $periode->nama_periode;
                                    $dataDiskrepansi = $calculations['diskrepansi'][$idTahun][$periodeNama] ?? null;
                                @endphp
                                
                                <!-- Kolom Berlaku -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-gray-50 {{ isset($dataDiskrepansi['pdrb_berlaku']) && abs($dataDiskrepansi['pdrb_berlaku']) > 1e-5 ? 'text-red-600' : '' }}"
                                    id="diskrepansi-pdrb-berlaku-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $dataDiskrepansi['pdrb_berlaku'] ?? 0 }}">
                                    {{ $dataDiskrepansi ? fmt($dataDiskrepansi['pdrb_berlaku'], 9) : '-' }}
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-gray-50 {{ isset($dataDiskrepansi['pdrb_adj_berlaku']) && abs($dataDiskrepansi['pdrb_adj_berlaku']) > 1e-5 ? 'text-red-600' : '' }}"
                                    id="diskrepansi-pdrb-adj-berlaku-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $dataDiskrepansi['pdrb_adj_berlaku'] ?? 0 }}">
                                    {{ $dataDiskrepansi ? fmt($dataDiskrepansi['pdrb_adj_berlaku'], 9) : '-' }}
                                </td>
                                
                                <!-- Kolom Konstan -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-gray-50 {{ isset($dataDiskrepansi['pdrb_konstan']) && abs($dataDiskrepansi['pdrb_konstan']) > 1e-5 ? 'text-red-600' : '' }}"
                                    id="diskrepansi-pdrb-konstan-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $dataDiskrepansi['pdrb_konstan'] ?? 0 }}">
                                    {{ $dataDiskrepansi ? fmt($dataDiskrepansi['pdrb_konstan'], 9) : '-' }}
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-gray-50 {{ isset($dataDiskrepansi['pdrb_adj_konstan']) && abs($dataDiskrepansi['pdrb_adj_konstan']) > 1e-5 ? 'text-red-600' : '' }}"
                                    id="diskrepansi-pdrb-adj-konstan-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $dataDiskrepansi['pdrb_adj_konstan'] ?? 0 }}">
                                    {{ $dataDiskrepansi ? fmt($dataDiskrepansi['pdrb_adj_konstan'], 9) : '-' }}
                                </td>
                                
                                <!-- Pertumbuhan Q-to-Q (PDRB & PDRB+Adj) -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                
                                <!-- Pertumbuhan Y-on-Y (PDRB & PDRB+Adj) -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                
                                <!-- Pertumbuhan C-to-C (PDRB & PDRB+Adj) -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                
                                <!-- Indeks Implisit (PDRB & PDRB+Adj) -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                
                                <!-- Laju Implisit (PDRB & PDRB+Adj) -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                            @endforeach
                            <!-- TOTAL TAHUNAN DISKREPANSI -->
                            @for ($i = 0; $i < 16; $i++)
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                            @endfor
                        @endforeach
                    </tr>

                    <!-- DISKREPANSI (%) DENGAN WARNA KONDISIONAL -->
                    <tr class="font-bold border-b border-gray-300 bg-gray-50">
                        <td class="border border-gray-300 px-3 py-2 sticky left-0 sticky-left bg-gray-50 z-10 pl-8 text-xs md:text-sm">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L11 10.586V7z" clip-rule="evenodd"/>
                                </svg>
                                <span class="truncate">DISKREPANSI (%)</span>
                            </div>
                        </td>
                        @foreach ($tahunIdsDisplay as $idTahun)
                            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                                @php
                                    $periodeNama = $periode->nama_periode;
                                    $dataDiskrepansiPersen = $calculations['diskrepansi_persen'][$idTahun][$periodeNama] ?? null;
                                    $persenPdrbBerlaku = $dataDiskrepansiPersen['pdrb_berlaku'] ?? 0;
                                    $persenPdrbAdjBerlaku = $dataDiskrepansiPersen['pdrb_adj_berlaku'] ?? 0;
                                    $persenPdrbKonstan = $dataDiskrepansiPersen['pdrb_konstan'] ?? 0;
                                    $persenPdrbAdjKonstan = $dataDiskrepansiPersen['pdrb_adj_konstan'] ?? 0;
                                    
                                    $getColorClass = function($value) {
                                        $absValue = abs($value);
                                        if ($absValue >= 5) return 'text-red-600 bg-red-strong';
                                        if ($absValue >= 2 && $absValue < 5) return 'text-yellow-600 bg-yellow-strong';
                                        return 'text-gray-600';
                                    };
                                    
                                    $formatValue = function($value) {
                                        return $value != 0 ? number_format($value, 2, ',', '.') . '%' : '-';
                                    };
                                @endphp
                                
                                <!-- Kolom Berlaku PDRB -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm {{ $getColorClass($persenPdrbBerlaku) }}"
                                    id="diskrepansi-persen-pdrb-berlaku-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $persenPdrbBerlaku }}">
                                    {{ $formatValue($persenPdrbBerlaku) }}
                                </td>
                                
                                <!-- Kolom Adj Berlaku -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                
                                <!-- Kolom Berlaku PDRB+Adj -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm {{ $getColorClass($persenPdrbAdjBerlaku) }}"
                                    id="diskrepansi-persen-pdrb-adj-berlaku-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $persenPdrbAdjBerlaku }}">
                                    {{ $formatValue($persenPdrbAdjBerlaku) }}
                                </td>
                                
                                <!-- Kolom Konstan PDRB -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm {{ $getColorClass($persenPdrbKonstan) }}"
                                    id="diskrepansi-persen-pdrb-konstan-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $persenPdrbKonstan }}">
                                    {{ $formatValue($persenPdrbKonstan) }}
                                </td>
                                
                                <!-- Kolom Adj Konstan -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                
                                <!-- Kolom Konstan PDRB+Adj -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm {{ $getColorClass($persenPdrbAdjKonstan) }}"
                                    id="diskrepansi-persen-pdrb-adj-konstan-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $persenPdrbAdjKonstan }}">
                                    {{ $formatValue($persenPdrbAdjKonstan) }}
                                </td>
                                
                                <!-- Sisa kolom lainnya tetap sama -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                            @endforeach
                            <!-- TOTAL TAHUNAN DISKREPANSI (%) -->
                            @for ($i = 0; $i < 16; $i++)
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold"></td>
                            @endfor
                        @endforeach
                    </tr>
                    @endif

                    <!-- PROVINSI -->
                    @if ($provinsi)
                    <tr class="font-bold hover:bg-gray-50 border-b border-gray-300" data-sign-row="1">
                        <td class="border border-gray-300 px-3 py-2 sticky left-0 sticky-left z-10 text-xs md:text-sm hover:bg-gray-50">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="truncate">{{ $provinsi['wilayah'] }}</span>
                            </div>
                        </td>
                        @foreach ($tahunIdsDisplay as $idTahun)
                            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                                @php
                                    $periodeNama = $periode->nama_periode;
                                    $data = $calculations['provinsi'][$idTahun][$periodeNama] ?? null;
                                    $display = $data['display'] ?? [];
                                @endphp
                                
                                <!-- Kolom Berlaku -->
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-white hover:bg-gray-50"
                                    id="berlaku-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['berlaku'] ?? 0 }}">
                                    {{ $display['berlaku'] ?? '-' }}
                                </td>
                                
                                <!-- Kolom Adj Berlaku untuk PROVINSI -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-white hover:bg-yellow-strong"
                                    id="pdrb-plus-adj-berlaku-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['berlaku_plus_adj'] ?? 0 }}">
                                    {{ $display['berlaku_plus_adj'] ?? '-' }}
                                </td>
                                
                                <!-- Kolom Konstan -->
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-white hover:bg-gray-50"
                                    id="konstan-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['konstan'] ?? 0 }}">
                                    {{ $display['konstan'] ?? '-' }}
                                </td>
                                
                                <!-- Kolom Adj Konstan untuk PROVINSI -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                                </td>
                                
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-white hover:bg-yellow-strong"
                                    id="pdrb-plus-adj-konstan-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['konstan_plus_adj'] ?? 0 }}">
                                    {{ $display['konstan_plus_adj'] ?? '-' }}
                                </td>
                                
                                <!-- Pertumbuhan Q-to-Q -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="qoq-konstan-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['qoq_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                           {{ $display['qoq_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['qoq_bg'] ?: 'bg-white' }} {{ $display['qoq_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="qoq-konstan-plus-adj-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['qoq_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $display['qoq_status'] ? 'Status: ' . $display['qoq_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                       {{ $display['qoq_konstan_plus_adj'] }}
                                    </div>
                                    {!! $display['qoq_popup'] !!}
                                </td>
                                
                                <!-- Pertumbuhan Y-on-Y -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="yoy-konstan-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['yoy_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['yoy_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['yoy_bg'] ?: 'bg-white' }} {{ $display['yoy_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="yoy-konstan-plus-adj-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['yoy_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $display['yoy_status'] ? 'Status: ' . $display['yoy_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                     {{ $display['yoy_konstan_plus_adj'] }}
                                    </div>
                                    {!! $display['yoy_popup'] !!}
                                </td>
                                
                                <!-- Pertumbuhan C-to-C -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="ctoc-konstan-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['ctoc_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['ctoc_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['ctoc_bg'] ?: 'bg-white' }} {{ $display['ctoc_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="ctoc-konstan-plus-adj-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['ctoc_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $display['ctoc_status'] ? 'Status: ' . $display['ctoc_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                       {{ $display['ctoc_konstan_plus_adj'] }}
                                    </div>
                                    {!! $display['ctoc_popup'] !!}
                                </td>
                                
                                <!-- Indeks Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="indeks-berlaku-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['indeks_berlaku'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                           {{ $display['indeks_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['indeks_bg'] ?: 'bg-white' }} {{ $display['indeks_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="indeks-berlaku-plus-adj-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['indeks_berlaku_plus_adj'] ?? 0 }}"
                                    title="{{ $display['indeks_status'] ? 'Status: ' . $display['indeks_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                       {{ $display['indeks_berlaku_plus_adj'] }}
                                    </div>
                                    {!! $display['indeks_popup'] !!}
                                </td>
                                
                                <!-- Laju Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="laju-berlaku-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['laju_berlaku'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['laju_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['laju_bg'] ?: 'bg-white' }} {{ $display['laju_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="laju-berlaku-plus-adj-provinsi-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['laju_berlaku_plus_adj'] ?? 0 }}"
                                    title="{{ $display['laju_status'] ? 'Status: ' . $display['laju_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                       {{ $display['laju_berlaku_plus_adj'] }}
                                    </div>
                                    {!! $display['laju_popup'] !!}
                                </td>
                            @endforeach
                            <!-- TOTAL TAHUNAN PROVINSI -->
                            @php
                                $dataTotalTahun = $calculations['provinsi_total'][$idTahun] ?? ($calculations['provinsi'][$idTahun]['TOTAL'] ?? ($calculations['provinsi'][$idTahun]['total'] ?? ($provinsi['data'][$idTahun]['TOTAL'] ?? null)));
                                $displayProvTotal = $dataTotalTahun['display'] ?? [];
                                
                                // Variabel untuk kolom pertumbuhan dan implisit provinsi total tahunan
                                $qoq_konstan_total = $dataTotalTahun['qoq_konstan'] ?? null;
                                $qoq_konstan_plus_adj_total = $dataTotalTahun['qoq_konstan_plus_adj'] ?? null;
                                $qoq_bg_total = $displayProvTotal['qoq_bg'] ?? '';
                                $qoq_status_total = $displayProvTotal['qoq_status'] ?? '';
                                
                                $yoy_konstan_total = $dataTotalTahun['yoy_konstan'] ?? null;
                                $yoy_konstan_plus_adj_total = $dataTotalTahun['yoy_konstan_plus_adj'] ?? null;
                                $yoy_bg_total = $displayProvTotal['yoy_bg'] ?? '';
                                $yoy_status_total = $displayProvTotal['yoy_status'] ?? '';
                                
                                $ctoc_konstan_total = $dataTotalTahun['ctoc_konstan'] ?? null;
                                $ctoc_konstan_plus_adj_total = $dataTotalTahun['ctoc_konstan_plus_adj'] ?? null;
                                $ctoc_bg_total = $displayProvTotal['ctoc_bg'] ?? '';
                                $ctoc_status_total = $displayProvTotal['ctoc_status'] ?? '';
                                
                                $indeks_berlaku_total = $dataTotalTahun['indeks_berlaku'] ?? null;
                                $indeks_berlaku_plus_adj_total = $dataTotalTahun['indeks_berlaku_plus_adj'] ?? null;
                                $indeks_bg_total = $displayProvTotal['indeks_bg'] ?? '';
                                $indeks_status_total = $displayProvTotal['indeks_status'] ?? '';
                                
                                $laju_berlaku_total = $dataTotalTahun['laju_berlaku'] ?? null;
                                $laju_berlaku_plus_adj_total = $dataTotalTahun['laju_berlaku_plus_adj'] ?? null;
                                $laju_bg_total = $displayProvTotal['laju_bg'] ?? '';
                                $laju_status_total = $displayProvTotal['laju_status'] ?? '';
                            @endphp
                            
                            <!-- Kolom Berlaku TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-strong hover:bg-yellow-strong"
                                id="provinsi-total-berlaku-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahun['berlaku'] ?? 0 }}"
                                title="Provinsi Total Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ ($dataTotalTahun['berlaku'] ?? 0) != 0 ? number_format($dataTotalTahun['berlaku'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Input Adj TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                            </td>
                            
                            <!-- Kolom PDRB+Adj TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-strong hover:bg-yellow-strong"
                                id="provinsi-total-berlaku-plus-adj-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahun['berlaku_plus_adj'] ?? 0 }}"
                                title="Provinsi Total Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahun ? number_format($dataTotalTahun['berlaku_plus_adj'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Kolom Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-konstan-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahun['konstan'] ?? 0 }}"
                                title="Provinsi Total Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahun ? number_format($dataTotalTahun['konstan'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Input Adj Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-gray-200 text-gray-600 font-bold">
                            </td>
                            
                            <!-- Kolom PDRB+Adj Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-konstan-plus-adj-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahun['konstan_plus_adj'] ?? 0 }}"
                                title="Provinsi Total Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahun ? number_format($dataTotalTahun['konstan_plus_adj'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Pertumbuhan dan Implisit untuk TOTAL TAHUNAN -->
                            <!-- Pertumbuhan Q-to-Q -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-qoq-konstan-{{ $idTahun }}"
                                data-value="{{ $qoq_konstan_total }}"
                                title="Provinsi Total QoQ Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahun && $qoq_konstan_total !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($qoq_konstan_total, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $qoq_bg_total ?: 'bg-yellow-50' }} {{ $qoq_bg_total ? '' : 'hover:bg-yellow-100' }}"
                                id="provinsi-total-qoq-konstan-plus-adj-{{ $idTahun }}"
                                data-value="{{ $qoq_konstan_plus_adj_total }}"
                                title="Provinsi Total QoQ Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $qoq_status_total ? ' | Status: ' . $qoq_status_total : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $qoq_konstan_plus_adj_total !== null ? number_format($qoq_konstan_plus_adj_total, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>
                            
                            <!-- Pertumbuhan Y-on-Y -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-yoy-konstan-{{ $idTahun }}"
                                data-value="{{ $yoy_konstan_total }}"
                                title="Provinsi Total YoY Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahun && $yoy_konstan_total !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($yoy_konstan_total, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $yoy_bg_total ?: 'bg-yellow-50' }} {{ $yoy_bg_total ? '' : 'hover:bg-yellow-100' }}"
                                id="provinsi-total-yoy-konstan-plus-adj-{{ $idTahun }}"
                                data-value="{{ $yoy_konstan_plus_adj_total }}"
                                title="Provinsi Total YoY Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $yoy_status_total ? ' | Status: ' . $yoy_status_total : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $yoy_konstan_plus_adj_total !== null ? number_format($yoy_konstan_plus_adj_total, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>
                            
                            <!-- Pertumbuhan C-to-C -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-ctoc-konstan-{{ $idTahun }}"
                                data-value="{{ $ctoc_konstan_total }}"
                                title="Provinsi Total CtC Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahun && $ctoc_konstan_total !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($ctoc_konstan_total, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $ctoc_bg_total ?: 'bg-yellow-50' }} {{ $ctoc_bg_total ? '' : 'hover:bg-yellow-100' }}"
                                id="provinsi-total-ctoc-konstan-plus-adj-{{ $idTahun }}"
                                data-value="{{ $ctoc_konstan_plus_adj_total }}"
                                title="Provinsi Total CtC Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $ctoc_status_total ? ' | Status: ' . $ctoc_status_total : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $ctoc_konstan_plus_adj_total !== null ? number_format($ctoc_konstan_plus_adj_total, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>
                            
                            <!-- Indeks Implisit -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-indeks-berlaku-{{ $idTahun }}"
                                data-value="{{ $indeks_berlaku_total }}"
                                title="Provinsi Total Indeks Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahun && $indeks_berlaku_total !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($indeks_berlaku_total, 2, ',', '.') }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $indeks_bg_total ?: 'bg-yellow-50' }} {{ $indeks_bg_total ? '' : 'hover:bg-yellow-100' }}"
                                id="provinsi-total-indeks-berlaku-plus-adj-{{ $idTahun }}"
                                data-value="{{ $indeks_berlaku_plus_adj_total }}"
                                title="Provinsi Total Indeks Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $indeks_status_total ? ' | Status: ' . $indeks_status_total : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $indeks_berlaku_plus_adj_total !== null ? number_format($indeks_berlaku_plus_adj_total, 2, ',', '.') : '-' }}
                                </div>
                            </td>
                            
                            <!-- Laju Implisit -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="provinsi-total-laju-berlaku-{{ $idTahun }}"
                                data-value="{{ $laju_berlaku_total }}"
                                title="Provinsi Total Laju Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahun && $laju_berlaku_total !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($laju_berlaku_total, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $laju_bg_total ?: 'bg-yellow-50' }} {{ $laju_bg_total ? '' : 'hover:bg-yellow-100' }}"
                                id="provinsi-total-laju-berlaku-plus-adj-{{ $idTahun }}"
                                data-value="{{ $laju_berlaku_plus_adj_total }}"
                                title="Provinsi Total Laju Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $laju_status_total ? ' | Status: ' . $laju_status_total : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $laju_berlaku_plus_adj_total !== null ? number_format($laju_berlaku_plus_adj_total, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>
                        @endforeach
                    </tr>

                    <!-- TOTAL KABUPATEN/KOTA -->
                    @if ($totalKabKota && !empty($totalKabKota['data']))
                    <tr class="font-bold hover:bg-gray-50 border-b border-gray-300" data-sign-row="1">
                        <td class="border border-gray-300 px-3 py-2 sticky left-0 sticky-left bg-green-50 z-10 pl-6 text-xs md:text-sm hover:bg-gray-50">
                            <div class="flex items-center">
                                <svg class="w-3 h-3 mr-1 text-gray-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                                </svg>
                                <span class="truncate">TOTAL KABUPATEN/KOTA</span>
                            </div>
                        </td>
                        @foreach ($tahunIdsDisplay as $idTahun)
                            @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                                @php
                                    $periodeNama = $periode->nama_periode;
                                    $data = $calculations['total_kabkota'][$idTahun][$periodeNama] ?? null;
                                    $display = $data['display'] ?? [];
                                @endphp
                                
                                <!-- Kolom Berlaku -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-white hover:bg-gray-50"
                                    id="berlaku-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['berlaku'] ?? 0 }}">
                                    {{ $display['berlaku'] ?? '-' }}
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-white hover:bg-gray-50">
                                    <!-- INPUT TOTAL BERLAKU - READONLY & DISABLED -->
                                    <input type="text" inputmode="decimal" readonly disabled
                                        aria-label="Total Adjustment Berlaku {{ $idTahun }} {{ $periode->nama_periode }}"
                                        class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                        value="{{ $display['adj_berlaku'] ?? '' }}"
                                        data-id-tahun="{{ $idTahun }}" 
                                        data-id-periode="{{ $periode->id_periode }}" 
                                        data-tipe="berlaku" 
                                        data-pdrb="{{ $data['berlaku'] ?? 0 }}" 
                                        data-group="total-kabkota"
                                        data-periode-nama="{{ $periode->nama_periode }}"
                                        data-sub-kategori="{{ $isKategori ? '' : $subKategori->id_sub_kategori }}"
                                        data-kategori="{{ $isKategori ? $subKategori->kategori_id : '' }}"
                                        data-pdrb-plus-adj="{{ $data['berlaku_plus_adj'] ?? 0 }}"
                                        id="adj-berlaku-total-{{ $idTahun }}-{{ $periode->id_periode }}">
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-white hover:bg-yellow-100"
                                    id="pdrb-plus-adj-berlaku-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['berlaku_plus_adj'] ?? 0 }}">
                                    {{ $display['berlaku_plus_adj'] ?? '-' }}
                                </td>
                                
                                <!-- Kolom Konstan -->
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-white hover:bg-gray-50"
                                    id="konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['konstan'] ?? 0 }}">
                                    {{ $display['konstan'] ?? '-' }}
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-white hover:bg-gray-50">
                                    <!-- INPUT TOTAL KONSTAN - READONLY & DISABLED -->
                                    <input type="text" inputmode="decimal" readonly disabled
                                        aria-label="Total Adjustment Konstan {{ $idTahun }} {{ $periode->nama_periode }}"
                                        class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                        value="{{ $display['adj_konstan'] ?? '' }}"
                                        data-id-tahun="{{ $idTahun }}" 
                                        data-id-periode="{{ $periode->id_periode }}" 
                                        data-tipe="konstan" 
                                        data-pdrb="{{ $data['konstan'] ?? 0 }}" 
                                        data-group="total-kabkota"
                                        data-periode-nama="{{ $periode->nama_periode }}"
                                        data-sub-kategori="{{ $isKategori ? '' : $subKategori->id_sub_kategori }}"
                                        data-kategori="{{ $isKategori ? $subKategori->kategori_id : '' }}"
                                        data-pdrb-plus-adj="{{ $data['konstan_plus_adj'] ?? 0 }}"
                                        id="adj-konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}">
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-white hover:bg-yellow-100"
                                    id="pdrb-plus-adj-konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['konstan_plus_adj'] ?? 0 }}">
                                    {{ $display['konstan_plus_adj'] ?? '-' }}
                                </td>
                                
                                <!-- Pertumbuhan Q-to-Q -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="qoq-konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['qoq_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                           {{ $display['qoq_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm {{ $display['qoq_bg'] ?: 'bg-white hover:bg-gray-50' }}"
                                    id="qoq-konstan-plus-adj-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['qoq_konstan_plus_adj'] ?? 0 }}"
                                    title="Total Kab/Kota QoQ Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $display['qoq_status'] ? ' | Status: ' . $display['qoq_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $display['qoq_konstan_plus_adj'] }}
                                    </div>
                                </td>
                                
                                <!-- Pertumbuhan Y-on-Y -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="yoy-konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['yoy_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['yoy_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm {{ $display['yoy_bg'] ?: 'bg-white hover:bg-gray-50' }}"
                                    id="yoy-konstan-plus-adj-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['yoy_konstan_plus_adj'] ?? 0 }}"
                                    title="Total Kab/Kota YoY Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $display['yoy_status'] ? ' | Status: ' . $display['yoy_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $display['yoy_konstan_plus_adj'] }}
                                    </div>
                                </td>
                                
                                <!-- Pertumbuhan C-to-C -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="ctoc-konstan-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['ctoc_konstan'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['ctoc_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm {{ $display['ctoc_bg'] ?: 'bg-white hover:bg-gray-50' }}"
                                    id="ctoc-konstan-plus-adj-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['ctoc_konstan_plus_adj'] ?? 0 }}"
                                    title="Total Kab/Kota CtC Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $display['ctoc_status'] ? ' | Status: ' . $display['ctoc_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $display['ctoc_konstan_plus_adj'] }}
                                    </div>
                                </td>
                                
                                <!-- Indeks Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="indeks-berlaku-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['indeks_berlaku'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['indeks_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['indeks_bg'] ?: 'bg-white' }} {{ $display['indeks_bg'] ? '' : 'hover:bg-gray-50' }}"
                                    id="indeks-berlaku-plus-adj-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['indeks_berlaku_plus_adj'] ?? 0 }}"
                                    title="{{ $display['indeks_status'] ? 'Status: ' . $display['indeks_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $display['indeks_berlaku_plus_adj'] }}
                                    </div>
                                </td>
                                
                                <!-- Laju Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-white hover:bg-gray-50"
                                    id="laju-berlaku-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['laju_berlaku'] ?? 0 }}">
                                    @if($data)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $display['laju_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['laju_bg'] ?: 'bg-white hover:bg-gray-50' }}"
                                    id="laju-berlaku-plus-adj-total-{{ $idTahun }}-{{ $periode->id_periode }}"
                                    data-value="{{ $data['laju_berlaku_plus_adj'] ?? 0 }}"
                                    title="Total Kab/Kota Laju Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $display['laju_status'] ? ' | Status: ' . $display['laju_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $display['laju_berlaku_plus_adj'] }}
                                    </div>
                                </td>
                            @endforeach
                            <!-- TOTAL TAHUNAN KABUPATEN/KOTA -->
                            @php
                                $totalTahunNamaKab = 'TOTAL';
                                $dataTotalTahunKab = $calculations['total_kabkota'][$idTahun][$totalTahunNamaKab] ?? null;
                                
                                // Format adj untuk total tahunan kab/kota
                                $adjValueTotalKab = $dataTotalTahunKab['adj_berlaku'] ?? 0;
                                $adjKonstanValueTotalKab = $dataTotalTahunKab['adj_konstan'] ?? 0;
                                
                                $formatAdjValue = function($value) {
                                    if ($value == 0 || $value == '0' || $value == '0.00') {
                                        return '';
                                    }
                                    $floatVal = floatval($value);
                                    if ($floatVal == 0) return '';
                                    return number_format($floatVal, 2, ',', '.');
                                };
                                
                                $adjBerlakuDisplayTotalKab = $formatAdjValue($adjValueTotalKab);
                                $adjKonstanDisplayTotalKab = $formatAdjValue($adjKonstanValueTotalKab);
                                
                                // Pastikan semua nilai ada dengan default
                                $qoq_konstan_total_kab = $dataTotalTahunKab['qoq_konstan'] ?? 0;
                                $qoq_konstan_plus_adj_total_kab = $dataTotalTahunKab['qoq_konstan_plus_adj'] ?? 0;
                                $yoy_konstan_total_kab = $dataTotalTahunKab['yoy_konstan'] ?? 0;
                                $yoy_konstan_plus_adj_total_kab = $dataTotalTahunKab['yoy_konstan_plus_adj'] ?? 0;
                                $ctoc_konstan_total_kab = $dataTotalTahunKab['ctoc_konstan'] ?? 0;
                                $ctoc_konstan_plus_adj_total_kab = $dataTotalTahunKab['ctoc_konstan_plus_adj'] ?? 0;
                                $indeks_berlaku_total_kab = $dataTotalTahunKab['indeks_berlaku'] ?? 0;
                                $indeks_berlaku_plus_adj_total_kab = $dataTotalTahunKab['indeks_berlaku_plus_adj'] ?? 0;
                                $laju_berlaku_total_kab = $dataTotalTahunKab['laju_berlaku'] ?? 0;
                                $laju_berlaku_plus_adj_total_kab = $dataTotalTahunKab['laju_berlaku_plus_adj'] ?? 0;
                                
                                $qoq_diff_total_kab = $qoq_konstan_plus_adj_total_kab - $qoq_konstan_total_kab;
                                $yoy_diff_total_kab = $yoy_konstan_plus_adj_total_kab - $yoy_konstan_total_kab;
                                $ctoc_diff_total_kab = $ctoc_konstan_plus_adj_total_kab - $ctoc_konstan_total_kab;
                                
                                // Tentukan kategori untuk total tahunan kab/kota
                                $getDiffCategory = function($baseValue, $adjValue) use ($diffThreshold) {
                                    $base = floatval($baseValue ?? 0);
                                    $adj = floatval($adjValue ?? 0);

                                    if ($base == 0 || $adj == 0) return '';

                                    $diff = abs($adj - $base);
                                    $bedaArah = (($base > 0 && $adj < 0) || ($base < 0 && $adj > 0));

                                    if ($diff >= $diffThreshold && $bedaArah) return 'extreme_beda_arah';
                                    if ($diff >= $diffThreshold) return 'extreme';
                                    if ($bedaArah) return 'beda_arah';

                                    return '';
                                };
                                
                                $qoq_category_total_kab = $getDiffCategory($qoq_konstan_total_kab, $qoq_konstan_plus_adj_total_kab);
                                $yoy_category_total_kab = $getDiffCategory($yoy_konstan_total_kab, $yoy_konstan_plus_adj_total_kab);
                                $ctoc_category_total_kab = $getDiffCategory($ctoc_konstan_total_kab, $ctoc_konstan_plus_adj_total_kab);
                                $indeks_category_total_kab = $getDiffCategory($indeks_berlaku_total_kab, $indeks_berlaku_plus_adj_total_kab);
                                $laju_category_total_kab = $getDiffCategory($laju_berlaku_total_kab, $laju_berlaku_plus_adj_total_kab);

                                $bgByCategory = function ($category) {
                                    if (!$category) return '';
                                    return strpos($category, 'extreme') !== false ? 'bg-red-strong' : (strpos($category, 'beda_arah') !== false ? 'bg-yellow-200' : '');
                                };
                                    $rank = 0;
                                    $prov_total_tahun = $calculations['provinsi_total'][$idTahun] 
                                                        ?? $calculations['provinsi'][$idTahun]['TOTAL'] 
                                                        ?? $calculations['provinsi'][$idTahun]['total'] 
                                                        ?? [];
                                    
                                    $prov_qoq_total = $prov_total_tahun['qoq_konstan_plus_adj'] ?? 0;
                                    $prov_yoy_total = $prov_total_tahun['yoy_konstan_plus_adj'] ?? 0;
                                    $prov_ctoc_total = $prov_total_tahun['ctoc_konstan_plus_adj'] ?? 0;
                                    $prov_laju_total = $prov_total_tahun['laju_berlaku_plus_adj'] ?? 0;

                                    $qoq_bg_total_kab = $isTotalKabExtreme('qoq', $qoq_konstan_plus_adj_total_kab, $prov_qoq_total, $rank, $idTahun) ? 'bg-red-strong text-white' : '';
                                    $yoy_bg_total_kab = $isTotalKabExtreme('yoy', $yoy_konstan_plus_adj_total_kab, $prov_yoy_total, $rank, $idTahun) ? 'bg-red-strong text-white' : '';
                                    $ctoc_bg_total_kab = $isTotalKabExtreme('ctoc', $ctoc_konstan_plus_adj_total_kab, $prov_ctoc_total, $rank, $idTahun) ? 'bg-red-strong text-white' : '';
                                    $laju_bg_total_kab = $isTotalKabExtreme('laju', $laju_berlaku_plus_adj_total_kab, $prov_laju_total, $rank, $idTahun) ? 'bg-red-strong text-white' : '';

                                    $qoq_status_total_kab = $isTotalKabExtreme('qoq', $qoq_konstan_plus_adj_total_kab, $prov_qoq_total, $rank, $idTahun) ? 'Extreme' : $statusLabel($qoq_category_total_kab);
                                    $yoy_status_total_kab = $isTotalKabExtreme('yoy', $yoy_konstan_plus_adj_total_kab, $prov_yoy_total, $rank, $idTahun) ? 'Extreme' : $statusLabel($yoy_category_total_kab);
                                    $ctoc_status_total_kab = $isTotalKabExtreme('ctoc', $ctoc_konstan_plus_adj_total_kab, $prov_ctoc_total, $rank, $idTahun) ? 'Extreme' : $statusLabel($ctoc_category_total_kab);
                                    $laju_status_total_kab = $isTotalKabExtreme('laju', $laju_berlaku_plus_adj_total_kab, $prov_laju_total, $rank, $idTahun) ? 'Extreme' : $statusLabel($laju_category_total_kab);
                                    
                                    $indeks_bg_total_kab = $bgByCategory($indeks_category_total_kab);
                                    $indeks_status_total_kab = $statusLabel($indeks_category_total_kab);
                            @endphp
                            
                            <!-- Kolom Berlaku TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="berlaku-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['berlaku'] ?? 0 }}"
                                title="Total Kab/Kota Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ ($dataTotalTahunKab['berlaku'] ?? 0) != 0 ? number_format($dataTotalTahunKab['berlaku'], 2, ',', '.') : '-' }}
                            </td>
                            <!-- Input Adj TOTAL TAHUNAN - READONLY & DISABLED -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-yellow-50">
                                <input type="text" inputmode="decimal" readonly disabled
                                    aria-label="Total Tahunan Adjustment Berlaku Kab/Kota {{ $idTahun }}"
                                    class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                    value="{{ $adjBerlakuDisplayTotalKab }}"
                                    id="adj-berlaku-total-kab-total-{{ $idTahun }}">
                            </td>
                            <!-- Kolom PDRB+Adj TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="pdrb-plus-adj-berlaku-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['berlaku_plus_adj'] ?? 0 }}"
                                title="Total Kab/Kota Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahunKab ? number_format($dataTotalTahunKab['berlaku_plus_adj'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Kolom Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="konstan-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['konstan'] ?? 0 }}"
                                title="Total Kab/Kota Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahunKab ? number_format($dataTotalTahunKab['konstan'], 2, ',', '.') : '-' }}
                            </td>
                            <!-- Input Adj Konstan TOTAL TAHUNAN - READONLY & DISABLED -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-yellow-50">
                                <input type="text" inputmode="decimal" readonly disabled
                                    aria-label="Total Tahunan Adjustment Konstan Kab/Kota {{ $idTahun }}"
                                    class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                    value="{{ $adjKonstanDisplayTotalKab }}"
                                    id="adj-konstan-total-kab-total-{{ $idTahun }}">
                            </td>
                            <!-- Kolom PDRB+Adj Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right font-bold text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                id="pdrb-plus-adj-konstan-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['konstan_plus_adj'] ?? 0 }}"
                                title="Total Kab/Kota Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                {{ $dataTotalTahunKab ? number_format($dataTotalTahunKab['konstan_plus_adj'], 2, ',', '.') : '-' }}
                            </td>
                            
                            <!-- Pertumbuhan dan Implisit untuk TOTAL TAHUNAN -->
                            <!-- Pertumbuhan Q-to-Q -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="qoq-konstan-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $qoq_konstan_total_kab }}"
                                title="Total Kab/Kota QoQ Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahunKab && $qoq_konstan_total_kab !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($qoq_konstan_total_kab, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $qoq_bg_total_kab ?: 'bg-yellow-50 hover:bg-yellow-100' }}"
                                id="qoq-konstan-plus-adj-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $qoq_konstan_plus_adj_total_kab }}"
                                title="Total Kab/Kota QoQ Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $qoq_status_total_kab ? ' | Status: ' . $qoq_status_total_kab : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $qoq_konstan_plus_adj_total_kab !== null ? number_format($qoq_konstan_plus_adj_total_kab, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>

                            <!-- Pertumbuhan Y-on-Y -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="yoy-konstan-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $yoy_konstan_total_kab }}"
                                title="Total Kab/Kota YoY Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahunKab && $yoy_konstan_total_kab !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($yoy_konstan_total_kab, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $yoy_bg_total_kab ?: 'bg-yellow-50 hover:bg-yellow-100' }}"
                                id="yoy-konstan-plus-adj-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $yoy_konstan_plus_adj_total_kab }}"
                                title="Total Kab/Kota YoY Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $yoy_status_total_kab ? ' | Status: ' . $yoy_status_total_kab : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $yoy_konstan_plus_adj_total_kab !== null ? number_format($yoy_konstan_plus_adj_total_kab, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>

                            <!-- Pertumbuhan C-to-C -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="ctoc-konstan-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $ctoc_konstan_total_kab }}"
                                title="Total Kab/Kota CtC Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahunKab && $ctoc_konstan_total_kab !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($ctoc_konstan_total_kab, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $ctoc_bg_total_kab ?: 'bg-yellow-50 hover:bg-yellow-100' }}"
                                id="ctoc-konstan-plus-adj-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $ctoc_konstan_plus_adj_total_kab }}"
                                title="Total Kab/Kota CtC Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $ctoc_status_total_kab ? ' | Status: ' . $ctoc_status_total_kab : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $ctoc_konstan_plus_adj_total_kab !== null ? number_format($ctoc_konstan_plus_adj_total_kab, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>

                            <!-- Indeks Implisit -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="indeks-berlaku-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $indeks_berlaku_total_kab }}"
                                title="Total Kab/Kota Indeks Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahunKab && $indeks_berlaku_total_kab !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($indeks_berlaku_total_kab, 2, ',', '.') }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $indeks_bg_total_kab ?: 'bg-yellow-50' }} {{ $indeks_bg_total_kab ? '' : 'hover:bg-yellow-100' }}"
                                id="indeks-berlaku-plus-adj-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $indeks_berlaku_plus_adj_total_kab }}"
                                title="Total Kab/Kota Indeks Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $indeks_status_total_kab ? ' | Status: ' . $indeks_status_total_kab : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $indeks_berlaku_plus_adj_total_kab !== null ? number_format($indeks_berlaku_plus_adj_total_kab, 2, ',', '.') : '-' }}
                                </div>
                            </td>

                            <!-- Laju Implisit -->
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                id="laju-berlaku-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $laju_berlaku_total_kab }}"
                                title="Total Kab/Kota Laju Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                @if($dataTotalTahunKab && $laju_berlaku_total_kab !== null)
                                    <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                        {{ number_format($laju_berlaku_total_kab, 2, ',', '.') }}%
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs md:text-sm">-</span>
                                @endif
                            </td>
                            <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $laju_bg_total_kab ?: 'bg-yellow-50 hover:bg-yellow-100' }}"
                                id="laju-berlaku-plus-adj-total-kab-total-{{ $idTahun }}"
                                data-value="{{ $laju_berlaku_plus_adj_total_kab }}"
                                title="Total Kab/Kota Laju Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $laju_status_total_kab ? ' | Status: ' . $laju_status_total_kab : '' }}">
                                <div class="font-semibold text-xs md:text-sm">
                                    {{ $laju_berlaku_plus_adj_total_kab !== null ? number_format($laju_berlaku_plus_adj_total_kab, 2, ',', '.') . '%' : '-' }}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @endif
                    @endif

                    <!-- KABUPATEN/KOTA -->
                    @foreach ($kabkota as $index => $row)
                        @php
                            // Tentukan apakah user bisa edit row ini
                            $canEditKabkota = $isProvinsi || ($isDaerah && $row['id_wilayah'] == $userWilayah);
                            $cannotEditKabkota = !$canEditKabkota;
                            $isSultra = trim($row['wilayah'] ?? '') === 'Sulawesi Tenggara';
                            $baseDisableAdj = $cannotEditKabkota || $isSultra || $isNetExport;
                            $disableAdj = $baseDisableAdj || (!empty($isLocked) && $isDaerah);
                        @endphp
                        <tr class="hover:bg-gray-50 border-b border-gray-300 {{ $loop->even ? 'bg-gray-50' : 'bg-white' }}"
                            @if($isSultra) data-sign-row="1" @endif>
                            <td class="border border-gray-300 px-3 py-2 sticky left-0 sticky-left {{ $loop->even ? 'bg-gray-50' : 'bg-white' }} z-10 pl-6 text-xs md:text-sm hover:bg-gray-50">
                                <div class="flex items-center">
                                    <svg class="w-2.5 h-2.5 mr-1 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                    <span class="truncate">{{ $row['wilayah'] }}</span>
                                </div>
                            </td>
                            @foreach ($tahunIdsDisplay as $idTahun)
                                @foreach (($periodeByTahunDisplay[$idTahun] ?? $periodeTriwulan) as $periode)
                                    @php
                                        $periodeNama = $periode->nama_periode;
                                        $dataRow = $row['data'][$idTahun][$periodeNama] ?? null;
                                        $calcData = $calculations['kabkota'][$row['id_wilayah']][$idTahun][$periodeNama] ?? null;
                                        $display = $calcData['display'] ?? [];
                                    @endphp
                                    
                                    {{-- Kolom Berlaku --}}
                                    <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="berlaku-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['berlaku'] ?? 0 }}">
                                        {{ $display['berlaku'] ?? '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-0 py-1 {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50">
                                          <input type="text" inputmode="decimal"
                                              value="{{ $isSultra ? '' : ($display['adj_berlaku'] ?? '') }}"
                                              {{ $disableAdj ? 'readonly disabled' : '' }}
                                              aria-label="Adjustment Berlaku {{ $row['wilayah'] }} {{ $idTahun }} {{ $periode->nama_periode }}"
                                             class="w-full text-right outline-none border border-gray-300 rounded px-1 py-0.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition adj-input adj-kabkota text-xs md:text-sm {{ $disableAdj ? 'bg-gray-200 cursor-not-allowed' : ($loop->parent->even ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white hover:bg-gray-50') }}"
                                             data-lock-base-disabled="{{ $baseDisableAdj ? '1' : '0' }}"
                                              data-id-wilayah="{{ $row['id_wilayah'] }}"
                                              data-id-tahun="{{ $idTahun }}"
                                              data-id-periode="{{ $periode->id_periode }}"
                                              data-tipe="berlaku"
                                            data-pdrb="{{ $calcData['berlaku'] ?? 0 }}"
                                            data-target="pdrb-plus-adj-berlaku-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                            data-group="kabkota"
                                            data-periode-nama="{{ $periode->nama_periode }}"
                                            data-sub-kategori="{{ $isKategori ? '' : $subKategori->id_sub_kategori }}"
                                            data-kategori="{{ $isKategori ? $subKategori->kategori_id : '' }}"
                                            data-index="{{ $index }}"
                                            data-history-id="{{ $calcData['id_nilai_sub_kategori_adj_berlaku'] ?? '' }}"
                                            data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}">
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-yellow-100 {{ (!$isSultra && !empty($calcData['id_nilai_sub_kategori_adj_berlaku'])) ? 'cursor-pointer' : '' }}"
                                        id="pdrb-plus-adj-berlaku-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['berlaku_plus_adj'] ?? 0 }}"
                                        @if(!$isSultra && !empty($calcData['id_nilai_sub_kategori_adj_berlaku']))
                                            data-history-id="{{ $calcData['id_nilai_sub_kategori_adj_berlaku'] }}"
                                            data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}"
                                            onclick="showHistory(this)"
                                        @endif>
                                        {{ $display['berlaku_plus_adj'] ?? '-' }}
                                    </td>
                                    
                                    {{-- Kolom Konstan --}}
                                    <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['konstan'] ?? 0 }}">
                                        {{ $display['konstan'] ?? '-' }}
                                    </td>
                                    <td class="border border-gray-300 px-0 py-1 {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50">
                                          <input type="text" inputmode="decimal" 
                                             class="w-full text-right outline-none border border-gray-300 rounded px-1 py-0.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition adj-input adj-kabkota text-xs md:text-sm {{ $disableAdj ? 'bg-gray-200 cursor-not-allowed' : ($loop->parent->even ? 'bg-gray-50 hover:bg-gray-100' : 'bg-white hover:bg-gray-50') }}"
                                              value="{{ $isSultra ? '' : ($display['adj_konstan'] ?? '') }}"
                                              {{ $disableAdj ? 'readonly disabled' : '' }}
                                              aria-label="Adjustment Konstan {{ $row['wilayah'] }} {{ $idTahun }} {{ $periode->nama_periode }}"
                                              data-lock-base-disabled="{{ $baseDisableAdj ? '1' : '0' }}"
                                              data-id-wilayah="{{ $row['id_wilayah'] }}" 
                                              data-id-tahun="{{ $idTahun }}"
                                              data-id-periode="{{ $periode->id_periode }}" 
                                              data-tipe="konstan" 
                                            data-pdrb="{{ $calcData['konstan'] ?? 0 }}"
                                            data-target="pdrb-plus-adj-konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                            data-group="kabkota"
                                            data-periode-nama="{{ $periode->nama_periode }}"
                                            data-sub-kategori="{{ $isKategori ? '' : $subKategori->id_sub_kategori }}"
                                            data-kategori="{{ $isKategori ? $subKategori->kategori_id : '' }}"
                                            data-index="{{ $index }}"
                                            data-history-id="{{ $calcData['id_nilai_sub_kategori_adj_konstan'] ?? '' }}"
                                            data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}">
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-yellow-100 {{ (!$isSultra && !empty($calcData['id_nilai_sub_kategori_adj_konstan'])) ? 'cursor-pointer' : '' }}"
                                        id="pdrb-plus-adj-konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['konstan_plus_adj'] ?? 0 }}"
                                        @if(!$isSultra && !empty($calcData['id_nilai_sub_kategori_adj_konstan']))
                                            data-history-id="{{ $calcData['id_nilai_sub_kategori_adj_konstan'] }}"
                                            data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}"
                                            onclick="showHistory(this)"
                                        @endif>
                                        {{ $display['konstan_plus_adj'] ?? '-' }}
                                    </td>
                                    
                                    {{-- Pertumbuhan Q-to-Q --}}
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="qoq-konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['qoq_konstan'] ?? 0 }}">
                                        @if($calcData)
                                            <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                                {{ $display['qoq_konstan'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs md:text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['qoq_bg'] ?: ($loop->parent->even ? 'bg-gray-50' : 'bg-white') }} {{ $display['qoq_bg'] ? '' : 'hover:bg-gray-50' }}"
                                        id="qoq-konstan-plus-adj-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['qoq_konstan_plus_adj'] ?? 0 }}"
                                        title="{{ $display['qoq_status'] ? 'Status: ' . $display['qoq_status'] : '' }}">
                                        <div class="font-semibold text-xs md:text-sm">
                                            {{ $display['qoq_konstan_plus_adj'] }}
                                        </div>
                                        {!! $display['qoq_popup'] !!}
                                    </td>
                                    
                                    {{-- Pertumbuhan Y-on-Y --}}
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="yoy-konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['yoy_konstan'] ?? 0 }}">
                                        @if($calcData)
                                            <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                                {{ $display['yoy_konstan'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs md:text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['yoy_bg'] ?: ($loop->parent->even ? 'bg-gray-50' : 'bg-white') }} {{ $display['yoy_bg'] ? '' : 'hover:bg-gray-50' }}"
                                        id="yoy-konstan-plus-adj-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['yoy_konstan_plus_adj'] ?? 0 }}"
                                        title="{{ $display['yoy_status'] ? 'Status: ' . $display['yoy_status'] : '' }}">
                                        <div class="font-semibold text-xs md:text-sm">
                                            {{ $display['yoy_konstan_plus_adj'] }}
                                        </div>
                                        {!! $display['yoy_popup'] !!}
                                    </td>
                                    
                                    {{-- Pertumbuhan C-to-C --}}
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="ctoc-konstan-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['ctoc_konstan'] ?? 0 }}">
                                        @if($calcData)
                                            <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                                {{ $display['ctoc_konstan'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs md:text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['ctoc_bg'] ?: ($loop->parent->even ? 'bg-gray-50' : 'bg-white') }} {{ $display['ctoc_bg'] ? '' : 'hover:bg-gray-50' }}"
                                        id="ctoc-konstan-plus-adj-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['ctoc_konstan_plus_adj'] ?? 0 }}"
                                        title="{{ $display['ctoc_status'] ? 'Status: ' . $display['ctoc_status'] : '' }}">
                                        <div class="font-semibold text-xs md:text-sm">
                                            {{ $display['ctoc_konstan_plus_adj'] }}
                                        </div>
                                        {!! $display['ctoc_popup'] !!}
                                    </td>
                                    
                                    {{-- Indeks Implisit --}}
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="indeks-berlaku-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['indeks_berlaku'] ?? 0 }}">
                                        @if($calcData)
                                            <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                                {{ $display['indeks_berlaku'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs md:text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['indeks_bg'] ?: ($loop->parent->even ? 'bg-gray-50' : 'bg-white') }} {{ $display['indeks_bg'] ? '' : 'hover:bg-gray-50' }}"
                                        id="indeks-berlaku-plus-adj-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['indeks_berlaku_plus_adj'] ?? 0 }}"
                                        title="{{ $display['indeks_status'] ? 'Status: ' . $display['indeks_status'] : '' }}">
                                        <div class="font-semibold text-xs md:text-sm">
                                            {{ $display['indeks_berlaku_plus_adj'] }}
                                        </div>
                                        {!! $display['indeks_popup'] !!}
                                    </td>
                                    
                                    {{-- Laju Implisit --}}
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center {{ $loop->parent->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-gray-50"
                                        id="laju-berlaku-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['laju_berlaku'] ?? 0 }}">
                                        @if($calcData)
                                            <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                                {{ $display['laju_berlaku'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs md:text-sm">-</span>
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $display['laju_bg'] ?: ($loop->parent->even ? 'bg-gray-50' : 'bg-white') }} {{ $display['laju_bg'] ? '' : 'hover:bg-gray-50' }}"
                                        id="laju-berlaku-plus-adj-kabkota-{{ $row['id_wilayah'] }}-{{ $idTahun }}-{{ $periode->id_periode }}"
                                        data-value="{{ $calcData['laju_berlaku_plus_adj'] ?? 0 }}"
                                        title="{{ $display['laju_status'] ? 'Status: ' . $display['laju_status'] : '' }}">
                                        <div class="font-semibold text-xs md:text-sm">
                                            {{ $display['laju_berlaku_plus_adj'] }}
                                        </div>
                                        {!! $display['laju_popup'] !!}
                                    </td>
                                @endforeach
                                <!-- TOTAL TAHUNAN KABUPATEN/KOTA INDIVIDUAL -->
                                @php
                                    $totalTahunNamaKab = 'TOTAL';
                                    $dataTotalTahunKab = $calculations['kabkota'][$row['id_wilayah']][$idTahun]['TOTAL'] ?? null;
                                    
                                    if (!$dataTotalTahunKab) {
                                        $dataTotalTahunKab = [
                                            'berlaku' => 0,
                                            'konstan' => 0,
                                            'adj_berlaku' => 0,
                                            'adj_konstan' => 0,
                                            'berlaku_plus_adj' => 0,
                                            'konstan_plus_adj' => 0,
                                            'qoq_konstan' => 0,
                                            'qoq_konstan_plus_adj' => 0,
                                            'yoy_konstan' => 0,
                                            'yoy_konstan_plus_adj' => 0,
                                            'ctoc_konstan' => 0,
                                            'ctoc_konstan_plus_adj' => 0,
                                            'indeks_berlaku' => 0,
                                            'indeks_berlaku_plus_adj' => 0,
                                            'laju_berlaku' => 0,
                                            'laju_berlaku_plus_adj' => 0
                                        ];
                                    }
                                    
                                    // Format adj untuk total tahunan
                                    $adjValueTotalKab = $dataTotalTahunKab['adj_berlaku'] ?? 0;
                                    $adjKonstanValueTotalKab = $dataTotalTahunKab['adj_konstan'] ?? 0;
                                    
                                    $formatAdjValue = function($value) {
                                        if ($value == 0 || $value == '0' || $value == '0.00') {
                                            return '';
                                        }
                                        $floatVal = floatval($value);
                                        if ($floatVal == 0) return '';
                                        return number_format($floatVal, 2, ',', '.');
                                    };
                                    
                                    $adjBerlakuDisplayTotalKab = $formatAdjValue($adjValueTotalKab);
                                    $adjKonstanDisplayTotalKab = $formatAdjValue($adjKonstanValueTotalKab);
                                    
                                    $qoq_konstan_kab = $dataTotalTahunKab['qoq_konstan'] ?? 0;
                                    $qoq_konstan_plus_adj_kab = $dataTotalTahunKab['qoq_konstan_plus_adj'] ?? 0;
                                    $yoy_konstan_kab = $dataTotalTahunKab['yoy_konstan'] ?? 0;
                                    $yoy_konstan_plus_adj_kab = $dataTotalTahunKab['yoy_konstan_plus_adj'] ?? 0;
                                    $ctoc_konstan_kab = $dataTotalTahunKab['ctoc_konstan'] ?? 0;
                                    $ctoc_konstan_plus_adj_kab = $dataTotalTahunKab['ctoc_konstan_plus_adj'] ?? 0;
                                    $indeks_berlaku_kab = $dataTotalTahunKab['indeks_berlaku'] ?? 0;
                                    $indeks_berlaku_plus_adj_kab = $dataTotalTahunKab['indeks_berlaku_plus_adj'] ?? 0;
                                    $laju_berlaku_kab = $dataTotalTahunKab['laju_berlaku'] ?? 0;
                                    $laju_berlaku_plus_adj_kab = $dataTotalTahunKab['laju_berlaku_plus_adj'] ?? 0;
                                    
                                    $qoq_diff_kab = $qoq_konstan_plus_adj_kab - $qoq_konstan_kab;
                                    $yoy_diff_kab = $yoy_konstan_plus_adj_kab - $yoy_konstan_kab;
                                    $ctoc_diff_kab = $ctoc_konstan_plus_adj_kab - $ctoc_konstan_kab;
                                    
                                    // Tentukan kategori untuk total tahunan kab/kota
                                    $diffThresholdKab = ($pendekatan ?? 'lapangan_usaha') === 'pengeluaran' ? 4 : 5;
                                    $getDiffCategory = function($baseValue, $adjValue) use ($diffThresholdKab) {
                                        $base = floatval($baseValue ?? 0);
                                        $adj = floatval($adjValue ?? 0);

                                        if ($base == 0 || $adj == 0) return '';

                                        $diff = abs($adj - $base);
                                        $bedaArah = (($base > 0 && $adj < 0) || ($base < 0 && $adj > 0));

                                        if ($diff >= $diffThresholdKab && $bedaArah) return 'extreme_beda_arah';
                                        if ($diff >= $diffThresholdKab) return 'extreme';
                                        if ($bedaArah) return 'beda_arah';

                                        return '';
                                    };
                                    
                                    $qoq_category_kab = $getDiffCategory($qoq_konstan_kab, $qoq_konstan_plus_adj_kab);
                                    $yoy_category_kab = $getDiffCategory($yoy_konstan_kab, $yoy_konstan_plus_adj_kab);
                                    $ctoc_category_kab = $getDiffCategory($ctoc_konstan_kab, $ctoc_konstan_plus_adj_kab);
                                    $indeks_category_kab = $getDiffCategory($indeks_berlaku_kab, $indeks_berlaku_plus_adj_kab);
                                    $laju_category_kab = $getDiffCategory($laju_berlaku_kab, $laju_berlaku_plus_adj_kab);

                                    // Ambil display data dari calculations (sudah disiapkan di controller)
                                    $displayTotal = $dataTotalTahunKab['display'] ?? [];
                                @endphp
                                
                                <!-- Kolom Berlaku TOTAL TAHUNAN -->
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                    id="berlaku-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['berlaku'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    {{ $displayTotal['berlaku'] ?? '-' }}
                                </td>
                                
                                <!-- Input Adj TOTAL TAHUNAN - READONLY & DISABLED -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-yellow-50">
                                    <input type="text" inputmode="decimal" readonly disabled
                                        aria-label="Total Tahunan Adjustment Berlaku {{ $row['wilayah'] }} {{ $idTahun }}"
                                        class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                        value="{{ $isSultra ? '' : ($displayTotal['adj_berlaku'] ?? '') }}"
                                        id="adj-berlaku-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}">
                                </td>
                                
                                <!-- Kolom PDRB+Adj TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100 {{ (!$isSultra && !empty($dataTotalTahunKab['id_nilai_sub_kategori_adj_berlaku'])) ? 'cursor-pointer' : '' }}"
                                id="pdrb-plus-adj-berlaku-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['berlaku_plus_adj'] ?? 0 }}"
                                title="{{ $row['wilayah'] }} Total Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}"
                                @if(!$isSultra && !empty($dataTotalTahunKab['id_nilai_sub_kategori_adj_berlaku']))
                                    data-history-id="{{ $dataTotalTahunKab['id_nilai_sub_kategori_adj_berlaku'] }}"
                                    data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}"
                                    onclick="showHistory(this)"
                                @endif>
                                {{ $displayTotal['berlaku_plus_adj'] ?? '-' }}
                            </td>
                                
                                <!-- Kolom Konstan TOTAL TAHUNAN -->
                                <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100"
                                    id="konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['konstan'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    {{ $displayTotal['konstan'] ?? '-' }}
                                </td>
                                
                                <!-- Input Adj Konstan TOTAL TAHUNAN - READONLY & DISABLED -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm bg-yellow-50">
                                    <input type="text" inputmode="decimal" readonly disabled
                                        aria-label="Total Tahunan Adjustment Konstan {{ $row['wilayah'] }} {{ $idTahun }}"
                                        class="w-full text-center bg-gray-200 cursor-not-allowed font-bold border border-gray-400 rounded px-1 py-0.5 text-xs md:text-sm"
                                        value="{{ $isSultra ? '' : ($displayTotal['adj_konstan'] ?? '') }}"
                                        id="adj-konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}">
                                </td>
                                
                                <!-- Kolom PDRB+Adj Konstan TOTAL TAHUNAN -->
                            <td class="border border-gray-300 px-1 py-1 text-right text-xs md:text-sm bg-yellow-50 hover:bg-yellow-100 {{ (!$isSultra && !empty($dataTotalTahunKab['id_nilai_sub_kategori_adj_konstan'])) ? 'cursor-pointer' : '' }}"
                                id="pdrb-plus-adj-konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                data-value="{{ $dataTotalTahunKab['konstan_plus_adj'] ?? 0 }}"
                                title="{{ $row['wilayah'] }} Total Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}"
                                @if(!$isSultra && !empty($dataTotalTahunKab['id_nilai_sub_kategori_adj_konstan']))
                                    data-history-id="{{ $dataTotalTahunKab['id_nilai_sub_kategori_adj_konstan'] }}"
                                    data-history-type="{{ $isKategori ? 'kategori' : 'subkategori' }}"
                                    onclick="showHistory(this)"
                                @endif>
                                {{ $displayTotal['konstan_plus_adj'] ?? '-' }}
                            </td>
                                
                                <!-- Pertumbuhan dan Implisit untuk TOTAL TAHUNAN KABUPATEN/KOTA -->
                                <!-- Pertumbuhan Q-to-Q -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                    id="qoq-konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['qoq_konstan'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total QoQ Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    @if($dataTotalTahunKab)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $displayTotal['qoq_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $displayTotal['qoq_bg'] ?: 'bg-yellow-50' }} {{ $displayTotal['qoq_bg'] ? '' : 'hover:bg-yellow-100' }}"
                                    id="qoq-konstan-plus-adj-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['qoq_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total QoQ Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $displayTotal['qoq_status'] ? ' | Status: ' . $displayTotal['qoq_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $displayTotal['qoq_konstan_plus_adj'] }}
                                    </div>
                                    {!! $displayTotal['qoq_popup'] !!}
                                </td>
                                
                                <!-- Pertumbuhan Y-on-Y -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                    id="yoy-konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['yoy_konstan'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total YoY Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    @if($dataTotalTahunKab)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $displayTotal['yoy_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $displayTotal['yoy_bg'] ?: 'bg-yellow-50' }} {{ $displayTotal['yoy_bg'] ? '' : 'hover:bg-yellow-100' }}"
                                    id="yoy-konstan-plus-adj-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['yoy_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total YoY Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $displayTotal['yoy_status'] ? ' | Status: ' . $displayTotal['yoy_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $displayTotal['yoy_konstan_plus_adj'] }}
                                    </div>
                                    {!! $displayTotal['yoy_popup'] !!}
                                </td>
                                
                                <!-- Pertumbuhan C-to-C -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                    id="ctoc-konstan-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['ctoc_konstan'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total CtC Konstan Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    @if($dataTotalTahunKab)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $displayTotal['ctoc_konstan'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $displayTotal['ctoc_bg'] ?: 'bg-yellow-50' }} {{ $displayTotal['ctoc_bg'] ? '' : 'hover:bg-yellow-100' }}"
                                    id="ctoc-konstan-plus-adj-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['ctoc_konstan_plus_adj'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total CtC Konstan+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $displayTotal['ctoc_status'] ? ' | Status: ' . $displayTotal['ctoc_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $displayTotal['ctoc_konstan_plus_adj'] }}
                                    </div>
                                    {!! $displayTotal['ctoc_popup'] !!}
                                </td>
                                
                                <!-- Indeks Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                    id="indeks-berlaku-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['indeks_berlaku'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Indeks Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    @if($dataTotalTahunKab)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $displayTotal['indeks_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $displayTotal['indeks_bg'] ?: 'bg-yellow-50' }} {{ $displayTotal['indeks_bg'] ? '' : 'hover:bg-yellow-100' }}"
                                    id="indeks-berlaku-plus-adj-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['indeks_berlaku_plus_adj'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Indeks Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $displayTotal['indeks_status'] ? ' | Status: ' . $displayTotal['indeks_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $displayTotal['indeks_berlaku_plus_adj'] }}
                                    </div>
                                    {!! $displayTotal['indeks_popup'] !!}
                                </td>
                                
                                <!-- Laju Implisit -->
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm min-h-[40px] flex flex-col justify-center items-center bg-yellow-50 hover:bg-yellow-100"
                                    id="laju-berlaku-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['laju_berlaku'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Laju Berlaku Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}">
                                    @if($dataTotalTahunKab)
                                        <div class="main-value font-semibold text-xs md:text-sm mb-0.5">
                                            {{ $displayTotal['laju_berlaku'] }}
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs md:text-sm">-</span>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-1 py-1 text-center text-xs md:text-sm status-cell {{ $displayTotal['laju_bg'] ?: 'bg-yellow-50 hover:bg-yellow-100' }}"
                                    id="laju-berlaku-plus-adj-kabkota-total-{{ $row['id_wilayah'] }}-{{ $idTahun }}"
                                    data-value="{{ $dataTotalTahunKab['laju_berlaku_plus_adj'] ?? 0 }}"
                                    title="{{ $row['wilayah'] }} Total Laju Berlaku+Adj Tahun {{ $tahunMap[$idTahun] ?? $idTahun }}{{ $displayTotal['laju_status'] ? ' | Status: ' . $displayTotal['laju_status'] : '' }}">
                                    <div class="font-semibold text-xs md:text-sm">
                                        {{ $displayTotal['laju_berlaku_plus_adj'] }}
                                    </div>
                                    {!! $displayTotal['laju_popup'] !!}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>










