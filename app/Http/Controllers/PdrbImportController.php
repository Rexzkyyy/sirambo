<?php

namespace App\Imports;

use App\Models\{
    NilaiSubKategori,
    NilaiKategori,
    SubKategori
};
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class PdrbImport implements ToCollection
{
    protected $tahun;
    protected $periode;
    protected $wilayah;
    protected $tipePdrb;

    /**
     * @param int    $tahun
     * @param int    $periode
     * @param int    $wilayah
     * @param string $tipePdrb  (berlaku / konstan)
     */
    public function __construct($tahun, $periode, $wilayah, $tipePdrb = 'berlaku')
    {
        $this->tahun    = $tahun;
        $this->periode  = $periode;
        $this->wilayah  = $wilayah;
        $this->tipePdrb = $tipePdrb;
    }

    public function collection(Collection $rows)
    {
        // skip header
        $rows->shift();

        /* =============================
         * IMPORT SUB KATEGORI
         * (LAPANGAN USAHA - DATA AWAL)
         * ============================= */
        foreach ($rows as $row) {

            // kolom:
            // [2] = id_sub_kategori
            // [4] = nilai
            if (!$row[2] || $row[4] === null) {
                continue;
            }

            NilaiSubKategori::updateOrCreate(
                [
                    'id_sub_kategori' => $row[2],
                    'id_tahun'        => $this->tahun,
                    'id_periode'      => $this->periode,
                    'id_wilayah'      => $this->wilayah,
                    'tipe_pdrb'       => $this->tipePdrb,
                    'tahap_data'      => 'awal',   // ✅ WAJIB
                ],
                [
                    'nilai' => (float) $row[4]
                ]
            );
        }

        /* =============================
         * HITUNG TOTAL KATEGORI
         * (HANYA LAPANGAN USAHA)
         * ============================= */
        $kategoriIds = SubKategori::whereHas('kategori', function ($q) {
                $q->where('pendekatan', 'lapangan_usaha');
            })
            ->select('id_kategori')
            ->distinct()
            ->pluck('id_kategori');

        foreach ($kategoriIds as $idKategori) {

            $total = NilaiSubKategori::whereIn(
                    'id_sub_kategori',
                    function ($q) use ($idKategori) {
                        $q->select('id_sub_kategori')
                          ->from('sub_kategori')
                          ->where('id_kategori', $idKategori);
                    }
                )
                ->where('id_tahun', $this->tahun)
                ->where('id_periode', $this->periode)
                ->where('id_wilayah', $this->wilayah)
                ->where('tipe_pdrb', $this->tipePdrb)
                ->where('tahap_data', 'awal') // ✅ AMBIL DATA AWAL
                ->sum('nilai');

            if ($total > 0) {
                NilaiKategori::updateOrCreate(
                    [
                        'id_kategori' => $idKategori,
                        'id_tahun'    => $this->tahun,
                        'id_periode'  => $this->periode,
                        'id_wilayah'  => $this->wilayah,
                        'tipe_pdrb'   => $this->tipePdrb,
                        'tahap_data'  => 'awal', // ✅ WAJIB
                    ],
                    [
                        'nilai' => $total
                    ]
                );
            }
        }
    }
}
