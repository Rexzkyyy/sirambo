<?php
namespace App\Http\Controllers;

use App\Exports\ResumeExport;
use App\Models\{
    Kategori,
    SubKategori,
    NilaiKategori,
    NilaiSubKategori,
    Kabupaten,
    Tahun,
    Periode,
    Wilayah
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Helpers\PdrbHelper;

class RekonsiliasiController extends Controller
{
    // =============================
    // METHOD UTAMA/INDEX
    // =============================

    public function index(Request $request)
    {
        /* ================= MASTER ================= */
        $tahunList = Tahun::orderBy('tahun', 'desc')->get();
        $periodeList = Periode::orderBy('id_periode')->get();
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');

        $kategoriList = Kategori::where('pendekatan', $pendekatan)->orderBy('id_kategori')->get();
        $subList = SubKategori::whereHas('kategori', fn($q) => $q->where('pendekatan', $pendekatan))->orderBy('id_sub_kategori')->get();

        $user = auth()->user();
        $wilayahUser = Wilayah::find($user->id_wilayah);
        $provKode = $wilayahUser->id_provinsi;
        $idProvinsi = $wilayahUser->tipe === 'provinsi' ? $wilayahUser->id_wilayah : Wilayah::where('id_provinsi', $provKode)->where('tipe', 'provinsi')->value('id_wilayah');

        $kabkota = Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
            ->where('kabupaten.id_provinsi', $provKode)
            ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
            ->select('kabupaten.nama_kabupaten', 'wilayah.id_wilayah')
            ->get();

        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];
        $kabkota = $kabkota->sortBy(fn($k) => array_search($k->nama_kabupaten, $urutan))->values();
        $kabIds = $kabkota->pluck('id_wilayah')->toArray();

        /* ================= PARAMETER ================= */
        $tahun = $request->tahun ?? $tahunList->first()?->id_tahun;
        $triwulan = $request->triwulan ?? $periodeList->first()?->id_periode;
        $tipePdrb = $request->tipe_pdrb ?? 'berlaku';
        $resume = $request->get('resume', 'p0');

        $rekonsiliasi = [];
        if (!$tahun) {
            return view('rekonsiliasi.index', compact('rekonsiliasi', 'kabkota', 'tahun', 'triwulan', 'tipePdrb', 'tahunList', 'periodeList', 'pendekatan', 'resume'));
        }

        /* ================= DATA PREPARATION ================= */
        $triwulans = ($triwulan === 'all') ? [1, 2, 3, 4] : [(int) $triwulan];

        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        $allRaw = NilaiKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $tahun)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $tahun)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        $getNominal = function ($id, $wilId, $pList, $isSub) use ($allRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $p;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $pList) use (&$getCategoryNominal, $subList, $pendekatan, $getNominal) {
            $hierarchy = [
                'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
                'sub-22' => ['sub-23', 'sub-24'],
            ];

            // PDRB Total logic (ID 21 for Lapus, ID 31 for Pengeluaran)
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $pList);
                }
                return $total;
            }

            // PDRB Non Migas (ID 22) - ONLY for Lapangan Usaha
            if ($pendekatan === 'lapangan_usaha' && $id == 22) {
                $pdrbTotal = $getCategoryNominal(21, $wilId, $pList);
                $migasA = $getNominal(11, $wilId, $pList, true); // Pertambangan Migas
                $migasB = $getNominal(24, $wilId, $pList, true); // Industri Migas
                return $pdrbTotal - $migasA - $migasB;
            }

            // Smart Hierarchical Sum
            $childRefs = $hierarchy['cat-' . $id] ?? null;
            if ($childRefs) {
                $parentVal = $getNominal($id, $wilId, $pList, false);
                if ($parentVal > 0)
                    return $parentVal;

                $total = 0;
                foreach ($childRefs as $ref) {
                    $cid = (int) str_replace('sub-', '', $ref);
                    $total += $getNominal($cid, $wilId, $pList, true);
                }
                return $total;
            }

            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    // If sub-category has its own children
                    $sId = $s->id_sub_kategori;
                    if (isset($hierarchy['sub-' . $sId])) {
                        $sVal = $getNominal($sId, $wilId, $pList, true);
                        if ($sVal > 0) {
                            $total += $sVal;
                        } else {
                            foreach ($hierarchy['sub-' . $sId] as $cref) {
                                $ccid = (int) str_replace('sub-', '', $cref);
                                $total += $getNominal($ccid, $wilId, $pList, true);
                            }
                        }
                    } else {
                        $total += $getNominal($sId, $wilId, $pList, true);
                    }
                }
                return $total;
            }
            return $getNominal($id, $wilId, $pList, false);
        };

        /* ================= CALCULATION ================= */
        foreach ($kategoriList as $kat) {
            $katId = $kat->id_kategori;
            $idDipakai = $katId;

            // PDRB Lapus link for Pengeluaran
            if ($pendekatan === 'pengeluaran' && strtoupper(trim($kat->nama_kategori)) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS') {
                $pdrbLu = Kategori::where('pendekatan', 'lapangan_usaha')->whereRaw('UPPER(nama_kategori) = ?', ['PRODUK DOMESTIK REGIONAL BRUTO'])->first();
                if ($pdrbLu)
                    $idDipakai = $pdrbLu->id_kategori;
            }

            $provVal = $getCategoryNominal($idDipakai, $idProvinsi, $triwulans);
            $totalKab = 0;
            $nilaiKab = [];
            foreach ($kabkota as $kab) {
                $val = $getCategoryNominal($idDipakai, $kab->id_wilayah, $triwulans);
                $nilaiKab[$kab->nama_kabupaten] = $val;
                $totalKab += $val;
            }

            // Fix Resume P0 fallback
            if ($provVal == 0 && ($resume === 'p0' || !$resume))
                $provVal = $totalKab;

            $selisih = round($totalKab - $provVal, 9);
            $persen = ($provVal != 0) ? ($selisih / $provVal) * 100 : 0;

            $rekonsiliasi[] = [
                'kode' => \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori),
                'kategori' => $kat->nama_kategori,
                'level' => 1,
                'provinsi' => $provVal,
                'kabkota' => $nilaiKab,
                'total' => $totalKab,
                'selisih' => $selisih,
                'persen' => $persen,
            ];

            // Sub categories
            $subs = $kat->subKategori->values();
            foreach ($subs as $idx => $sub) {
                $provSub = $getNominal($sub->id_sub_kategori, $idProvinsi, $triwulans, true);
                $totalSub = 0;
                $nilaiSubKab = [];
                foreach ($kabkota as $kab) {
                    $val = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $triwulans, true);
                    $nilaiSubKab[$kab->nama_kabupaten] = $val;
                    $totalSub += $val;
                }

                if ($provSub == 0 && ($resume === 'p0' || !$resume))
                    $provSub = $totalSub;

                $selSub = round($totalSub - $provSub, 9);
                $perSub = ($provSub != 0) ? ($selSub / $provSub) * 100 : 0;

                $rekonsiliasi[] = [
                    'kode' => \App\Helpers\PdrbHelper::getSubCode($idx + 1, $kat->nama_kategori),
                    'kategori' => $sub->nama_sub_kategori,
                    'level' => $sub->parent_id ? 3 : 2,
                    'provinsi' => $provSub,
                    'kabkota' => $nilaiSubKab,
                    'total' => $totalSub,
                    'selisih' => $selSub,
                    'persen' => $perSub,
                ];
            }
        }

        // Di dalam method index(), sebelum return view

        // ================= PERBAIKAN NET EKSPOR UNTUK PENDEKATAN PENGELUARAN (RESUME) =================
        if ($pendekatan === 'pengeluaran') {
            $eksporRow = null;
            $imporRow = null;
            $eksporIndex = null;

            // Cari baris Ekspor dan Impor, catat index Ekspor
            foreach ($rekonsiliasi as $index => $row) {
                $kategori = strtoupper($row['kategori']);
                if (str_contains($kategori, 'EKSPOR') && !str_contains($kategori, 'IMPOR')) {
                    $eksporRow = $row;
                    $eksporIndex = $index; // Catat posisi Ekspor
                } elseif (str_contains($kategori, 'IMPOR') && !str_contains($kategori, 'EKSPOR')) {
                    $imporRow = $row;
                }
            }

            // Jika Ekspor dan Impor ditemukan
            if ($eksporRow && $imporRow) {

                // FILTER: HAPUS SEMUA BARIS NET EKSPOR
                $filteredRekonsiliasi = [];
                foreach ($rekonsiliasi as $row) {
                    $kategori = strtoupper($row['kategori']);
                    if (!str_contains($kategori, 'NET EKSPOR')) {
                        $filteredRekonsiliasi[] = $row;
                    }
                }

                // Hitung ulang nilai Net Ekspor yang benar
                $netEksporKabkota = [];
                foreach ($kabkota as $kab) {
                    $eksporVal = $eksporRow['kabkota'][$kab->nama_kabupaten] ?? 0;
                    $imporVal = $imporRow['kabkota'][$kab->nama_kabupaten] ?? 0;
                    $netEksporKabkota[$kab->nama_kabupaten] = $eksporVal - $imporVal;
                }

                $totalNetEksporKab = array_sum($netEksporKabkota);
                $provNetEkspor = ($eksporRow['provinsi'] ?? 0) - ($imporRow['provinsi'] ?? 0);

                if (($resume === 'p0' || !$resume) && $provNetEkspor == 0) {
                    $provNetEkspor = $totalNetEksporKab;
                }

                $selisihNet = $totalNetEksporKab - $provNetEkspor;
                $persenNet = ($provNetEkspor != 0) ? ($selisihNet / $provNetEkspor) * 100 : 0;

                // Buat baris Net Ekspor baru
                $newRow = [
                    'kode' => 'PNE',
                    'kategori' => 'Net Ekspor',
                    'level' => 2,
                    'provinsi' => $provNetEkspor,
                    'total' => $totalNetEksporKab,
                    'kabkota' => $netEksporKabkota,
                    'selisih' => $selisihNet,
                    'persen' => $persenNet,
                ];

                // 🔥 INSERT DI ATAS EKSPOR (bukan setelah Impor)
                // Cari ulang posisi Ekspor di array yang sudah difilter
                $newEksporIndex = null;
                foreach ($filteredRekonsiliasi as $index => $row) {
                    $kategori = strtoupper($row['kategori']);
                    if (str_contains($kategori, 'EKSPOR') && !str_contains($kategori, 'IMPOR')) {
                        $newEksporIndex = $index;
                        break;
                    }
                }

                // Insert SEBELUM Ekspor (di atas Ekspor)
                $insertPos = ($newEksporIndex !== null) ? $newEksporIndex : count($filteredRekonsiliasi);
                array_splice($filteredRekonsiliasi, $insertPos, 0, [$newRow]);

                // Assign kembali ke $rekonsiliasi
                $rekonsiliasi = collect($filteredRekonsiliasi);
            }
        }

        return view('rekonsiliasi.index', compact('rekonsiliasi', 'kabkota', 'tahun', 'triwulan', 'tipePdrb', 'tahunList', 'periodeList', 'pendekatan', 'resume'));
    }

    // =============================
    // Q TO Q (QUARTER TO QUARTER)
    // =============================

    public function qtoq(Request $request)
    {
        $user = auth()->user();
        $wilayahUser = \App\Models\Wilayah::find($user->id_wilayah);

        $provKode = $wilayahUser->id_provinsi;

        $idProvinsi = $wilayahUser->tipe === 'provinsi'
            ? $wilayahUser->id_wilayah
            : \App\Models\Wilayah::where('id_provinsi', $provKode)
                ->where('tipe', 'provinsi')
                ->value('id_wilayah');
        $tahun = (int) $request->get('tahun');
        $triwulan = $request->get('triwulan'); // angka | all
        $tipePdrb = 'konstan';
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        $resume = $request->get('resume');

        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();

        $allKabkota = Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
            ->where('kabupaten.id_provinsi', $provKode) // ← FIX UTAMA
            ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
            ->select('kabupaten.*', 'wilayah.id_wilayah')
            ->get();

        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];
        $allKabkota = $allKabkota->sortBy(fn($item) => array_search($item->nama_kabupaten, $urutan))->values();

        $grouped = collect();

        if (!$tahun || !$triwulan) {
            return view('rekonsiliasi.qtoq', compact(
                'tahunList',
                'periodeList',
                'tahun',
                'triwulan',
                'grouped',
                'allKabkota',
                'tipePdrb'
            ))->with('kabkota', $allKabkota);
        }

        /* ===================== PERIODE & TAHUN ===================== */
        $tahunId = (int) $tahun;
        $tahunModel = \App\Models\Tahun::find($tahunId);
        $tahunVal = $tahunModel ? $tahunModel->tahun : date('Y');

        // Fix sequential ID bug: fetch previous year ID by actual year value
        $tahunPrevModel = \App\Models\Tahun::where('tahun', $tahunVal - 1)->first();
        $tahunPrevId = $tahunPrevModel ? $tahunPrevModel->id_tahun : $tahunId - 1;

        if ($triwulan === 'all') {
            $periodeNow = [1, 2, 3, 4];
            $periodePrev = [1, 2, 3, 4];
            $tahunActualPrev = $tahunPrevId;
        } else {
            $t = (int) $triwulan;
            $periodeNow = [$t];
            if ($t > 1) {
                $tahunActualPrev = $tahunId;
                $periodePrev = [$t - 1];
            } else {
                $tahunActualPrev = $tahunPrevId;
                $periodePrev = [4];
            }
        }

        $wilayahIds = $allKabkota->pluck('id_wilayah')->push($idProvinsi);

        /* ===================== DATA RESOLVER ===================== */
        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;

            // 30% rule from index()
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        /* ===================== FETCH RAW DATA ===================== */
        $nilaiKategoriAwal = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
            ->whereIn('id_tahun', [$tahunId, $tahunPrevId])
            ->whereIn('id_periode', [1, 2, 3, 4])
            ->where('tipe_pdrb', $tipePdrb)
            ->where('tahap_data', 'awal')
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode);

        $nilaiKategoriRekon = collect();
        if ($resume === 'p1') {
            $nilaiKategoriRekon = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
                ->whereIn('id_tahun', [$tahunId, $tahunPrevId])
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', 'rekonsiliasi')
                ->get()
                ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode);
        }

        $nilaiSubAwal = NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
            ->whereIn('id_tahun', [$tahunId, $tahunPrevId])
            ->whereIn('id_periode', [1, 2, 3, 4])
            ->where('tipe_pdrb', $tipePdrb)
            ->where('tahap_data', 'awal')
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode);

        $nilaiSubRekon = collect();
        if ($resume === 'p1') {
            $nilaiSubRekon = NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
                ->whereIn('id_tahun', [$tahunId, $tahunPrevId])
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', $tipePdrb)
                ->where('tahap_data', 'rekonsiliasi')
                ->get()
                ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode);
        }

        $subList = SubKategori::all();

        /* ===================== NOMINAL CALCULATOR ===================== */
        $getNominal = function ($id, $wilId, $thn, $pList, $isSub = false) use (&$getNominal, $nilaiKategoriAwal, $nilaiKategoriRekon, $nilaiSubAwal, $nilaiSubRekon, $resolveValue, $resume, $subList, $pendekatan) {
            $hierarchy = [
                'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
                'sub-22' => ['sub-23', 'sub-24'],
            ];

            if (!$isSub) {
                // PDRB Total logic
                if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                    $ids = ($pendekatan === 'lapangan_usaha') ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18] : [23, 24, 25, 26, 27, 28];
                    $total = 0;
                    foreach ($ids as $kid) {
                        $total += $getNominal($kid, $wilId, $thn, $pList, false);
                    }
                    return $total;
                }

                // Hierarchical Category Sum
                $childRefs = $hierarchy['cat-' . $id] ?? null;
                if ($childRefs) {
                    $pVal = 0;
                    foreach ($pList as $p) {
                        $key = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                        $awal = ($nilaiKategoriAwal[$key] ?? collect())->first();
                        $rekon = ($resume === 'p1') ? ($nilaiKategoriRekon[$key] ?? collect())->first() : null;
                        $pVal += $resolveValue($awal, $rekon);
                    }
                    if ($pVal > 0)
                        return $pVal;

                    $total = 0;
                    foreach ($childRefs as $ref) {
                        $cid = (int) str_replace('sub-', '', $ref);
                        $total += $getNominal($cid, $wilId, $thn, $pList, true);
                    }
                    return $total;
                }

                // Category with Sub-categories logic (Flat)
                $subs = $subList->where('id_kategori', $id);
                if ($subs->count() > 0) {
                    $total = 0;
                    foreach ($subs as $s) {
                        $total += $getNominal($s->id_sub_kategori, $wilId, $thn, $pList, true);
                    }
                    return $total;
                }

                // Base Category
                $total = 0;
                foreach ($pList as $p) {
                    $key = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                    $awal = ($nilaiKategoriAwal[$key] ?? collect())->first();
                    $rekon = ($resume === 'p1') ? ($nilaiKategoriRekon[$key] ?? collect())->first() : null;
                    $total += $resolveValue($awal, $rekon);
                }
                return $total;
            } else {
                // Sub-category with Hierarchy
                if (isset($hierarchy['sub-' . $id])) {
                    $pVal = 0;
                    foreach ($pList as $p) {
                        $key = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                        $awal = ($nilaiSubAwal[$key] ?? collect())->first();
                        $rekon = ($resume === 'p1') ? ($nilaiSubRekon[$key] ?? collect())->first() : null;
                        $pVal += $resolveValue($awal, $rekon);
                    }
                    if ($pVal > 0)
                        return $pVal;

                    $total = 0;
                    foreach ($hierarchy['sub-' . $id] as $cref) {
                        $cid = (int) str_replace('sub-', '', $cref);
                        $total += $getNominal($cid, $wilId, $thn, $pList, true);
                    }
                    return $total;
                }

                // Base Sub-category
                $total = 0;
                foreach ($pList as $p) {
                    $key = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                    $awal = ($nilaiSubAwal[$key] ?? collect())->first();
                    $rekon = ($resume === 'p1') ? ($nilaiSubRekon[$key] ?? collect())->first() : null;
                    $total += $resolveValue($awal, $rekon);
                }
                return $total;
            }
        };

        $kategoriList = Kategori::when($pendekatan === 'pengeluaran', fn($q) => $q->whereIn('pendekatan', ['pengeluaran', 'lapangan_usaha']), fn($q) => $q->where('pendekatan', $pendekatan))
            ->orderBy('id_kategori')
            ->get();

        foreach ($kategoriList as $kat) {
            $kodeKategori = PdrbHelper::getKategoriCode($kat->nama_kategori);
            $lastKategori = $kat->nama_kategori;

            $kabData = [];
            $sumNowKab = 0;
            $sumPrevKab = 0;
            foreach ($allKabkota as $kab) {
                $now = $getNominal($kat->id_kategori, $kab->id_wilayah, $tahunId, $periodeNow);
                $prev = $getNominal($kat->id_kategori, $kab->id_wilayah, $tahunActualPrev, $periodePrev);

                $kabData[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                $sumNowKab += $now;
                $sumPrevKab += $prev;
            }

            $totalKab = $sumPrevKab != 0 ? (($sumNowKab - $sumPrevKab) / $sumPrevKab) * 100 : 0;
            $nowProv = $getNominal($kat->id_kategori, $idProvinsi, $tahunId, $periodeNow);
            $prevProv = $getNominal($kat->id_kategori, $idProvinsi, $tahunActualPrev, $periodePrev);

            // 🔥 FIX RESUME P0: Nominal-level fallback to ensure growth matches aggregate kabkota
            if ($resume === 'p0' || !$resume) {
                if ($nowProv == 0)
                    $nowProv = $sumNowKab;
                if ($prevProv == 0)
                    $prevProv = $sumPrevKab;
            }

            $provinsi = $prevProv != 0 ? (($nowProv - $prevProv) / $prevProv) * 100 : 0;

            $selisih = $totalKab - $provinsi;
            $grouped->push([
                'kode' => $kodeKategori,
                'kategori' => $kat->nama_kategori,
                'is_sub' => false,
                'provinsi' => $provinsi,
                'total_kab' => $totalKab,
                'kab' => $kabData,
                'selisih' => $selisih,
                'cek' => ($provinsi > 0 && $totalKab < 0) || ($provinsi < 0 && $totalKab > 0) ? 'BEDA ARAH' : (abs($selisih) > 10 ? 'SELISIH > 10 %' : (abs($selisih) > 5 ? 'SELISIH 5 - 10 %' : 'NORMAL')),
                'hidden' => ($pendekatan === 'pengeluaran' && $kat->pendekatan === 'lapangan_usaha')
            ]);

            $subIndex = 0;
            foreach ($subList->where('id_kategori', $kat->id_kategori) as $sub) {
                $subIndex++;
                $kodeSub = PdrbHelper::getSubCode($subIndex, $lastKategori);

                $kabDataSub = [];
                $sumNowKabSub = 0;
                $sumPrevKabSub = 0;
                foreach ($allKabkota as $kab) {
                    $now = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahunId, $periodeNow, true);
                    $prev = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahunActualPrev, $periodePrev, true);

                    $kabDataSub[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                    $sumNowKabSub += $now;
                    $sumPrevKabSub += $prev;
                }

                $totalKabSub = $sumPrevKabSub != 0 ? (($sumNowKabSub - $sumPrevKabSub) / $sumPrevKabSub) * 100 : 0;
                $nowProvSub = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahunId, $periodeNow, true);
                $prevProvSub = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahunActualPrev, $periodePrev, true);

                // 🔥 FIX RESUME P0: Nominal-level fallback for sub-categories
                if ($resume === 'p0' || !$resume) {
                    if ($nowProvSub == 0)
                        $nowProvSub = $sumNowKabSub;
                    if ($prevProvSub == 0)
                        $prevProvSub = $sumPrevKabSub;
                }

                $provinsiSub = $prevProvSub != 0 ? (($nowProvSub - $prevProvSub) / $prevProvSub) * 100 : 0;

                $selisihSub = $totalKabSub - $provinsiSub;
                $grouped->push([
                    'kode' => $kodeSub,
                    'kategori' => $sub->nama_sub_kategori,
                    'is_sub' => true,
                    'provinsi' => $provinsiSub,
                    'total_kab' => $totalKabSub,
                    'kab' => $kabDataSub,
                    'selisih' => $selisihSub,
                    'cek' => ($provinsiSub > 0 && $totalKabSub < 0) || ($provinsiSub < 0 && $totalKabSub > 0) ? 'BEDA ARAH' : (abs($selisihSub) > 10 ? 'SELISIH > 10 %' : (abs($selisihSub) > 5 ? 'SELISIH 5 - 10 %' : 'NORMAL')),
                    'hidden' => ($pendekatan === 'pengeluaran' && $kat->pendekatan === 'lapangan_usaha')
                ]);
            }
        }

        /* ===================== SINKRON LAPUS (KHUSUS PENGELUARAN) ===================== */
        if ($pendekatan === 'pengeluaran') {
            $pdrbLU = $grouped->first(fn($r) => !$r['is_sub'] && str_contains(strtoupper($r['kategori']), 'PRODUK DOMESTIK REGIONAL BRUTO') && !str_contains(strtoupper($r['kategori']), 'LAPUS'));
            if ($pdrbLU) {
                $grouped = $grouped->map(function ($row) use ($pdrbLU, $resume) {
                    if (!$row['is_sub'] && strtoupper(trim($row['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS') {
                        $row['provinsi'] = $pdrbLU['provinsi'];
                        $row['total_kab'] = $pdrbLU['total_kab'];
                        $row['kab'] = $pdrbLU['kab'];
                        $row['selisih'] = round($row['total_kab'] - $row['provinsi'], 9);
                        $row['cek'] = ($row['provinsi'] > 0 && $row['total_kab'] < 0) || ($row['provinsi'] < 0 && $row['total_kab'] > 0) ? 'BEDA ARAH' : (abs($row['selisih']) > 10 ? 'SELISIH > 10 %' : (abs($row['selisih']) > 5 ? 'SELISIH 5 - 10 %' : 'NORMAL'));
                    }
                    return $row;
                })->values();
            }
        }

        // Sebelum return view di method qtoq()
        if ($pendekatan === 'pengeluaran') {
            $data = ['grouped' => $grouped, 'allKabkota' => $allKabkota];
            $data = $this->fixNetEkspor($data, true, $allKabkota);
            $grouped = $data['grouped'];
        }

        return view('rekonsiliasi.qtoq', compact(
            'tahunList',
            'periodeList',
            'tahun',
            'triwulan',
            'allKabkota',
            'tipePdrb',
            'pendekatan',
            'resume'
        ))->with('grouped', $grouped);
    }


    public function yony(Request $request)
    {
        $user = auth()->user();
        $wilayahUser = \App\Models\Wilayah::find($user->id_wilayah);
        $provKode = $wilayahUser->id_provinsi;

        $idProvinsi = $wilayahUser->tipe === 'provinsi'
            ? $wilayahUser->id_wilayah
            : \App\Models\Wilayah::where('id_provinsi', $provKode)
                ->where('tipe', 'provinsi')
                ->value('id_wilayah');

        $tahun = (int) $request->get('tahun');
        $triwulan = $request->get('triwulan'); // id_periode | all
        $tipePdrb = 'konstan';
        $wilayah = $request->get('wilayah');
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        $resume = $request->get('resume'); // untuk resume p1

        /* ===================== MASTER ===================== */
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();

        /* ===================== KAB/KOTA ===================== */
        $allKabkota = Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
            ->where('kabupaten.id_provinsi', $provKode)
            ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
            ->select('kabupaten.*', 'wilayah.id_wilayah')
            ->get();

        // ===================== URUTAN KAB/KOTA =====================
        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];

        $allKabkota = $allKabkota->sortBy(fn($item) => array_search($item->nama_kabupaten, $urutan))->values();

        /* ===================== FLAG ===================== */
        $filterKabkota = collect();
        $showProvinsi = $showKabkota = $showTotal = false;

        if ($wilayah === 'prov_sultra') {
            $showProvinsi = true;
        } elseif ($wilayah) {
            $filterKabkota = $allKabkota->where('id_wilayah', (int) $wilayah);
            $showKabkota = true;
        } else {
            $filterKabkota = $allKabkota;
            $showProvinsi = $showKabkota = $showTotal = true;
        }

        /* ===================== PERIODE & TAHUN ===================== */
        $tahunModel = Tahun::find($tahun);
        $tahunVal = $tahunModel ? $tahunModel->tahun : date('Y');

        $tahunPrevModel = Tahun::where('tahun', $tahunVal - 1)->first();
        $tahunPrev = $tahunPrevModel ? $tahunPrevModel->id_tahun : $tahun - 1;

        $triwulanList = ($triwulan === 'all' || !$triwulan) ? [1, 2, 3, 4] : [(int) $triwulan];

        if (!$tahun || !$tahunPrev) {
            return view('rekonsiliasi.ytoy', [
                'tahunList' => $tahunList,
                'periodeList' => $periodeList,
                'tahun' => $tahun,
                'triwulan' => $triwulan,
                'wilayah' => $wilayah,
                'allKabkota' => $allKabkota,
                'filterKabkota' => $filterKabkota,
                'showTotal' => true,
                'showProvinsi' => true,
                'showKabkota' => true,
                'tipePdrb' => $tipePdrb,
                'rekonsiliasi' => collect()
            ]);
        }

        /* ===================== PILIH TAHAP DATA ===================== */
        // Standardized Resolver logic matching index()
        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        // Pull all relevant records to allow per-triwulan merging and summation
        $allKategoriRaw = NilaiKategori::whereIn('id_wilayah', array_merge($filterKabkota->pluck('id_wilayah')->toArray(), [$idProvinsi]))
            ->whereIn('id_tahun', [$tahun, $tahunPrev])
            ->whereIn('id_periode', $triwulanList)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', array_merge($filterKabkota->pluck('id_wilayah')->toArray(), [$idProvinsi]))
            ->whereIn('id_tahun', [$tahun, $tahunPrev])
            ->whereIn('id_periode', $triwulanList)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tahap_data);

        // Resolve values for both current and previous years
        $getResolvedNominal = function ($id, $wilId, $thn, $pList, $isSub) use ($allKategoriRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allKategoriRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };
        $subList = SubKategori::all();

        $rekonsiliasi = collect();

        // $subAwal = NilaiSubKategori::select('id_sub_kategori','id_wilayah',DB::raw('SUM(nilai) total'))
        //     ->where('id_tahun',$tahun)
        //     ->whereIn('id_periode',$triwulanList)
        //     ->where('tipe_pdrb',$tipePdrb)
        //     ->where('tahap_data','awal')
        //     ->groupBy('id_sub_kategori','id_wilayah')
        //     ->get()
        //     ->keyBy(fn($i)=>$i->id_sub_kategori.'_'.$i->id_wilayah);

        // $subPrev = NilaiSubKategori::select('id_sub_kategori','id_wilayah',DB::raw('SUM(nilai) total'))
        //     ->where('id_tahun',$tahunPrev)
        //     ->whereIn('id_periode',$triwulanList)
        //     ->where('tipe_pdrb',$tipePdrb)
        //     ->where('tahap_data',$tahapPrev)
        //     ->groupBy('id_sub_kategori','id_wilayah')
        //     ->get()
        //     ->keyBy(fn($i)=>$i->id_sub_kategori.'_'.$i->id_wilayah);

        /* ===================== LOOP KATEGORI & SUB ===================== */
        $kategoriList = Kategori::where('pendekatan', $pendekatan)
            ->orderBy('id_kategori')->get();

        foreach ($kategoriList as $kat) {
            $kabData = [];
            $sumNowKab = 0;
            $sumPrevKab = 0;
            $subKats = SubKategori::where('id_kategori', $kat->id_kategori)->get();
            $idSubList = $subKats->pluck('id_sub_kategori')->toArray();
            $isPDRBTotal = ($pendekatan === 'lapangan_usaha' && $kat->id_kategori == 21);
            $sumKategoriPDRB = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

            $getCategoryNominal = function ($id, $wilId, $thn, $pList) use (&$getCategoryNominal, $subList, $pendekatan, $getResolvedNominal) {
                $hierarchy = [
                    'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                    'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                    'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
                    'sub-22' => ['sub-23', 'sub-24'],
                ];

                // 🔥 TAMBAHKAN: Untuk kategori Pengeluaran ID 23-30, ambil langsung dari database
                if ($pendekatan === 'pengeluaran' && in_array($id, [23, 24, 25, 26, 27, 28, 29, 30])) {
                    return $getResolvedNominal($id, $wilId, $thn, $pList, false);
                }

                // PDRB Total logic (ID 21 for Lapus, ID 31 for Pengeluaran)
                if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                    $ids = ($pendekatan === 'lapangan_usaha')
                        ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                        : [23, 24, 25, 26, 27, 28];
                    $total = 0;
                    foreach ($ids as $kid) {
                        $total += $getCategoryNominal($kid, $wilId, $thn, $pList);
                    }
                    return $total;
                }

                // Hierarchy support for categories
                $childRefs = $hierarchy['cat-' . $id] ?? null;
                if ($childRefs) {
                    $parentVal = $getResolvedNominal($id, $wilId, $thn, $pList, false);
                    if ($parentVal > 0)
                        return $parentVal;

                    $total = 0;
                    foreach ($childRefs as $ref) {
                        $cid = (int) str_replace('sub-', '', $ref);
                        $total += $getResolvedNominal($cid, $wilId, $thn, $pList, true);
                    }
                    return $total;
                }

                // Sub-category logic (Sum of sub-categories if any exist)
                $subs = $subList->where('id_kategori', $id);
                if ($subs->count() > 0) {
                    $total = 0;
                    foreach ($subs as $s) {
                        // Check if sub-category has its own hierarchy
                        $sId = $s->id_sub_kategori;
                        if (isset($hierarchy['sub-' . $sId])) {
                            $sVal = $getResolvedNominal($sId, $wilId, $thn, $pList, true);
                            if ($sVal > 0) {
                                $total += $sVal;
                            } else {
                                foreach ($hierarchy['sub-' . $sId] as $cref) {
                                    $ccid = (int) str_replace('sub-', '', $cref);
                                    $total += $getResolvedNominal($ccid, $wilId, $thn, $pList, true);
                                }
                            }
                        } else {
                            $total += $getResolvedNominal($sId, $wilId, $thn, $pList, true);
                        }
                    }
                    return $total;
                }

                return $getResolvedNominal($id, $wilId, $thn, $pList, false);
            };

            foreach ($filterKabkota as $kab) {
                $now = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahun, $triwulanList);
                $prev = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahunPrev, $triwulanList);

                $kabData[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                $sumNowKab += $now;
                $sumPrevKab += $prev;
            }

            $totalKab = $sumPrevKab != 0 ? (($sumNowKab - $sumPrevKab) / $sumPrevKab) * 100 : 0;

            $nowProv = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahun, $triwulanList);
            $prevProv = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahunPrev, $triwulanList);

            // 🔥 FIX RESUME P0: Nominal-level fallback to ensure growth matches aggregate kabkota
            if ($resume === 'p0' || !$resume) {
                if ($nowProv == 0)
                    $nowProv = $sumNowKab;
                if ($prevProv == 0)
                    $prevProv = $sumPrevKab;
            }

            $provinsi = $prevProv != 0 ? (($nowProv - $prevProv) / $prevProv) * 100 : 0;

            $selisih = round($totalKab - $provinsi, 9);

            $rekonsiliasi->push([
                'kategori' => $kat->nama_kategori,
                'is_sub' => false,
                'provinsi' => $provinsi,
                'total_kab' => $totalKab,
                'kab' => $kabData,
                'selisih' => $selisih,
                'cek' => ($provinsi > 0 && $totalKab < 0) || ($provinsi < 0 && $totalKab > 0)
                    ? 'BEDA ARAH'
                    : ((abs($selisih) > 5 && abs($selisih) <= 10) ? 'SELISIH 5 - 10 %'
                        : (abs($selisih) > 10 ? 'SELISIH > 10 %' : 'NORMAL'))
            ]);

            // ===================== SUBKATEGORI =====================
            foreach ($subKats as $sub) {
                $kabData = [];
                $sumNowKab = $sumPrevKab = 0;
                foreach ($filterKabkota as $kab) {
                    $now = $getResolvedNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahun, $triwulanList, true);
                    $prev = $getResolvedNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahunPrev, $triwulanList, true);

                    $kabData[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                    $sumNowKab += $now;
                    $sumPrevKab += $prev;
                }

                $totalKab = $sumPrevKab != 0 ? (($sumNowKab - $sumPrevKab) / $sumPrevKab) * 100 : 0;

                $nowProv = $getResolvedNominal($sub->id_sub_kategori, $idProvinsi, $tahun, $triwulanList, true);
                $prevProv = $getResolvedNominal($sub->id_sub_kategori, $idProvinsi, $tahunPrev, $triwulanList, true);

                // 🔥 FIX RESUME P0: Nominal-level fallback for sub-categories
                if ($resume === 'p0' || !$resume) {
                    if ($nowProv == 0)
                        $nowProv = $sumNowKab;
                    if ($prevProv == 0)
                        $prevProv = $sumPrevKab;
                }

                $provinsi = $prevProv != 0 ? (($nowProv - $prevProv) / $prevProv) * 100 : 0;

                $selisih = round($totalKab - $provinsi, 9);

                $rekonsiliasi->push([
                    'kategori' => $sub->nama_sub_kategori,
                    'is_sub' => true,
                    'provinsi' => $provinsi,
                    'total_kab' => $totalKab,
                    'kab' => $kabData,
                    'selisih' => $selisih,
                    'cek' => ($provinsi > 0 && $totalKab < 0) || ($provinsi < 0 && $totalKab > 0)
                        ? 'BEDA ARAH'
                        : ((abs($selisih) > 5 && abs($selisih) <= 10) ? 'SELISIH 5 - 10 %'
                            : (abs($selisih) > 10 ? 'SELISIH > 10 %' : 'NORMAL'))
                ]);
            }
        }

        /* ===================== SINKRON LAPUS (KHUSUS PENGELUARAN) ===================== */
        if ($pendekatan === 'pengeluaran') {
            $pdrbLU = $rekonsiliasi->first(fn($r) => str_contains(strtoupper($r['kategori']), 'PRODUK DOMESTIK REGIONAL BRUTO') && !str_contains(strtoupper($r['kategori']), 'LAPUS'));
            if ($pdrbLU) {
                $rekonsiliasi = $rekonsiliasi->map(function ($row) use ($pdrbLU, $resume) {
                    if (!$row['is_sub'] && strtoupper(trim($row['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS') {
                        $row['provinsi'] = $pdrbLU['provinsi'];
                        $row['total_kab'] = $pdrbLU['total_kab'];
                        $row['kab'] = $pdrbLU['kab'];
                        if ($row['provinsi'] == 0 && ($resume === 'p0' || !$resume)) {
                            $row['provinsi'] = $row['total_kab'];
                        }
                        $row['selisih'] = $row['total_kab'] - $row['provinsi'];
                        $row['cek'] = ($row['provinsi'] > 0 && $row['total_kab'] < 0) || ($row['provinsi'] < 0 && $row['total_kab'] > 0) ? 'BEDA ARAH' : (abs($row['selisih']) > 10 ? 'SELISIH > 10 %' : (abs($row['selisih']) > 5 ? 'SELISIH 5 - 10 %' : 'NORMAL'));
                    }
                    return $row;
                })->values();
            }
        }

        $isLapanganUsaha = ($pendekatan === 'lapangan_usaha');

        /* ===================== GENERATE KODE KATEGORI ===================== */
        $lastKategori = null;
        $subIndex = 0;

        $rekonsiliasi = $rekonsiliasi->map(function ($row) use (&$lastKategori, &$subIndex) {
            if (!$row['is_sub']) {
                $row['kode'] = PdrbHelper::getKategoriCode($row['kategori']);
                $lastKategori = $row['kategori'];
                $subIndex = 0;
            } else {
                $subIndex++;
                $row['kode'] = PdrbHelper::getSubCode($subIndex, $lastKategori);
            }
            return $row;
        });

        // Sebelum return view di method yony()
        if ($pendekatan === 'pengeluaran') {
            $data = ['rekonsiliasi' => $rekonsiliasi, 'allKabkota' => $allKabkota];
            $data = $this->fixNetEkspor($data, false, $allKabkota);
            $rekonsiliasi = $data['rekonsiliasi'];
        }

        return view('rekonsiliasi.ytoy', compact(
            'tahunList',
            'periodeList',
            'tahun',
            'triwulan',
            'wilayah',
            'allKabkota',
            'filterKabkota',
            'showTotal',
            'showProvinsi',
            'showKabkota',
            'tipePdrb',
            'pendekatan',
            'isLapanganUsaha',
            'resume'
        ))->with('rekonsiliasi', $rekonsiliasi);
    }


    public function ctoc(Request $request)
    {
        $user = auth()->user();

        $wilayahUser = \App\Models\Wilayah::find($user->id_wilayah);

        $provKode = $wilayahUser->id_provinsi;

        $idProvinsi = $wilayahUser->tipe === 'provinsi'
            ? $wilayahUser->id_wilayah
            : \App\Models\Wilayah::where('id_provinsi', $provKode)
                ->where('tipe', 'provinsi')
                ->value('id_wilayah');

        $tahun = (int) $request->get('tahun');
        $triwulan = $request->get('triwulan');
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha'); // default
        $tipePdrb = 'konstan';
        $resume = $request->get('resume'); // 🔥 cek resume

        /* ================= MASTER DATA ================= */
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();

        /* ================= KAB / KOTA ================= */
        $allKabkota = Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
            ->where('kabupaten.id_provinsi', $provKode)

            ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
            ->select('kabupaten.*', 'wilayah.id_wilayah')
            ->get();

        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];

        $allKabkota = $allKabkota->sortBy(fn($item) => array_search($item->nama_kabupaten, $urutan))->values();
        $kabIds = $allKabkota->pluck('id_wilayah')->toArray();
        $kabIds[] = $idProvinsi;


        $grouped = collect();

        if (!$tahun || !$triwulan) {
            return view('rekonsiliasi.ctoc', compact(
                'tahunList',
                'periodeList',
                'tahun',
                'triwulan',
                'grouped',
                'allKabkota',
                'tipePdrb',
                'pendekatan'
            ));
        }

        /* ================= PERIODE & TAHUN ================= */
        $tahunModel = Tahun::find($tahun);
        $tahunVal = $tahunModel ? $tahunModel->tahun : date('Y');

        $tahunPrevModel = Tahun::where('tahun', $tahunVal - 1)->first();
        $tahunPrev = $tahunPrevModel ? $tahunPrevModel->id_tahun : $tahun - 1;

        if ($triwulan === 'all') {
            $periodeNow = [1, 2, 3, 4];
            $periodePrev = [1, 2, 3, 4];
        } else {
            $periodeNow = range(1, (int) $triwulan);
            $periodePrev = $periodeNow;
        }

        /* ================= KATEGORI & SUBKATEGORI ================= */
        $kategoriList = Kategori::when($pendekatan === 'pengeluaran', function ($q) {
            $q->whereIn('pendekatan', ['lapangan_usaha', 'pengeluaran']);
        }, function ($q) use ($pendekatan) {
            $q->where('pendekatan', $pendekatan);
        })->orderBy('id_kategori')->get();

        // dd($kategoriList->pluck('nama_kategori'));

        $subList = SubKategori::whereIn('id_kategori', $kategoriList->pluck('id_kategori'))->get();

        /* ================= VALUE RESOLVER ================= */
        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        /* ================= RAW DATA ================= */
        $allKategoriRaw = NilaiKategori::whereIn('id_wilayah', $kabIds)
            ->whereIn('id_tahun', [$tahun, $tahunPrev])
            ->whereIn('id_periode', array_merge($periodeNow, $periodePrev))
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', $kabIds)
            ->whereIn('id_tahun', [$tahun, $tahunPrev])
            ->whereIn('id_periode', array_merge($periodeNow, $periodePrev))
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tahap_data);

        /* ================= NOMINAL HELPERS ================= */
        $getNominal = function ($id, $wilId, $thn, $pList, $isSub) use ($allKategoriRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allKategoriRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $thn . '-' . $p;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $thn, $pList) use (&$getCategoryNominal, $getNominal, $pendekatan, $subList) {
            $hierarchy = [
                'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
                'sub-22' => ['sub-23', 'sub-24'],
            ];

            // PDRB Total logic (ID 21 for Lapus, ID 31 for Pengeluaran)
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $thn, $pList);
                }
                return $total;
            }

            // Hierarchy support for categories
            $childRefs = $hierarchy['cat-' . $id] ?? null;
            if ($childRefs) {
                $parentVal = $getNominal($id, $wilId, $thn, $pList, false);
                if ($parentVal > 0)
                    return $parentVal;

                $total = 0;
                foreach ($childRefs as $ref) {
                    $cid = (int) str_replace('sub-', '', $ref);
                    $total += $getNominal($cid, $wilId, $thn, $pList, true);
                }
                return $total;
            }

            // Sub-category sum
            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    $sId = $s->id_sub_kategori;
                    if (isset($hierarchy['sub-' . $sId])) {
                        $sVal = $getNominal($sId, $wilId, $thn, $pList, true);
                        if ($sVal > 0) {
                            $total += $sVal;
                        } else {
                            foreach ($hierarchy['sub-' . $sId] as $cref) {
                                $ccid = (int) str_replace('sub-', '', $cref);
                                $total += $getNominal($ccid, $wilId, $thn, $pList, true);
                            }
                        }
                    } else {
                        $total += $getNominal($sId, $wilId, $thn, $pList, true);
                    }
                }
                return $total;
            }

            return $getNominal($id, $wilId, $thn, $pList, false);
        };
        // Refactored to use raw record resolution and summation helpers above

        /* ================= HITUNG PERTUMBUHAN ================= */
        foreach ($kategoriList as $kat) {
            $kabData = [];
            $nowSum = $prevSum = 0;

            foreach ($allKabkota as $kab) {
                $now = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahun, $periodeNow);
                $prev = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahunPrev, $periodePrev);

                $kabData[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                $nowSum += $now;
                $prevSum += $prev;
            }

            $totalKab = $prevSum != 0 ? (($nowSum - $prevSum) / $prevSum) * 100 : 0;
            $nowProv = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahun, $periodeNow);
            $prevProv = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahunPrev, $periodePrev);

            // 🔥 FIX RESUME P0: Nominal-level fallback to ensure growth matches aggregate kabkota
            if ($resume === 'p0' || !$resume) {
                if ($nowProv == 0)
                    $nowProv = $nowSum;
                if ($prevProv == 0)
                    $prevProv = $prevSum;
            }

            $provinsi = $prevProv != 0 ? (($nowProv - $prevProv) / $prevProv) * 100 : 0;

            $selisih = $totalKab - $provinsi;

            $grouped->push([
                'kategori' => $kat->nama_kategori,
                'pendekatan' => $kat->pendekatan,
                'is_sub' => false,
                'provinsi' => $provinsi,
                'total_kab' => $totalKab,
                'kab' => $kabData,
                'selisih' => $selisih,
                'cek' => ($provinsi > 0 && $totalKab < 0) || ($provinsi < 0 && $totalKab > 0) ? 'BEDA ARAH'
                    : ((abs($selisih) > 5 && abs($selisih) <= 10) ? 'SELISIH 5 - 10 %'
                        : (abs($selisih) > 10 ? 'SELISIH > 10 %' : 'NORMAL'))
            ]);

            foreach ($subList->where('id_kategori', $kat->id_kategori) as $sub) {
                $kabData = [];
                $nowSum = $prevSum = 0;
                foreach ($allKabkota as $kab) {
                    $now = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahun, $periodeNow, true);
                    $prev = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahunPrev, $periodePrev, true);

                    $kabData[$kab->id_wilayah] = $prev != 0 ? (($now - $prev) / $prev) * 100 : 0;
                    $nowSum += $now;
                    $prevSum += $prev;
                }

                $totalKab = $prevSum != 0 ? (($nowSum - $prevSum) / $prevSum) * 100 : 0;
                $nowProv = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahun, $periodeNow, true);
                $prevProv = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahunPrev, $periodePrev, true);

                // 🔥 FIX RESUME P0: Nominal-level fallback for sub-categories
                if ($resume === 'p0' || !$resume) {
                    if ($nowProv == 0)
                        $nowProv = $nowSum;
                    if ($prevProv == 0)
                        $prevProv = $prevSum;
                }

                $provinsi = $prevProv != 0 ? (($nowProv - $prevProv) / $prevProv) * 100 : 0;

                $selisih = $totalKab - $provinsi;

                $grouped->push([
                    'kategori' => $sub->nama_sub_kategori,
                    'pendekatan' => $kat->pendekatan,
                    'is_sub' => true,
                    'provinsi' => $provinsi,
                    'total_kab' => $totalKab,
                    'kab' => $kabData,
                    'selisih' => $selisih,
                    'cek' => ($provinsi > 0 && $totalKab < 0) || ($provinsi < 0 && $totalKab > 0) ? 'BEDA ARAH'
                        : ((abs($selisih) > 5 && abs($selisih) <= 10) ? 'SELISIH 5 - 10 %'
                            : (abs($selisih) > 10 ? 'SELISIH > 10 %' : 'NORMAL'))
                ]);
            }
        }

        /* ===================== SINKRON LAPUS PENGELUARAN ===================== */
        if ($pendekatan === 'pengeluaran') {
            $pdrbLU = $grouped->first(
                fn($r) =>
                !$r['is_sub']
                && str_contains(strtoupper($r['kategori']), 'PRODUK DOMESTIK REGIONAL BRUTO')
                && !str_contains(strtoupper($r['kategori']), 'LAPUS')
            );

            if ($pdrbLU) {
                $grouped = $grouped->map(function ($row) use ($pdrbLU) {
                    if (!$row['is_sub'] && strtoupper(trim($row['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS') {
                        $row['provinsi'] = $pdrbLU['provinsi'];
                        $row['total_kab'] = $pdrbLU['total_kab'];
                        $row['kab'] = $pdrbLU['kab'];

                        // 🔥 FIX RESUME P0: Jika data provinsi 0 dan ini resume P0, gunakan total kabkota
                        if ($row['provinsi'] == 0 && ($resume === 'p0' || !$resume)) {
                            $row['provinsi'] = $row['total_kab'];
                        }

                        $row['selisih'] = $row['total_kab'] - $row['provinsi'];
                        if (($row['provinsi'] > 0 && $row['total_kab'] < 0) || ($row['provinsi'] < 0 && $row['total_kab'] > 0)) {
                            $row['cek'] = 'BEDA ARAH';
                        } elseif (abs($row['selisih']) > 10) {
                            $row['cek'] = 'SELISIH > 10 %';
                        } elseif (abs($row['selisih']) > 5) {
                            $row['cek'] = 'SELISIH 5 - 10 %';
                        } else {
                            $row['cek'] = 'NORMAL';
                        }
                    }
                    return $row;
                })->values();
            }
        }

        $wilayah = '';
        $showTotal = true;

        /* ===================== FILTER SESUAI PENDEKATAN ===================== */
        $grouped = $grouped->filter(function ($row) use ($pendekatan) {
            if ($pendekatan === 'lapangan_usaha')
                return $row['pendekatan'] === 'lapangan_usaha';
            if ($pendekatan === 'pengeluaran') {
                if ($row['pendekatan'] === 'pengeluaran')
                    return true;
                if (strtoupper(trim($row['kategori'])) === 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS')
                    return true;
                return false;
            }
            return true;
        })->values();

        /* ===================== GENERATE KODE KATEGORI ===================== */
        $lastKategori = null;
        $subIndex = 0;

        $grouped = $grouped->map(function ($row) use (&$lastKategori, &$subIndex) {

            if (!$row['is_sub']) {

                $row['kode'] = PdrbHelper::getKategoriCode($row['kategori']);
                $lastKategori = $row['kategori'];
                $subIndex = 0;

            } else {

                $subIndex++;
                $row['kode'] = PdrbHelper::getSubCode($subIndex, $lastKategori);

            }

            return $row;
        });

        // Sebelum return view di method ctoc()
        if ($pendekatan === 'pengeluaran') {
            $data = ['grouped' => $grouped, 'allKabkota' => $allKabkota];
            $data = $this->fixNetEkspor($data, true, $allKabkota);
            $grouped = $data['grouped'];
        }


        return view('rekonsiliasi.ctoc', compact(
            'tahunList',
            'periodeList',
            'tahun',
            'triwulan',
            'allKabkota',
            'tipePdrb',
            'pendekatan',
            'resume'
        ))->with('grouped', $grouped);
    }



    public function indeksImplisit(Request $request)
    {
        $user = auth()->user();
        $wilayahUser = \App\Models\Wilayah::find($user->id_wilayah);
        $provKode = $wilayahUser->id_provinsi;

        $idProvinsi = $wilayahUser->tipe === 'provinsi'
            ? $wilayahUser->id_wilayah
            : \App\Models\Wilayah::where('id_provinsi', $provKode)
                ->where('tipe', 'provinsi')
                ->value('id_wilayah');

        $tahun = (int) $request->get('tahun');
        $triwulan = $request->get('triwulan');
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        $tipePdrb = $request->tipe_pdrb ?? 'berlaku';
        $resume = $request->get('resume');

        /* ================= MASTER DATA ================= */
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();

        /* ================= KAB / KOTA ================= */
        $allKabkota = Kabupaten::join('wilayah', 'wilayah.id_kabupaten', '=', 'kabupaten.id_kabupaten')
            ->where('kabupaten.id_provinsi', $provKode)
            ->whereIn('kabupaten.tipe', ['kabupaten', 'kota'])
            ->select('kabupaten.*', 'wilayah.id_wilayah')
            ->get();

        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];

        $allKabkota = $allKabkota->sortBy(fn($item) => array_search($item->nama_kabupaten, $urutan))->values();
        $kabIds = $allKabkota->pluck('id_wilayah')->toArray();
        $kabIds[] = $idProvinsi;

        $grouped = collect();

        if (!$tahun || !$triwulan) {
            return view('rekonsiliasi.indeksImplisit', compact(
                'tahunList',
                'periodeList',
                'tahun',
                'triwulan',
                'grouped',
                'allKabkota',
                'pendekatan',
                'tipePdrb',
                'resume'
            ));
        }

        /* ================= DATA PREPARATION ================= */
        $triwulans = ($triwulan === 'all') ? [1, 2, 3, 4] : [(int) $triwulan];

        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        $allKategoriRaw = NilaiKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $tahun)
            ->whereIn('id_periode', $triwulans)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tipe_pdrb . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $tahun)
            ->whereIn('id_periode', $triwulans)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tipe_pdrb . '-' . $x->tahap_data);

        $subList = SubKategori::all();

        $kategoriList = Kategori::where('pendekatan', $pendekatan)
            ->orderBy('id_kategori')
            ->get();

        $getNominal = function ($id, $wilId, $thn, $pList, $isSub, $type) use ($allKategoriRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allKategoriRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $p . '-' . $type;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $thn, $pList, $type) use (&$getCategoryNominal, $subList, $pendekatan, $getNominal) {
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $thn, $pList, $type);
                }
                return $total;
            }
            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    $total += $getNominal($s->id_sub_kategori, $wilId, $thn, $pList, true, $type);
                }
                return $total;
            }
            return $getNominal($id, $wilId, $thn, $pList, false, $type);
        };

        /* ================= CALCULATION ================= */
        foreach ($kategoriList as $kat) {
            $kabData = [];
            $totalBerlakuKab = $totalKonstanKab = 0;

            foreach ($allKabkota as $kab) {
                $b = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahun, $triwulans, 'berlaku');
                $k = $getCategoryNominal($kat->id_kategori, $kab->id_wilayah, $tahun, $triwulans, 'konstan');
                $idx = ($k != 0) ? ($b / $k) * 100 : 0;
                $kabData[$kab->id_wilayah] = $idx;
                $totalBerlakuKab += $b;
                $totalKonstanKab += $k;
            }

            $provB = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahun, $triwulans, 'berlaku');
            $provK = $getCategoryNominal($kat->id_kategori, $idProvinsi, $tahun, $triwulans, 'konstan');

            if ($provK <= 0 && $totalKonstanKab > 0) {
                $provB = $totalBerlakuKab;
                $provK = $totalKonstanKab;
            }

            $provinsi = ($provK != 0) ? ($provB / $provK) * 100 : 0;
            $totalKab = ($totalKonstanKab != 0) ? ($totalBerlakuKab / $totalKonstanKab) * 100 : 0;
            $selisih = $totalKab - $provinsi;

            $minKab = !empty($kabData) ? min($kabData) : 0;
            $maxKab = !empty($kabData) ? max($kabData) : 0;

            if (($provinsi > 0 && $minKab < 0) || ($provinsi < 0 && $maxKab > 0)) {
                $cek = 'BEDA ARAH';
            } elseif (abs($selisih) > 10) {
                $cek = 'SELISIH > 10 %';
            } elseif (abs($selisih) > 5) {
                $cek = 'SELISIH 5 - 10 %';
            } else {
                $cek = 'SEIMBANG';
            }

            $grouped->push([
                'kategori' => $kat->nama_kategori,
                'pendekatan' => $kat->pendekatan,
                'is_sub' => false,
                'provinsi' => $provinsi,
                'total_kab' => $totalKab,
                'kab' => $kabData,
                'selisih' => $selisih,
                'cek' => $cek,
                'kode' => \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori)
            ]);

            $subIndex = 0;
            foreach ($subList->where('id_kategori', $kat->id_kategori) as $sub) {
                $subIndex++;
                $kabDataSub = [];
                $totalBerlakuKabSub = $totalKonstanKabSub = 0;

                foreach ($allKabkota as $kab) {
                    $b = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahun, $triwulans, true, 'berlaku');
                    $k = $getNominal($sub->id_sub_kategori, $kab->id_wilayah, $tahun, $triwulans, true, 'konstan');
                    $idx = ($k != 0) ? ($b / $k) * 100 : 0;
                    $kabDataSub[$kab->id_wilayah] = $idx;
                    $totalBerlakuKabSub += $b;
                    $totalKonstanKabSub += $k;
                }

                $provBSub = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahun, $triwulans, true, 'berlaku');
                $provKSub = $getNominal($sub->id_sub_kategori, $idProvinsi, $tahun, $triwulans, true, 'konstan');

                if ($provKSub <= 0 && $totalKonstanKabSub > 0) {
                    $provBSub = $totalBerlakuKabSub;
                    $provKSub = $totalKonstanKabSub;
                }

                $provinsiSub = ($provKSub != 0) ? ($provBSub / $provKSub) * 100 : 0;
                $totalKabSub = ($totalKonstanKabSub != 0) ? ($totalBerlakuKabSub / $totalKonstanKabSub) * 100 : 0;
                $selisihSub = $totalKabSub - $provinsiSub;

                $minKabSub = !empty($kabDataSub) ? min($kabDataSub) : 0;
                $maxKabSub = !empty($kabDataSub) ? max($kabDataSub) : 0;

                if (($provinsiSub > 0 && $minKabSub < 0) || ($provinsiSub < 0 && $maxKabSub > 0)) {
                    $cekSub = 'BEDA ARAH';
                } elseif (abs($selisihSub) > 10) {
                    $cekSub = 'SELISIH > 10 %';
                } elseif (abs($selisihSub) > 5) {
                    $cekSub = 'SELISIH 5 - 10 %';
                } else {
                    $cekSub = 'SEIMBANG';
                }

                $grouped->push([
                    'kategori' => $sub->nama_sub_kategori,
                    'pendekatan' => $kat->pendekatan,
                    'is_sub' => true,
                    'provinsi' => $provinsiSub,
                    'total_kab' => $totalKabSub,
                    'kab' => $kabDataSub,
                    'selisih' => $selisihSub,
                    'cek' => $cekSub,
                    'kode' => \App\Helpers\PdrbHelper::getSubCode($subIndex, $kat->nama_kategori)
                ]);
            }
        }

        // Sebelum return view di method indeksImplisit()
        if ($pendekatan === 'pengeluaran') {
            $data = ['grouped' => $grouped, 'allKabkota' => $allKabkota];
            $data = $this->fixNetEkspor($data, true, $allKabkota);
            $grouped = $data['grouped'];
        }

        return view('rekonsiliasi.indeksImplisit', compact(
            'tahunList',
            'periodeList',
            'tahun',
            'triwulan',
            'grouped',
            'allKabkota',
            'pendekatan',
            'tipePdrb',
            'resume'
        ));
    }

    public function lajuImplisit(Request $request)
    {
        /* ================= MASTER ================= */
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');

        $kategoriList = Kategori::where('pendekatan', $pendekatan)->orderBy('id_kategori')->get();
        $subList = SubKategori::whereHas('kategori', fn($q) => $q->where('pendekatan', $pendekatan))->orderBy('id_sub_kategori')->get();

        $wilayahs = Wilayah::orderBy('id_wilayah')->get();
        $allKabkota = $wilayahs->whereIn('tipe', ['kabupaten', 'kota']);
        $kabIds = $allKabkota->pluck('id_wilayah')->toArray();
        $idProvinsi = $wilayahs->where('tipe', 'provinsi')->first()?->id_wilayah;

        // 🔥 DEFINE $selectedWilayahs DI AWAL
        $selectedWilayahs = $wilayahs;

        /* ================= PARAMETER ================= */
        $selectedTahun = $request->tahun;
        $selectedPeriode = $request->triwulan ?? 'all';
        $resume = $request->get('resume', 'p0');

        $hasilKategori = [];
        $hasilSub = [];
        $hasilTotalKabKota = [];

        if (!$selectedTahun) {
            return view('rekonsiliasi.lajuImplisit', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'wilayahs',
                'allKabkota',
                'selectedTahun',
                'selectedPeriode',
                'pendekatan',
                'resume',
                'hasilKategori',
                'hasilSub',
                'hasilTotalKabKota',
                'selectedWilayahs'
            ))->with('triwulan', $selectedPeriode);  // ← Gunakan selectedPeriode, bukan triwulan
        }

        /* ================= DATA PREPARATION ================= */
        $tahunObj = Tahun::find($selectedTahun);
        if (!$tahunObj) {
            return view('rekonsiliasi.lajuImplisit', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'wilayahs',
                'allKabkota',
                'selectedTahun',
                'selectedPeriode',
                'pendekatan',
                'resume',
                'hasilKategori',
                'hasilSub',
                'hasilTotalKabKota',
                'selectedWilayahs'
            ))->with('triwulan', $selectedPeriode);
        }

        $prevTahunObj = Tahun::where('tahun', $tahunObj->tahun - 1)->first();

        $triwulansNow = ($selectedPeriode === 'all') ? [1, 2, 3, 4] : [(int) $selectedPeriode];
        $triwulansPrev = [];
        $prevTahunId = $selectedTahun;

        if ($selectedPeriode === 'all') {
            $triwulansPrev = [1, 2, 3, 4];
            $prevTahunId = $prevTahunObj?->id_tahun;
        } else {
            $p = (int) $selectedPeriode;
            if ($p == 1) {
                $triwulansPrev = [4];
                $prevTahunId = $prevTahunObj?->id_tahun;
            } else {
                $triwulansPrev = [$p - 1];
                $prevTahunId = $selectedTahun;
            }
        }

        if (!$prevTahunId) {
            return view('rekonsiliasi.lajuImplisit', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'wilayahs',
                'allKabkota',
                'selectedTahun',
                'selectedPeriode',
                'pendekatan',
                'resume',
                'hasilKategori',
                'hasilSub',
                'hasilTotalKabKota',
                'selectedWilayahs'
            ))->with('triwulan', $selectedPeriode);
        }

        /* ================= DATA PREPARATION ================= */
        $tahunObj = Tahun::find($selectedTahun);
        $prevTahunObj = Tahun::where('tahun', $tahunObj->tahun - 1)->first();

        $triwulansNow = ($selectedPeriode === 'all') ? [1, 2, 3, 4] : [(int) $selectedPeriode];
        $triwulansPrev = [];
        $prevTahunId = $selectedTahun;

        if ($selectedPeriode === 'all') {
            $triwulansPrev = [1, 2, 3, 4];
            $prevTahunId = $prevTahunObj?->id_tahun;
        } else {
            $p = (int) $selectedPeriode;
            if ($p == 1) {
                $triwulansPrev = [4];
                $prevTahunId = $prevTahunObj?->id_tahun;
            } else {
                $triwulansPrev = [$p - 1];
                $prevTahunId = $selectedTahun;
            }
        }

        if (!$prevTahunId) {
            // Fallback or empty if no previous year
            return view('rekonsiliasi.lajuImplisit', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'wilayahs',
                'allKabkota',
                'selectedTahun',
                'selectedPeriode',
                'pendekatan',
                'resume',
                'hasilKategori',
                'hasilSub',
                'hasilTotalKabKota'
            ));
        }

        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        $allRaw = NilaiKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->whereIn('id_tahun', [$selectedTahun, $prevTahunId])
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tipe_pdrb . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->whereIn('id_tahun', [$selectedTahun, $prevTahunId])
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_tahun . '-' . $x->id_periode . '-' . $x->tipe_pdrb . '-' . $x->tahap_data);

        $getNominal = function ($id, $wilId, $thn, $pList, $isSub, $type) use ($allRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $thn . '-' . $p . '-' . $type;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $thn, $pList, $type) use (&$getCategoryNominal, $subList, $pendekatan, $getNominal) {
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $thn, $pList, $type);
                }
                return $total;
            }
            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    $total += $getNominal($s->id_sub_kategori, $wilId, $thn, $pList, true, $type);
                }
                return $total;
            }
            return $getNominal($id, $wilId, $thn, $pList, false, $type);
        };

        /* ================= CALCULATION ================= */
        foreach ($kategoriList as $k) {
            $katId = $k->id_kategori;
            $totalBerlakuNowKab = $totalKonstanNowKab = 0;
            $totalBerlakuPrevKab = $totalKonstanPrevKab = 0;

            foreach ($wilayahs as $w) {
                $bNow = $getCategoryNominal($katId, $w->id_wilayah, $selectedTahun, $triwulansNow, 'berlaku');
                $kNow = $getCategoryNominal($katId, $w->id_wilayah, $selectedTahun, $triwulansNow, 'konstan');
                $bPrev = $getCategoryNominal($katId, $w->id_wilayah, $prevTahunId, $triwulansPrev, 'berlaku');
                $kPrev = $getCategoryNominal($katId, $w->id_wilayah, $prevTahunId, $triwulansPrev, 'konstan');

                $idxNow = ($kNow != 0) ? ($bNow / $kNow) * 100 : 0;
                $idxPrev = ($kPrev != 0) ? ($bPrev / $kPrev) * 100 : 0;

                $laju = ($idxPrev != 0) ? (($idxNow - $idxPrev) / $idxPrev) * 100 : 0;
                $hasilKategori[$katId][$w->id_wilayah] = $laju;

                if (in_array($w->id_wilayah, $kabIds)) {
                    $totalBerlakuNowKab += $bNow;
                    $totalKonstanNowKab += $kNow;
                    $totalBerlakuPrevKab += $bPrev;
                    $totalKonstanPrevKab += $kPrev;
                }
            }

            $idxNowTotal = ($totalKonstanNowKab != 0) ? ($totalBerlakuNowKab / $totalKonstanNowKab) * 100 : 0;
            $idxPrevTotal = ($totalKonstanPrevKab != 0) ? ($totalBerlakuPrevKab / $totalKonstanPrevKab) * 100 : 0;
            $hasilTotalKabKota[$katId] = ($idxPrevTotal != 0) ? (($idxNowTotal - $idxPrevTotal) / $idxPrevTotal) * 100 : 0;
        }

        foreach ($subList as $s) {
            $subId = $s->id_sub_kategori;
            $totalBerlakuNowKab = $totalKonstanNowKab = 0;
            $totalBerlakuPrevKab = $totalKonstanPrevKab = 0;

            foreach ($wilayahs as $w) {
                $bNow = $getNominal($subId, $w->id_wilayah, $selectedTahun, $triwulansNow, true, 'berlaku');
                $kNow = $getNominal($subId, $w->id_wilayah, $selectedTahun, $triwulansNow, true, 'konstan');
                $bPrev = $getNominal($subId, $w->id_wilayah, $prevTahunId, $triwulansPrev, true, 'berlaku');
                $kPrev = $getNominal($subId, $w->id_wilayah, $prevTahunId, $triwulansPrev, true, 'konstan');

                $idxNow = ($kNow != 0) ? ($bNow / $kNow) * 100 : 0;
                $idxPrev = ($kPrev != 0) ? ($bPrev / $kPrev) * 100 : 0;

                $laju = ($idxPrev != 0) ? (($idxNow - $idxPrev) / $idxPrev) * 100 : 0;
                $hasilSub[$subId][$w->id_wilayah] = $laju;

                if (in_array($w->id_wilayah, $kabIds)) {
                    $totalBerlakuNowKab += $bNow;
                    $totalKonstanNowKab += $kNow;
                    $totalBerlakuPrevKab += $bPrev;
                    $totalKonstanPrevKab += $kPrev;
                }
            }

            $idxNowTotal = ($totalKonstanNowKab != 0) ? ($totalBerlakuNowKab / $totalKonstanNowKab) * 100 : 0;
            $idxPrevTotal = ($totalKonstanPrevKab != 0) ? ($totalBerlakuPrevKab / $totalKonstanPrevKab) * 100 : 0;
            $hasilTotalKabKota['sub_' . $subId] = ($idxPrevTotal != 0) ? (($idxNowTotal - $idxPrevTotal) / $idxPrevTotal) * 100 : 0;
        }

        $kategoriList = $kategoriList->values()->map(function ($kat) {
            $kat->kode = \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori);
            return $kat;
        });

        $subList = $subList->groupBy('id_kategori')->map(function ($subs, $katId) use ($kategoriList) {
            $kategori = $kategoriList->firstWhere('id_kategori', $katId);
            $namaKategori = $kategori->nama_kategori ?? null;
            return $subs->values()->map(function ($sub, $index) use ($namaKategori) {
                $sub->kode = \App\Helpers\PdrbHelper::getSubCode($index + 1, $namaKategori);
                return $sub;
            });
        })->flatten(1);

        // Sebelum return view di method lajuImplisit()
        if ($pendekatan === 'pengeluaran') {
            // Untuk lajuImplisit struktur datanya berbeda
            // Anda perlu menyesuaikan atau bisa juga diabaikan dulu
        }

        return view('rekonsiliasi.lajuImplisit', compact(
            'tahunList',
            'periodeList',
            'kategoriList',
            'subList',
            'wilayahs',
            'allKabkota',
            'selectedTahun',
            'selectedPeriode',
            'pendekatan',
            'resume',
            'hasilKategori',
            'hasilSub',
            'hasilTotalKabKota',
            'selectedWilayahs'
        ))->with('triwulan', $selectedPeriode);
    }

    private function getNamaWilayah($wilayah)
    {
        if ($wilayah->tipe == 'provinsi' && $wilayah->provinsi) {
            return $wilayah->provinsi->nama_provinsi;
        } elseif (($wilayah->tipe == 'kabupaten' || $wilayah->tipe == 'kota') && $wilayah->kabupaten) {
            return $wilayah->kabupaten->nama_kabupaten;
        }
        return 'Wilayah ' . $wilayah->id_wilayah;
    }
    // =============================

    public function stukturDalam(Request $request)
    {
        $user = auth()->user();

        // ================= MASTER =================
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();
        $pendekatan = $request->pendekatan ?? 'lapangan_usaha';

        // 🔥 REFERENSI PDRB LAPANGAN USAHA (UNTUK BRUTO LAPUS)
        $pdrbLapusKategori = Kategori::where('pendekatan', 'lapangan_usaha')
            ->where('nama_kategori', 'Produk Domestik Regional Bruto')
            ->first();


        $kategoriList = Kategori::where('pendekatan', $pendekatan)
            ->orderBy('id_kategori')
            ->get();

        $subList = SubKategori::whereHas('kategori', function ($q) use ($pendekatan) {
            $q->where('pendekatan', $pendekatan);
        })
            ->orderBy('id_sub_kategori')
            ->get();


        $pendekatan = $request->pendekatan ?? 'lapangan_usaha';



        $wilayahs = Wilayah::with(['provinsi', 'kabupaten'])->orderBy('id_wilayah')->get();
        foreach ($wilayahs as $w) {
            $w->nama_wilayah = $this->getNamaWilayah($w);
        }

        // ================= PARAMETER =================
        $resume = $request->get('resume', 'p0');
        $isResumeP1 = $request->get('resume') === 'p1';

        $selectedTahun = $request->id_tahun ?? $request->tahun;
        $selectedPeriode = $request->id_periode ?? $request->triwulan;
        $tipePdrb = $request->tipe_pdrb ?? 'berlaku';

        if (!$selectedTahun) {
            return view('rekonsiliasi.stukturDalam', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'selectedTahun',
                'selectedPeriode',
                'wilayahs',
                'tipePdrb'
            ));
        }

        // ================= WILAYAH =================
        $selectedWilayahs = $wilayahs;
        /* ===================== URUTAN KAB/KOTA ===================== */
        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];

        // Sort $selectedWilayahs sesuai urutan
        $selectedWilayahs = $selectedWilayahs->sortBy(function ($item) use ($urutan) {
            return array_search($item->nama_wilayah, $urutan);
        })->values();
        $provinsiWilayahs = $wilayahs->where('tipe', 'provinsi');
        $kabupatenWilayahs = $wilayahs->whereIn('tipe', ['kabupaten', 'kota']);
        $kabupatenIds = $kabupatenWilayahs->pluck('id_wilayah')->toArray();
        $idProvinsi = $wilayahs->where('tipe', 'provinsi')->first()?->id_wilayah;
        $kabIds = $kabupatenIds;
        $wilayahIds = array_merge($kabIds, [$idProvinsi]);

        $triwulans = ($selectedPeriode === 'all') ? [1, 2, 3, 4] : [(int) $selectedPeriode];

        // Standardized Resolver logic matching index()
        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        /* ===================== RAW DATA FETCH ===================== */
        $allKategoriRaw = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
            ->where('id_tahun', $selectedTahun)
            ->whereIn('id_periode', $triwulans)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
            ->where('id_tahun', $selectedTahun)
            ->whereIn('id_periode', $triwulans)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        /* ===================== NOMINAL HELPERS ===================== */
        $getNominal = function ($id, $wilId, $thn, $pList, $isSub) use ($allKategoriRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allKategoriRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $p;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $thn, $pList) use (&$getCategoryNominal, $subList, $pendekatan, $getNominal) {
            // PDRB Total logic (ID 21 for Lapus, ID 31 for Pengeluaran)
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $thn, $pList);
                }
                return $total;
            }        // Hierarchy support for categories
            $hierarchy = [
                'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
            ];
            $childRefs = $hierarchy['cat-' . $id] ?? null;
            if ($childRefs) {
                $parentVal = $getNominal($id, $wilId, $thn, $pList, false);
                if ($parentVal > 0)
                    return $parentVal;

                $total = 0;
                foreach ($childRefs as $ref) {
                    $cid = (int) str_replace('sub-', '', $ref);
                    $total += $getNominal($cid, $wilId, $thn, $pList, true);
                }
                return $total;
            }

            // Sub-category logic
            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    $sId = $s->id_sub_kategori;
                    $hierarchy = [
                        'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                        'sub-22' => ['sub-23', 'sub-24'],
                    ];
                    if (isset($hierarchy['sub-' . $sId])) {
                        $sVal = $getNominal($sId, $wilId, $thn, $pList, true);
                        if ($sVal > 0) {
                            $total += $sVal;
                        } else {
                            foreach ($hierarchy['sub-' . $sId] as $cref) {
                                $ccid = (int) str_replace('sub-', '', $cref);
                                $total += $getNominal($ccid, $wilId, $thn, $pList, true);
                            }
                        }
                    } else {
                        $total += $getNominal($sId, $wilId, $thn, $pList, true);
                    }
                }
                return $total;
            }

            return $getNominal($id, $wilId, $thn, $pList, false);
        };

        /* ===================== CALCULATE STRUCTURE ===================== */
        $periodeKey = ($selectedPeriode === 'all') ? 'all' : (int) $selectedPeriode;
        $hasil[$periodeKey] = [
            'dataPerWilayah' => [],
            'totalKabKota' => ['kategori' => [], 'sub' => []]
        ];

        // Get Total PDRB for each region
        $regionTotals = [];
        $totalPdrbKabKota = 0;
        $pdrbTotalId = ($pendekatan === 'lapangan_usaha') ? 21 : 31;

        foreach ($wilayahIds as $wid) {
            $val = $getCategoryNominal($pdrbTotalId, $wid, $selectedTahun, $triwulans);
            $regionTotals[$wid] = $val;
            if (in_array($wid, $kabIds)) {
                $totalPdrbKabKota += $val;
            }
        }

        // 🔥 FIX RESUME P0 for Province Total
        if (($regionTotals[$idProvinsi] ?? 0) == 0 && ($resume === 'p0' || !$resume)) {
            $regionTotals[$idProvinsi] = $totalPdrbKabKota;
        }

        // Categories structure
        foreach ($kategoriList as $kat) {
            $katId = $kat->id_kategori;
            $totalValKabKota = 0;
            $tempValues = [];

            foreach ($selectedWilayahs as $wil) {
                $wid = $wil->id_wilayah;
                $val = $getCategoryNominal($katId, $wid, $selectedTahun, $triwulans);
                $tempValues[$wid] = $val;
                if (in_array($wid, $kabIds)) {
                    $totalValKabKota += $val;
                }
            }

            // 🔥 FIX RESUME P0 for Category
            if (($tempValues[$idProvinsi] ?? 0) == 0 && ($resume === 'p0' || !$resume)) {
                $tempValues[$idProvinsi] = $totalValKabKota;
            }

            foreach ($selectedWilayahs as $wil) {
                $wid = $wil->id_wilayah;
                $val = $tempValues[$wid];
                $totalPdrb = $regionTotals[$wid];
                $struktur = ($totalPdrb != 0) ? ($val / $totalPdrb) * 100 : 0;

                $hasil[$periodeKey]['dataPerWilayah'][$wid]['strukturKategori'][$katId] = [
                    'struktur' => $struktur,
                    'nilai' => $val
                ];
            }

            // Total Kab/Kota structure
            $strukturKabKota = ($totalPdrbKabKota != 0) ? ($totalValKabKota / $totalPdrbKabKota) * 100 : 0;
            $hasil[$periodeKey]['totalKabKota']['kategori'][$katId] = [
                'struktur' => $strukturKabKota
            ];
        }

        // Sub-categories structure
        foreach ($subList as $sub) {
            $subId = $sub->id_sub_kategori;
            $totalValKabKota = 0;
            $tempValues = [];

            foreach ($selectedWilayahs as $wil) {
                $wid = $wil->id_wilayah;
                $val = $getNominal($subId, $wid, $selectedTahun, $triwulans, true);
                $tempValues[$wid] = $val;
                if (in_array($wid, $kabIds)) {
                    $totalValKabKota += $val;
                }
            }

            // 🔥 FIX RESUME P0 for Sub-Category
            if (($tempValues[$idProvinsi] ?? 0) == 0 && ($resume === 'p0' || !$resume)) {
                $tempValues[$idProvinsi] = $totalValKabKota;
            }

            foreach ($selectedWilayahs as $wil) {
                $wid = $wil->id_wilayah;
                $val = $tempValues[$wid];
                $totalPdrb = $regionTotals[$wid];
                $struktur = ($totalPdrb != 0) ? ($val / $totalPdrb) * 100 : 0;

                $hasil[$periodeKey]['dataPerWilayah'][$wid]['strukturSub'][$subId] = [
                    'struktur' => $struktur
                ];
            }

            $strukturKabKota = ($totalPdrbKabKota != 0) ? ($totalValKabKota / $totalPdrbKabKota) * 100 : 0;
            $hasil[$periodeKey]['totalKabKota']['sub'][$subId] = [
                'struktur' => $strukturKabKota
            ];
        }
        /* Tambahkan kode kategori (A, B, C ...) */
        $kategoriList = $kategoriList->values()->map(function ($kat) {
            $kat->kode = \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori);
            return $kat;
        });

        /* Tambahkan kode sub kategori (A.1, A.2 ...) */
        $subList = $subList->groupBy('id_kategori')->map(function ($subs, $katId) use ($kategoriList) {
            $kategori = $kategoriList->firstWhere('id_kategori', $katId);
            $namaKategori = $kategori->nama_kategori ?? null;
            return $subs->values()->map(function ($sub, $index) use ($namaKategori) {
                $sub->kode = \App\Helpers\PdrbHelper::getSubCode($index + 1, $namaKategori);
                return $sub;
            });
        })->flatten(1);

        $tahun = $selectedTahun;
        $triwulan = $selectedPeriode;

        return view('rekonsiliasi.stukturDalam', compact(
            'tahunList',
            'periodeList',
            'kategoriList',
            'subList',
            'hasil',
            'wilayahs',
            'selectedWilayahs',
            'provinsiWilayahs',
            'tahun',
            'triwulan',
            'tipePdrb',
            'pendekatan',
            'resume'
        ));
    }

    // =============================

    public function stukturAntar(Request $request)
    {
        /* ================= MASTER ================= */
        $tahunList = Tahun::orderBy('tahun')->get();
        $periodeList = Periode::orderBy('id_periode')->get();
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');

        $kategoriList = Kategori::where('pendekatan', $pendekatan)->orderBy('id_kategori')->get();
        $subList = SubKategori::whereHas('kategori', fn($q) => $q->where('pendekatan', $pendekatan))->orderBy('id_sub_kategori')->get();

        $wilayahs = Wilayah::orderBy('id_wilayah')->get();
        $kabkota = $wilayahs->whereIn('tipe', ['kabupaten', 'kota']);
        $idProvinsi = $wilayahs->where('tipe', 'provinsi')->first()?->id_wilayah;

        $urutan = [
            'Kabupaten Buton',
            'Kabupaten Muna',
            'Kabupaten Konawe',
            'Kabupaten Kolaka',
            'Kabupaten Konawe Selatan',
            'Kabupaten Bombana',
            'Kabupaten Wakatobi',
            'Kabupaten Kolaka Utara',
            'Kabupaten Buton Utara',
            'Kabupaten Konawe Utara',
            'Kabupaten Kolaka Timur',
            'Kabupaten Konawe Kepulauan',
            'Kabupaten Muna Barat',
            'Kabupaten Buton Tengah',
            'Kabupaten Buton Selatan',
            'Kota Kendari',
            'Kota Baubau'
        ];
        $kabkota = $kabkota->sortBy(fn($w) => array_search($w->nama_wilayah, $urutan))->values();
        $kabIds = $kabkota->pluck('id_wilayah')->toArray();

        /* ================= PARAMETER ================= */
        $selectedTahun = $request->tahun;
        $selectedPeriode = $request->triwulan ?? 'all';
        $tipePdrb = 'berlaku';
        $resume = $request->get('resume', 'p0');

        $hasilKategori = [];
        $hasilSub = [];

        if (!$selectedTahun) {
            return view('rekonsiliasi.stukturAntar', compact(
                'tahunList',
                'periodeList',
                'kategoriList',
                'subList',
                'wilayahs',
                'kabkota',
                'selectedTahun',
                'selectedPeriode',
                'tipePdrb',
                'hasilKategori',
                'hasilSub',
                'pendekatan',
                'resume'
            ));
        }

        /* ================= DATA PREPARATION ================= */
        $triwulans = ($selectedPeriode === 'all') ? [1, 2, 3, 4] : [(int) $selectedPeriode];

        $resolveValue = function ($awal, $rekon) {
            $awalVal = $awal ? (float) $awal->nilai : 0;
            $rekVal = $rekon ? (float) $rekon->nilai : 0;
            if ($awalVal != 0 && abs($rekVal) <= abs($awalVal) * 0.3) {
                return $awalVal + $rekVal;
            }
            return ($rekon !== null) ? $rekVal : $awalVal;
        };

        $allKategoriRaw = NilaiKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $selectedTahun)
            ->whereIn('id_periode', $triwulans)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        $allSubRaw = NilaiSubKategori::whereIn('id_wilayah', array_merge($kabIds, [$idProvinsi]))
            ->where('id_tahun', $selectedTahun)
            ->whereIn('id_periode', $triwulans)
            ->where('tipe_pdrb', $tipePdrb)
            ->get()
            ->groupBy(fn($x) => $x->id_sub_kategori . '-' . $x->id_wilayah . '-' . $x->id_periode . '-' . $x->tahap_data);

        $getNominal = function ($id, $wilId, $thn, $pList, $isSub) use ($allKategoriRaw, $allSubRaw, $resolveValue, $resume) {
            $total = 0;
            $source = $isSub ? $allSubRaw : $allKategoriRaw;
            foreach ($pList as $p) {
                $baseKey = $id . '-' . $wilId . '-' . $p;
                $awal = ($source[$baseKey . '-awal'] ?? collect())->first();
                $rekon = ($resume === 'p1') ? ($source[$baseKey . '-rekonsiliasi'] ?? collect())->first() : null;
                $total += $resolveValue($awal, $rekon);
            }
            return $total;
        };

        $getCategoryNominal = function ($id, $wilId, $thn, $pList) use (&$getCategoryNominal, $subList, $pendekatan, $getNominal) {
            $hierarchy = [
                'cat-1' => ['sub-1', 'sub-9', 'sub-10'],
                'sub-1' => ['sub-2', 'sub-3', 'sub-4', 'sub-5', 'sub-6', 'sub-7', 'sub-8'],
                'cat-3' => ['sub-22', 'sub-25', 'sub-26', 'sub-27', 'sub-28', 'sub-29', 'sub-30', 'sub-31', 'sub-32', 'sub-33', 'sub-34', 'sub-35', 'sub-36', 'sub-37', 'sub-38', 'sub-39'],
                'sub-22' => ['sub-23', 'sub-24'],
            ];

            // PDRB Total logic (ID 21 for Lapus, ID 31 for Pengeluaran)
            if (($pendekatan === 'lapangan_usaha' && $id == 21) || ($pendekatan === 'pengeluaran' && $id == 31)) {
                $ids = ($pendekatan === 'lapangan_usaha')
                    ? [1, 2, 3, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
                    : [23, 24, 25, 26, 27, 28];
                $total = 0;
                foreach ($ids as $kid) {
                    $total += $getCategoryNominal($kid, $wilId, $thn, $pList);
                }
                return $total;
            }

            // Hierarchy support for categories
            $childRefs = $hierarchy['cat-' . $id] ?? null;
            if ($childRefs) {
                $parentVal = $getNominal($id, $wilId, $thn, $pList, false);
                if ($parentVal > 0)
                    return $parentVal;

                $total = 0;
                foreach ($childRefs as $ref) {
                    $cid = (int) str_replace('sub-', '', $ref);
                    $total += $getNominal($cid, $wilId, $thn, $pList, true);
                }
                return $total;
            }

            // Sub-category logic
            $subs = $subList->where('id_kategori', $id);
            if ($subs->count() > 0) {
                $total = 0;
                foreach ($subs as $s) {
                    $sId = $s->id_sub_kategori;
                    if (isset($hierarchy['sub-' . $sId])) {
                        $sVal = $getNominal($sId, $wilId, $thn, $pList, true);
                        if ($sVal > 0) {
                            $total += $sVal;
                        } else {
                            foreach ($hierarchy['sub-' . $sId] as $cref) {
                                $ccid = (int) str_replace('sub-', '', $cref);
                                $total += $getNominal($ccid, $wilId, $thn, $pList, true);
                            }
                        }
                    } else {
                        $total += $getNominal($sId, $wilId, $thn, $pList, true);
                    }
                }
                return $total;
            }
            return $getNominal($id, $wilId, $thn, $pList, false);
        };

        $periodeKey = $selectedPeriode;

        /* ================= CALCULATE KATEGORI ================= */
        foreach ($kategoriList as $k) {
            $katId = $k->id_kategori;
            $kabValues = [];
            $totalKab = 0;

            foreach ($kabkota as $w) {
                $val = $getCategoryNominal($katId, $w->id_wilayah, $selectedTahun, $triwulans);
                $kabValues[$w->id_wilayah] = $val;
                $totalKab += $val;
            }

            if ($totalKab > 0) {
                $rounded = [];
                foreach ($kabValues as $wid => $val) {
                    $rounded[$wid] = ($val / $totalKab) * 100;
                }

                // Adjust to 100%
                $diff = 100 - array_sum($rounded);
                if (abs($diff) >= 0.01) {
                    $maxId = array_search(max($rounded), $rounded);
                    $rounded[$maxId] += $diff;
                }

                foreach ($kabkota as $w) {
                    $hasilKategori[$periodeKey][$katId][$w->id_wilayah] = [
                        'nilai' => $kabValues[$w->id_wilayah],
                        'persen' => $rounded[$w->id_wilayah]
                    ];
                }
            } else {
                foreach ($kabkota as $w) {
                    $hasilKategori[$periodeKey][$katId][$w->id_wilayah] = ['nilai' => 0, 'persen' => 0];
                }
            }
        }

        /* ================= CALCULATE SUB ================= */
        foreach ($subList as $s) {
            $subId = $s->id_sub_kategori;
            $kabValues = [];
            $totalKab = 0;

            foreach ($kabkota as $w) {
                $val = $getNominal($subId, $w->id_wilayah, $selectedTahun, $triwulans, true);
                $kabValues[$w->id_wilayah] = $val;
                $totalKab += $val;
            }

            if ($totalKab > 0) {
                $rounded = [];
                foreach ($kabValues as $wid => $val) {
                    $rounded[$wid] = ($val / $totalKab) * 100;
                }

                $diff = 100 - array_sum($rounded);
                if (abs($diff) >= 0.01) {
                    $maxId = array_search(max($rounded), $rounded);
                    $rounded[$maxId] += $diff;
                }

                foreach ($kabkota as $w) {
                    $hasilSub[$periodeKey][$subId][$w->id_wilayah] = [
                        'nilai' => $kabValues[$w->id_wilayah],
                        'persen' => $rounded[$w->id_wilayah]
                    ];
                }
            } else {
                foreach ($kabkota as $w) {
                    $hasilSub[$periodeKey][$subId][$w->id_wilayah] = ['nilai' => 0, 'persen' => 0];
                }
            }
        }

        /* ================= KODE & VIEW ================= */
        $kategoriList = $kategoriList->values()->map(function ($kat) {
            $kat->kode = \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori);
            return $kat;
        });

        $subList = $subList->groupBy('id_kategori')->map(function ($subs, $katId) use ($kategoriList) {
            $kategori = $kategoriList->firstWhere('id_kategori', $katId);
            $namaKategori = $kategori->nama_kategori ?? null;
            return $subs->values()->map(function ($sub, $index) use ($namaKategori) {
                $sub->kode = \App\Helpers\PdrbHelper::getSubCode($index + 1, $namaKategori);
                return $sub;
            });
        })->flatten(1);

        return view('rekonsiliasi.stukturAntar', compact(
            'tahunList',
            'periodeList',
            'kategoriList',
            'subList',
            'wilayahs',
            'kabkota',
            'selectedTahun',
            'selectedPeriode',
            'tipePdrb',
            'hasilKategori',
            'hasilSub',
            'pendekatan',
            'resume'
        ));
    }


    public function redirect(Request $request)
    {
        $map = [
            'diskrepansi' => 'rekonsiliasi.index',
            'qtoq' => 'rekonsiliasi.qtoq',
            'ytoy' => 'rekonsiliasi.ytoy',
            'ctoc' => 'rekonsiliasi.ctoc',
            'indeks-implisit' => 'rekonsiliasi.indeksImplisit',
            'laju-implisit' => 'rekonsiliasi.lajuImplisit',
            'struktur-dalam' => 'rekonsiliasi.stukturDalam',
            'struktur-antar' => 'rekonsiliasi.stukturAntar',
        ];

        return redirect()->route(
            $map[$request->jenis],
            $request->except('jenis')
        );
    }

    public function exportUniversal(Request $request, $mode)
    {
        switch ($mode) {
            case 'resume':
                $view = $this->index($request);
                $title = 'RESUME';
                break;

            case 'qtoq':
                $view = $this->qtoq($request);
                $title = 'Q TO Q';
                break;

            case 'yony':
                $view = $this->yony($request);
                $title = 'Y ON Y';
                break;

            case 'ctoc':
                $view = $this->ctoc($request);
                $title = 'C TO C';
                break;

            case 'indeks':
                $view = $this->indeksImplisit($request);
                $title = 'INDEKS IMPLISIT';
                break;

            case 'laju':
                $view = $this->lajuImplisit($request);
                $title = 'LAJU IMPLISIT';
                break;

            case 'struktur_dalam':
                $view = $this->stukturDalam($request);
                $title = 'STRUKTUR DALAM';
                break;



            case 'struktur_antar':
                $view = $this->stukturAntar($request);
                $title = 'STRUKTUR ANTAR';
                break;

            default:
                abort(404, 'Mode export tidak dikenali');
        }

        $data = $view->getData();

        return $this->exportExcelGeneric($data, $title, $request);
    }

    private function exportExcelGeneric(array $data, string $title, Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ================= FILTER =================
        $tahun = $data['tahunNama'] ?? $request->get('tahun') ?? '';
        $triwulan = $data['triwulanNama'] ?? $request->get('triwulan') ?? '';
        $pendekatan = $request->get('pendekatan')
            ? ucwords(str_replace('_', ' ', $request->get('pendekatan')))
            : '';

        $sheet->setTitle($title);

        // Mapping ID wilayah → Nama
        $wilayahMap = \DB::table('wilayah')
            ->join('kabupaten', 'kabupaten.id_kabupaten', '=', 'wilayah.id_kabupaten')
            ->pluck('kabupaten.nama_kabupaten', 'wilayah.id_wilayah')
            ->toArray();

        // ================= DATA BUILD =================
        $rows = [];

        if (!empty($data['grouped'] ?? null)) {

            foreach ($data['grouped']->where('hidden', '!=', true) as $row) {
                $rows[] = $this->buildUniversalRow($row, $wilayahMap, strtolower($request->route('mode')));

            }
            $isResume = false;

        } elseif (!empty($data['rekonsiliasi'] ?? null)) {

            foreach ($data['rekonsiliasi'] as $row) {
                $rows[] = $this->buildUniversalRow($row, $wilayahMap, strtolower($request->route('mode')));

            }
            $isResume = true;

        } elseif (
            !empty($data['hasilKategori'] ?? null)
            && isset($data['kategoriList'])
            && isset($data['kabkota'])
        ) { // STRUKTUR ANTAR

            $rows = $this->buildStrukturAntarExportRows($data);
            $isResume = false;

        } elseif (!empty($data['hasilKategori'] ?? null)) { // LAJU

            $rows = $this->buildLajuExportRows($data, $wilayahMap);
            $isResume = false;

        } elseif (!empty($data['hasil'] ?? null) && isset($data['kategoriList'])) { // STRUKTUR DALAM

            $rows = $this->buildStrukturDalamExportRows($data);
            $isResume = false;

        } else {
            abort(500, 'Struktur data export tidak dikenali');
        }

        // ================= TAMBAHKAN BARIS SELISIH =================
        if ($request->get('pendekatan') === 'pengeluaran') {
            $totalProv = array_sum(array_column($rows, 'provinsi'));
            $totalKab = array_sum(array_column($rows, 'total'));
            $selisih = $totalKab - $totalProv;

            $rows[] = [
                'id' => '',
                'kategori' => 'SELISIH TOTAL (KAB/KOTA - PROVINSI)',
                'cek' => '',
                'persen' => $totalProv != 0 ? ($selisih / $totalProv) * 100 : 0,
                'selisih' => $selisih,
                'provinsi' => $totalProv,
                'total' => $totalKab,
            ];
        }

        if (empty($rows)) {
            abort(500, 'Data kosong / struktur export tidak valid');
        }


        if ($title === 'STRUKTUR ANTAR') {
            // baris pertama adalah header manual
            $headers = array_shift($rows);
        } else {
            $headers = array_keys((array) $rows[0]);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));


        // ================= KONVERSI ID → NAMA =================
        if (is_numeric($tahun)) {
            $tahun = \DB::table('tahun')->where('id_tahun', $tahun)->value('tahun') ?? $tahun;
        }

        if (is_numeric($triwulan)) {
            $triwulan = \DB::table('periode')->where('id_periode', $triwulan)->value('nama_periode') ?? $triwulan;
        }

        // ================= INFO FILTER =================
        $resume = $isResume ? 'P1' : 'P0';

        $modeMap = [
            'resume' => 'PDRB',
            'qtoq' => 'Q to Q',
            'yony' => 'Y on Y',
            'ctoc' => 'C to C',
            'indeks' => 'Indeks Implisit',
            'laju' => 'Laju Implisit',
            'struktur_antar' => 'Struktur Antar',
            'struktur_dalam' => 'Struktur Dalam'
        ];

        $pilihData = $modeMap[strtolower($title)] ?? ucwords(strtolower($title));

        // Tambahkan tipe PDRB jika ada
        $tipe = $request->get('tipe_pdrb');
        if ($tipe) {
            $pilihData .= ' - ' . ucfirst($tipe);
        }

        // ================= CETAK FILTER =================
        $startRow = 1;
        $sheet->setCellValue("A{$startRow}", 'Resume');
        $sheet->setCellValue("B{$startRow}", ": {$resume}");

        $sheet->setCellValue("A" . ($startRow + 1), 'Pilih Data');
        $sheet->setCellValue("B" . ($startRow + 1), ": {$pilihData}");

        $sheet->setCellValue("A" . ($startRow + 2), 'Tahun');
        $sheet->setCellValue("B" . ($startRow + 2), ": {$tahun}");

        $sheet->setCellValue("A" . ($startRow + 3), 'Triwulan');
        $sheet->setCellValue("B" . ($startRow + 3), ": {$triwulan}");

        $sheet->setCellValue("A" . ($startRow + 4), 'Pendekatan');
        $sheet->setCellValue("B" . ($startRow + 4), ": {$pendekatan}");

        $sheet->getStyle("A{$startRow}:A" . ($startRow + 4))->getFont()->setBold(true);

        // ================= HEADER =================
        $headerRow = $startRow + 7;
        $col = 'A';
        foreach ($headers as $h) {

            if ($h === 'id') {
                $titleHeader = 'ID';
            } elseif ($h === 'kategori') {
                $titleHeader = 'KATEGORI / SUB KATEGORI';
            } else {
                $titleHeader = strtoupper(str_replace('_', ' ', $h));
            }

            $sheet->setCellValue($col . $headerRow, $titleHeader);
            $col++;
        }


        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
            ->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);

        $rowNum = $headerRow + 1;
        foreach ($rows as $r) {
            $col = 'A';

            foreach ($headers as $key) {
                $value = $r[$key] ?? '';
                if ($value instanceof \Illuminate\Support\Collection)
                    $value = $value->implode(', ');
                elseif (is_array($value))
                    $value = implode(', ', $value);

                $sheet->setCellValue($col . $rowNum, $value);
                $col++;
            }

            $rowNum++;
        }

        $lastRow = $rowNum - 1;

        // ================= JUDUL LAPORAN =================
        $sheet->insertNewRowBefore(1, 2);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $sheet->setCellValue('A1', strtoupper("REKONSILIASI PDRB - {$title}"));
        $sheet->setCellValue('A2', "TAHUN {$tahun}  TRIWULAN {$triwulan}");

        $sheet->getStyle("A1:A2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Geser header karena ada 2 baris tambahan
        $headerRow += 2;
        $lastRow += 2;

        // ================= FREEZE HEADER =================
        $sheet->freezePane("C" . ($headerRow + 1));

        // ================= AUTO WIDTH =================
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ================= BORDER FULL =================
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ================= ALIGNMENT =================
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("A{$headerRow}:B{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle("C{$headerRow}:{$lastCol}{$lastRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ================= DETEKSI KOLOM ANGKA =================
        $percentCols = [];
        $numberCols = [];

        foreach ($headers as $i => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);

            if (strtolower($h) === 'persen') {
                $percentCols[] = $colLetter;
            }

            if (
                in_array(strtolower($h), ['selisih', 'provinsi', 'total']) ||
                str_starts_with(strtolower($h), 'kabupaten') ||
                str_starts_with(strtolower($h), 'kota')
            ) {
                $numberCols[] = $colLetter;
            }
        }

        // ================= FORMAT ANGKA =================
        foreach ($numberCols as $c) {
            $sheet->getStyle("{$c}" . ($headerRow + 1) . ":{$c}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach ($percentCols as $c) {
            $sheet->getStyle("{$c}" . ($headerRow + 1) . ":{$c}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('0.00" %"');
        }


        // ================= HIGHLIGHT BARIS TOTAL =================
        for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
            $kategori = strtoupper(trim($sheet->getCell("B$r")->getValue()));

            if (
                in_array($kategori, [
                    'PRODUK DOMESTIK REGIONAL BRUTO',
                    'PRODUK DOMESTIK REGIONAL BRUTO NON MIGAS'
                ])
            ) {
                $sheet->getStyle("A$r:{$lastCol}$r")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DCFCE7']
                    ]
                ]);
            }
        }
        // ================= TEMPLATE WARNA UNIVERSAL =================
        for ($r = $headerRow + 1; $r <= $lastRow; $r++) {

            $valA = trim((string) $sheet->getCell("A$r")->getValue());
            $valB = trim((string) $sheet->getCell("B$r")->getValue());

            $text = strtoupper($valA . ' ' . $valB);

            // ===== KATEGORI UTAMA (A., B., C., dst) =====
            if (preg_match('/^[A-Z]\.?$/', $valA) || preg_match('/^[A-Z]\.? /', $valB)) {

                $sheet->getStyle("A$r:{$lastCol}$r")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'] // biru muda
                    ]
                ]);
            }

            // ===== SUB KATEGORI =====
            if (str_starts_with(trim($valB), '-')) {

                $sheet->getStyle("A$r:{$lastCol}$r")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFEAD5'] // krem
                    ]
                ]);
            }

            // ===== BARIS SELISIH =====
            if (str_contains($text, 'SELISIH')) {

                $sheet->getStyle("A$r:{$lastCol}$r")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DCFCE7'] // hijau
                    ]
                ]);
            }

            // ===== TOTAL PDRB =====
            if (str_contains($text, 'PRODUK DOMESTIK REGIONAL BRUTO')) {

                $sheet->getStyle("A$r:{$lastCol}$r")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BBF7D0'] // hijau muda
                    ]
                ]);
            }
        }

        // ================= SIMPAN FILE =================
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(fn() => $writer->save('php://output'), $filename);
    }



    private function buildUniversalRow(array $row, array $wilayahMap, string $mode)
    {
        $cek = $this->hitungCek($row, $mode);
        $mapKode = $this->mapKodeKategori();

        $nama = trim($row['kategori'] ?? '');
        $id = $mapKode[$nama] ?? '';

        $rowExcel = [
            'id' => $id,
            'kategori' => $nama,
            'cek' => $cek,
            'persen' => isset($row['persen']) ? (float) $row['persen'] : 0,
            'selisih' => isset($row['selisih']) ? (float) $row['selisih'] : 0,
            'provinsi' => isset($row['provinsi']) ? (float) $row['provinsi'] : 0,
            'total' => isset($row['total']) ? (float) $row['total']
                : (isset($row['total_kab']) ? (float) $row['total_kab'] : 0),
        ];

        foreach (($row['kab'] ?? $row['kabkota'] ?? []) as $idk => $val) {
            $namaKab = $wilayahMap[$idk] ?? $idk;
            $rowExcel[$namaKab] = (float) $val;
        }

        return $rowExcel;
    }

    private function hitungCek(array $row, string $mode): string
    {
        switch ($mode) {

            // ================= RESUME =================
            case 'resume':
                $abs = abs((float) ($row['persen'] ?? 0));

                if ($abs < 2)
                    return 'DISK < 2 %';
                elseif ($abs <= 5)
                    return 'DISK 2 - 5 %';
                else
                    return 'DISK > 5 %';


            // ================= Q TO Q & Y ON Y =================
            case 'qtoq':
            case 'yony':

                $prov = $row['provinsi'] ?? null;
                $total = $row['total_kab'] ?? null;

                if ($prov === null || $total === null)
                    return '';

                $selisih = $total - $prov;

                if (($prov > 0 && $total < 0) || ($prov < 0 && $total > 0)) {
                    return 'BEDA ARAH';
                }

                $abs = abs($selisih);

                if ($abs > 10)
                    return 'SELISIH > 10 %';
                elseif ($abs > 5)
                    return 'SELISIH 5 - 10 %';
                elseif ($abs > 2)
                    return 'SELISIH 2 - 5 %';
                else
                    return '-';


            // ================= C TO C =================
            case 'ctoc':

                $prov = $row['provinsi'] ?? null;
                $total = $row['total_kab'] ?? null;

                if ($prov === null || $total === null)
                    return '-';

                $selisih = $total - $prov;

                if (($prov > 0 && $total < 0) || ($prov < 0 && $total > 0)) {
                    return 'BEDA ARAH';
                }

                $abs = abs($selisih);

                if ($abs > 5)
                    return 'SELISIH 5 - 10 %';
                elseif ($abs > 2)
                    return 'SELISIH 2 - 5 %';
                else
                    return '-';


            // ================= INDEKS IMPLISIT =================
            case 'indeks':

                $prov = $row['provinsi'] ?? null;
                $total = $row['total_kab'] ?? null;
                $kab = collect($row['kab'] ?? [])->values();

                if ($prov === null || $total === null)
                    return '-';

                if (
                    ($prov > 0 && $kab->min() < 0) ||
                    ($prov < 0 && $kab->max() > 0)
                ) {
                    return 'BEDA ARAH';
                }

                $selisih = $total - $prov;
                $abs = abs($selisih);

                if ($abs > 10)
                    return 'SELISIH > 10 %';
                elseif ($abs > 5)
                    return 'SELISIH 5 - 10 %';
                else
                    return '-';


            // ================= LAJU IMPLISIT =================
            case 'laju':

                $prov = $row['provinsi'] ?? null;
                $total = $row['total_kab'] ?? null;
                $kab = collect($row['kab'] ?? [])->values();

                if ($prov === null || $total === null)
                    return '-';

                if (
                    ($prov > 0 && $kab->min() < 0) ||
                    ($prov < 0 && $kab->max() > 0)
                ) {
                    return 'BEDA ARAH';
                }

                $selisih = $total - $prov;
                $abs = abs($selisih);

                if ($abs > 10)
                    return 'SELISIH > 10 %';
                elseif ($abs > 5)
                    return 'SELISIH 5 - 10 %';
                else
                    return '-';


            default:
                return '-';
        }
    }

    private function buildLajuExportRows(array $data, array $wilayahMap)
    {
        $rows = [];

        $hasilKategori = $data['hasilKategori'];
        $hasilSub = $data['hasilSub'];
        $hasilTotalKabKota = $data['hasilTotalKabKota'];
        $kategori = $data['kategori'];
        $sub = $data['sub'];
        $selectedWilayahs = $data['selectedWilayahs'];

        $provId = optional($selectedWilayahs->firstWhere('tipe', 'provinsi'))->id_wilayah;

        foreach ($kategori as $k) {

            $prov = $provId ? ($hasilKategori[$k->id_kategori][$provId] ?? null) : null;
            $total = $hasilTotalKabKota[$k->id_kategori] ?? null;
            $selisih = ($prov !== null && $total !== null) ? $total - $prov : null;

            $rowTmp = [
                'provinsi' => $prov,
                'total_kab' => $total,
                'kab' => $hasilKategori[$k->id_kategori] ?? [],
            ];

            $row = [
                'kategori' => $k->nama_kategori,
                'cek' => $this->hitungCek($rowTmp, 'laju'),
                'selisih' => $selisih ?? 0,
                'provinsi' => $prov ?? 0,
                'total' => $total ?? 0,
            ];


            foreach ($selectedWilayahs->whereIn('tipe', ['kabupaten', 'kota']) as $kab) {
                $nama = $kab->kabupaten->nama_kabupaten ?? $kab->nama_wilayah;
                $row[$nama] = $hasilKategori[$k->id_kategori][$kab->id_wilayah] ?? 0;
            }

            $rows[] = $row;

            // ===== SUB KATEGORI =====
            foreach ($sub->where('id_kategori', $k->id_kategori) as $s) {

                $provSub = $provId ? ($hasilSub[$s->id_sub_kategori][$provId] ?? null) : null;
                $totalSub = $hasilTotalKabKota['sub_' . $s->id_sub_kategori] ?? null;
                $selSub = ($provSub !== null && $totalSub !== null) ? $totalSub - $provSub : null;

                $rowSubTmp = [
                    'provinsi' => $provSub,
                    'total_kab' => $totalSub,
                    'kab' => $hasilSub[$s->id_sub_kategori] ?? [],
                ];

                $rowSub = [
                    'kategori' => '  - ' . $s->nama_sub_kategori,
                    'cek' => $this->hitungCek($rowSubTmp, 'laju'),
                    'selisih' => $selSub ?? 0,
                    'provinsi' => $provSub ?? 0,
                    'total' => $totalSub ?? 0,
                ];


                foreach ($selectedWilayahs->whereIn('tipe', ['kabupaten', 'kota']) as $kab) {
                    $nama = $kab->kabupaten->nama_kabupaten ?? $kab->nama_wilayah;
                    $rowSub[$nama] = $hasilSub[$s->id_sub_kategori][$kab->id_wilayah] ?? 0;
                }

                $rows[] = $rowSub;
            }
        }

        return $rows;
    }

    private function buildStrukturDalamExportRows(array $data)
    {
        $rows = [];

        $periodeKey = array_key_first($data['hasil']);
        $hasil = $data['hasil'][$periodeKey];

        $kategoriList = $data['kategoriList'];
        $subList = $data['subList'];

        $wilayahs = $data['selectedWilayahs']
            ->whereIn('tipe', ['kabupaten', 'kota']);

        $provId = optional($data['provinsiWilayahs']->first())->id_wilayah;

        foreach ($kategoriList as $kat) {

            $prov = $hasil['dataPerWilayah'][$provId]['strukturKategori'][$kat->id_kategori]['struktur'] ?? 0;
            $total = $hasil['totalKabKota']['kategori'][$kat->id_kategori]['struktur'] ?? 0;
            $sel = $total - $prov;

            $persen = ($prov != 0) ? ($sel / $prov * 100) : 0;

            // ===== CEK =====
            if (abs($persen) > 10)
                $cek = 'DISK > 10 %';
            elseif (abs($persen) > 5)
                $cek = 'DISK 5 - 10 %';
            elseif (abs($persen) > 2)
                $cek = 'DISK 2 - 5 %';
            else
                $cek = 'DISK < 2 %';

            $row = [
                'kategori' => $kat->nama_kategori,
                'cek' => $cek,
                'persen' => $persen,
                'selisih' => $sel,
                'provinsi' => $prov,
                'total' => $total,
            ];

            foreach ($wilayahs as $w) {
                $row[$w->nama_wilayah] =
                    $hasil['dataPerWilayah'][$w->id_wilayah]
                    ['strukturKategori'][$kat->id_kategori]['struktur'] ?? 0;
            }

            $rows[] = $row;

            // ===== SUB =====
            foreach ($subList->where('id_kategori', $kat->id_kategori) as $sub) {

                $provSub = $hasil['dataPerWilayah'][$provId]['strukturSub'][$sub->id_sub_kategori]['struktur'] ?? 0;
                $totalSub = $hasil['totalKabKota']['sub'][$sub->id_sub_kategori]['struktur'] ?? 0;
                $selSub = $totalSub - $provSub;
                $persenSub = ($provSub != 0) ? ($selSub / $provSub * 100) : 0;

                if (abs($persenSub) > 10)
                    $cekSub = 'DISK > 10 %';
                elseif (abs($persenSub) > 5)
                    $cekSub = 'DISK 5 - 10 %';
                elseif (abs($persenSub) > 2)
                    $cekSub = 'DISK 2 - 5 %';
                else
                    $cekSub = 'DISK < 2 %';

                $rowSub = [
                    'kategori' => '  - ' . $sub->nama_sub_kategori,
                    'cek' => $cekSub,
                    'persen' => $persenSub,
                    'selisih' => $selSub,
                    'provinsi' => $provSub,
                    'total' => $totalSub,
                ];

                foreach ($wilayahs as $w) {
                    $rowSub[$w->nama_wilayah] =
                        $hasil['dataPerWilayah'][$w->id_wilayah]
                        ['strukturSub'][$sub->id_sub_kategori]['struktur'] ?? 0;
                }

                $rows[] = $rowSub;
            }
        }

        return $rows;
    }

    private function buildStrukturAntarExportRows(array $data)
    {
        $rows = [];

        $hasilKategori = $data['hasilKategori'];
        $hasilSub = $data['hasilSub'];
        $kategoriList = $data['kategoriList'];
        $subList = $data['subList'];
        $kabkota = $data['kabkota'];
        $pendekatan = $data['pendekatan'] ?? 'lapangan_usaha';

        $periodeKey = $data['selectedPeriode']
            ?? array_key_first($hasilKategori);

        // ================= HEADER =================
        $header = ['kategori', 'total'];

        foreach ($kabkota as $w) {
            $header[] = $w->nama_wilayah;
        }

        $rows[] = $header;

        // ================= DATA =================
        foreach ($kategoriList as $k) {

            // ===== TOTAL KATEGORI =====
            $totalKategori = isset($hasilKategori[$periodeKey][$k->id_kategori])
                ? array_sum(array_column(
                    $hasilKategori[$periodeKey][$k->id_kategori],
                    'persen'
                ))
                : 0;

            $row = [
                'kategori' => $k->kode . '. ' . $k->nama_kategori,
                'total' => $totalKategori
            ];

            foreach ($kabkota as $w) {
                $row[$w->nama_wilayah] =
                    $hasilKategori[$periodeKey][$k->id_kategori][$w->id_wilayah]['persen']
                    ?? 0;
            }

            $rows[] = $row;

            // ===== SUB KATEGORI =====
            foreach ($subList->where('id_kategori', $k->id_kategori) as $s) {

                $totalSub = isset($hasilSub[$periodeKey][$s->id_sub_kategori])
                    ? array_sum(array_column(
                        $hasilSub[$periodeKey][$s->id_sub_kategori],
                        'persen'
                    ))
                    : 0;

                $rowSub = [
                    'kategori' => '  - ' . ($s->kode ?? '') . ' ' . $s->nama_sub_kategori,
                    'total' => $totalSub
                ];

                foreach ($kabkota as $w) {
                    $rowSub[$w->nama_wilayah] =
                        $hasilSub[$periodeKey][$s->id_sub_kategori][$w->id_wilayah]['persen']
                        ?? 0;
                }

                $rows[] = $rowSub;
            }
        }

        // ================= BARIS SELISIH =================
        if ($pendekatan == 'pengeluaran') {

            $pdrbBrutoId = optional($kategoriList->first(
                fn($k) =>
                str_contains(strtoupper($k->nama_kategori), 'PRODUK DOMESTIK REGIONAL BRUTO')
                && !str_contains(strtoupper($k->nama_kategori), 'LAPUS')
            ))->id_kategori;

            $pdrbLapusId = optional($kategoriList->first(
                fn($k) =>
                str_contains(strtoupper($k->nama_kategori), 'LAPUS')
            ))->id_kategori;

            if ($pdrbBrutoId && $pdrbLapusId) {

                $totalSelisih = 0;
                $rowSelisih = [
                    'kategori' => 'SELISIH',
                    'total' => 0
                ];

                foreach ($kabkota as $w) {
                    $bruto = $hasilKategori[$periodeKey][$pdrbBrutoId][$w->id_wilayah]['persen'] ?? 0;
                    $lapus = $hasilKategori[$periodeKey][$pdrbLapusId][$w->id_wilayah]['persen'] ?? 0;

                    $selisih = round($bruto - $lapus, 2);
                    $rowSelisih[$w->nama_wilayah] = $selisih;

                    $totalSelisih += $selisih;
                }

                $rowSelisih['total'] = $totalSelisih;

                $rows[] = $rowSelisih;
            }
        }

        return $rows;
    }

    private function mapKodeKategori(): array
    {
        return [
            'Pertanian, Kehutanan, dan Perikanan' => 'A',
            'Pertanian, Peternakan, Perburuan dan Jasa Pertanian' => '1',
            'Tanaman Pangan' => 'A1A',
            'Tanaman Hortikultura Semusim' => 'A1B',
            'Perkebunan Semusim' => 'A1C',
            'Tanaman Hortikultura Tahunan dan Lainnya' => 'A1D',
            'Perkebunan Tahunan' => 'A1E',
            'Peternakan' => 'A1F',
            'Jasa Pertanian dan Perburuan' => 'A1G',
            'Kehutanan dan Penebangan Kayu' => '2',
            'Perikanan' => '3',

            'Pertambangan dan Penggalian' => 'B',
            'Pertambangan Minyak, Gas dan Panas Bumi' => '1',
            'Pertambangan Batubara dan Lignit' => '2',
            'Pertambangan Bijih Logam' => '3',
            'Pertambangan dan Penggalian Lainnya' => '4',

            'Industri Pengolahan' => 'C',
            'Industri Batubara dan Pengilangan Migas' => '1',
            'Industri Batu Bara' => 'A1A',
            'Industri Pengilangan Migas' => 'A1B',
            'Industri Makanan dan Minuman' => '2',
            'Pengolahan Tembakau' => '3',
            'Industri Tekstil dan Pakaian Jadi' => '4',
            'Industri Kulit, Barang dari Kulit dan Alas Kaki' => '5',
            'Industri Kayu, Barang dari Kayu dan Gabus' => '6',
            'Industri Kertas dan Percetakan' => '7',
            'Industri Kimia, Farmasi dan Obat Tradisional' => '8',
            'Industri Karet, Barang dari Karet dan Plastik' => '9',
            'Industri Barang Galian bukan Logam' => '10',
            'Industri Logam Dasar' => '11',
            'Industri Barang dari Logam, Elektronik dan Optik' => '12',
            'Industri Mesin dan Perlengkapan YTDL' => '13',
            'Industri Alat Angkutan' => '14',
            'Industri Furnitur' => '15',
            'Industri Pengolahan Lainnya' => '16',

            'Pengadaan Listrik dan Gas' => 'D',
            'Ketenagalistrikan' => '1',
            'Pengadaan Gas dan Produksi Es' => '2',

            'Pengadaan Air, Pengelolaan Sampah, Limbah dan Daur Ulang' => 'E',
            'Konstruksi' => 'F',

            'Perdagangan Besar dan Eceran; Reparasi Mobil dan Sepeda Motor' => 'G',
            'Perdagangan Mobil, Sepeda Motor dan Reparasinya' => '1',
            'Perdagangan Besar dan Eceran, Bukan Mobil dan Sepeda Motor' => '2',

            'Transportasi dan Pergudangan' => 'H',
            'Angkutan Rel' => '1',
            'Angkutan Darat' => '2',
            'Angkutan Laut' => '3',
            'Angkutan Sungai Danau dan Penyeberangan' => '4',
            'Angkutan Udara' => '5',
            'Pergudangan dan Jasa Penunjang Angkutan, Pos dan Kurir' => '6',

            'Penyediaan Akomodasi dan Makan Minum' => 'I',
            'Penyediaan Akomodasi' => '1',
            'Penyediaan Makan Minum' => '2',

            'Informasi dan Komunikasi' => 'J',

            'Jasa Keuangan dan Asuransi' => 'K',
            'Jasa Perantara Keuangan' => '1',
            'Asuransi dan Dana Pensiun' => '2',
            'Jasa Keuangan Lainnya' => '3',
            'Jasa Penunjang Keuangan' => '4',

            'Real Estate' => 'L',
            'Jasa Perusahaan' => 'M,N',
            'Administrasi Pemerintahan, Pertahanan dan Jaminan Sosial Wajib' => 'N',
            'Jasa Pendidikan' => 'O',
            'Jasa Kesehatan dan Kegiatan Sosial' => 'P',
            'Jasa Lainnya' => 'Q',

            'Produk Domestik Regional Bruto' => 'PDRB',
            'Produk Domestik Regional Bruto Non Migas' => 'NON MIGAS',
        ];
    }


    public function release(Request $request)
    {
        $user = auth()->user();

        if (!$user->wilayah || $user->wilayah->tipe !== 'provinsi') {
            abort(403, 'Hanya provinsi yang bisa merilis data.');
        }

        $tahun = $request->tahun;
        $triwulan = $request->triwulan;

        $prov = $user->wilayah->id_provinsi;

        $wilayahIds = Wilayah::where('id_provinsi', $prov)
            ->pluck('id_wilayah')
            ->push($user->wilayah->id_wilayah)
            ->unique();

        \DB::beginTransaction();

        try {

            foreach (['berlaku', 'konstan'] as $tipe) {

                /* ============================
                   1️⃣ BACKUP SEMUA P0
                   ============================ */
                $p0Kategori = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'awal')
                    ->get();

                foreach ($p0Kategori as $row) {
                    $b = $row->replicate();
                    $b->tahap_data = 'backup_awal';
                    $b->save();
                }

                $p0Sub = NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'awal')
                    ->get();

                foreach ($p0Sub as $row) {
                    $b = $row->replicate();
                    $b->tahap_data = 'backup_awal';
                    $b->save();
                }

                /* ============================
                   2️⃣ UPDATE P0 HANYA YANG DIREKON
                   ============================ */
                $kategoriP1 = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'rekonsiliasi')
                    ->get();

                foreach ($kategoriP1 as $row) {
                    NilaiKategori::where([
                        'id_wilayah' => $row->id_wilayah,
                        'id_kategori' => $row->id_kategori,
                        'id_tahun' => $tahun,
                        'id_periode' => $triwulan,
                        'tipe_pdrb' => $tipe,
                        'tahap_data' => 'awal',
                    ])->update([
                                'nilai' => $row->nilai,
                                'andil' => $row->andil,
                                'share' => $row->share,
                                'diskrepansi' => $row->diskrepansi,
                            ]);
                }

                $subP1 = NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'rekonsiliasi')
                    ->get();

                foreach ($subP1 as $row) {
                    NilaiSubKategori::where([
                        'id_wilayah' => $row->id_wilayah,
                        'id_sub_kategori' => $row->id_sub_kategori,
                        'id_tahun' => $tahun,
                        'id_periode' => $triwulan,
                        'tipe_pdrb' => $tipe,
                        'tahap_data' => 'awal',
                    ])->update([
                                'nilai' => $row->nilai,
                            ]);
                }
            }

            \DB::commit();

        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        return back()->with('success', 'Rilis berhasil. Hanya data hasil rekonsiliasi yang diperbarui.');
    }

    public function reset(Request $request)
    {
        $user = auth()->user();

        if (!$user->wilayah || $user->wilayah->tipe !== 'provinsi') {
            abort(403, 'Hanya provinsi yang bisa melakukan reset.');
        }

        $tahun = $request->tahun;
        $triwulan = $request->triwulan;

        $prov = $user->wilayah->id_provinsi;

        $wilayahIds = Wilayah::where('id_provinsi', $prov)
            ->pluck('id_wilayah')
            ->push($user->wilayah->id_wilayah)
            ->unique();

        \DB::beginTransaction();

        try {

            foreach (['berlaku', 'konstan'] as $tipe) {

                // hapus P0 hasil rilis
                NilaiKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'awal')
                    ->delete();

                NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'awal')
                    ->delete();

                // restore backup → P0
                NilaiKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'backup_awal')
                    ->update(['tahap_data' => 'awal']);

                NilaiSubKategori::whereIn('id_wilayah', $wilayahIds)
                    ->where('id_tahun', $tahun)
                    ->where('id_periode', $triwulan)
                    ->where('tipe_pdrb', $tipe)
                    ->where('tahap_data', 'backup_awal')
                    ->update(['tahap_data' => 'awal']);
            }

            \DB::commit();

        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }

        return back()->with('success', 'Reset berhasil. Data P0 kembali ke kondisi sebelum rilis.');
    }

    //cek selisih
    public function cekSelisih(Request $request)
    {
        // ================= AMBIL INPUT =================
        $jenis = $request->jenis ?? 'konstan';
        $allowedJenis = [
            'konstan',
            'berlaku',
            'qtoq',
            'yoy',
            'ctoc',
            'indeks_implisit',
            'laju_implisit',
            'struktur_dalam',
            'struktur_antar'
        ];

        if (!in_array($jenis, $allowedJenis)) {
            $jenis = 'konstan';
        }

        // Tentukan tipe PDRB dan mode otomatis
        if (in_array($jenis, ['konstan', 'berlaku'])) {
            $tipePdrb = $jenis;
            $mode = null;
        } else {
            $tipePdrb = 'konstan';
            $mode = $jenis;
        }

        $idTahun = $request->tahun ?? null;
        $triwulan = $request->triwulan ?? 'all';
        // ================= PENDEKATAN =================
        $pendekatan = $request->pendekatan ?? $request->jenis ?? 'lapangan_usaha';

        // Validasi pendekatan yang diizinkan
        $allowedPendekatan = ['lapangan_usaha', 'pengeluaran', 'sektor_lapus', 'sektor_pengeluaran'];
        if (!in_array($pendekatan, $allowedPendekatan)) {
            $pendekatan = 'lapangan_usaha';
        }

        $user = auth()->user();
        $isProvinsi = $user->role === 'provinsi';

        $wilayahList = [];

        if ($isProvinsi) {
            $wilayahProv = \App\Models\Wilayah::where('id_wilayah', $user->id_wilayah)
                ->where('tipe', 'provinsi')
                ->first();

            if ($wilayahProv) {
                $provinsiId = $wilayahProv->id_provinsi;
                $wilayahList = \App\Models\Kabupaten::where('id_provinsi', $provinsiId)->get();

                // Urutan khusus
                $urutan = [
                    'Kabupaten Buton',
                    'Kabupaten Muna',
                    'Kabupaten Konawe',
                    'Kabupaten Kolaka',
                    'Kabupaten Konawe Selatan',
                    'Kabupaten Bombana',
                    'Kabupaten Wakatobi',
                    'Kabupaten Kolaka Utara',
                    'Kabupaten Buton Utara',
                    'Kabupaten Konawe Utara',
                    'Kabupaten Kolaka Timur',
                    'Kabupaten Konawe Kepulauan',
                    'Kabupaten Muna Barat',
                    'Kabupaten Buton Tengah',
                    'Kabupaten Buton Selatan',
                    'Kota Kendari',
                    'Kota Baubau'
                ];

                $wilayahList = $wilayahList->sortBy(function ($item) use ($urutan) {
                    return array_search($item->nama_kabupaten, $urutan);
                })->values();

                // Ambil id_kabupaten dari dropdown
                $selectedKabupaten = $request->id_wilayah
                    ?? $wilayahList->first()?->id_kabupaten;

                // Mapping ke id_wilayah yang benar
                $wilayahModel = \App\Models\Wilayah::where('id_kabupaten', $selectedKabupaten)
                    ->whereIn('tipe', ['kabupaten', 'kota'])
                    ->first();

                $wilayah = $wilayahModel?->id_wilayah;
            } else {
                $wilayahList = collect();
                $wilayah = null;
            }
        } else {
            $wilayahList = collect();
            $wilayah = $user->id_wilayah ?? null;
        }

        // ================= AMBIL DATA SUBKATEGORI =================
        $query = \App\Models\NilaiSubKategori::where('id_wilayah', $wilayah);

        if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
            $query->where('tipe_pdrb', $tipePdrb);
        }

        // Filter berdasarkan pendekatan
        if ($pendekatan === 'pengeluaran') {
            $query->whereHas('subKategori.kategori', function ($q) {
                $q->where('pendekatan', 'pengeluaran');
            });
        }

        $allData = $query->get()->groupBy(function ($item) {
            return $item->id_sub_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
        });

        // ================= AMBIL DATA KATEGORI =================
        $queryKategori = \App\Models\NilaiKategori::where('id_wilayah', $wilayah);

        if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
            $queryKategori->where('tipe_pdrb', $tipePdrb);
        }

        $allKategori = $queryKategori->get()->groupBy(function ($item) {
            return $item->id_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
        });

        // ================= AMBIL KATEGORI LIST =================
        $kategoriList = \App\Models\Kategori::with('subKategori')
            ->where('pendekatan', $pendekatan)
            ->get();

        $tahunMap = \App\Models\Tahun::pluck('id_tahun', 'tahun');

        // ================= PERIODE DINAMIS =================
        $modelTahun = \App\Models\Tahun::find($idTahun);
        $tahunAktif = $modelTahun ? (int) $modelTahun->tahun : now()->year;
        $startYear = $tahunAktif - 3;
        $periodeTampil = [];

        for ($y = $startYear; $y <= $tahunAktif; $y++) {
            $maxTriwulan = ($y == $tahunAktif && $triwulan !== 'all') ? (int) $triwulan : 4;
            $tahunKeys = [];

            for ($tw = 1; $tw <= $maxTriwulan; $tw++) {
                $key = $y . '_tw' . $tw;
                $periodeTampil[] = ['tahun' => $y, 'triwulan' => $tw, 'key' => $key];
                $tahunKeys[] = $key;
            }

            // Total per tahun
            $periodeTampil[] = [
                'tahun' => $y,
                'triwulan' => 'total',
                'key' => $y . '_total',
                'children' => $tahunKeys
            ];
        }

        $rows = [];

        // ================= CEK APAKAH INI MODE SEKTOR? =================
        $isSektorLapus = ($pendekatan === 'sektor_lapus');
        $isSektorPengeluaran = ($pendekatan === 'sektor_pengeluaran');

        if ($isSektorLapus || $isSektorPengeluaran) {

            $sektorRows = [];

            // ================= SEKTOR LAPUS =================
            if ($isSektorLapus) {

                // Ambil ulang data subkategori dengan filter yang benar
                $querySektor = \App\Models\NilaiSubKategori::where('id_wilayah', $wilayah);

                if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
                    $querySektor->where('tipe_pdrb', $tipePdrb);
                }

                // Filter untuk memastikan hanya data lapangan usaha
                $querySektor->whereHas('subKategori.kategori', function ($q) {
                    $q->where('pendekatan', 'lapangan_usaha');
                });

                $allDataSektor = $querySektor->get()->groupBy(function ($item) {
                    return $item->id_sub_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
                });

                // Ambil juga data kategori sebagai fallback
                $queryKategoriSektor = \App\Models\NilaiKategori::where('id_wilayah', $wilayah);
                if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
                    $queryKategoriSektor->where('tipe_pdrb', $tipePdrb);
                }
                $allKategoriSektor = $queryKategoriSektor->get()->groupBy(function ($item) {
                    return $item->id_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
                });

                // Ambil semua kategori lapangan usaha
                $kategoriLapanganUsaha = \App\Models\Kategori::with('subKategori')
                    ->where('pendekatan', 'lapangan_usaha')
                    ->get();

                // Tambahkan kode ke setiap kategori
                $kategoriWithCode = [];
                foreach ($kategoriLapanganUsaha as $kat) {
                    $kode = \App\Helpers\PdrbHelper::getKategoriCode($kat->nama_kategori);
                    $kat->kode = $kode;
                    $kategoriWithCode[$kode] = $kat;
                }

                // Mapping sektor dengan kode yang benar
                $sektorMap = [
                    'Sektor Primer / Primer Sector' => ['A'], // A = Pertanian, Kehutanan, Perikanan
                    'Sektor Sekunder / Secondary Sector' => ['B', 'C', 'D', 'E', 'F'], // B=Pertambangan, C=Industri, D=Listrik, E=Air, F=Konstruksi
                    'Sektor Tersier / Tertiary Sector' => ['G', 'H', 'I', 'J', 'K', 'L', 'MN', 'O', 'P', 'Q', 'RSTU'] // Jasa-jasa
                ];

                $totalPDRBSektorLapus = [
                    'kategori' => 'PRODUK DOMESTIK REGIONAL BRUTO',
                    'level' => 1,
                    'kode' => 'PDRB'
                ];

                // Array untuk menyimpan total per tahun
                $totalPerTahun = [];

                foreach ($sektorMap as $namaSektor => $kodeList) {
                    $rowSektor = ['kategori' => $namaSektor, 'level' => 1];

                    // Array untuk menyimpan nilai per periode
                    $nilaiPerPeriode = [];

                    foreach ($periodeTampil as $p) {
                        $key = $p['key'];

                        if ($p['triwulan'] === 'total') {
                            // Untuk TOTAL, hitung dari akumulasi nilai TW1-4 tahun tersebut
                            $tahun = $p['tahun'];
                            $totalTahun = 0;
                            for ($tw = 1; $tw <= 4; $tw++) {
                                $twKey = $tahun . '_tw' . $tw;
                                if (isset($nilaiPerPeriode[$twKey])) {
                                    $totalTahun += $nilaiPerPeriode[$twKey];
                                }
                            }

                            $rowSektor[$key] = [
                                'nilai' => $totalTahun,
                                'persen' => 0,
                                'flag' => abs($totalTahun) > 0.0001 ? 'WARNING' : 'OK'
                            ];

                            // Simpan total tahunan untuk PDRB
                            $totalPerTahun[$tahun] = ($totalPerTahun[$tahun] ?? 0) + $totalTahun;

                            continue;
                        }

                        $y = $p['tahun'];
                        $tw = $p['triwulan'];
                        $idTahunLoop = $tahunMap[$y] ?? null;

                        if (!$idTahunLoop) {
                            $rowSektor[$key] = ['nilai' => 0, 'persen' => 0, 'flag' => 'OK'];
                            $nilaiPerPeriode[$key] = 0;
                            continue;
                        }

                        $totalP0 = 0;
                        $totalP1 = 0;

                        // Loop melalui semua kategori yang sesuai dengan kodeList
                        foreach ($kodeList as $kode) {
                            $kategori = null;

                            // Coba cari berdasarkan kode
                            if (isset($kategoriWithCode[$kode])) {
                                $kategori = $kategoriWithCode[$kode];
                            } else {
                                // Fallback: cari berdasarkan nama untuk kode khusus
                                if ($kode == 'MN') {
                                    foreach ($kategoriLapanganUsaha as $kat) {
                                        if (strpos($kat->nama_kategori, 'Jasa Perusahaan') !== false) {
                                            $kategori = $kat;
                                            // Set kode agar bisa digunakan下次
                                            $kat->kode = 'MN';
                                            $kategoriWithCode['MN'] = $kat;
                                            break;
                                        }
                                    }
                                } elseif ($kode == 'RSTU') {
                                    foreach ($kategoriLapanganUsaha as $kat) {
                                        if (strpos($kat->nama_kategori, 'Jasa Lainnya') !== false) {
                                            $kategori = $kat;
                                            // Set kode agar bisa digunakan下次
                                            $kat->kode = 'RSTU';
                                            $kategoriWithCode['RSTU'] = $kat;
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!$kategori)
                                continue;

                            // Jika kategori memiliki subkategori
                            if ($kategori->subKategori->count() > 0) {
                                foreach ($kategori->subKategori as $sub) {
                                    if (!$mode) {
                                        $groupKey = $sub->id_sub_kategori . '_' . $idTahunLoop . '_' . $tw;
                                        $nilai = $allDataSektor->get($groupKey, collect());

                                        if ($nilai->isEmpty()) {
                                            // Fallback ke data kategori jika subkategori tidak ada data
                                            $groupKeyKategori = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                                            $nilaiKategori = $allKategoriSektor->get($groupKeyKategori, collect());

                                            $nilaiP0 = $nilaiKategori->where('tahap_data', 'awal')->sum('nilai');
                                            $nilaiP1 = $nilaiKategori->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                            if ($nilaiKategori->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                                $nilaiP1 = $nilaiP0;
                                        } else {
                                            $nilaiP0 = $nilai->where('tahap_data', 'awal')->sum('nilai');
                                            $nilaiP1 = $nilai->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                            if ($nilai->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                                $nilaiP1 = $nilaiP0;
                                        }
                                    } else {
                                        // Mode GROWTH
                                        $nilaiP0 = $this->hitungGrowthFinal($mode, $allDataSektor, $sub->id_sub_kategori, $tahunMap, $y, $tw, 'awal');
                                        $nilaiP1 = $this->hitungGrowthFinal($mode, $allDataSektor, $sub->id_sub_kategori, $tahunMap, $y, $tw, 'rekonsiliasi');
                                    }

                                    $totalP0 += $nilaiP0;
                                    $totalP1 += $nilaiP1;
                                }
                            } else {
                                // Kategori tanpa subkategori, gunakan data kategori langsung
                                if (!$mode) {
                                    $groupKeyKategori = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                                    $nilaiKategori = $allKategoriSektor->get($groupKeyKategori, collect());

                                    $nilaiP0 = $nilaiKategori->where('tahap_data', 'awal')->sum('nilai');
                                    $nilaiP1 = $nilaiKategori->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                    if ($nilaiKategori->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                        $nilaiP1 = $nilaiP0;
                                } else {
                                    // Mode GROWTH untuk kategori
                                    $nilaiP0 = $this->hitungGrowthFinalKategori($mode, $allKategoriSektor, $kategori->id_kategori, $tahunMap, $y, $tw, 'awal');
                                    $nilaiP1 = $this->hitungGrowthFinalKategori($mode, $allKategoriSektor, $kategori->id_kategori, $tahunMap, $y, $tw, 'rekonsiliasi');
                                }

                                $totalP0 += $nilaiP0;
                                $totalP1 += $nilaiP1;
                            }
                        }

                        $selisih = $totalP1 - $totalP0;
                        $persen = (!$mode && $totalP0 != 0) ? ($selisih / $totalP0) * 100 : $selisih;

                        $rowSektor[$key] = [
                            'nilai' => $selisih,
                            'persen' => $persen,
                            'flag' => abs($selisih) > 0.0001 ? 'WARNING' : 'OK'
                        ];

                        $nilaiPerPeriode[$key] = $selisih;
                    }

                    $sektorRows[] = $rowSektor;
                }

                // Hitung PDRB untuk setiap periode
                foreach ($periodeTampil as $p) {
                    $key = $p['key'];

                    if ($p['triwulan'] === 'total') {
                        // Untuk TOTAL, gunakan total per tahun yang sudah dihitung
                        $tahun = $p['tahun'];
                        $nilaiTotal = $totalPerTahun[$tahun] ?? 0;

                        $totalPDRBSektorLapus[$key] = [
                            'nilai' => $nilaiTotal,
                            'persen' => 0,
                            'flag' => abs($nilaiTotal) > 0.0001 ? 'WARNING' : 'OK'
                        ];
                    } else {
                        // Untuk TW, jumlahkan nilai dari semua sektor
                        $nilaiTotal = 0;
                        foreach ($sektorRows as $row) {
                            if (isset($row[$key])) {
                                $nilaiTotal += $row[$key]['nilai'];
                            }
                        }

                        $totalPDRBSektorLapus[$key] = [
                            'nilai' => $nilaiTotal,
                            'persen' => 0,
                            'flag' => abs($nilaiTotal) > 0.0001 ? 'WARNING' : 'OK'
                        ];
                    }
                }

                // Tambahkan baris total PDRB di akhir
                $sektorRows[] = $totalPDRBSektorLapus;
            }

            // ================= SEKTOR PENGELUARAN =================
            if ($isSektorPengeluaran) {

                // Ambil ulang data subkategori dengan filter yang benar
                $querySektor = \App\Models\NilaiSubKategori::where('id_wilayah', $wilayah);

                if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
                    $querySektor->where('tipe_pdrb', $tipePdrb);
                }

                // Filter untuk memastikan hanya data pengeluaran
                $querySektor->whereHas('subKategori.kategori', function ($q) {
                    $q->where('pendekatan', 'pengeluaran');
                });

                $allDataSektor = $querySektor->get()->groupBy(function ($item) {
                    return $item->id_sub_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
                });

                // Ambil juga data kategori sebagai fallback
                $queryKategoriSektor = \App\Models\NilaiKategori::where('id_wilayah', $wilayah);
                if (!in_array($jenis, ['indeks_implisit', 'laju_implisit'])) {
                    $queryKategoriSektor->where('tipe_pdrb', $tipePdrb);
                }
                $allKategoriSektor = $queryKategoriSektor->get()->groupBy(function ($item) {
                    return $item->id_kategori . '_' . $item->id_tahun . '_' . $item->id_periode;
                });

                $sektorMap = [
                    'KONSUMSI RUMAH TANGGA' => [23],
                    'KONSUMSI PEMERINTAH' => [25],
                    'PEMBENTUKAN MODAL TETAP BRUTO' => [26],
                    'LAINNYA' => [24, 27, 28, 29, 30] // 24=LNPRT, 27=Inventori, 28=Ekspor, 29=Impor, 30=Net Ekspor Antar Daerah
                ];

                $totalPDRBSektorPengeluaran = [
                    'kategori' => 'PRODUK DOMESTIK REGIONAL BRUTO',
                    'level' => 1,
                    'kode' => 'PDRB'
                ];

                // Array untuk menyimpan total per tahun
                $totalPerTahun = [];

                foreach ($sektorMap as $namaSektor => $kategoriIds) {
                    $rowSektor = ['kategori' => $namaSektor, 'level' => 1];

                    // Array untuk menyimpan nilai per periode
                    $nilaiPerPeriode = [];

                    $kategoriSektor = \App\Models\Kategori::with('subKategori')
                        ->whereIn('id_kategori', $kategoriIds)
                        ->get();

                    foreach ($periodeTampil as $p) {
                        $key = $p['key'];

                        if ($p['triwulan'] === 'total') {
                            // Untuk TOTAL, hitung dari akumulasi nilai TW1-4 tahun tersebut
                            $tahun = $p['tahun'];
                            $totalTahun = 0;
                            for ($tw = 1; $tw <= 4; $tw++) {
                                $twKey = $tahun . '_tw' . $tw;
                                if (isset($nilaiPerPeriode[$twKey])) {
                                    $totalTahun += $nilaiPerPeriode[$twKey];
                                }
                            }

                            $rowSektor[$key] = [
                                'nilai' => $totalTahun,
                                'persen' => 0,
                                'flag' => abs($totalTahun) > 0.0001 ? 'WARNING' : 'OK'
                            ];

                            // Simpan total tahunan untuk PDRB
                            $totalPerTahun[$tahun] = ($totalPerTahun[$tahun] ?? 0) + $totalTahun;

                            continue;
                        }

                        $y = $p['tahun'];
                        $tw = $p['triwulan'];
                        $idTahunLoop = $tahunMap[$y] ?? null;

                        if (!$idTahunLoop) {
                            $rowSektor[$key] = ['nilai' => 0, 'persen' => 0, 'flag' => 'OK'];
                            $nilaiPerPeriode[$key] = 0;
                            continue;
                        }

                        $totalP0 = 0;
                        $totalP1 = 0;

                        foreach ($kategoriSektor as $kategori) {
                            // jika ada subkategori
                            if ($kategori->subKategori->count() > 0) {
                                foreach ($kategori->subKategori as $sub) {
                                    if (!$mode) {
                                        $groupKey = $sub->id_sub_kategori . '_' . $idTahunLoop . '_' . $tw;
                                        $nilai = $allDataSektor->get($groupKey, collect());

                                        if ($nilai->isEmpty()) {
                                            // Fallback ke data kategori
                                            $groupKeyKategori = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                                            $nilaiKategori = $allKategoriSektor->get($groupKeyKategori, collect());

                                            $nilaiP0 = $nilaiKategori->where('tahap_data', 'awal')->sum('nilai');
                                            $nilaiP1 = $nilaiKategori->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                            if ($nilaiKategori->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                                $nilaiP1 = $nilaiP0;
                                        } else {
                                            $nilaiP0 = $nilai->where('tahap_data', 'awal')->sum('nilai');
                                            $nilaiP1 = $nilai->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                            if ($nilai->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                                $nilaiP1 = $nilaiP0;
                                        }
                                    } else {
                                        // Mode GROWTH
                                        $nilaiP0 = $this->hitungGrowthFinal($mode, $allDataSektor, $sub->id_sub_kategori, $tahunMap, $y, $tw, 'awal');
                                        $nilaiP1 = $this->hitungGrowthFinal($mode, $allDataSektor, $sub->id_sub_kategori, $tahunMap, $y, $tw, 'rekonsiliasi');
                                    }

                                    $totalP0 += $nilaiP0;
                                    $totalP1 += $nilaiP1;
                                }
                            } else {
                                // kategori tanpa subkategori
                                if (!$mode) {
                                    $groupKey = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                                    $nilai = $allDataSektor->get($groupKey, collect());

                                    if ($nilai->isEmpty()) {
                                        $nilaiP0 = 0;
                                        $nilaiP1 = 0;
                                    } else {
                                        $nilaiP0 = $nilai->where('tahap_data', 'awal')->sum('nilai');
                                        $nilaiP1 = $nilai->where('tahap_data', 'rekonsiliasi')->sum('nilai');
                                        if ($nilai->where('tahap_data', 'rekonsiliasi')->count() == 0)
                                            $nilaiP1 = $nilaiP0;
                                    }
                                } else {
                                    // Mode GROWTH
                                    $nilaiP0 = $this->hitungGrowthFinalKategori($mode, $allKategoriSektor, $kategori->id_kategori, $tahunMap, $y, $tw, 'awal');
                                    $nilaiP1 = $this->hitungGrowthFinalKategori($mode, $allKategoriSektor, $kategori->id_kategori, $tahunMap, $y, $tw, 'rekonsiliasi');
                                }

                                $totalP0 += $nilaiP0;
                                $totalP1 += $nilaiP1;
                            }
                        }

                        $selisih = $totalP1 - $totalP0;
                        $persen = $totalP0 != 0 ? ($selisih / $totalP0) * 100 : 0;

                        $rowSektor[$key] = [
                            'nilai' => $selisih,
                            'persen' => $persen,
                            'flag' => abs($selisih) > 0.0001 ? 'WARNING' : 'OK'
                        ];

                        $nilaiPerPeriode[$key] = $selisih;
                    }

                    $sektorRows[] = $rowSektor;
                }

                // Hitung PDRB untuk setiap periode
                foreach ($periodeTampil as $p) {
                    $key = $p['key'];

                    if ($p['triwulan'] === 'total') {
                        // Untuk TOTAL, gunakan total per tahun yang sudah dihitung
                        $tahun = $p['tahun'];
                        $nilaiTotal = $totalPerTahun[$tahun] ?? 0;

                        $totalPDRBSektorPengeluaran[$key] = [
                            'nilai' => $nilaiTotal,
                            'persen' => 0,
                            'flag' => abs($nilaiTotal) > 0.0001 ? 'WARNING' : 'OK'
                        ];
                    } else {
                        // Untuk TW, jumlahkan nilai dari semua sektor
                        $nilaiTotal = 0;
                        foreach ($sektorRows as $row) {
                            if (isset($row[$key])) {
                                $nilaiTotal += $row[$key]['nilai'];
                            }
                        }

                        $totalPDRBSektorPengeluaran[$key] = [
                            'nilai' => $nilaiTotal,
                            'persen' => 0,
                            'flag' => abs($nilaiTotal) > 0.0001 ? 'WARNING' : 'OK'
                        ];
                    }
                }

                // Tambahkan baris total PDRB di akhir
                $sektorRows[] = $totalPDRBSektorPengeluaran;
            }

            // ================= KEMBALIKAN ROWS (HANYA SEKTOR) =================
            $rows = $sektorRows;

        } else {

            // ================= BUKAN MODE SEKTOR =================
// Lanjutkan dengan loop kategori seperti biasa

            foreach ($kategoriList as $kategori) {
                $twKategori = [];
                $startIndex = count($rows);
                $lastKategori = $kategori->nama_kategori;
                $subIndex = 0;

                // 🔥 PERBAIKAN: Ambil data kategori langsung untuk kategori level 2
                $nilaiKategoriLangusng = [];

                // Loop periode untuk kategori
                foreach ($periodeTampil as $p) {
                    if ($p['triwulan'] === 'total')
                        continue;

                    $y = $p['tahun'];
                    $tw = $p['triwulan'];
                    $key = $p['key'];
                    $idTahunLoop = $tahunMap[$y] ?? null;

                    if (!$idTahunLoop)
                        continue;

                    // Ambil data kategori langsung
                    $groupKey = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                    $nilaiKat = $allKategori->get($groupKey, collect());

                    $nilaiKatP0 = $nilaiKat->where('tahap_data', 'awal')->sum('nilai');
                    $nilaiKatP1 = $nilaiKat->where('tahap_data', 'rekonsiliasi')->sum('nilai');

                    if ($nilaiKat->where('tahap_data', 'rekonsiliasi')->count() == 0) {
                        $nilaiKatP1 = $nilaiKatP0;
                    }

                    $nilaiKategoriLangusng[$key] = [
                        'p0' => $nilaiKatP0,
                        'p1' => $nilaiKatP1
                    ];
                }

                // Mode NORMAL atau GROWTH untuk kategori
                foreach ($periodeTampil as $p) {
                    if ($p['triwulan'] === 'total')
                        continue;

                    $y = $p['tahun'];
                    $tw = $p['triwulan'];
                    $key = $p['key'];
                    $idTahunLoop = $tahunMap[$y] ?? null;

                    if (!$idTahunLoop)
                        continue;

                    // Mode NORMAL (selisih nilai)
                    if (!$mode) {
                        // Gunakan nilai dari data langsung jika ada
                        if (isset($nilaiKategoriLangusng[$key])) {
                            $nilaiP0 = $nilaiKategoriLangusng[$key]['p0'];
                            $nilaiP1 = $nilaiKategoriLangusng[$key]['p1'];
                        } else {
                            $groupKey = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                            $nilai = $allKategori->get($groupKey, collect());

                            if ($nilai->count() == 0)
                                continue;

                            $nilaiP0 = $nilai->where('tahap_data', 'awal')->sum('nilai');
                            $nilaiP1 = $nilai->where('tahap_data', 'rekonsiliasi')->sum('nilai');

                            if ($nilai->where('tahap_data', 'rekonsiliasi')->count() == 0) {
                                $nilaiP1 = $nilaiP0;
                            }
                        }

                        $selisih = $nilaiP1 - $nilaiP0;
                        $persen = $nilaiP0 != 0 ? ($selisih / $nilaiP0) * 100 : 0;

                        $flag = $this->getStatusFlag($nilaiP0, $nilaiP1, $persen, false, $jenis);

                        $twKategori[$key] = [
                            'nilai' => $selisih,
                            'persen' => $persen,
                            'flag' => $flag
                        ];
                    }
                    // Mode GROWTH
                    else {
                        $valueP0 = $this->hitungGrowthFinalKategori(
                            $mode,
                            $allKategori,
                            $kategori->id_kategori,
                            $tahunMap,
                            $y,
                            $tw,
                            'awal'
                        );
                        $valueP1 = $this->hitungGrowthFinalKategori(
                            $mode,
                            $allKategori,
                            $kategori->id_kategori,
                            $tahunMap,
                            $y,
                            $tw,
                            'rekonsiliasi'
                        );

                        $selisih = $valueP1 - $valueP0;
                        $persen = $selisih;

                        $flag = $this->getStatusFlag($valueP0, $valueP1, $persen, false);

                        $twKategori[$key] = [
                            'nilai' => $selisih,
                            'persen' => $persen,
                            'flag' => $flag
                        ];
                    }
                }

                // ================= LOOP SUBKATEGORI =================
                foreach ($kategori->subKategori as $sub) {
                    $subIndex++;
                    $twSub = [];
                    $tahunAkumulasi = [];

                    // Untuk pengeluaran, semua subkategori adalah level 2
                    $isLevel2 = ($pendekatan === 'pengeluaran') ? true : ($sub->parent_id === null);

                    foreach ($periodeTampil as $p) {
                        if ($p['triwulan'] === 'total')
                            continue;

                        $y = $p['tahun'];
                        $tw = $p['triwulan'];
                        $key = $p['key'];
                        $idTahunLoop = $tahunMap[$y] ?? null;

                        if (!$idTahunLoop) {
                            $twSub[$key] = ['nilai' => 0, 'persen' => 0, 'flag' => 'OK'];
                            continue;
                        }

                        // Ambil data subkategori
                        $groupKey = $sub->id_sub_kategori . '_' . $idTahunLoop . '_' . $tw;
                        $nilai = $allData->get($groupKey, collect());

                        if ($nilai->count() > 0) {
                            $nilaiP0 = $nilai->where('tahap_data', 'awal')->sum('nilai');
                            $nilaiP1 = $nilai->where('tahap_data', 'rekonsiliasi')->sum('nilai');

                            if ($nilai->where('tahap_data', 'rekonsiliasi')->count() == 0) {
                                $nilaiP1 = $nilaiP0;
                            }
                        } else {
                            // Fallback ke data kategori
                            $groupKeyKategori = $kategori->id_kategori . '_' . $idTahunLoop . '_' . $tw;
                            $nilaiKategori = $allKategori->get($groupKeyKategori, collect());

                            $nilaiP0 = $nilaiKategori->where('tahap_data', 'awal')->sum('nilai');
                            $nilaiP1 = $nilaiKategori->where('tahap_data', 'rekonsiliasi')->sum('nilai');

                            if ($nilaiKategori->where('tahap_data', 'rekonsiliasi')->count() == 0) {
                                $nilaiP1 = $nilaiP0;
                            }
                        }

                        // Mode BIASA
                        if (!$mode) {
                            $selisih = $nilaiP1 - $nilaiP0;
                            $persen = $nilaiP0 != 0 ? ($selisih / $nilaiP0) * 100 : 0;
                            $flag = $this->getStatusFlag($nilaiP0, $nilaiP1, $persen, false, $jenis);
                        }
                        // Mode GROWTH
                        else {
                            $valueP0 = $this->hitungGrowthFinal(
                                $mode,
                                $allData,
                                $sub->id_sub_kategori,
                                $tahunMap,
                                $y,
                                $tw,
                                'awal'
                            );
                            $valueP1 = $this->hitungGrowthFinal(
                                $mode,
                                $allData,
                                $sub->id_sub_kategori,
                                $tahunMap,
                                $y,
                                $tw,
                                'rekonsiliasi'
                            );

                            $selisih = $valueP1 - $valueP0;
                            $persen = $selisih;
                            $flag = $this->getStatusFlag($valueP0, $valueP1, $persen, false);
                        }

                        $twSub[$key] = [
                            'nilai' => $selisih,
                            'persen' => $persen,
                            'flag' => $flag
                        ];

                        // 🔥 PERBAIKAN: Hanya akumulasi ke kategori jika bukan level 2
                        if (!$isLevel2) {
                            $twKategori[$key]['nilai'] = ($twKategori[$key]['nilai'] ?? 0) + $selisih;
                            $twKategori[$key]['persen'] = 0;
                            $twKategori[$key]['flag'] = $this->getStatusFlag(
                                0,
                                $twKategori[$key]['nilai'],
                                abs($twKategori[$key]['nilai']),
                                false,
                                $jenis
                            );
                        }

                        // Akumulasi tahunan
                        $tahunAkumulasi[$y]['p0'] = ($tahunAkumulasi[$y]['p0'] ?? 0) + $nilaiP0;
                        $tahunAkumulasi[$y]['p1'] = ($tahunAkumulasi[$y]['p1'] ?? 0) + $nilaiP1;
                    }

                    // Total tahunan untuk subkategori
                    foreach ($tahunAkumulasi as $tahun => $akum) {
                        $keyTotal = $tahun . '_total';

                        if (!$mode) {
                            $resumeP0 = $akum['p0'] ?? 0;
                            $resumeP1 = $akum['p1'] ?? 0;
                            $selisihTotal = $resumeP1 - $resumeP0;
                            $persenTotal = $resumeP0 != 0 ? ($selisihTotal / $resumeP0) * 100 : 0;
                            $flagTotal = $this->getStatusFlag($resumeP0, $resumeP1, $persenTotal, true, $jenis);
                        } else {
                            $growthTotalP0 = $this->hitungGrowthFinal(
                                $mode,
                                $allData,
                                $sub->id_sub_kategori,
                                $tahunMap,
                                $tahun,
                                4,
                                'awal'
                            );
                            $growthTotalP1 = $this->hitungGrowthFinal(
                                $mode,
                                $allData,
                                $sub->id_sub_kategori,
                                $tahunMap,
                                $tahun,
                                4,
                                'rekonsiliasi'
                            );
                            $selisihTotal = $growthTotalP1 - $growthTotalP0;
                            $persenTotal = $selisihTotal;
                            $flagTotal = $this->getStatusFlag($growthTotalP0, $growthTotalP1, $persenTotal, true);
                        }

                        $twSub[$keyTotal] = [
                            'nilai' => $selisihTotal,
                            'persen' => $persenTotal,
                            'flag' => $flagTotal
                        ];

                        // 🔥 PERBAIKAN: Hanya akumulasi total ke kategori jika bukan level 2
                        if (!$isLevel2) {
                            $twKategori[$keyTotal]['nilai'] = ($twKategori[$keyTotal]['nilai'] ?? 0) + $selisihTotal;
                            $twKategori[$keyTotal]['persen'] = 0;
                            $twKategori[$keyTotal]['flag'] = $this->getStatusFlag(
                                0,
                                $twKategori[$keyTotal]['nilai'],
                                abs($twKategori[$keyTotal]['nilai']),
                                true,
                                $jenis
                            );
                        }
                    }

                    // Kode untuk subkategori
                    $kode = \App\Helpers\PdrbHelper::getSubCode($subIndex, $lastKategori);
                    $level = $this->getLevelFromCode($kode);

                    $rows[] = array_merge([
                        'kategori' => $sub->nama_sub_kategori,
                        'level' => $level,
                        'kode' => $kode
                    ], $twSub);
                }

                // 🔥 PERBAIKAN: Hitung ulang nilai kategori untuk yang memiliki subkategori
                if ($kategori->subKategori->count() > 0) {
                    foreach ($periodeTampil as $p) {
                        $key = $p['key'];

                        if ($p['triwulan'] === 'total') {
                            $tahun = $p['tahun'];
                            $totalTahun = 0;

                            for ($tw = 1; $tw <= 4; $tw++) {
                                $twKey = $tahun . '_tw' . $tw;
                                $subTotal = 0;

                                foreach ($kategori->subKategori as $sub) {
                                    foreach ($rows as $row) {
                                        if ($row['kategori'] == $sub->nama_sub_kategori && isset($row[$twKey])) {
                                            $subTotal += $row[$twKey]['nilai'];
                                            break;
                                        }
                                    }
                                }
                                $totalTahun += $subTotal;
                            }

                            $twKategori[$key] = [
                                'nilai' => $totalTahun,
                                'persen' => 0,
                                'flag' => abs($totalTahun) > 0.0001 ? 'WARNING' : 'OK'
                            ];
                        } else {
                            $totalTw = 0;
                            foreach ($kategori->subKategori as $sub) {
                                foreach ($rows as $row) {
                                    if ($row['kategori'] == $sub->nama_sub_kategori && isset($row[$key])) {
                                        $totalTw += $row[$key]['nilai'];
                                        break;
                                    }
                                }
                            }

                            $twKategori[$key] = [
                                'nilai' => $totalTw,
                                'persen' => 0,
                                'flag' => abs($totalTw) > 0.0001 ? 'WARNING' : 'OK'
                            ];
                        }
                    }
                }

                // Insert kategori ke dalam rows
                $rows = array_merge(
                    array_slice($rows, 0, $startIndex),
                    [['kategori' => $kategori->nama_kategori, 'level' => 1] + $twKategori],
                    array_slice($rows, $startIndex)
                );
            }

            // ================= HAPUS SEMUA BARIS PDRB YANG ADA =================
            $filteredRows = [];
            $pdrbKeywords = ['PRODUK DOMESTIK REGIONAL BRUTO', 'PDRB', 'NON MIGAS', 'LAPUS'];

            foreach ($rows as $row) {
                $kategori = strtoupper($row['kategori']);
                $skip = false;

                foreach ($pdrbKeywords as $keyword) {
                    if (str_contains($kategori, $keyword)) {
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $filteredRows[] = $row;
                }
            }

            $rows = $filteredRows;

            // ================= PERBAIKAN NET EKSPOR UNTUK PENDEKATAN PENGELUARAN =================
            if ($pendekatan === 'pengeluaran') {
                // Cari nilai Ekspor, Impor, dan Net Ekspor dari rows yang sudah ada
                $eksporValue = null;
                $imporValue = null;
                $netEksporIndex = null;
                $eksporIndex = null;
                $imporIndex = null;

                foreach ($rows as $idx => $row) {
                    $kategori = strtoupper($row['kategori'] ?? '');

                    if (str_contains($kategori, 'EKSPOR') && !str_contains($kategori, 'IMPOR') && !str_contains($kategori, 'NET')) {
                        $eksporValue = $row;
                        $eksporIndex = $idx;
                    } elseif (str_contains($kategori, 'IMPOR') && !str_contains($kategori, 'NET')) {
                        $imporValue = $row;
                        $imporIndex = $idx;
                    } elseif (str_contains($kategori, 'NET EKSPOR') || $kategori === 'Net Ekspor') {
                        $netEksporIndex = $idx;
                    }
                }

                // Hitung Net Ekspor = Ekspor - Impor untuk setiap periode
                if ($eksporValue && $imporValue) {
                    $netEksporValues = [];

                    foreach ($periodeTampil as $p) {
                        $key = $p['key'];

                        $ekspor = 0;
                        $impor = 0;

                        // Ambil nilai ekspor
                        if (isset($eksporValue[$key])) {
                            if (is_array($eksporValue[$key]) && isset($eksporValue[$key]['nilai'])) {
                                $ekspor = $eksporValue[$key]['nilai'];
                            } elseif (is_numeric($eksporValue[$key])) {
                                $ekspor = $eksporValue[$key];
                            } elseif (isset($eksporValue[$key]['selisih'])) {
                                $ekspor = $eksporValue[$key]['selisih'];
                            } else {
                                $ekspor = 0;
                            }
                        }

                        // Ambil nilai impor
                        if (isset($imporValue[$key])) {
                            if (is_array($imporValue[$key]) && isset($imporValue[$key]['nilai'])) {
                                $impor = $imporValue[$key]['nilai'];
                            } elseif (is_numeric($imporValue[$key])) {
                                $impor = $imporValue[$key];
                            } elseif (isset($imporValue[$key]['selisih'])) {
                                $impor = $imporValue[$key]['selisih'];
                            } else {
                                $impor = 0;
                            }
                        }

                        $netEkspor = $ekspor - $impor;
                        $netEksporValues[$key] = $netEkspor;
                    }

                    // Update atau tambahkan baris Net Ekspor
                    if ($netEksporIndex !== null) {
                        // Update existing net ekspor
                        foreach ($netEksporValues as $key => $nilai) {
                            if (isset($rows[$netEksporIndex][$key])) {
                                if (is_array($rows[$netEksporIndex][$key])) {
                                    $rows[$netEksporIndex][$key]['nilai'] = $nilai;
                                    $rows[$netEksporIndex][$key]['persen'] = 0;
                                } else {
                                    $rows[$netEksporIndex][$key] = $nilai;
                                }
                            } else {
                                $rows[$netEksporIndex][$key] = $nilai;
                            }
                        }
                    } else {
                        // Buat baris baru Net Ekspor
                        $newRow = [
                            'kategori' => 'Net Ekspor',
                            'level' => 2,
                            'kode' => 'PNE'
                        ];

                        foreach ($netEksporValues as $key => $nilai) {
                            $newRow[$key] = $nilai;
                        }

                        // Insert setelah Impor (atau di akhir jika impor tidak ditemukan)
                        $insertPos = ($imporIndex !== null) ? $imporIndex + 1 : count($rows);
                        array_splice($rows, $insertPos, 0, [$newRow]);

                        // Update index net ekspor
                        $netEksporIndex = $insertPos;
                    }
                }
            }

            // ================= TAMBAHKAN BARIS PDRB SESUAI PENDEKATAN =================

            // Buat baris PDRB utama (selalu ditampilkan)
            $rowPDRB = [
                'kategori' => 'Produk Domestik Regional Bruto',
                'level' => 1,
                'kode' => 'PDRB'
            ];

            // Tentukan baris tambahan berdasarkan pendekatan
            if ($pendekatan === 'lapangan_usaha') {
                // Untuk Lapangan Usaha: tambahkan Non Migas
                $rowTambahan = [
                    'kategori' => 'Produk Domestik Regional Bruto Non Migas',
                    'level' => 1,
                    'kode' => 'NON MIGAS'
                ];
            } else {
                // Untuk Pengeluaran: tambahkan Lapus
                $rowTambahan = [
                    'kategori' => 'Produk Domestik Regional Bruto Lapus',
                    'level' => 1,
                    'kode' => 'PDRB'
                ];
            }

            // Isi dengan nilai default 0 untuk semua periode
            foreach ($periodeTampil as $p) {
                $key = $p['key'];
                $rowPDRB[$key] = ['nilai' => 0, 'persen' => 0, 'flag' => 'OK'];
                $rowTambahan[$key] = ['nilai' => 0, 'persen' => 0, 'flag' => 'OK'];
            }

            // TAMBAHKAN DI AKHIR (SETELAH SEMUA KATEGORI)
            $rows[] = $rowTambahan;  // Non Migas atau Lapus
            $rows[] = $rowPDRB;      // PDRB utama

            // ================= TAMBAHKAN KODE HIERARKI =================
            $lastKategori = null;
            $subIndex = 0;

            foreach ($rows as &$row) {
                if ($row['level'] == 1) {
                    $row['kode'] = \App\Helpers\PdrbHelper::getKategoriCode($row['kategori']);
                    $lastKategori = $row['kategori'];
                    $subIndex = 0;
                } else {
                    $subIndex++;
                    $row['kode'] = \App\Helpers\PdrbHelper::getSubCode($subIndex, $lastKategori);
                }
            }
            unset($row);

            $fields = array_map(fn($p) => $p['key'], $periodeTampil);

            // ================= HITUNG TOTAL PDRB =================
            $pdrb = [];
            $pdrbTambahan = [];

            foreach ($periodeTampil as $p) {
                $key = $p['key'];

                if ($pendekatan === 'pengeluaran') {
                    // Inisialisasi variabel untuk Pengeluaran
                    $konsumsiRT = 0;
                    $konsumsiLNPRT = 0;
                    $konsumsiPem = 0;
                    $pmtb = 0;
                    $inventori = 0;
                    $ekspor = 0;
                    $impor = 0;

                    foreach ($rows as $r) {
                        if (!isset($r['level']) || $r['level'] != 1)
                            continue;
                        if (!isset($r[$key]))
                            continue;

                        $kategori = $r['kategori'];
                        $nilai = 0;

                        // Ambil nilai dari array atau langsung
                        if (is_array($r[$key])) {
                            $nilai = $r[$key]['nilai'] ?? $r[$key]['selisih'] ?? 0;
                        } else {
                            $nilai = $r[$key];
                        }

                        if (str_contains($kategori, 'Konsumsi Rumah Tangga') && !str_contains($kategori, 'LNPRT')) {
                            $konsumsiRT = $nilai;
                        } elseif (str_contains($kategori, 'Konsumsi Lembaga Nonprofit') || str_contains($kategori, 'LNPRT')) {
                            $konsumsiLNPRT = $nilai;
                        } elseif (str_contains($kategori, 'Konsumsi Pemerintah')) {
                            $konsumsiPem = $nilai;
                        } elseif (str_contains($kategori, 'Pembentukan Modal Tetap Bruto') || str_contains($kategori, 'PMTB')) {
                            $pmtb = $nilai;
                        } elseif (str_contains($kategori, 'Perubahan Inventori')) {
                            $inventori = $nilai;
                        } elseif (str_contains($kategori, 'Ekspor') && !str_contains($kategori, 'Impor') && !str_contains($kategori, 'Net')) {
                            $ekspor = $nilai;
                        } elseif (str_contains($kategori, 'Impor') && !str_contains($kategori, 'Ekspor') && !str_contains($kategori, 'Net')) {
                            $impor = $nilai;
                        }
                    }

                    // Cari Net Ekspor yang sudah dihitung (jika ada)
                    $netEkspor = 0;
                    foreach ($rows as $r) {
                        $kategori = strtoupper($r['kategori'] ?? '');
                        if (str_contains($kategori, 'NET EKSPOR') || $kategori === 'NET EKSPOR') {
                            if (isset($r[$key])) {
                                if (is_array($r[$key])) {
                                    $netEkspor = $r[$key]['nilai'] ?? $r[$key]['selisih'] ?? 0;
                                } else {
                                    $netEkspor = $r[$key];
                                }
                            }
                            break;
                        }
                    }

                    // Jika Net Ekspor tidak ditemukan, hitung dari Ekspor - Impor
                    if ($netEkspor == 0 && ($ekspor != 0 || $impor != 0)) {
                        $netEkspor = $ekspor - $impor;
                    }

                    // Rumus PDRB Pengeluaran
                    $totalSelisih = $konsumsiRT + $konsumsiLNPRT + $konsumsiPem + $pmtb + $inventori + $netEkspor;

                } else {
                    // Untuk lapangan usaha, jumlahkan semua kategori level 1
                    $totalSelisih = 0;
                    foreach ($rows as $r) {
                        if (!isset($r['level']) || $r['level'] != 1)
                            continue;
                        if (
                            in_array($r['kategori'], [
                                'Produk Domestik Regional Bruto',
                                'Produk Domestik Regional Bruto Non Migas',
                                'Produk Domestik Regional Bruto Lapus'
                            ])
                        )
                            continue;

                        if (isset($r[$key])) {
                            if (is_array($r[$key])) {
                                $totalSelisih += $r[$key]['nilai'] ?? $r[$key]['selisih'] ?? 0;
                            } else {
                                $totalSelisih += $r[$key];
                            }
                        }
                    }
                }

                $flag = abs($totalSelisih) > 0.0001 ? 'WARNING' : 'OK';

                $pdrb[$key] = [
                    'nilai' => $totalSelisih,
                    'persen' => 0,
                    'flag' => $flag
                ];

                $pdrbTambahan[$key] = $pdrb[$key];
            }

            // ================= UPDATE ROW PDRB =================
            foreach ($rows as &$row) {
                if ($row['kategori'] === 'Produk Domestik Regional Bruto') {
                    foreach ($pdrb as $k => $v) {
                        $row[$k] = $v;
                    }
                }

                // Update baris tambahan (Non Migas untuk Lapus, atau Lapus untuk Pengeluaran)
                if ($pendekatan === 'lapangan_usaha' && $row['kategori'] === 'Produk Domestik Regional Bruto Non Migas') {
                    foreach ($pdrbTambahan as $k => $v) {
                        $row[$k] = $v;
                    }
                }

                if ($pendekatan === 'pengeluaran' && $row['kategori'] === 'Produk Domestik Regional Bruto Lapus') {
                    foreach ($pdrbTambahan as $k => $v) {
                        $row[$k] = $v;
                    }
                }
            }
            unset($row);
        }

        $tahunList = \App\Models\Tahun::all();
        $periodeList = \App\Models\Periode::all();

        // Di akhir method cekSelisih(), SESUDAH semua perhitungan selesai
// TAPI SEBELUM return view

        // ================= FORCE FIX NET EKSPOR =================
        if ($pendekatan === 'pengeluaran') {
            $eksporValue = null;
            $imporValue = null;
            $netIndex = null;

            // Cari baris Ekspor, Impor, dan Net Ekspor
            foreach ($rows as $index => $row) {
                $kat = strtoupper($row['kategori'] ?? '');
                if (str_contains($kat, 'EKSPOR') && !str_contains($kat, 'IMPOR') && !str_contains($kat, 'NET')) {
                    $eksporValue = $row;
                } elseif (str_contains($kat, 'IMPOR') && !str_contains($kat, 'EKSPOR')) {
                    $imporValue = $row;
                } elseif (str_contains($kat, 'NET EKSPOR')) {
                    $netIndex = $index;
                }
            }

            // Jika Ekspor dan Impor ditemukan
            if ($eksporValue && $imporValue) {
                // Loop semua periode
                foreach ($periodeTampil as $p) {
                    $key = $p['key'];

                    // Ambil nilai Ekspor (support berbagai format)
                    $ekspor = 0;
                    if (isset($eksporValue[$key])) {
                        if (is_array($eksporValue[$key])) {
                            $ekspor = $eksporValue[$key]['nilai'] ?? $eksporValue[$key]['selisih'] ?? 0;
                        } else {
                            $ekspor = $eksporValue[$key];
                        }
                    }

                    // Ambil nilai Impor
                    $impor = 0;
                    if (isset($imporValue[$key])) {
                        if (is_array($imporValue[$key])) {
                            $impor = $imporValue[$key]['nilai'] ?? $imporValue[$key]['selisih'] ?? 0;
                        } else {
                            $impor = $imporValue[$key];
                        }
                    }

                    // Hitung ulang Net Ekspor
                    $netEksporBaru = $ekspor - $impor;

                    // Update nilai Net Ekspor
                    if ($netIndex !== null) {
                        if (isset($rows[$netIndex][$key])) {
                            if (is_array($rows[$netIndex][$key])) {
                                $rows[$netIndex][$key]['nilai'] = $netEksporBaru;
                                $rows[$netIndex][$key]['selisih'] = $netEksporBaru;
                            } else {
                                $rows[$netIndex][$key] = $netEksporBaru;
                            }
                        } else {
                            $rows[$netIndex][$key] = $netEksporBaru;
                        }
                    }
                }
            }
        }

        return view('rekonsiliasi.cekSelisih', compact(
            'rows',
            'wilayah',
            'idTahun',
            'tahunAktif',
            'triwulan',
            'pendekatan',
            'tipePdrb',
            'tahunList',
            'periodeList',
            'periodeTampil',
            'jenis',
            'mode',
            'wilayahList'
        ));
    }

    private function getLevelFromCode($kode)
    {
        if (preg_match('/^[A-Z]$/', $kode))
            return 1;
        return 2;
    }

    private function ambilNilaiGrowth(
        $allData,
        $subId,
        $idTahun,
        $periode,
        $tahap,
        $tipe = 'konstan'
    ) {
        $groupKey = $subId . '_' . $idTahun . '_' . $periode;
        $data = $allData->get($groupKey, collect());

        if ($tahap === 'rekonsiliasi') {
            $rekon = $data
                ->where('tahap_data', 'rekonsiliasi')
                ->where('tipe_pdrb', $tipe)
                ->sum('nilai');

            if ($rekon != 0)
                return $rekon;
        }

        return $data
            ->where('tahap_data', 'awal')
            ->where('tipe_pdrb', $tipe)
            ->sum('nilai');
    }


    private function hitungGrowthFinal(
        $mode,
        $allData,
        $subId,
        $tahunMap,
        $tahun,
        $triwulan,
        $tahap
    ) {

        $idNow = $tahunMap[$tahun] ?? null;
        if (!$idNow)
            return 0;

        // =========================
// INDEKS IMPLISIT
// =========================
        if ($mode === 'indeks_implisit' || $mode === 'laju_implisit') {

            $berlaku = $this->ambilNilaiGrowth(
                $allData,
                $subId,
                $idNow,
                $triwulan,
                $tahap,
                'berlaku'
            );

            $konstan = $this->ambilNilaiGrowth(
                $allData,
                $subId,
                $idNow,
                $triwulan,
                $tahap,
                'konstan'
            );

            if ($konstan == 0)
                return 0;

            $indeksNow = ($berlaku / $konstan) * 100;

            // jika hanya indeks
            if ($mode === 'indeks_implisit') {
                return $indeksNow;
            }

            // =========================
            // LAJU IMPLISIT
            // =========================

            $tahunPrev = $tahun - 1;
            $idPrev = $tahunMap[$tahunPrev] ?? null;

            if (!$idPrev)
                return 0;

            $berlakuPrev = $this->ambilNilaiGrowth(
                $allData,
                $subId,
                $idPrev,
                $triwulan,
                $tahap,
                'berlaku'
            );

            $konstanPrev = $this->ambilNilaiGrowth(
                $allData,
                $subId,
                $idPrev,
                $triwulan,
                $tahap,
                'konstan'
            );

            if ($konstanPrev == 0)
                return 0;

            $indeksPrev = ($berlakuPrev / $konstanPrev) * 100;

            if ($indeksPrev == 0)
                return 0;

            return (($indeksNow - $indeksPrev) / $indeksPrev) * 100;
        }

        // =========================
        // MODE GROWTH
        // =========================

        switch ($mode) {

            case 'qtoq':
                if ($triwulan == 1) {
                    $tahunPrev = $tahun - 1;
                    $twPrev = 4;
                } else {
                    $tahunPrev = $tahun;
                    $twPrev = $triwulan - 1;
                }
                break;

            case 'yoy':
                $tahunPrev = $tahun - 1;
                $twPrev = $triwulan;
                break;

            case 'ctoc':
                $tahunPrev = $tahun - 1;
                break;

            default:
                return 0;
        }

        $idPrev = $tahunMap[$tahunPrev] ?? null;
        if (!$idPrev)
            return 0;

        if ($mode === 'ctoc') {

            $now = 0;
            $prev = 0;

            for ($i = 1; $i <= $triwulan; $i++) {
                $now += $this->ambilNilaiGrowth($allData, $subId, $idNow, $i, $tahap);
                $prev += $this->ambilNilaiGrowth($allData, $subId, $idPrev, $i, $tahap);
            }

        } else {

            $now = $this->ambilNilaiGrowth($allData, $subId, $idNow, $triwulan, $tahap);
            $prev = $this->ambilNilaiGrowth($allData, $subId, $idPrev, $twPrev, $tahap);
        }

        if ($prev == 0)
            return 0;

        return (($now - $prev) / $prev) * 100;
    }

    private function hitungGrowthFinalKategori(
        $mode,
        $allKategori,
        $kategoriId,
        $tahunMap,
        $tahun,
        $triwulan,
        $tahap
    ) {

        $get = function ($y, $tw) use ($allKategori, $kategoriId, $tahunMap, $tahap) {

            $idTahun = $tahunMap[$y] ?? null;
            if (!$idTahun)
                return 0;

            $key = $kategoriId . '_' . $idTahun . '_' . $tw;

            $data = $allKategori->get($key, collect());

            $rekon = $data->where('tahap_data', 'rekonsiliasi')->sum('nilai');
            $awal = $data->where('tahap_data', 'awal')->sum('nilai');

            if ($tahap === 'rekonsiliasi' && $rekon != 0) {
                return $rekon;
            }

            return $awal;
        };

        // ================= QTOQ =================
        if ($mode === 'qtoq') {

            if ($triwulan == 1) {
                $prevYear = $tahun - 1;
                $prevTw = 4;
            } else {
                $prevYear = $tahun;
                $prevTw = $triwulan - 1;
            }

            $now = $get($tahun, $triwulan);
            $prev = $get($prevYear, $prevTw);

            if ($prev == 0)
                return 0;

            return (($now - $prev) / $prev) * 100;
        }

        // ================= YOY =================
        if ($mode === 'yoy') {

            $now = $get($tahun, $triwulan);
            $prev = $get($tahun - 1, $triwulan);

            if ($prev == 0)
                return 0;

            return (($now - $prev) / $prev) * 100;
        }

        // ================= CTOC =================
        if ($mode === 'ctoc') {

            $now = 0;
            $prev = 0;

            for ($i = 1; $i <= $triwulan; $i++) {
                $now += $get($tahun, $i);
                $prev += $get($tahun - 1, $i);
            }

            if ($prev == 0)
                return 0;

            return (($now - $prev) / $prev) * 100;
        }

        // ================= INDEKS IMPLISIT =================
        if ($mode === 'indeks_implisit') {

            $konstan = $get($tahun, $triwulan);
            $berlaku = $get($tahun, $triwulan);

            if ($konstan == 0)
                return 0;

            return ($berlaku / $konstan) * 100;
        }

        // ================= LAJU IMPLISIT =================
        if ($mode === 'laju_implisit') {

            $now = $get($tahun, $triwulan);
            $prev = $get($tahun - 1, $triwulan);

            if ($prev == 0)
                return 0;

            return (($now - $prev) / $prev) * 100;
        }

        return 0;
    }
    private function getStatusFlag($awal, $revisi, $persen, $isTotal = false, $jenis = null)
    {
        // =============================
        // KHUSUS PDRB BERLAKU / KONSTAN
        // =============================
        if (in_array($jenis, ['berlaku', 'konstan'])) {

            $selisih = abs($revisi - $awal);

            if ($selisih > 0.0001) {
                return 'ADA SELISIH'; // kuning
            }

            return 'OK';
        }

        // =============================
        // MODE GROWTH (qtoq, yoy, dll)
        // =============================
        $balikArah = ($awal > 0 && $revisi < 0) || ($awal < 0 && $revisi > 0);
        $extreme = false;

        if ($isTotal) {
            if (abs($persen) > 0.02)
                $extreme = true;
        } else {
            if (abs($persen) > 4.99)
                $extreme = true;
        }

        if ($extreme && $balikArah)
            return 'EXTREME & BALIK ARAH';
        if ($extreme)
            return 'EXTREME';
        if ($balikArah)
            return 'BALIK ARAH';

        return 'OK';
    }

    /**
     * Helper untuk mengambil nilai dengan logika yang konsisten
     */
    private function getNilaiKategori($idKategori, $idWilayah, $idTahun, $idPeriode, $tipePdrb, $tahap = 'awal')
    {
        $query = NilaiKategori::where('id_kategori', $idKategori)
            ->where('id_wilayah', $idWilayah)
            ->where('id_tahun', $idTahun)
            ->where('tipe_pdrb', $tipePdrb);

        if ($idPeriode !== 'all') {
            $query->where('id_periode', $idPeriode);
            $nilai = $query->where('tahap_data', $tahap)->first();
            return $nilai ? (float) $nilai->nilai : 0;
        } else {
            // Untuk 'all', jumlahkan semua periode 1-4
            $total = 0;
            for ($p = 1; $p <= 4; $p++) {
                $nilai = (clone $query)->where('id_periode', $p)
                    ->where('tahap_data', $tahap)
                    ->first();
                $total += $nilai ? (float) $nilai->nilai : 0;
            }
            return $total;
        }
    }

    /**
     * Helper untuk mengambil nilai subkategori dengan logika yang konsisten
     */
    private function getNilaiSub($idSub, $idWilayah, $idTahun, $idPeriode, $tipePdrb, $tahap = 'awal')
    {
        $query = NilaiSubKategori::where('id_sub_kategori', $idSub)
            ->where('id_wilayah', $idWilayah)
            ->where('id_tahun', $idTahun)
            ->where('tipe_pdrb', $tipePdrb);

        if ($idPeriode !== 'all') {
            $query->where('id_periode', $idPeriode);
            $nilai = $query->where('tahap_data', $tahap)->first();
            return $nilai ? (float) $nilai->nilai : 0;
        } else {
            // Untuk 'all', jumlahkan semua periode 1-4
            $total = 0;
            for ($p = 1; $p <= 4; $p++) {
                $nilai = (clone $query)->where('id_periode', $p)
                    ->where('tahap_data', $tahap)
                    ->first();
                $total += $nilai ? (float) $nilai->nilai : 0;
            }
            return $total;
        }
    }

    /**
     * Helper untuk mengambil nilai dengan fallback P1 -> P0
     */
    private function getNilaiWithResume($id, $idWilayah, $idTahun, $idPeriode, $tipePdrb, $isResumeP1, $type = 'kategori')
    {
        if ($type === 'kategori') {
            $nilaiP1 = $this->getNilaiKategori($id, $idWilayah, $idTahun, $idPeriode, $tipePdrb, 'rekonsiliasi');
            if ($isResumeP1 && $nilaiP1 != 0) {
                return $nilaiP1;
            }
            return $this->getNilaiKategori($id, $idWilayah, $idTahun, $idPeriode, $tipePdrb, 'awal');
        } else {
            $nilaiP1 = $this->getNilaiSub($id, $idWilayah, $idTahun, $idPeriode, $tipePdrb, 'rekonsiliasi');
            if ($isResumeP1 && $nilaiP1 != 0) {
                return $nilaiP1;
            }
            return $this->getNilaiSub($id, $idWilayah, $idTahun, $idPeriode, $tipePdrb, 'awal');
        }
    }

    /**
     * Get nilai from rekonsiliasi for Fenomena input
     */
    /**
     * Get nilai from rekonsiliasi for Fenomena input
     * Mengikuti logika Y on Y dan Laju Implisit yang sudah ada
     */
    // app/Http/Controllers/RekonsiliasiController.php

    /**
     * Get nilai from rekonsiliasi for Fenomena input
     * Berdasarkan wilayah yang dipilih (kabupaten/kota)
     */
    /**
     * Get nilai from rekonsiliasi for Fenomena input
     * Berdasarkan wilayah yang dipilih (kabupaten/kota)

     * Get nilai from rekonsiliasi for Fenomena input - VERSION SEDERHANA UNTUK TESTING
     */
    /**
     * Get nilai from rekonsiliasi for Fenomena input
     * Berdasarkan wilayah yang dipilih (kabupaten/kota)
     */
    /**
     * Get nilai untuk fenomena tahunan - EXCLUDE NON MIGAS
     */
    public function getNilaiForFenomena(Request $request)
    {
        try {
            $tahun = $request->get('tahun');
            $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
            $idWilayah = $request->get('id_wilayah');

            \Log::info('=== getNilaiForFenomena ASLI ===');
            \Log::info('Tahun: ' . $tahun);
            \Log::info('Pendekatan: ' . $pendekatan);
            \Log::info('ID Wilayah: ' . $idWilayah);

            if (!$tahun) {
                return response()->json(['error' => 'Tahun tidak ditemukan'], 400);
            }

            if (!$idWilayah) {
                return response()->json(['error' => 'Wilayah tidak ditemukan'], 400);
            }

            // ===========================================
            // 1. DAPATKAN ID TAHUN
            // ===========================================
            $tahunModel = \App\Models\Tahun::where('tahun', $tahun)->first();

            if (!$tahunModel) {
                return response()->json(['error' => 'Tahun tidak ditemukan di database'], 404);
            }

            $idTahun = $tahunModel->id_tahun;

            // ===========================================
            // 2. DAPATKAN TAHUN SEBELUMNYA
            // ===========================================
            $tahunPrevModel = \App\Models\Tahun::where('tahun', $tahun - 1)->first();

            if (!$tahunPrevModel) {
                return response()->json([
                    'warning' => 'Data tahun ' . ($tahun - 1) . ' tidak ditemukan',
                    'data' => []
                ]);
            }

            $idTahunPrev = $tahunPrevModel->id_tahun;

            // ===========================================
            // 3. DAPATKAN ID KATEGORI YANG DIEXCLUDE (NON MIGAS)
            // ===========================================
            $excludeKategoriIds = \App\Models\Kategori::where('pendekatan', $pendekatan)
                ->where(function ($q) {
                    $q->where('nama_kategori', 'LIKE', '%Non Migas%')
                        ->orWhere('nama_kategori', 'Produk Domestik Regional Bruto Non Migas');
                })
                ->pluck('id_kategori')
                ->toArray();

            // Dapatkan juga ID subkategori yang terkait dengan kategori Non Migas
            $excludeSubKategoriIds = \App\Models\SubKategori::whereIn('id_kategori', $excludeKategoriIds)
                ->pluck('id_sub_kategori')
                ->toArray();

            \Log::info('Exclude kategori IDs: ' . json_encode($excludeKategoriIds));
            \Log::info('Exclude subkategori IDs: ' . json_encode($excludeSubKategoriIds));

            // ===========================================
            // 4. AMBIL DATA UNTUK WILAYAH YANG DIPILIH
            //    AKUMULASI SEMUA TRIWULAN (1-4)
            // ===========================================

            // Data tahun ini - PERTUMBUHAN (konstan) - AKUMULASI 4 TRIWULAN
            $nilaiNowRaw = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->select('id_kategori', 'nilai', 'tahap_data', 'id_periode')
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get();

            // Kelompokkan per kategori dan akumulasi
            $nilaiNow = [];
            foreach ($nilaiNowRaw as $item) {
                $id = $item->id_kategori;
                if (!isset($nilaiNow[$id])) {
                    $nilaiNow[$id] = 0;
                }
                $nilaiNow[$id] += (float) $item->nilai;
            }

            // Data tahun lalu - PERTUMBUHAN (konstan) - AKUMULASI 4 TRIWULAN
            $nilaiPrevRaw = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahunPrev)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                ->where('tahap_data', 'awal')
                ->select('id_kategori', 'nilai', 'id_periode')
                ->get();

            // Kelompokkan per kategori dan akumulasi
            $nilaiPrev = [];
            foreach ($nilaiPrevRaw as $item) {
                $id = $item->id_kategori;
                if (!isset($nilaiPrev[$id])) {
                    $nilaiPrev[$id] = 0;
                }
                $nilaiPrev[$id] += (float) $item->nilai;
            }

            // Data tahun ini - LAJU IMPLISIT (berlaku) - AKUMULASI 4 TRIWULAN
            $nilaiBerlakuNowRaw = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'berlaku')
                ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->select('id_kategori', 'nilai', 'tahap_data', 'id_periode')
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get();

            // Kelompokkan per kategori dan akumulasi
            $nilaiBerlakuNow = [];
            foreach ($nilaiBerlakuNowRaw as $item) {
                $id = $item->id_kategori;
                if (!isset($nilaiBerlakuNow[$id])) {
                    $nilaiBerlakuNow[$id] = 0;
                }
                $nilaiBerlakuNow[$id] += (float) $item->nilai;
            }

            // Data tahun lalu - LAJU IMPLISIT (berlaku) - AKUMULASI 4 TRIWULAN
            $nilaiBerlakuPrevRaw = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahunPrev)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'berlaku')
                ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                ->where('tahap_data', 'awal')
                ->select('id_kategori', 'nilai', 'id_periode')
                ->get();

            // Kelompokkan per kategori dan akumulasi
            $nilaiBerlakuPrev = [];
            foreach ($nilaiBerlakuPrevRaw as $item) {
                $id = $item->id_kategori;
                if (!isset($nilaiBerlakuPrev[$id])) {
                    $nilaiBerlakuPrev[$id] = 0;
                }
                $nilaiBerlakuPrev[$id] += (float) $item->nilai;
            }

            // ===========================================
            // 5. AMBIL DATA SUBKATEGORI - AKUMULASI 4 TRIWULAN
            //    EXCLUDE NON MIGAS
            // ===========================================

            // Subkategori - tahun ini (konstan)
            $subNowRaw = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)  // EXCLUDE NON MIGAS
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->select('id_sub_kategori', 'nilai', 'tahap_data', 'id_periode')
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get();

            $subNow = [];
            foreach ($subNowRaw as $item) {
                $id = $item->id_sub_kategori;
                if (!isset($subNow[$id])) {
                    $subNow[$id] = 0;
                }
                $subNow[$id] += (float) $item->nilai;
            }

            // Subkategori - tahun lalu (konstan)
            $subPrevRaw = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahunPrev)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)  // EXCLUDE NON MIGAS
                ->where('tahap_data', 'awal')
                ->select('id_sub_kategori', 'nilai', 'id_periode')
                ->get();

            $subPrev = [];
            foreach ($subPrevRaw as $item) {
                $id = $item->id_sub_kategori;
                if (!isset($subPrev[$id])) {
                    $subPrev[$id] = 0;
                }
                $subPrev[$id] += (float) $item->nilai;
            }

            // Subkategori - tahun ini (berlaku)
            $subBerlakuNowRaw = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'berlaku')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)  // EXCLUDE NON MIGAS
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->select('id_sub_kategori', 'nilai', 'tahap_data', 'id_periode')
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get();

            $subBerlakuNow = [];
            foreach ($subBerlakuNowRaw as $item) {
                $id = $item->id_sub_kategori;
                if (!isset($subBerlakuNow[$id])) {
                    $subBerlakuNow[$id] = 0;
                }
                $subBerlakuNow[$id] += (float) $item->nilai;
            }

            // Subkategori - tahun lalu (berlaku)
            $subBerlakuPrevRaw = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahunPrev)
                ->whereIn('id_periode', [1, 2, 3, 4])
                ->where('tipe_pdrb', 'berlaku')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)  // EXCLUDE NON MIGAS
                ->where('tahap_data', 'awal')
                ->select('id_sub_kategori', 'nilai', 'id_periode')
                ->get();

            $subBerlakuPrev = [];
            foreach ($subBerlakuPrevRaw as $item) {
                $id = $item->id_sub_kategori;
                if (!isset($subBerlakuPrev[$id])) {
                    $subBerlakuPrev[$id] = 0;
                }
                $subBerlakuPrev[$id] += (float) $item->nilai;
            }

            // ===========================================
            // 6. AMBIL SEMUA KATEGORI (EXCLUDE NON MIGAS)
            // ===========================================
            $kategoriList = \App\Models\Kategori::where('pendekatan', $pendekatan)
                ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                ->get();

            $result = [
                'pertumbuhan_kategori' => [],
                'laju_implisit_kategori' => [],
                'pertumbuhan_sub' => [],
                'laju_implisit_sub' => []
            ];

            foreach ($kategoriList as $kategori) {
                $id = $kategori->id_kategori;

                // === HITUNG PERTUMBUHAN ===
                $now = isset($nilaiNow[$id]) ? $nilaiNow[$id] : 0;
                $prev = isset($nilaiPrev[$id]) ? $nilaiPrev[$id] : 0;

                if ($prev != 0) {
                    $result['pertumbuhan_kategori'][$id] = round((($now - $prev) / $prev) * 100, 2);
                } else {
                    $result['pertumbuhan_kategori'][$id] = 0;
                }

                // === HITUNG LAJU IMPLISIT ===
                $berlakuNow = isset($nilaiBerlakuNow[$id]) ? $nilaiBerlakuNow[$id] : 0;
                $berlakuPrev = isset($nilaiBerlakuPrev[$id]) ? $nilaiBerlakuPrev[$id] : 0;

                // Indeks Implisit = (Nilai Berlaku / Nilai Konstan) * 100
                $indeksNow = ($now != 0) ? ($berlakuNow / $now) * 100 : 0;
                $indeksPrev = ($prev != 0) ? ($berlakuPrev / $prev) * 100 : 0;

                // Laju Implisit = (Indeks Now - Indeks Prev) / Indeks Prev * 100
                if ($indeksPrev != 0) {
                    $result['laju_implisit_kategori'][$id] = round((($indeksNow - $indeksPrev) / $indeksPrev) * 100, 2);
                } else {
                    $result['laju_implisit_kategori'][$id] = 0;
                }
            }

            // ===========================================
            // 7. AMBIL SEMUA SUBKATEGORI (EXCLUDE NON MIGAS)
            // ===========================================
            $subKategoriList = \App\Models\SubKategori::whereHas('kategori', function ($q) use ($pendekatan, $excludeKategoriIds) {
                $q->where('pendekatan', $pendekatan)
                    ->whereNotIn('id_kategori', $excludeKategoriIds);
            })
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)  // EXCLUDE NON MIGAS
                ->get();

            foreach ($subKategoriList as $sub) {
                $id = $sub->id_sub_kategori;

                // === PERTUMBUHAN SUBKATEGORI ===
                $now = isset($subNow[$id]) ? $subNow[$id] : 0;
                $prev = isset($subPrev[$id]) ? $subPrev[$id] : 0;

                if ($prev != 0) {
                    $result['pertumbuhan_sub'][$id] = round((($now - $prev) / $prev) * 100, 2);
                } else {
                    $result['pertumbuhan_sub'][$id] = 0;
                }

                // === LAJU IMPLISIT SUBKATEGORI ===
                $berlakuNow = isset($subBerlakuNow[$id]) ? $subBerlakuNow[$id] : 0;
                $berlakuPrev = isset($subBerlakuPrev[$id]) ? $subBerlakuPrev[$id] : 0;

                $indeksNow = ($now != 0) ? ($berlakuNow / $now) * 100 : 0;
                $indeksPrev = ($prev != 0) ? ($berlakuPrev / $prev) * 100 : 0;

                if ($indeksPrev != 0) {
                    $result['laju_implisit_sub'][$id] = round((($indeksNow - $indeksPrev) / $indeksPrev) * 100, 2);
                } else {
                    $result['laju_implisit_sub'][$id] = 0;
                }
            }

            // ===========================================
            // 8. TAMBAHKAN DATA PDRB KHUSUS UNTUK PENGELUARAN
            //    (EXCLUDE NON MIGAS)
            // ===========================================
            if ($pendekatan === 'pengeluaran') {
                $pdrbLapus = \App\Models\Kategori::where('pendekatan', 'lapangan_usaha')
                    ->where('nama_kategori', 'Produk Domestik Regional Bruto')
                    ->whereNotIn('id_kategori', $excludeKategoriIds)  // EXCLUDE NON MIGAS
                    ->first();

                if ($pdrbLapus) {
                    $id = $pdrbLapus->id_kategori;

                    // Pertumbuhan PDRB Lapus
                    $now = isset($nilaiNow[$id]) ? $nilaiNow[$id] : 0;
                    $prev = isset($nilaiPrev[$id]) ? $nilaiPrev[$id] : 0;

                    if ($prev != 0) {
                        $result['pertumbuhan_kategori'][999] = round((($now - $prev) / $prev) * 100, 2);
                    } else {
                        $result['pertumbuhan_kategori'][999] = 0;
                    }

                    // Laju Implisit PDRB Lapus
                    $berlakuNow = isset($nilaiBerlakuNow[$id]) ? $nilaiBerlakuNow[$id] : 0;
                    $berlakuPrev = isset($nilaiBerlakuPrev[$id]) ? $nilaiBerlakuPrev[$id] : 0;

                    $indeksNow = ($now != 0) ? ($berlakuNow / $now) * 100 : 0;
                    $indeksPrev = ($prev != 0) ? ($berlakuPrev / $prev) * 100 : 0;

                    if ($indeksPrev != 0) {
                        $result['laju_implisit_kategori'][999] = round((($indeksNow - $indeksPrev) / $indeksPrev) * 100, 2);
                    } else {
                        $result['laju_implisit_kategori'][999] = 0;
                    }
                }
            }

            \Log::info('Jumlah data pertumbuhan_kategori: ' . count($result['pertumbuhan_kategori']));

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Error in getNilaiForFenomena: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nilai untuk fenomena triwulanan - VERSION SEDERHANA UNTUK TESTING
     */

    /**
     * Get nilai untuk fenomena triwulanan - EXCLUDE NON MIGAS
     */
    public function getNilaiForFenomenaTriwulanan(Request $request)
    {
        try {
            $tahun = $request->get('tahun');
            $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
            $triwulan = (int) $request->get('triwulan', 1);
            $idWilayah = $request->get('id_wilayah');

            \Log::info('=== getNilaiForFenomenaTriwulanan ASLI ===');
            \Log::info('Tahun: ' . $tahun);
            \Log::info('Triwulan: ' . $triwulan);
            \Log::info('Pendekatan: ' . $pendekatan);
            \Log::info('ID Wilayah: ' . $idWilayah);

            if (!$tahun) {
                return response()->json(['error' => 'Tahun tidak ditemukan'], 400);
            }

            if (!$idWilayah) {
                return response()->json(['error' => 'Wilayah tidak ditemukan'], 400);
            }

            // ===========================================
            // 1. DAPATKAN ID TAHUN
            // ===========================================
            $tahunModel = \App\Models\Tahun::where('tahun', $tahun)->first();
            $tahunPrevModel = \App\Models\Tahun::where('tahun', $tahun - 1)->first();

            if (!$tahunModel) {
                return response()->json(['error' => 'Tahun tidak ditemukan di database'], 404);
            }

            $idTahun = $tahunModel->id_tahun;

            if (!$tahunPrevModel) {
                return response()->json([
                    'warning' => 'Data tahun ' . ($tahun - 1) . ' tidak ditemukan',
                    'data' => []
                ]);
            }

            $idTahunPrev = $tahunPrevModel->id_tahun;

            // ===========================================
            // 2. TENTUKAN PERIODE UNTUK PERHITUNGAN
            // ===========================================

            // Untuk Q-to-Q
            if ($triwulan == 1) {
                $prevTriwulanQtoQ = 4;
                $prevTahunQtoQ = $idTahunPrev;
            } else {
                $prevTriwulanQtoQ = $triwulan - 1;
                $prevTahunQtoQ = $idTahun;
            }

            // Untuk Y-on-Y (triwulan yang sama tahun lalu)
            $prevTriwulanYoy = $triwulan;
            $prevTahunYoy = $idTahunPrev;

            // Untuk C-to-C (cumulative-to-cumulative) - hanya untuk pendekatan pengeluaran
            // C-to-C adalah perbandingan cumulative dari triwulan 1 sampai triwulan saat ini
            // dengan cumulative dari triwulan 1 sampai triwulan yang sama tahun lalu

            // ===========================================
            // 3. DAPATKAN ID KATEGORI YANG DIEXCLUDE (NON MIGAS)
            // ===========================================
            $excludeKategoriIds = \App\Models\Kategori::where('pendekatan', $pendekatan)
                ->where(function ($q) {
                    $q->where('nama_kategori', 'LIKE', '%Non Migas%')
                        ->orWhere('nama_kategori', 'Produk Domestik Regional Bruto Non Migas');
                })
                ->pluck('id_kategori')
                ->toArray();

            // Dapatkan juga ID subkategori yang terkait dengan kategori Non Migas
            $excludeSubKategoriIds = \App\Models\SubKategori::whereIn('id_kategori', $excludeKategoriIds)
                ->pluck('id_sub_kategori')
                ->toArray();

            \Log::info('Exclude kategori IDs: ' . json_encode($excludeKategoriIds));
            \Log::info('Exclude subkategori IDs: ' . json_encode($excludeSubKategoriIds));

            // ===========================================
            // 4. AMBIL DATA KATEGORI (EXCLUDE NON MIGAS)
            // ===========================================

            // Data untuk triwulan ini - PRIORITAS REKONSILIASI
            $nilaiKatNow = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $triwulan)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_kategori', $excludeKategoriIds)
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get()
                ->groupBy('id_kategori')
                ->map(function ($items) {
                    return (float) $items->first()->nilai;
                });

            // Data untuk q-to-q (triwulan sebelumnya)
            $nilaiKatPrevQtoQ = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $prevTahunQtoQ)
                ->where('id_periode', $prevTriwulanQtoQ)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_kategori', $excludeKategoriIds)
                ->where('tahap_data', 'awal')
                ->get()
                ->keyBy('id_kategori')
                ->map(function ($item) {
                    return (float) $item->nilai;
                });

            // Data untuk y-on-y (triwulan yang sama tahun lalu)
            $nilaiKatYoy = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $prevTahunYoy)
                ->where('id_periode', $prevTriwulanYoy)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_kategori', $excludeKategoriIds)
                ->where('tahap_data', 'awal')
                ->get()
                ->keyBy('id_kategori')
                ->map(function ($item) {
                    return (float) $item->nilai;
                });

            // ===========================================
            // 4b. AMBIL DATA CUMULATIVE UNTUK C-TO-C (khusus pengeluaran)
            // ===========================================
            $nilaiKatCumulativeNow = [];
            $nilaiKatCumulativePrev = [];

            if ($pendekatan === 'pengeluaran') {
                // Ambil cumulative dari triwulan 1 sampai triwulan saat ini (tahun sekarang)
                for ($q = 1; $q <= $triwulan; $q++) {
                    $data = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                        ->where('id_tahun', $idTahun)
                        ->where('id_periode', $q)
                        ->where('tipe_pdrb', 'konstan')
                        ->whereNotIn('id_kategori', $excludeKategoriIds)
                        ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                        ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                        ->get()
                        ->groupBy('id_kategori')
                        ->map(function ($items) {
                            return (float) $items->first()->nilai;
                        });

                    foreach ($data as $kategoriId => $nilai) {
                        if (!isset($nilaiKatCumulativeNow[$kategoriId])) {
                            $nilaiKatCumulativeNow[$kategoriId] = 0;
                        }
                        $nilaiKatCumulativeNow[$kategoriId] += $nilai;
                    }
                }

                // Ambil cumulative dari triwulan 1 sampai triwulan yang sama tahun lalu
                for ($q = 1; $q <= $triwulan; $q++) {
                    $data = \App\Models\NilaiKategori::where('id_wilayah', $idWilayah)
                        ->where('id_tahun', $prevTahunYoy)
                        ->where('id_periode', $q)
                        ->where('tipe_pdrb', 'konstan')
                        ->whereNotIn('id_kategori', $excludeKategoriIds)
                        ->where('tahap_data', 'awal')
                        ->get()
                        ->keyBy('id_kategori')
                        ->map(function ($item) {
                            return (float) $item->nilai;
                        });

                    foreach ($data as $kategoriId => $nilai) {
                        if (!isset($nilaiKatCumulativePrev[$kategoriId])) {
                            $nilaiKatCumulativePrev[$kategoriId] = 0;
                        }
                        $nilaiKatCumulativePrev[$kategoriId] += $nilai;
                    }
                }
            }

            // ===========================================
            // 5. AMBIL DATA SUBKATEGORI (EXCLUDE NON MIGAS)
            // ===========================================

            $nilaiSubNow = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $triwulan)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                ->get()
                ->groupBy('id_sub_kategori')
                ->map(function ($items) {
                    return (float) $items->first()->nilai;
                });

            $nilaiSubPrevQtoQ = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $prevTahunQtoQ)
                ->where('id_periode', $prevTriwulanQtoQ)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                ->where('tahap_data', 'awal')
                ->get()
                ->keyBy('id_sub_kategori')
                ->map(function ($item) {
                    return (float) $item->nilai;
                });

            $nilaiSubYoy = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                ->where('id_tahun', $prevTahunYoy)
                ->where('id_periode', $prevTriwulanYoy)
                ->where('tipe_pdrb', 'konstan')
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                ->where('tahap_data', 'awal')
                ->get()
                ->keyBy('id_sub_kategori')
                ->map(function ($item) {
                    return (float) $item->nilai;
                });

            // ===========================================
            // 5b. AMBIL DATA CUMULATIVE SUBKATEGORI UNTUK C-TO-C
            // ===========================================
            $nilaiSubCumulativeNow = [];
            $nilaiSubCumulativePrev = [];

            if ($pendekatan === 'pengeluaran') {
                // Cumulative subkategori tahun sekarang
                for ($q = 1; $q <= $triwulan; $q++) {
                    $data = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                        ->where('id_tahun', $idTahun)
                        ->where('id_periode', $q)
                        ->where('tipe_pdrb', 'konstan')
                        ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                        ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
                        ->orderByRaw("FIELD(tahap_data, 'rekonsiliasi', 'awal')")
                        ->get()
                        ->groupBy('id_sub_kategori')
                        ->map(function ($items) {
                            return (float) $items->first()->nilai;
                        });

                    foreach ($data as $subId => $nilai) {
                        if (!isset($nilaiSubCumulativeNow[$subId])) {
                            $nilaiSubCumulativeNow[$subId] = 0;
                        }
                        $nilaiSubCumulativeNow[$subId] += $nilai;
                    }
                }

                // Cumulative subkategori tahun lalu
                for ($q = 1; $q <= $triwulan; $q++) {
                    $data = \App\Models\NilaiSubKategori::where('id_wilayah', $idWilayah)
                        ->where('id_tahun', $prevTahunYoy)
                        ->where('id_periode', $q)
                        ->where('tipe_pdrb', 'konstan')
                        ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                        ->where('tahap_data', 'awal')
                        ->get()
                        ->keyBy('id_sub_kategori')
                        ->map(function ($item) {
                            return (float) $item->nilai;
                        });

                    foreach ($data as $subId => $nilai) {
                        if (!isset($nilaiSubCumulativePrev[$subId])) {
                            $nilaiSubCumulativePrev[$subId] = 0;
                        }
                        $nilaiSubCumulativePrev[$subId] += $nilai;
                    }
                }
            }

            // ===========================================
            // 6. AMBIL SEMUA KATEGORI (EXCLUDE NON MIGAS)
            // ===========================================
            $kategoriList = \App\Models\Kategori::where('pendekatan', $pendekatan)
                ->whereNotIn('id_kategori', $excludeKategoriIds)
                ->get();

            $result = [
                'q_to_q_kategori' => [],
                'y_on_y_kategori' => [],
                'c_to_c_kategori' => [],  // Tambahkan untuk C-to-C
                'q_to_q_sub' => [],
                'y_on_y_sub' => [],
                'c_to_c_sub' => []       // Tambahkan untuk C-to-C subkategori
            ];

            foreach ($kategoriList as $kategori) {
                $id = $kategori->id_kategori;

                // Nilai sekarang
                $now = isset($nilaiKatNow[$id]) ? $nilaiKatNow[$id] : 0;

                // Hitung Q-to-Q
                $prevQtoQ = isset($nilaiKatPrevQtoQ[$id]) ? $nilaiKatPrevQtoQ[$id] : 0;
                if ($prevQtoQ != 0) {
                    $result['q_to_q_kategori'][$id] = round((($now - $prevQtoQ) / $prevQtoQ) * 100, 2);
                } else {
                    $result['q_to_q_kategori'][$id] = 0;
                }

                // Hitung Y-on-Y
                $prevYoy = isset($nilaiKatYoy[$id]) ? $nilaiKatYoy[$id] : 0;
                if ($prevYoy != 0) {
                    $result['y_on_y_kategori'][$id] = round((($now - $prevYoy) / $prevYoy) * 100, 2);
                } else {
                    $result['y_on_y_kategori'][$id] = 0;
                }

                // Hitung C-to-C (cumulative-to-cumulative) - khusus pengeluaran
                if ($pendekatan === 'pengeluaran') {
                    $cumulativeNow = isset($nilaiKatCumulativeNow[$id]) ? $nilaiKatCumulativeNow[$id] : 0;
                    $cumulativePrev = isset($nilaiKatCumulativePrev[$id]) ? $nilaiKatCumulativePrev[$id] : 0;

                    if ($cumulativePrev != 0) {
                        $result['c_to_c_kategori'][$id] = round((($cumulativeNow - $cumulativePrev) / $cumulativePrev) * 100, 2);
                    } else {
                        $result['c_to_c_kategori'][$id] = 0;
                    }
                }
            }

            // ===========================================
            // 7. AMBIL SEMUA SUBKATEGORI (EXCLUDE NON MIGAS)
            // ===========================================
            $subKategoriList = \App\Models\SubKategori::whereHas('kategori', function ($q) use ($pendekatan, $excludeKategoriIds) {
                $q->where('pendekatan', $pendekatan)
                    ->whereNotIn('id_kategori', $excludeKategoriIds);
            })
                ->whereNotIn('id_sub_kategori', $excludeSubKategoriIds)
                ->get();

            foreach ($subKategoriList as $sub) {
                $id = $sub->id_sub_kategori;

                // Nilai sekarang
                $now = isset($nilaiSubNow[$id]) ? $nilaiSubNow[$id] : 0;

                // Hitung Q-to-Q
                $prevQtoQ = isset($nilaiSubPrevQtoQ[$id]) ? $nilaiSubPrevQtoQ[$id] : 0;
                if ($prevQtoQ != 0) {
                    $result['q_to_q_sub'][$id] = round((($now - $prevQtoQ) / $prevQtoQ) * 100, 2);
                } else {
                    $result['q_to_q_sub'][$id] = 0;
                }

                // Hitung Y-on-Y
                $prevYoy = isset($nilaiSubYoy[$id]) ? $nilaiSubYoy[$id] : 0;
                if ($prevYoy != 0) {
                    $result['y_on_y_sub'][$id] = round((($now - $prevYoy) / $prevYoy) * 100, 2);
                } else {
                    $result['y_on_y_sub'][$id] = 0;
                }

                // Hitung C-to-C untuk subkategori
                if ($pendekatan === 'pengeluaran') {
                    $cumulativeNow = isset($nilaiSubCumulativeNow[$id]) ? $nilaiSubCumulativeNow[$id] : 0;
                    $cumulativePrev = isset($nilaiSubCumulativePrev[$id]) ? $nilaiSubCumulativePrev[$id] : 0;

                    if ($cumulativePrev != 0) {
                        $result['c_to_c_sub'][$id] = round((($cumulativeNow - $cumulativePrev) / $cumulativePrev) * 100, 2);
                    } else {
                        $result['c_to_c_sub'][$id] = 0;
                    }
                }
            }

            // ===========================================
            // 8. TAMBAHKAN DATA PDRB KHUSUS UNTUK PENGELUARAN
            // ===========================================
            if ($pendekatan === 'pengeluaran') {
                $pdrbLapus = \App\Models\Kategori::where('pendekatan', 'lapangan_usaha')
                    ->where('nama_kategori', 'Produk Domestik Regional Bruto')
                    ->whereNotIn('id_kategori', $excludeKategoriIds)
                    ->first();

                if ($pdrbLapus) {
                    $id = $pdrbLapus->id_kategori;

                    // Q-to-Q PDRB Lapus
                    $now = isset($nilaiKatNow[$id]) ? $nilaiKatNow[$id] : 0;
                    $prevQtoQ = isset($nilaiKatPrevQtoQ[$id]) ? $nilaiKatPrevQtoQ[$id] : 0;
                    if ($prevQtoQ != 0) {
                        $result['q_to_q_kategori'][999] = round((($now - $prevQtoQ) / $prevQtoQ) * 100, 2);
                    } else {
                        $result['q_to_q_kategori'][999] = 0;
                    }

                    // Y-on-Y PDRB Lapus
                    $prevYoy = isset($nilaiKatYoy[$id]) ? $nilaiKatYoy[$id] : 0;
                    if ($prevYoy != 0) {
                        $result['y_on_y_kategori'][999] = round((($now - $prevYoy) / $prevYoy) * 100, 2);
                    } else {
                        $result['y_on_y_kategori'][999] = 0;
                    }

                    // C-to-C PDRB Lapus
                    if ($pendekatan === 'pengeluaran') {
                        $cumulativeNow = isset($nilaiKatCumulativeNow[$id]) ? $nilaiKatCumulativeNow[$id] : 0;
                        $cumulativePrev = isset($nilaiKatCumulativePrev[$id]) ? $nilaiKatCumulativePrev[$id] : 0;
                        if ($cumulativePrev != 0) {
                            $result['c_to_c_kategori'][999] = round((($cumulativeNow - $cumulativePrev) / $cumulativePrev) * 100, 2);
                        } else {
                            $result['c_to_c_kategori'][999] = 0;
                        }
                    }
                }
            }

            \Log::info('Jumlah data q_to_q_kategori: ' . count($result['q_to_q_kategori']));
            \Log::info('Jumlah data c_to_c_kategori: ' . count($result['c_to_c_kategori']));

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Error in getNilaiForFenomenaTriwulanan: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perbaiki Net Ekspor untuk Pendekatan Pengeluaran
     * Digunakan di semua method (index, qtoq, yony, ctoc, indeksImplisit)
     */
    /**
     * Perbaiki Net Ekspor untuk Pendekatan Pengeluaran
     * Digunakan di semua method (index, qtoq, yony, ctoc, indeksImplisit)
     */
    /**
     * Perbaiki Net Ekspor untuk Pendekatan Pengeluaran
     * Digunakan di semua method (index, qtoq, yony, ctoc, indeksImplisit)
     * Net Ekspor ditempatkan di ATAS Ekspor
     */
    private function fixNetEkspor($data, $isGrouped = true, $kabkota = null)
    {
        if (!$kabkota) {
            if (isset($data['allKabkota'])) {
                $kabkota = $data['allKabkota'];
            } elseif (isset($data['kabkota'])) {
                $kabkota = $data['kabkota'];
            } else {
                return $data;
            }
        }

        // Deteksi mode dari request
        $mode = request()->route()->getName() ?? '';
        $isYony = str_contains($mode, 'yony') || request()->routeIs('rekonsiliasi.ytoy');

        $eksporRow = null;
        $imporRow = null;
        $eksporIndex = null;
        $collection = $isGrouped ? $data['grouped'] : $data['rekonsiliasi'];

        if (!$collection)
            return $data;

        // Cari baris Ekspor dan Impor, catat posisi Ekspor
        foreach ($collection as $index => $row) {
            $kategori = strtoupper($row['kategori'] ?? '');
            if (str_contains($kategori, 'EKSPOR') && !str_contains($kategori, 'IMPOR')) {
                $eksporRow = $row;
                $eksporIndex = $index;
            } elseif (str_contains($kategori, 'IMPOR') && !str_contains($kategori, 'EKSPOR')) {
                $imporRow = $row;
            }
        }

        if ($eksporRow && $imporRow) {
            // Filter: Hapus semua baris Net Ekspor yang lama
            $filteredCollection = [];
            foreach ($collection as $index => $row) {
                $kategori = strtoupper($row['kategori'] ?? '');
                if (!str_contains($kategori, 'NET EKSPOR')) {
                    $filteredCollection[] = $row;
                }
            }

            // Cari ulang posisi Ekspor setelah difilter
            $newEksporIndex = null;
            foreach ($filteredCollection as $index => $row) {
                $kategori = strtoupper($row['kategori'] ?? '');
                if (str_contains($kategori, 'EKSPOR') && !str_contains($kategori, 'IMPOR')) {
                    $newEksporIndex = $index;
                    break;
                }
            }

            if ($isYony) {
                // ================= Y ON Y =================
                $netEksporKab = [];
                foreach ($kabkota as $kab) {
                    $eksporVal = $eksporRow['kab'][$kab->id_wilayah] ?? 0;
                    $imporVal = $imporRow['kab'][$kab->id_wilayah] ?? 0;
                    $netEksporKab[$kab->id_wilayah] = $eksporVal - $imporVal;
                }

                $totalNetEksporKab = array_sum($netEksporKab);
                $provNetEkspor = ($eksporRow['provinsi'] ?? 0) - ($imporRow['provinsi'] ?? 0);
                $selisihNet = $totalNetEksporKab - $provNetEkspor;

                $newRow = [
                    'kode' => 'PNE',
                    'kategori' => 'Net Ekspor',
                    'is_sub' => false,
                    'provinsi' => $provNetEkspor,
                    'total_kab' => $totalNetEksporKab,
                    'kab' => $netEksporKab,
                    'selisih' => $selisihNet,
                    'cek' => $this->hitungCek(['provinsi' => $provNetEkspor, 'total_kab' => $totalNetEksporKab], 'yony')
                ];
            } else {
                // ================= MODE LAIN =================
                $netEksporKab = [];
                foreach ($kabkota as $kab) {
                    $eksporVal = 0;
                    $imporVal = 0;

                    if ($isGrouped) {
                        $eksporVal = $eksporRow['kab'][$kab->id_wilayah] ?? 0;
                        $imporVal = $imporRow['kab'][$kab->id_wilayah] ?? 0;
                    } else {
                        $namaKab = is_object($kab) ? ($kab->nama_kabupaten ?? $kab->nama_wilayah) : $kab;
                        $eksporVal = $eksporRow['kabkota'][$namaKab] ?? 0;
                        $imporVal = $imporRow['kabkota'][$namaKab] ?? 0;
                    }
                    $netEksporKab[$kab->id_wilayah ?? $namaKab] = $eksporVal - $imporVal;
                }

                $totalNetEksporKab = array_sum($netEksporKab);
                $provNetEkspor = ($eksporRow['provinsi'] ?? 0) - ($imporRow['provinsi'] ?? 0);

                $resume = request()->get('resume', 'p0');
                if (($resume === 'p0' || !$resume) && $provNetEkspor == 0) {
                    $provNetEkspor = $totalNetEksporKab;
                }

                $selisihNet = $totalNetEksporKab - $provNetEkspor;

                $newRow = [
                    'kategori' => 'Net Ekspor',
                    'kode' => 'PNE',
                    'is_sub' => false,
                    'provinsi' => $provNetEkspor,
                    'total' => $totalNetEksporKab,
                    'selisih' => $selisihNet,
                ];

                if ($isGrouped) {
                    $newRow['total_kab'] = $totalNetEksporKab;
                    $newRow['kab'] = $netEksporKab;
                    $newRow['cek'] = $eksporRow['cek'] ?? 'NORMAL';
                } else {
                    $newRow['kabkota'] = $netEksporKab;
                }
            }

            // 🔥 INSERT DI ATAS EKSPOR (bukan setelah Impor)
            $insertPos = ($newEksporIndex !== null) ? $newEksporIndex : 0;
            array_splice($filteredCollection, $insertPos, 0, [$newRow]);

            // Update data
            if ($isGrouped) {
                $data['grouped'] = collect($filteredCollection);
            } else {
                $data['rekonsiliasi'] = collect($filteredCollection);
            }
        }

        return $data;
    }
}










