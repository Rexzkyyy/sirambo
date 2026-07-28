<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wilayah;
use App\Models\Provinsi;
use App\Models\Kabupaten;
use App\Models\Kategori;
use App\Models\SubKategori;
use App\Models\NilaiKategori;
use App\Models\Tahun;
use App\Models\Periode;
use App\Models\Fenomena;
use App\Helpers\PdrbHelper;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        /* ===============================
         * NAMA WILAYAH USER
         * =============================== */
        $namaWilayah = '-';

        if ($user->id_wilayah) {
            $wilayah = Wilayah::find($user->id_wilayah);

            if ($wilayah) {
                if ($wilayah->tipe === 'provinsi' && $wilayah->id_provinsi) {
                    $namaWilayah = Provinsi::find($wilayah->id_provinsi)?->nama_provinsi ?? '-';
                }

                if (in_array($wilayah->tipe, ['kabupaten', 'kota']) && $wilayah->id_kabupaten) {
                    $namaWilayah = Kabupaten::find($wilayah->id_kabupaten)?->nama_kabupaten ?? '-';
                }
            }
        }

        /* ===============================
        * DATA RINGKAS PER PENDEKATAN
        * =============================== */

        // Lapangan Usaha (EXCLUDE PDRB & PDRB Non Migas)
        $totalKategori = Kategori::where('pendekatan', 'lapangan_usaha')
            ->whereNotIn('nama_kategori', [
                'Produk Domestik Regional Bruto',
                'Produk Domestik Regional Bruto Non Migas'
            ])
            ->count();

        $totalSubKategori = SubKategori::whereHas('kategori', function ($q) {
            $q->where('pendekatan', 'lapangan_usaha')
            ->whereNotIn('nama_kategori', [
                'Produk Domestik Regional Bruto',
                'Produk Domestik Regional Bruto Non Migas'
            ]);
        })->count();

        // Pengeluaran (EXCLUDE PDRB & PDRB LAPUS)
        $totalKomponent = Kategori::where('pendekatan', 'pengeluaran')
            ->whereNotIn('nama_kategori', [
                'PRODUK DOMESTIK REGIONAL BRUTO',
                'PRODUK DOMESTIK REGIONAL BRUTO LAPUS'
            ])
            ->count();

        $totalSubKomponent = SubKategori::whereHas('kategori', function ($q) {
            $q->where('pendekatan', 'pengeluaran')
            ->whereNotIn('nama_kategori', [
                'PRODUK DOMESTIK REGIONAL BRUTO',
                'PRODUK DOMESTIK REGIONAL BRUTO LAPUS'
            ]);
        })->count();


        /* ===============================
         * FILTER TAHUN & TRIWULAN DEFAULT
         * =============================== */
        $currentMonth = date('n');
        $tahunSekarang = date('Y');
        
        // Tentukan triwulan kemarin dan tahunnya
        if ($currentMonth <= 3) {
            $defaultTriwulan = 4;
            $defaultYear = $tahunSekarang - 1;
        } elseif ($currentMonth <= 6) {
            $defaultTriwulan = 1;
            $defaultYear = $tahunSekarang;
        } elseif ($currentMonth <= 9) {
            $defaultTriwulan = 2;
            $defaultYear = $tahunSekarang;
        } else {
            $defaultTriwulan = 3;
            $defaultYear = $tahunSekarang;
        }

        $mode = $request->get('mode', 'triwulanan');

        $idTahun = $request->id_tahun
            ?? Tahun::where('tahun', $defaultYear)->value('id_tahun')
            ?? Tahun::where('tahun', $tahunSekarang)->value('id_tahun')
            ?? Tahun::orderByDesc('tahun')->value('id_tahun');

        $tahunNama = Tahun::where('id_tahun', $idTahun)->value('tahun');

        $idPeriode = $request->id_periode ?? $defaultTriwulan;

        /* ===============================
         * DATA KATEGORI PER PENDEKATAN
         * =============================== */
        $kategoriLapus = Kategori::where('pendekatan','lapangan_usaha')
            ->whereNotIn('nama_kategori', [
                'Produk Domestik Regional Bruto',
                'Produk Domestik Regional Bruto Non Migas'
            ])
            ->orderBy('id_kategori')
            ->get();

        $kategoriPengeluaran = Kategori::where('pendekatan','pengeluaran')
            ->whereNotIn('nama_kategori', [
                'PRODUK DOMESTIK REGIONAL BRUTO',
                'PRODUK DOMESTIK REGIONAL BRUTO LAPUS'
            ])
            ->orderBy('id_kategori')
            ->get();


        /* ===============================
         * DATA CHART DISTRIBUSI (4 CHART)
         * =============================== */
        $labelsLapus = $kategoriLapus->pluck('nama_kategori');
        $labelsPengeluaran = $kategoriPengeluaran->pluck('nama_kategori');

        $kodeLapus = $kategoriLapus->map(fn($k) =>
            \App\Helpers\PdrbHelper::getKategoriCode($k->nama_kategori)
        )->values();

        $kodePengeluaran = $kategoriPengeluaran->map(fn($k) =>
            \App\Helpers\PdrbHelper::getKategoriCode($k->nama_kategori)
        )->values();

        $dataLapusBerlaku = $this->ambilDataChartCepat(
            $kategoriLapus, $user->id_wilayah, $idTahun, $idPeriode, 'berlaku'
        );

        $dataLapusKonstan = $this->ambilDataChartCepat(
            $kategoriLapus, $user->id_wilayah, $idTahun, $idPeriode, 'konstan'
        );

        $dataPengBerlaku = $this->ambilDataChartCepat(
            $kategoriPengeluaran, $user->id_wilayah, $idTahun, $idPeriode, 'berlaku'
        );

        $dataPengKonstan = $this->ambilDataChartCepat(
            $kategoriPengeluaran, $user->id_wilayah, $idTahun, $idPeriode, 'konstan'
        );


        /* ===============================
        * RINGKASAN REKONSILIASI & DISKREPANSI (BENAR)
        * =============================== */

        // TOTAL LAPANGAN USAHA
        $totalLapusBerlaku = collect($dataLapusBerlaku)->sum();
        $totalLapusKonstan = collect($dataLapusKonstan)->sum();

        // ===============================
        // TOTAL PENGELUARAN (FORMULA RESMI PDRB)
        // C + LNPRT + G + PMTB + ΔInventori + NET EKSPOR
        // ===============================

        $pengMap = $kategoriPengeluaran
            ->pluck('nama_kategori', 'id_kategori')
            ->map(fn($v) => strtoupper(trim($v)))
            ->toArray();

        $pengBerlaku = collect($dataPengBerlaku)->values()->toArray();
        $pengKonstan = collect($dataPengKonstan)->values()->toArray();

        $mapBerlaku = array_combine(array_values($pengMap), $pengBerlaku);
        $mapKonstan = array_combine(array_values($pengMap), $pengKonstan);

        $komponenValid = [
            'KONSUMSI RUMAH TANGGA',
            'KONSUMSI LEMBAGA NONPROFIT YANG MELAYANI RUMAH TANGGA',
            'KONSUMSI PEMERINTAH',
            'PEMBENTUKAN MODAL TETAP BRUTO',
            'PERUBAHAN INVENTORI',
            'NET EKSPOR'
        ];

        $totalPengBerlaku = 0;
        $totalPengKonstan = 0;

        foreach ($komponenValid as $k) {
            $totalPengBerlaku += $mapBerlaku[$k] ?? 0;
            $totalPengKonstan += $mapKonstan[$k] ?? 0;
        }

        // DISKREPANSI REKONSILIASI (LAPUS - PENGELUARAN)

        // Harga Berlaku
        $diskrepansiBerlaku = $totalLapusBerlaku - $totalPengBerlaku;
        $persenDiskBerlaku = $totalLapusBerlaku > 0
            ? round((abs($diskrepansiBerlaku) / $totalLapusBerlaku) * 100, 2)
            : 0;

        // Harga Konstan
        $diskrepansiKonstan = $totalLapusKonstan - $totalPengKonstan;
        $persenDiskKonstan = $totalLapusKonstan > 0
            ? round((abs($diskrepansiKonstan) / $totalLapusKonstan) * 100, 2)
            : 0;
            
        /* ===============================
         * MONITORING UPLOAD PDRB
         * =============================== */
        $wilayahKabKota = Wilayah::whereIn('tipe', ['kabupaten', 'kota'])->get();

        $wilayahIds = $wilayahKabKota->pluck('id_wilayah');

        $dataUpload = NilaiKategori::whereIn('id_wilayah', $wilayahIds)
            ->where('id_tahun', $idTahun)
            ->where('id_periode', $idPeriode)
            ->where('tahap_data', 'awal')
            ->select('id_wilayah')
            ->distinct()
            ->pluck('id_wilayah')
            ->toArray();

        $labelsMonitoring = [];
        $dataMonitoring   = [];
        $warnaMonitoring  = [];

        foreach ($wilayahKabKota as $w) {
            $labelsMonitoring[] = $w->nama_wilayah;
            $dataMonitoring[]  = 1;
            $warnaMonitoring[] = in_array($w->id_wilayah, $dataUpload)
                ? '#22c55e'
                : '#ef4444';
        }

        /* ===============================
        * MONITORING UPLOAD PDRB - BERPISAH BERLAKU & KONSTAN
        * =============================== */
        
        $wilayahKabKota = Wilayah::whereIn('tipe', ['kabupaten', 'kota'])->get();
        $wilayahIds = $wilayahKabKota->pluck('id_wilayah');

        $labelsMonitoring = $wilayahKabKota->pluck('nama_wilayah');

        $dataMonitoringBerlaku = [];
        $dataMonitoringKonstan = [];
        $warnaMonitoringBerlaku = [];
        $warnaMonitoringKonstan = [];

        foreach ($wilayahKabKota as $w) {
            // Berlaku
            $existsBerlaku = NilaiKategori::where('id_wilayah', $w->id_wilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $idPeriode)
                ->where('tipe_pdrb', 'berlaku')
                ->whereIn('tahap_data', ['awal', 'rekonsiliasi'])
                ->exists();

            $dataMonitoringBerlaku[] = $existsBerlaku ? 100 : 1;
            $warnaMonitoringBerlaku[] = $existsBerlaku ? '#f97316' : '#0f172a';

            // Konstan
            $existsKonstan = NilaiKategori::where('id_wilayah', $w->id_wilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $idPeriode)
                ->where('tipe_pdrb', 'konstan')
                ->whereIn('tahap_data', ['awal', 'rekonsiliasi'])
                ->exists();

            $dataMonitoringKonstan[] = $existsKonstan ? 100 : 1;
            $warnaMonitoringKonstan[] = $existsKonstan ? '#3b82f6' : '#0f172a';
        }

        /* ===============================
         * MONITORING UPLOAD PDRB PENGELUARAN
         * =============================== */

        $labelsMonitoringPengeluaran = $wilayahKabKota->pluck('nama_wilayah');

        $dataMonitoringPengBerlaku = [];
        $dataMonitoringPengKonstan = [];
        $warnaMonitoringPengBerlaku = [];
        $warnaMonitoringPengKonstan = [];

        $kategoriPengIds = $kategoriPengeluaran->pluck('id_kategori');

        foreach ($wilayahKabKota as $w) {

            $existsPengBerlaku = NilaiKategori::where('id_wilayah', $w->id_wilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $idPeriode)
                ->where('tipe_pdrb', 'berlaku')
                ->whereIn('tahap_data', ['awal', 'rekonsiliasi'])
                ->whereIn('id_kategori', $kategoriPengIds)
                ->exists();

            $dataMonitoringPengBerlaku[] = $existsPengBerlaku ? 100 : 1;
            $warnaMonitoringPengBerlaku[] = $existsPengBerlaku ? '#f97316' : '#0f172a';

            $existsPengKonstan = NilaiKategori::where('id_wilayah', $w->id_wilayah)
                ->where('id_tahun', $idTahun)
                ->where('id_periode', $idPeriode)
                ->where('tipe_pdrb', 'konstan')
                ->whereIn('tahap_data', ['awal', 'rekonsiliasi'])
                ->whereIn('id_kategori', $kategoriPengIds)
                ->exists();

            $dataMonitoringPengKonstan[] = $existsPengKonstan ? 100 : 1;
            $warnaMonitoringPengKonstan[] = $existsPengKonstan ? '#3b82f6' : '#0f172a';
        }

       /* ===============================
 * MONITORING FENOMENA (LAPANGAN USAHA) - DENGAN FILTER PERIODE
 * =============================== */

