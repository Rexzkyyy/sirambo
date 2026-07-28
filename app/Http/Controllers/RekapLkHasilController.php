<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Kategori,
    SubKategori,
    Tahun,
    Wilayah,
    Periode
};

class RekapLkHasilController extends Controller
{
    const ID_TOTAL_PDRB = 21;

    public function index(Request $request)
    {
        $user = auth()->user();

        $pendekatan = $request->get('jenis', $request->get('pendekatan', 'lapangan_usaha'));
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }

        // Ambil data statis (Tahun, Periode, Kategori, SubKategori, Wilayah)
        $dataStatis = $this->getDataStatis($pendekatan);
        extract($dataStatis); // $tahun, $periode, $kategori, $sub, $allWilayahs

        // Ambil parameter filter
        $selectedTahun = $request->id_tahun;
        $selectedPeriodeRaw = $request->id_periode;
        $scopeWilayah = $request->scope_wilayah;
        $typePdrb = $request->tipe_pdrb ?: 'berlaku';
        $rentangTahun = $request->rentang_tahun;
        $totalSemuaKategoriTahunan = [];

        // Tentukan wilayah
        $wilayahData = $this->getWilayahData($allWilayahs, $scopeWilayah, $user);
        extract($wilayahData); // $wilayahIds, $selectedWilayahs

        // Tentukan tahun
        $tampilan = $this->getTampilanData($selectedTahun, $selectedPeriodeRaw, $rentangTahun, $tahun);
        extract($tampilan); // $showAllTahun, $showAllPeriode, $selectedYears

        $selectedPeriode = $this->normalizeSelectedPeriode($selectedPeriodeRaw, $periode);
        
        // PENTING: Untuk keperluan Lembar Kerja, kita ambil FINAL ADJ (berarti dari rekon_lembar_kerja FINAL_BERLAKU / FINAL_KONSTAN)
        // Jika belum masuk rekon, fallback ke lembar_kerja tapi normalnya kita ambil rekap dari tabel komoditas atau agregasinya.
        // Di sini kita gunakan Query Builder ke rekon_lembar_kerja digabung dgn lembar_kerja
        
        $result = $this->processByTypePdrb(
            $typePdrb,
            $wilayahIds,
            $selectedYears,
            $selectedPeriode,
            $tahun,
            $periode,
            $kategori,
            $sub,
            $showAllTahun,
            $showAllPeriode,
            $pendekatan
        );

        extract($result); // $nilaiKategori, $nilaiSub, $totalTahunanKategori, $totalTahunanSub

        // Map kategori_id untuk sub kategori
        $subMap = $sub->keyBy('id_sub_kategori');
        $nilaiSub = $nilaiSub->map(function ($item) use ($subMap) {
            $item->kategori_id = $subMap->get($item->id_sub_kategori)?->id_kategori;
            return $item;
        });

        // Tentukan tahun yang akan ditampilkan
        $tahunFilter = $tahun;
        $periodeFilter = $periode;
        $tampilTahun = $tahun->whereIn('id_tahun', $selectedYears);

