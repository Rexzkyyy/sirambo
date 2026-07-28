<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Wilayah;
use App\Models\Kabupaten;
use App\Models\Provinsi;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('penduduk:import {path}', function (string $path) {
    $filePath = trim($path);
    if (!is_file($filePath)) {
        $this->error("File tidak ditemukan: {$filePath}");
        return 1;
    }

    $sheet = IOFactory::load($filePath)->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    $headerRowIndex = null;
    $colMap = [];
    foreach ($rows as $idx => $row) {
        $values = array_map(fn ($v) => trim((string) $v), $row);
        if (in_array('Kab/Kot', $values, true)) {
            $headerRowIndex = $idx;
            foreach ($row as $col => $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $colMap[$name] = $col;
                }
            }
            break;
        }
    }

    if (!$headerRowIndex) {
        $this->error('Header tidak ditemukan. Pastikan ada kolom "Kab/Kot".');
        return 1;
    }

    $nameCol = $colMap['Kab/Kot'] ?? null;
    if (!$nameCol) {
        $this->error('Kolom "Kab/Kot" tidak ditemukan.');
        return 1;
    }

    $yearCols = [];
    foreach ($colMap as $name => $col) {
        if (is_numeric($name)) {
            $yearCols[(int) $name] = $col;
        }
    }
    if (empty($yearCols)) {
        $this->error('Kolom tahun tidak ditemukan.');
        return 1;
    }

    $payload = [];
    $skippedTotal = 0;
    $unmatched = 0;
    $rowCount = 0;
    $now = now();

    foreach ($rows as $idx => $row) {
        if ($idx <= $headerRowIndex) {
            continue;
        }

        $rawName = trim((string) ($row[$nameCol] ?? ''));
        if ($rawName === '') {
            continue;
        }

        if (Str::startsWith($rawName, 'Total Kab/Kota')) {
            $skippedTotal++;
            continue;
        }

        $tipe = null;
        $lookupName = $rawName;
        if (Str::startsWith($rawName, 'Provinsi ')) {
            $tipe = 'provinsi';
            $lookupName = trim(Str::after($rawName, 'Provinsi '));
        } elseif (Str::startsWith($rawName, 'Kabupaten ')) {
            $tipe = 'kabupaten';
        } elseif (Str::startsWith($rawName, 'Kota ')) {
            $tipe = 'kota';
        }

        $wilayahId = null;
        if ($tipe === 'provinsi') {
            $prov = Provinsi::whereRaw('LOWER(nama_provinsi) = ?', [Str::lower($lookupName)])->first();
            if ($prov) {
                $wil = Wilayah::where('tipe', 'provinsi')
                    ->where('id_provinsi', $prov->id_provinsi)
                    ->first();
                $wilayahId = $wil?->id_wilayah;
            }
        } elseif (in_array($tipe, ['kabupaten', 'kota'], true)) {
            $kab = Kabupaten::whereRaw('LOWER(nama_kabupaten) = ?', [Str::lower($lookupName)])->first();
            if ($kab) {
                $wil = Wilayah::where('tipe', $tipe)
                    ->where('id_kabupaten', $kab->id_kabupaten)
                    ->first();
                $wilayahId = $wil?->id_wilayah;
            }
        }

        if (!$wilayahId) {
            $unmatched++;
            continue;
        }

        foreach ($yearCols as $year => $col) {
            $val = $row[$col] ?? null;
            if ($val === null || $val === '') {
                continue;
            }
            $jumlah = (int) round((float) $val);

            $payload[] = [
                'id_wilayah' => $wilayahId,
                'tahun' => (int) $year,
                'jumlah' => $jumlah,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $rowCount++;
    }

    if (empty($payload)) {
        $this->error('Tidak ada data yang bisa diimport.');
        return 1;
    }

    $chunks = array_chunk($payload, 1000);
    foreach ($chunks as $chunk) {
        DB::table('penduduk')->upsert(
            $chunk,
            ['id_wilayah', 'tahun'],
            ['jumlah', 'updated_at']
        );
    }

    $this->info("Import selesai. Baris sumber: {$rowCount}. Total Kab/Kota dilewati: {$skippedTotal}. Tidak terhubung ke wilayah: {$unmatched}.");
    return 0;
})->purpose('Import data penduduk dari file Excel');