/* ===============================
 * MONITORING FENOMENA (LAPANGAN USAHA) - DENGAN FILTER PERIODE
 * =============================== */

$wilayahKabKota = Wilayah::whereIn('tipe', ['kabupaten', 'kota'])->get();

// Ambil periode yang dipilih dari filter (id_periode)
$selectedPeriode = $idPeriode;

// Hitung total kategori dan subkategori
$kategoriList = Kategori::where('pendekatan', 'lapangan_usaha')
    ->whereNotIn('nama_kategori', [
        'Produk Domestik Regional Bruto Non Migas'
    ])
    ->with('subKategori')
    ->get();

$totalKategoriData = 0;
$totalSubKategoriData = 0;

foreach ($kategoriList as $kategori) {
    $totalKategoriData += 2; // Pertumbuhan dan Laju Implisit (atau QtoQ dan YonY)
    
    foreach ($kategori->subKategori as $sub) {
        $totalSubKategoriData += 2;
    }
}

$totalPerWilayah = $totalKategoriData + $totalSubKategoriData;

$fenomenaLabels = [];
$fenomenaData = [];
$fenomenaTotal = [];
$fenomenaBelumDiisi = [];  // <-- TAMBAHKAN INI
$persentaseFenomena = [];

foreach ($wilayahKabKota as $wilayah) {
    $fenomenaLabels[] = $wilayah->nama_wilayah;
    $fenomenaTotal[] = $totalPerWilayah;
    
    // Query untuk data yang sudah terisi (nilai ATAU fenomena)
    $query = Fenomena::where('id_wilayah', $wilayah->id_wilayah)
        ->where('tahun', $tahunNama)
        ->where('pendekatan', 'lapangan_usaha')
        ->where(function($q) {
            $q->whereNotNull('nilai')
              ->orWhereNotNull('fenomena')
              ->orWhere('fenomena', '!=', '');
        })
        ->whereNotIn('id_kategori', function($q) {
            $q->select('id_kategori')
              ->from('kategori')
              ->where('pendekatan', 'lapangan_usaha')
              ->where('nama_kategori', 'Produk Domestik Regional Bruto Non Migas');
        });
    
    if ($mode === 'triwulanan') {
        $query->where('id_periode', $selectedPeriode);
    } else {
        $query->whereNull('id_periode');
    }
    
    $sudahDiisi = $query->count();
    
    $fenomenaData[] = $sudahDiisi;
    $fenomenaBelumDiisi[] = $totalPerWilayah - $sudahDiisi;  // <-- TAMBAHKAN INI
    
    $persentaseFenomena[] = $totalPerWilayah > 0 
        ? round(($sudahDiisi / $totalPerWilayah) * 100, 1) 
        : 0;
}