        // Render menggunakan view hasil pdrb (karena strukturnya match 100%)
        return view('pdrb.hasil', compact(
            'tahun',
            'periode',
            'kategori',
            'sub',
            'nilaiKategori',
            'nilaiSub',
            'selectedTahun',
            'selectedPeriode',
            'allWilayahs',
            'typePdrb',
            'scopeWilayah',
            'selectedWilayahs',
            'showAllTahun',
            'showAllPeriode',
            'tampilTahun',
            'totalTahunanKategori',
            'totalTahunanSub',
            'totalSemuaKategoriTahunan',
            'rentangTahun',
            'pendekatan'
        ))
        ->with('ID_TOTAL_PDRB', self::ID_TOTAL_PDRB)
        ->with('tahunFilter', $tahunFilter)
        ->with('periodeFilter', $periodeFilter)
        ->with('is_rekap_lk', true); // Penanda ini dari mode LK
    }

    // =================================================================
    // METODE BANTUAN
    // =================================================================

    private function getDataStatis($pendekatan)
    {
        $tahun = Cache::remember('tahun_all_rekaplk_v2', 86400, fn() => Tahun::orderBy('tahun')->get(['id_tahun', 'tahun']));
        $periode = Cache::remember('periode_all_rekaplk_v2', 86400, fn() => Periode::orderBy('id_periode')->get(['id_periode', 'nama_periode']));
        $kategori = Cache::remember("kategori_all_rekaplk_v2_{$pendekatan}", 86400, fn() => Kategori::where('pendekatan', $pendekatan)->get(['id_kategori', 'nama_kategori', 'pendekatan']));
        $kategoriIds = $kategori->pluck('id_kategori')->toArray();
        $sub = Cache::remember("subkategori_all_rekaplk_v2_{$pendekatan}", 86400, fn() => empty($kategoriIds) ? collect() : SubKategori::whereIn('id_kategori', $kategoriIds)->get(['id_sub_kategori', 'nama_sub_kategori', 'id_kategori']));
        $allWilayahs = Cache::remember('wilayah_all_rekaplk_v2', 86400, function () {
            return Wilayah::with(['provinsi:id_provinsi,nama_provinsi', 'kabupaten:id_kabupaten,nama_kabupaten'])
                ->orderBy('id_wilayah')
                ->get(['id_wilayah', 'tipe', 'id_provinsi', 'id_kabupaten'])
                ->map(function ($w) {
                    if ($w->tipe == 'provinsi' && $w->provinsi) $w->nama_wilayah = $w->provinsi->nama_provinsi;
                    elseif (in_array($w->tipe, ['kabupaten', 'kota']) && $w->kabupaten) $w->nama_wilayah = $w->kabupaten->nama_kabupaten;
                    else $w->nama_wilayah = 'Wilayah ' . $w->id_wilayah;
                    return $w;
                });
        });
        return compact('tahun', 'periode', 'kategori', 'sub', 'allWilayahs');
    }

    private function getWilayahData($allWilayahs, $scopeWilayah, $user)
    {
        $wilayahId = $scopeWilayah && $scopeWilayah !== 'all' ? $scopeWilayah : $user->id_wilayah;
        $wilayahIds = [(int)$wilayahId];
        $selectedWilayahs = $allWilayahs->where('id_wilayah', $wilayahId);
        return compact('wilayahIds', 'selectedWilayahs');
    }

    private function getTampilanData($selectedTahun, $selectedPeriode, $rentangTahun, $tahunModel)
    {
        $showAllTahun = false;
        $showAllPeriode = (!$selectedPeriode || $selectedPeriode === 'semua' || $selectedPeriode === '');
        $selectedYears = [];

        if ($rentangTahun) {
            $tahunRange = explode('-', $rentangTahun);
            $tahunAwal = intval($tahunRange[0] ?? 0);
            $tahunAkhir = intval($tahunRange[1] ?? 0);

            if ($tahunAwal > 0 && $tahunAkhir > 0 && $tahunAwal <= $tahunAkhir) {
                $selectedYears = $tahunModel->where('tahun', '>=', $tahunAwal)->where('tahun', '<=', $tahunAkhir)->pluck('id_tahun')->toArray();
                $showAllTahun = true; 
            }
        } elseif ($selectedTahun && $selectedTahun !== 'semua' && $selectedTahun !== '') {
            $selectedYears = [(int) $selectedTahun];
            $showAllTahun = false;
        } else {
            $selectedYears = $tahunModel->pluck('id_tahun')->toArray();
            $showAllTahun = true;
        }
        return compact('showAllTahun', 'showAllPeriode', 'selectedYears');
    }

    private function normalizeSelectedPeriode($selectedPeriode, $periode)
    {
        if (in_array($selectedPeriode, [null, '', 'semua'], true)) return $selectedPeriode;
        if (is_numeric($selectedPeriode)) return (int) $selectedPeriode;
        $normalized = strtolower(trim((string) $selectedPeriode));
        $match = $periode->first(fn($p) => strtolower(trim((string) $p->nama_periode)) === $normalized);
        if ($match) return (int) $match->id_periode;
        if (preg_match('/\\b(1|2|3|4)\\b/', $normalized, $m)) return (int) $m[1];
        if (preg_match('/\\b(i|ii|iii|iv)\\b/', $normalized, $m)) {
            $romanMap = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4];
            return $romanMap[strtolower($m[1])] ?? $selectedPeriode;
        }
        return $selectedPeriode;
    }

    private function processByTypePdrb($typePdrb, $wilayahIds, $selectedYears, $selectedPeriode, $tahun, $periode, $kategori, $sub, $showAllTahun, $showAllPeriode, $pendekatan)
    {
        // Untuk sekarang, karena LK Rekap juga mungkin dipanggil dalam berbagai tipe (Y-on-Y, laju, dst)
        // kita batasi hanya untuk 'berlaku' dan 'konstan'
        if (!in_array($typePdrb, ['berlaku', 'konstan'])) {
            $typePdrb = 'berlaku';
        }

        $allYearsToCheck = $selectedYears;
        $fieldToSelect = $typePdrb === 'berlaku' ? 'rlk.final_berlaku' : 'rlk.final_konstan';

        // 1. Fetch values from Lembar Kerja & Rekon Lembar Kerja
        $query = DB::table('lembar_kerja as lk')
            ->select(
                'lk.id_kategori',
                'lk.id_sub_kategori',
                'lk.id_tahun',
                'lk.id_periode',
                'lk.wilayah_id',
                DB::raw("MAX($fieldToSelect) as nilai")
            )
            ->leftJoin('rekon_lembar_kerja as rlk', 'rlk.lembar_kerja_id', '=', 'lk.id')
            ->whereIn('lk.wilayah_id', $wilayahIds)
            ->whereIn('lk.id_tahun', $allYearsToCheck)
            ->where('lk.jenis', $pendekatan);
            
        if ($selectedPeriode && $selectedPeriode !== 'semua' && $selectedPeriode !== '') {
            $query->where('lk.id_periode', $selectedPeriode);
        }

        $records = $query->groupBy('lk.id_tahun', 'lk.id_periode', 'lk.wilayah_id', 'lk.id_kategori', 'lk.id_sub_kategori')->get();

        // 2. Separate into Kategori and SubKategori Aggregates, converting values safely
        // Sub-categories
        $nilaiSub = collect();
        $subGroup = [];
        foreach ($records as $r) {
            if ($r->id_sub_kategori) {
                // Konversi dari rupiah absolut ke hitungan JUTA-an untuk disamakan format dengan PDRB Hasil (di HasilPdrbController nilainya dalam jutaan)
                $nilai_jutaan = ((float)$r->nilai) / 1000000;
                
                $key = $r->id_sub_kategori.'_'.$r->id_tahun.'_'.$r->id_periode;
                if (!isset($subGroup[$key])) {
                    $subGroup[$key] = [
                        'id_sub_kategori' => $r->id_sub_kategori,
                        'id_tahun' => $r->id_tahun,
                        'id_periode' => $r->id_periode,
                        'nilai' => 0
                    ];
                }
                $subGroup[$key]['nilai'] += $nilai_jutaan;
            }
        }
        foreach ($subGroup as $item) {
            $nilaiSub->push((object) $item);
        }

        // Kategories (Total Kategori dari Sub Kategorinya ATAU dari row Kategori tunggalnya)
        $nilaiKategori = collect();
        $katGroup = [];
        foreach ($records as $r) {
            // Aggregate all records (both category-only and sub-categories inside that category)
            if ($r->id_kategori) {
                $nilai_jutaan = ((float)$r->nilai) / 1000000;
                $key = $r->id_kategori.'_'.$r->id_tahun.'_'.$r->id_periode;
                if (!isset($katGroup[$key])) {
                    $katGroup[$key] = [
                        'id_kategori' => $r->id_kategori,
                        'id_tahun' => $r->id_tahun,
                        'id_periode' => $r->id_periode,
                        'nilai' => 0
                    ];
                }
                $katGroup[$key]['nilai'] += $nilai_jutaan;
            }
        }

        // --- Calculate Total PDRB Adhb/Adhk ---
        // Jumlahkan semua nilai kategori yang masuk struktur PDRB (skip non-PDRB kategori 22 misalnya)
        $totalPdrbMap = [];
        foreach ($katGroup as $key => $item) {
            if ($item['id_kategori'] != self::ID_TOTAL_PDRB && $item['id_kategori'] != 22) { // 22 usually Non Migas etc
                $totKey = $item['id_tahun'].'_'.$item['id_periode'];
                if (!isset($totalPdrbMap[$totKey])) {
                    $totalPdrbMap[$totKey] = 0;
                }
                $totalPdrbMap[$totKey] += $item['nilai'];
            }
        }

        foreach ($totalPdrbMap as $totKey => $tval) {
            list($thn, $per) = explode('_', $totKey);
            $katGroup[self::ID_TOTAL_PDRB.'_'.$totKey] = [
                'id_kategori' => self::ID_TOTAL_PDRB,
                'id_tahun' => (int)$thn,
                'id_periode' => (int)$per,
                'nilai' => $tval
            ];
        }

        foreach ($katGroup as $item) {
            $nilaiKategori->push((object) $item);
        }

        // 3. Calculate Annual Totals if showing all periods
        $totalTahunanKategori = collect();
        $totalTahunanSub = collect();
        
        if ($showAllPeriode) {
            // Group Kategori
            $groupedKategori = [];
            foreach ($nilaiKategori as $nk) {
                $groupedKategori[$nk->id_kategori][$nk->id_tahun][] = $nk->nilai;
            }
            foreach ($selectedYears as $tahunId) {
                foreach ($kategori as $kat) {
                    $totalKat = array_sum($groupedKategori[$kat->id_kategori][$tahunId] ?? []);
                    $totalTahunanKategori->push((object) [
                        'id_kategori' => $kat->id_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => $totalKat
                    ]);
                }
            }

            // Group Sub
            $groupedSub = [];
            foreach ($nilaiSub as $ns) {
                $groupedSub[$ns->id_sub_kategori][$ns->id_tahun][] = $ns->nilai;
            }
            foreach ($selectedYears as $tahunId) {
                foreach ($sub as $s) {
                    $totalSubVal = array_sum($groupedSub[$s->id_sub_kategori][$tahunId] ?? []);
                    $totalTahunanSub->push((object) [
                        'id_sub_kategori' => $s->id_sub_kategori,
                        'id_tahun' => $tahunId,
                        'nilai' => $totalSubVal,
                        'kategori_id' => $s->id_kategori
                    ]);
                }
            }
        }

        return compact('nilaiKategori', 'nilaiSub', 'totalTahunanKategori', 'totalTahunanSub');
    }
}
