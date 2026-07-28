<?php

namespace App\Helpers;

class PdrbHelper
{
    /**
     * Map sub-category ID to a template name.
     * 
     * @param int $idSubKategori
     * @return string
     */
    public static function getTemplate($idSubKategori)
    {
        $map = [
            2 => 'lapangan_usaha', // Tanaman Pangan (Previously tanaman_pangan)
            3 => 'hortikultura', // Tanaman Hortikultura
            4 => 'lapangan_usaha', // Tanaman Perkebunan (Previously perkebunan)
            7 => 'peternakan', // Peternakan
            8 => 'jasa_pertanian', // Jasa Pertanian
            9 => 'lapangan_usaha', // Kehutanan (Previously kehutanan)
            10 => 'perikanan', // Perikanan
            11 => 'tambang_migas', // Pertambangan Migas
            12 => 'tambang_non_migas', // Pertambangan Non Migas
            13 => 'penggalian', // Penggalian
            14 => 'jasa_pertambangan', // Jasa Pertambangan
        ];

        return $map[$idSubKategori] ?? 'produksi_dasar';
    }


    /**
     * Format a value as Indonesian Rupiah or Number.
     * 
     * @param mixed $value
     * @param int $decimals
     * @return string
     */
    public static function formatId($value, $decimals = 0)
    {
        if ($value === null || $value === '')
            return '0';
        return number_format((float) $value, $decimals, ',', '.');
    }

    /**
     * Get the letter/number code for a category name.
     */
    public static function getKategoriCode($nama, $defaultCode = '')
    {
        $specialMap = [
            'Pertanian, Peternakan, Perburuan dan Jasa Pertanian' => 'A1',
            'Kehutanan dan Penebangan Kayu' => '2',
            'Perikanan' => '3',
            'Pertanian, Kehutanan, dan Perikanan' => 'A',
            'Pertambangan dan Penggalian' => 'B',
            'Industri Pengolahan' => 'C',
            'Pengadaan Listrik dan Gas' => 'D',
            'Pengadaan Air, Pengelolaan Sampah, Limbah dan Daur Ulang' => 'E',
            'Konstruksi' => 'F',
            'Perdagangan Besar dan Eceran; Reparasi Mobil dan Sepeda Motor' => 'G',
            'Transportasi dan Pergudangan' => 'H',
            'Penyediaan Akomodasi dan Makan Minum' => 'I',
            'Informasi dan Komunikasi' => 'J',
            'Jasa Keuangan dan Asuransi' => 'K',
            'Real Estat' => 'L',
            'Jasa Perusahaan' => 'M,N',
            'Administrasi Pemerintahan, Pertahanan dan Jaminan Sosial Wajib' => 'O',
            'Jasa Pendidikan' => 'P',
            'Jasa Kesehatan dan Kegiatan Sosial' => 'Q',
            'Jasa lainnya' => 'R,S,T,U',
        ];

        return $specialMap[$nama] ?? $defaultCode;
    }

    /**
     * Get prefix for category label.
     */
    public static function getKategoriPrefix($nama)
    {
        $map = [
            'Jasa Perusahaan' => 'M,N',
            'Jasa lainnya' => 'R,S,T,U',
            'Produk Domestik Regional Bruto' => 'PDRB',
            'Produk Domestik Regional Bruto Non Migas' => 'NON MIGAS',
            'PRODUK DOMESTIK REGIONAL BRUTO' => 'PDRB',
            'PRODUK DOMESTIK REGIONAL BRUTO LAPUS' => 'PDRB LAPUS',
        ];

        return $map[$nama] ?? '';
    }

    /**
     * Get code for sub-category.
     */
    public static function getSubCode($subId, $subIndex, $catCode = '')
    {
        $customMap = [
            1 => '1',
            2 => 'A1A',
            3 => 'A1B',
            4 => 'A1C',
            5 => 'A1D',
            6 => 'A1E',
            7 => 'A1F',
            8 => 'A1G',
            9 => '2',
            10 => '3',
            23 => 'A1A',
            24 => 'A1B',
        ];

        if (isset($customMap[$subId])) {
            return $customMap[$subId];
        }

        $rangeList = array_merge([22], range(25, 39));
        $pos = array_search($subId, $rangeList, true);
        if ($pos !== false) {
            return (string) ($pos + 1);
        }

        return (string) $subIndex;
    }

    /**
     * Convert numeric index to Excel-style code (A, B, C... AA, AB...).
     */
    public static function indexToCode($num)
    {
        $code = '';
        while ($num > 0) {
            $num--;
            $code = chr(65 + ($num % 26)) . $code;
            $num = intdiv($num, 26);
        }
        return $code;
    }
}