// Hitung total keseluruhan
$totalDataFenomena = array_sum($fenomenaTotal);
$totalTerisiFenomena = array_sum($fenomenaData);
$persenKeseluruhanFenomena = $totalDataFenomena > 0 
    ? round(($totalTerisiFenomena / $totalDataFenomena) * 100, 1) 
    : 0;

 /* ===============================
 * MONITORING FENOMENA (PENGELUARAN)
 * =============================== */

$kategoriPengList = Kategori::where('pendekatan', 'pengeluaran')
    ->whereNotIn('nama_kategori', [
        'PRODUK DOMESTIK REGIONAL BRUTO LAPUS'
    ])
    ->with('subKategori')
    ->get();

$totalKategoriPengData = 0;
$totalSubKategoriPengData = 0;

foreach ($kategoriPengList as $kategori) {
    $totalKategoriPengData += 3;
    
    foreach ($kategori->subKategori as $sub) {
        $totalSubKategoriPengData += 3;
    }
}

$totalPerWilayahPeng = $totalKategoriPengData + $totalSubKategoriPengData;

$fenomenaLabelsPeng = [];
$fenomenaDataPeng = [];
$fenomenaTotalPeng = [];
$fenomenaBelumDiisiPeng = [];  // <-- TAMBAHKAN INI
$persentaseFenomenaPeng = [];

