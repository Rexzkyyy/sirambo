<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixKonstanHarga extends Command
{
    protected $signature   = 'pdrb:fix-konstan-harga {--sub_kategori=} {--wilayah_id=}';
    protected $description = 'Propagate harga_produsen konstan across all periods/years where it is still 0';

    public function handle()
    {
        $this->info('Mulai propagasi harga_produsen konstan...');

        $subKategoriFilter = $this->option('sub_kategori');
        $wilayahFilter     = $this->option('wilayah_id');

        // Step 1: Kumpulkan sumber harga terbaik per wilayah + jenis + sub_kategori + komoditas
        $sourceQuery = DB::table('lembar_kerja as lk')
            ->join('lembar_kerja_item as i', function ($j) {
                $j->on('i.lembar_kerja_id', '=', 'lk.id')
                  ->where('i.tipe_pdrb', '=', 'konstan');
            })
            ->where('i.harga_produsen', '>', 0)
            ->select(
                'lk.wilayah_id',
                'lk.jenis',
                'lk.id_sub_kategori',
                'i.komoditas_id',
                DB::raw('MAX(i.harga_produsen) as harga_produsen'),
                DB::raw('MAX(i.rasio_output_ikut) as rasio_output_ikut'),
                DB::raw('MAX(i.rasio_konsumsi_antara) as rasio_konsumsi_antara')
            );

        if ($subKategoriFilter) {
            $sourceQuery->where('lk.id_sub_kategori', $subKategoriFilter);
        }
        if ($wilayahFilter) {
            $sourceQuery->where('lk.wilayah_id', $wilayahFilter);
        }

        $sources = $sourceQuery
            ->groupBy('lk.wilayah_id', 'lk.jenis', 'lk.id_sub_kategori', 'i.komoditas_id')
            ->get();

        $this->info("Ditemukan {$sources->count()} kombinasi komoditas dengan harga > 0.");

        $updatedCount = 0;
        $now = now()->toDateTimeString();

        foreach ($sources as $src) {
            // Temukan semua LK ID untuk kombinasi ini
            $lkIds = DB::table('lembar_kerja')
                ->where('wilayah_id', $src->wilayah_id)
                ->where('jenis', $src->jenis)
                ->where('id_sub_kategori', $src->id_sub_kategori)
                ->pluck('id')
                ->toArray();

            if (empty($lkIds)) continue;

            // Update baris yang harga_produsen masih 0
            $updated = DB::table('lembar_kerja_item')
                ->whereIn('lembar_kerja_id', $lkIds)
                ->where('komoditas_id', $src->komoditas_id)
                ->where('tipe_pdrb', 'konstan')
                ->where(function ($q) {
                    $q->whereNull('harga_produsen')->orWhere('harga_produsen', '=', 0);
                })
                ->update([
                    'harga_produsen'        => $src->harga_produsen,
                    'rasio_output_ikut'     => $src->rasio_output_ikut,
                    'rasio_konsumsi_antara' => $src->rasio_konsumsi_antara,
                    'updated_at'            => $now,
                ]);
            $updatedCount += $updated;
        }

        $this->info("Diperbarui: {$updatedCount} baris harga_produsen.");

        // Step 2: Rekalkulasi semua item konstan yang sudah ada kuantum & harga
        $this->info('Rekalkulasi nilai turunan (hanya baris yang punya kuantum & harga)...');
        DB::statement("
            UPDATE lembar_kerja_item
            SET
                nilai_output_utama = kuantum * harga_produsen,
                nilai_output_ikut  = (kuantum * harga_produsen) * rasio_output_ikut,
                output_adh = (kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip,
                konsumsi_antara = ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip) * rasio_konsumsi_antara,
                nilai_ntb = ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip)
                            - (((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip) * rasio_konsumsi_antara),
                updated_at = NOW()
            WHERE tipe_pdrb = 'konstan'
              AND kuantum > 0
              AND harga_produsen > 0
        ");

        $this->info("✅ Selesai! {$updatedCount} baris harga diperbarui dan nilai turunan dikalkulasi ulang.");
        return 0;
    }
}