foreach ($wilayahKabKota as $wilayah) {
    $fenomenaLabelsPeng[] = $wilayah->nama_wilayah;
    $fenomenaTotalPeng[] = $totalPerWilayahPeng;
    
    $query = Fenomena::where('id_wilayah', $wilayah->id_wilayah)
        ->where('tahun', $tahunNama)
        ->where('pendekatan', 'pengeluaran')
        ->where(function($q) {
            $q->whereNotNull('nilai')
              ->orWhereNotNull('fenomena')
              ->orWhere('fenomena', '!=', '');
        })
        ->whereNotIn('id_kategori', function($q) {
            $q->select('id_kategori')
              ->from('kategori')
              ->where('pendekatan', 'pengeluaran')
              ->where('nama_kategori', 'PRODUK DOMESTIK REGIONAL BRUTO LAPUS');
        });
    
    if ($mode === 'triwulanan') {
        $query->where('id_periode', $selectedPeriode);
    } else {
        $query->whereNull('id_periode');
    }
    
    $sudahDiisiPeng = $query->count();
    
    $fenomenaDataPeng[] = $sudahDiisiPeng;
    $fenomenaBelumDiisiPeng[] = $totalPerWilayahPeng - $sudahDiisiPeng;  // <-- TAMBAHKAN INI
    
    $persentaseFenomenaPeng[] = $totalPerWilayahPeng > 0 
        ? round(($sudahDiisiPeng / $totalPerWilayahPeng) * 100, 1) 
        : 0;
}

// Hitung total keseluruhan untuk pengeluaran
$totalDataFenomenaPeng = array_sum($fenomenaTotalPeng);
$totalTerisiFenomenaPeng = array_sum($fenomenaDataPeng);
$persenKeseluruhanFenomenaPeng = $totalDataFenomenaPeng > 0 
    ? round(($totalTerisiFenomenaPeng / $totalDataFenomenaPeng) * 100, 1) 
    : 0;

$fenomenaBelumDiisiPeng = [];
foreach ($fenomenaTotalPeng as $index => $total) {
    $fenomenaBelumDiisiPeng[] = $total - ($fenomenaDataPeng[$index] ?? 0);
}
        /* ===============================
         * KIRIM KE VIEW
         * =============================== */
        return view('pdrb.dashboard', [
            'namaWilayah' => $namaWilayah,

            'totalKategori'    => $totalKategori,
            'totalSubKategori' => $totalSubKategori,
            'totalKomponen'    => $totalKomponent,
            'totalSubKomponen' => $totalSubKomponent,

            'tahunSekarang' => $tahunSekarang,
            'currentMode' => $mode,

            'labelsLapus' => $labelsLapus,
            'labelsPengeluaran' => $labelsPengeluaran,

            'dataLapusBerlaku' => $dataLapusBerlaku,
            'dataLapusKonstan' => $dataLapusKonstan,

            'dataPengBerlaku' => $dataPengBerlaku,
            'dataPengKonstan' => $dataPengKonstan,

            'labelsMonitoring' => $labelsMonitoring,
            'dataMonitoring'   => $dataMonitoring,
            'warnaMonitoring'  => $warnaMonitoring,

            'dataMonitoringBerlaku' => $dataMonitoringBerlaku,
            'dataMonitoringKonstan' => $dataMonitoringKonstan,
            'warnaMonitoringBerlaku' => $warnaMonitoringBerlaku,
            'warnaMonitoringKonstan' => $warnaMonitoringKonstan,

            'labelsMonitoringPengeluaran' => $labelsMonitoringPengeluaran,
            'dataMonitoringPengBerlaku'  => $dataMonitoringPengBerlaku,
            'dataMonitoringPengKonstan'  => $dataMonitoringPengKonstan,
            'warnaMonitoringPengBerlaku' => $warnaMonitoringPengBerlaku,
            'warnaMonitoringPengKonstan' => $warnaMonitoringPengKonstan,

            'totalLapusBerlaku' => $totalLapusBerlaku,
            'totalLapusKonstan' => $totalLapusKonstan,
            'totalPengBerlaku'  => $totalPengBerlaku,
            'totalPengKonstan'  => $totalPengKonstan,

            'diskrepansiBerlaku' => $diskrepansiBerlaku,
            'persenDiskBerlaku'  => $persenDiskBerlaku,

            'diskrepansiKonstan' => $diskrepansiKonstan,
            'persenDiskKonstan'  => $persenDiskKonstan,

            'kodeLapus' => $kodeLapus,
            'kodePengeluaran' => $kodePengeluaran,

            'tahun' => Tahun::orderBy('tahun')->get(),
            'periode' => Periode::orderBy('id_periode')->get(),
            'idTahun' => $idTahun,
            'idPeriode' => $idPeriode,
            'tahunNama' => $tahunNama,
            
            // Data monitoring fenomena lapangan usaha
            'fenomenaLabels' => $fenomenaLabels,
            'fenomenaData' => $fenomenaData,
            'fenomenaTotal' => $fenomenaTotal,
            'fenomenaBelumDiisi' => $fenomenaBelumDiisi,
            'persentaseFenomena' => $persentaseFenomena,
            'totalDataFenomena' => $totalDataFenomena,
            'totalTerisiFenomena' => $totalTerisiFenomena,
            'persenKeseluruhanFenomena' => $persenKeseluruhanFenomena,
            'totalPerWilayah' => $totalPerWilayah,
            
            // Data monitoring fenomena pengeluaran
            'fenomenaLabelsPeng' => $fenomenaLabelsPeng,
            'fenomenaDataPeng' => $fenomenaDataPeng,
            'fenomenaTotalPeng' => $fenomenaTotalPeng,
            'fenomenaBelumDiisiPeng' => $fenomenaBelumDiisiPeng,
            'persentaseFenomenaPeng' => $persentaseFenomenaPeng,
            'totalDataFenomenaPeng' => $totalDataFenomenaPeng,
            'totalTerisiFenomenaPeng' => $totalTerisiFenomenaPeng,
            'persenKeseluruhanFenomenaPeng' => $persenKeseluruhanFenomenaPeng,
            'totalPerWilayahPeng' => $totalPerWilayahPeng,
            'selectedTriwulan' => $request->get('triwulan', 'all'),
        ]);
    }
    
    /* ===============================
     * AMBIL DATA CHART CEPAT
     * =============================== */
    private function ambilDataChartCepat($kategori, $wilayah, $tahun, $periode, $tipePdrb)
    {
        $data = NilaiKategori::where([
            'id_wilayah' => $wilayah,
            'id_tahun'   => $tahun,
            'id_periode' => $periode,
            'tipe_pdrb'  => $tipePdrb
        ])
        ->whereIn('tahap_data', ['rekonsiliasi', 'awal'])
        ->get()
        ->groupBy('id_kategori');

        return $kategori->map(function ($k) use ($data) {

            if (!isset($data[$k->id_kategori])) return 0;

            return $data[$k->id_kategori]
                ->groupBy('id_sub_kategori')
                ->map(function ($rows) {
                    return $rows->firstWhere('tahap_data', 'rekonsiliasi')->nilai
                        ?? $rows->firstWhere('tahap_data', 'awal')->nilai
                        ?? 0;
                })
                ->sum();
        });
    }
}