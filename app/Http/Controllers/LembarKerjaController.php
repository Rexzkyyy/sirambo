<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Periode;
use App\Models\SubKategori;
use App\Models\Tahun;
use App\Models\Wilayah;
use App\Helpers\PdrbHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LembarKerjaController extends Controller
{
    public function index(Request $request)
    {
        $pendekatan = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'], true)) {
            $pendekatan = 'lapangan_usaha';
        }

        $kategoriList = Kategori::where('pendekatan', $pendekatan)
            ->orderBy('id_kategori')
            ->get();

        $subKategoriList = SubKategori::with('kategori')
            ->whereHas('kategori', function ($q) use ($pendekatan) {
                $q->where('pendekatan', $pendekatan);
            })
            ->orderBy('id_kategori')
            ->orderBy('id_sub_kategori')
            ->get();

        $filteredSubKategori = $pendekatan === 'lapangan_usaha'
            ? $subKategoriList->filter(function ($item, $index) {
                return $index > 0;
            })->values()
            : $subKategoriList->values();

        if ($pendekatan === 'lapangan_usaha') {
            $indeksYangDitampilkan = [9, 10, 11, 12, 13, 14, 15, 16];
            $filteredKategori = $kategoriList->filter(function ($item, $index) use ($indeksYangDitampilkan) {
                return in_array($index, $indeksYangDitampilkan, true);
            })->values();
        } else {
            $filteredKategori = $kategoriList->filter(function ($item) {
                return !in_array($item->id_kategori, [31, 32, 23, 26], true);
            })->values();
        }

        $gabunganData = collect();

        foreach ($filteredSubKategori as $subKategori) {
            $gabunganData->push([
                'type' => 'subkategori',
                'data' => $subKategori,
                'nama' => $subKategori->nama_sub_kategori,
                'kategori_nama' => $subKategori->kategori ? $subKategori->kategori->nama_kategori : null,
                'id' => $subKategori->id_sub_kategori,
                'kategori_id' => $subKategori->kategori_id,
                'route' => route('lembar_kerja.detail', $subKategori->id_sub_kategori) . '?jenis=' . $pendekatan,
            ]);
        }

        foreach ($filteredKategori as $kategori) {
            $subKategoriCount = $subKategoriList->where('kategori_id', $kategori->id_kategori)->count();
            $gabunganData->push([
                'type' => 'kategori',
                'data' => $kategori,
                'nama' => $kategori->nama_kategori,
                'kategori_nama' => null,
                'id' => $kategori->id_kategori,
                'kategori_id' => null,
                'subkategori_count' => $subKategoriCount,
                'route' => route('lembar_kerja.detail.kategori', $kategori->id_kategori) . '?jenis=' . $pendekatan,
            ]);
        }

        return view('lembar_kerja.index', [
            'gabunganData' => $gabunganData,
            'totalItems' => $gabunganData->count(),
            'pendekatan' => $pendekatan,
        ]);
    }

    public function detail($idSubKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($jenis, ['lapangan_usaha', 'pengeluaran'], true)) {
            $jenis = 'lapangan_usaha';
        }
        $tipePdrb = $request->get('tipe_pdrb', 'berlaku');
        if (!in_array($tipePdrb, ['berlaku', 'konstan'], true)) {
            $tipePdrb = 'berlaku';
        }

        $user = auth()->user();
        if (!$user || !$user->id_wilayah) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Wilayah user belum ditentukan.');
        }

        $isProvinsiRole = in_array($user->role, ['provinsi', 'provinsi_supervisor'], true);
        $wilayahList = $isProvinsiRole ? $this->getProvinsiWilayahList($user) : collect();
        $selectedWilayahId = $this->resolveSelectedWilayahId($request, $user, $wilayahList);

        $subKategori = SubKategori::with('kategori')->findOrFail($idSubKategori);
        if (!$subKategori->kategori || $subKategori->kategori->pendekatan !== $jenis) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Sub kategori tidak ditemukan untuk pendekatan ini.');
        }

        $jenisLabel = $jenis === 'pengeluaran' ? 'Pengeluaran' : 'Lapangan Usaha';

        $periodeList = cache()->remember('periode_list', 86400, function () {
            return Periode::orderBy('id_periode')->get(['id_periode', 'nama_periode']);
        });

        // Filter out the redundant "Tahunan" (id 5) and rename "Total Tahun" (id 99) to "Tahunan"
        $periodeList = collect($periodeList)
            ->filter(fn($p) => (int) $p->id_periode !== 5)
            ->map(fn($p) => clone $p)
            ->values();

        $periodeList->push((object) [
            'id_periode' => 99,
            'nama_periode' => 'Tahunan',
        ]);

        $periodeId = $request->get('id_periode') ?: ($request->get('periode_id') ?: $periodeList->first()?->id_periode);

        $tahunList = cache()->remember('tahun_list', 86400, function () {
            return Tahun::orderBy('tahun', 'desc')->get(['id_tahun', 'tahun']);
        });
        $tahunId = $request->get('id_tahun') ?: $tahunList->first()?->id_tahun;

        // CEK APAKAH MODE TOTAL TAHUN
        if ((int) $periodeId === 99) {
            // Mode Total Tahun - tidak perlu membuat lembar kerja
            $lembarKerja = null; // DEFINISIKAN VARIABEL $lembarKerja SEBAGAI NULL

            // Ambil akumulasi dari 4 triwulan
            // Optimize query by using a join for rasio_konsumsi_antara from Q4 instead of a correlated subquery
            $itemRows = DB::table('lembar_kerja_item as i')
                ->join('lembar_kerja as l', 'l.id', '=', 'i.lembar_kerja_id')
                ->join('komoditas as k', 'k.id', '=', 'i.komoditas_id')
                ->leftJoin(DB::raw("(
                    SELECT i2.komoditas_id, i2.rasio_konsumsi_antara
                    FROM lembar_kerja_item i2
                    JOIN lembar_kerja l2 ON l2.id = i2.lembar_kerja_id
                    WHERE l2.id_tahun = " . (int) $tahunId . "
                      AND l2.wilayah_id = " . (int) $selectedWilayahId . "
                      AND l2.jenis = '" . $jenis . "'
                      AND l2.id_periode = 4
                      AND i2.tipe_pdrb = '" . $tipePdrb . "'
                ) as q4"), 'q4.komoditas_id', '=', 'i.komoditas_id')
                ->where('l.wilayah_id', (int) $selectedWilayahId)
                ->where('l.id_tahun', (int) $tahunId)
                ->whereIn('l.id_periode', [1, 2, 3, 4])
                ->where('l.jenis', $jenis)
                ->where('i.tipe_pdrb', $tipePdrb)
                ->groupBy(
                    'i.komoditas_id',
                    'k.nama',
                    'k.satuan',
                    'k.wujud',
                    'q4.rasio_konsumsi_antara'
                )
                ->selectRaw("
                    i.komoditas_id,
                    k.nama as komoditas_nama,
                    k.satuan as satuan_nama,
                    k.wujud as komoditas_wujud,

                    SUM(i.kuantum) as kuantum,
                    CASE WHEN SUM(i.kuantum) > 0 THEN SUM(i.nilai_output_utama) / (SUM(i.kuantum)) ELSE AVG(i.harga_produsen) END as harga_produsen,
                    SUM(i.nilai_output_utama) as nilai_output_utama,
                    CASE WHEN SUM(i.nilai_output_utama) > 0 THEN SUM(i.nilai_output_ikut) / SUM(i.nilai_output_utama) ELSE MAX(i.rasio_output_ikut) END as rasio_output_ikut,
                    SUM(i.nilai_output_ikut) as nilai_output_ikut,
                    SUM(i.biaya_perawatan) as biaya_perawatan,
                    SUM(i.biaya_sebelumnya) as biaya_sebelumnya,
                    SUM(i.wip) as wip,
                    SUM(i.wip_berlaku) as wip_berlaku,
                    SUM(i.output_adh) as output_adh,
                    SUM(i.konsumsi_antara) as konsumsi_antara,
                    SUM(i.nilai_ntb) as nilai_ntb,
                    CASE WHEN SUM(i.output_adh) > 0 THEN SUM(i.konsumsi_antara) / SUM(i.output_adh) ELSE MAX(i.rasio_konsumsi_antara) END as rasio_konsumsi_antara,
                    MAX(i.deflator) as deflator
                ")
                ->get();



            return view('lembar_kerja.detail', compact(
                'jenis',
                'jenisLabel',
                'subKategori',
                'periodeList',
                'periodeId',
                'tahunList',
                'tahunId',
                'lembarKerja',
                'itemRows',
                'isProvinsiRole',
                'tipePdrb'
            ))->with([
                        'wilayahList' => $wilayahList,
                        'selectedWilayahId' => $selectedWilayahId,
                        'template' => PdrbHelper::getTemplate($subKategori->id_sub_kategori)
                    ]);
        }

        // =============================================
        // PROSES UNTUK MODE BUKAN TOTAL TAHUN (1,2,3,4)
        // =============================================

        // Cari atau buat lembar kerja
        $lembarKerja = DB::table('lembar_kerja')
            ->where('wilayah_id', $selectedWilayahId)
            ->where('id_periode', $periodeId)
            ->where('id_tahun', $tahunId)
            ->where('jenis', $jenis)
            ->where('id_kategori', $subKategori->kategori_id)
            ->where('id_sub_kategori', $subKategori->id_sub_kategori)
            ->first();

        if (!$lembarKerja) {
            $now = now();
            $columnKeys = ['kuantum', 'harga_produsen', 'nilai_output_utama', 'rasio_output_ikut', 'nilai_output_ikut', 'biaya_perawatan', 'biaya_sebelumnya', 'wip', 'output_adh', 'konsumsi_antara', 'nilai_ntb', 'rasio_konsumsi_antara', 'deflator', 'wip_berlaku'];
            $id = DB::table('lembar_kerja')->insertGetId([
                'wilayah_id' => $selectedWilayahId,
                'id_periode' => $periodeId,
                'id_tahun' => $tahunId,
                'jenis' => $jenis,
                'id_kategori' => $subKategori->kategori_id,
                'id_sub_kategori' => $subKategori->id_sub_kategori,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $lembarKerja = DB::table('lembar_kerja')->where('id', $id)->first();
        }

        // SYNCHRONIZE KOMODITAS BETWEEN BERLAKU AND KONSTAN IN THE SAME LEMBAR KERJA
        $otherTipe = $tipePdrb === 'berlaku' ? 'konstan' : 'berlaku';

        $currentItems = DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerja->id)
            ->where('tipe_pdrb', $tipePdrb)
            ->pluck('komoditas_id')
            ->toArray();

        $otherItems = DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerja->id)
            ->where('tipe_pdrb', $otherTipe)
            ->pluck('komoditas_id')
            ->toArray();

        $now = now();
        $columnKeys = ['kuantum', 'harga_produsen', 'nilai_output_utama', 'rasio_output_ikut', 'nilai_output_ikut', 'biaya_perawatan', 'biaya_sebelumnya', 'wip', 'output_adh', 'konsumsi_antara', 'nilai_ntb', 'rasio_konsumsi_antara', 'deflator', 'wip_berlaku'];
        $missingInCurrent = array_diff($otherItems, $currentItems);
        if (!empty($missingInCurrent)) {
            $insertData = [];
            foreach ($missingInCurrent as $kId) {
                $insertData[] = [
                    'lembar_kerja_id' => $lembarKerja->id,
                    'komoditas_id' => $kId,
                    'tipe_pdrb' => $tipePdrb,
                    'kuantum' => 0,
                    'harga_produsen' => 0,
                    'nilai_output_utama' => 0,
                    'rasio_output_ikut' => 0,
                    'nilai_output_ikut' => 0,
                    'biaya_perawatan' => 0,
                    'biaya_sebelumnya' => 0,
                    'wip' => 0,
                    'output_adh' => 0,
                    'konsumsi_antara' => 0,
                    'nilai_ntb' => 0,
                    'rasio_konsumsi_antara' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('lembar_kerja_item')->insert($insertData);
        }

        $missingInOther = array_diff($currentItems, $otherItems);
        if (!empty($missingInOther)) {
            $insertData = [];
            foreach ($missingInOther as $kId) {
                $insertData[] = [
                    'lembar_kerja_id' => $lembarKerja->id,
                    'komoditas_id' => $kId,
                    'tipe_pdrb' => $otherTipe,
                    'kuantum' => 0,
                    'harga_produsen' => 0,
                    'nilai_output_utama' => 0,
                    'rasio_output_ikut' => 0,
                    'nilai_output_ikut' => 0,
                    'biaya_perawatan' => 0,
                    'biaya_sebelumnya' => 0,
                    'wip' => 0,
                    'output_adh' => 0,
                    'konsumsi_antara' => 0,
                    'nilai_ntb' => 0,
                    'rasio_konsumsi_antara' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('lembar_kerja_item')->insert($insertData);
        }

        // AUTO COPY KOMODITAS JIKA KOSONG
        $existingCount = DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerja->id)
            ->count();

        if ($existingCount === 0 && is_null($lembarKerja->updated_by)) {
            // 1. Cari lembar kerja di wilayah yang sama dulu
            $previousLembar = DB::table('lembar_kerja')
                ->where('wilayah_id', $selectedWilayahId)
                ->where('jenis', $jenis)
                ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                ->where('id', '!=', $lembarKerja->id)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('lembar_kerja_item')
                        ->whereColumn('lembar_kerja_item.lembar_kerja_id', 'lembar_kerja.id');
                })
                ->orderByDesc('updated_at')
                ->orderByDesc('id_tahun')
                ->orderByDesc('id_periode')
                ->first();

            $isSameWilayah = true;
            if (!$previousLembar) {
                // 2. Fallback: Cari lembar kerja di wilayah MANAPUN sebagai template
                $previousLembar = DB::table('lembar_kerja')
                    ->where('jenis', $jenis)
                    ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                    ->where('id', '!=', $lembarKerja->id)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('lembar_kerja_item')
                            ->whereColumn('lembar_kerja_item.lembar_kerja_id', 'lembar_kerja.id');
                    })
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id_tahun')
                    ->orderByDesc('id_periode')
                    ->first();
                $isSameWilayah = false;
            }

            if ($previousLembar) {
                $now = now();
                if ($isSameWilayah) {
                    // Bulk copy jika wilayah sama (lebih cepat)
                    DB::statement("
                        INSERT INTO lembar_kerja_item (
                            lembar_kerja_id, komoditas_id, tipe_pdrb, 
                            kuantum, harga_produsen, nilai_output_utama, 
                            rasio_output_ikut, nilai_output_ikut, biaya_perawatan, 
                            biaya_sebelumnya, wip, output_adh, 
                            konsumsi_antara, nilai_ntb, rasio_konsumsi_antara, 
                            created_at, updated_at
                        )
                        SELECT 
                            ?, komoditas_id, tipe_pdrb,
                            0, CASE WHEN tipe_pdrb = 'konstan' THEN harga_produsen ELSE 0 END, 0,
                            CASE WHEN tipe_pdrb = 'konstan' THEN rasio_output_ikut ELSE 0 END, 0, 0,
                            0, 0, 0,
                            0, 0, CASE WHEN tipe_pdrb = 'konstan' THEN rasio_konsumsi_antara ELSE 0 END,
                            ?, ?
                        FROM lembar_kerja_item
                        WHERE lembar_kerja_id = ?
                    ", [$lembarKerja->id, $now, $now, $previousLembar->id]);
                } else {
                    // Copy baris demi baris jika wilayah beda (untuk sinkronisasi komoditas_id)
                    $sourceItems = DB::table('lembar_kerja_item as i')
                        ->join('komoditas as k', 'k.id', '=', 'i.komoditas_id')
                        ->where('i.lembar_kerja_id', $previousLembar->id)
                        ->get([
                            'i.*',
                            'k.nama as komoditas_nama',
                            'k.satuan as satuan_nama',
                            'k.wujud as komoditas_wujud'
                        ]);

                    $komoditasHasSatuanColumn = Schema::hasColumn('komoditas', 'satuan');
                    $komoditasHasWujudColumn = Schema::hasColumn('komoditas', 'wujud');

                    $komoditasMap = []; // Cache translation source_komoditas_id -> target_komoditas_id

                    foreach ($sourceItems as $item) {
                        $sourceKId = $item->komoditas_id;
                        if (!isset($komoditasMap[$sourceKId])) {
                            // Cari atau buat komoditas di wilayah target
                            $targetKomoditas = DB::table('komoditas')
                                ->where('id_wilayah', $selectedWilayahId)
                                ->whereRaw('LOWER(nama) = ?', [strtolower($item->komoditas_nama)])
                                ->first();

                            if (!$targetKomoditas) {
                                $insertK = [
                                    'nama' => $item->komoditas_nama,
                                    'id_wilayah' => $selectedWilayahId,
                                    'aktif' => 1,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                                if ($komoditasHasSatuanColumn) $insertK['satuan'] = $item->satuan_nama ?? '';
                                if ($komoditasHasWujudColumn) $insertK['wujud'] = $item->komoditas_wujud ?? '';
                                
                                $targetKId = DB::table('komoditas')->insertGetId($insertK);
                            } else {
                                $targetKId = $targetKomoditas->id;
                            }
                            $komoditasMap[$sourceKId] = $targetKId;
                        }

                        DB::table('lembar_kerja_item')->insert([
                            'lembar_kerja_id' => $lembarKerja->id,
                            'komoditas_id' => $komoditasMap[$sourceKId],
                            'tipe_pdrb' => $item->tipe_pdrb,
                            'kuantum' => $item->kuantum, // Ambil kuantum dari sumber (misal triwulan sebelumnya)
                            'harga_produsen' => $item->tipe_pdrb === 'konstan' ? $item->harga_produsen : 0,
                            'nilai_output_utama' => 0,
                            'rasio_output_ikut' => $item->rasio_output_ikut, // Ambil rasio dari sumber
                            'nilai_output_ikut' => 0,
                            'biaya_perawatan' => 0,
                            'biaya_sebelumnya' => 0,
                            'wip' => 0,
                            'output_adh' => 0,
                            'konsumsi_antara' => 0,
                            'nilai_ntb' => 0,
                            'rasio_konsumsi_antara' => $item->rasio_konsumsi_antara, // Ambil rasio dari sumber
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        // Ambil item rows untuk ditampilkan
        $itemRows = DB::table('lembar_kerja_item as i')
            ->join('komoditas as k', function ($join) use ($selectedWilayahId) {
                $join->on('k.id', '=', 'i.komoditas_id')
                    ->where('k.id_wilayah', '=', $selectedWilayahId);
            })
            ->where('i.lembar_kerja_id', $lembarKerja->id)
            ->where('i.tipe_pdrb', $tipePdrb)
            ->orderBy('i.id')
            ->get([
                'i.*',
                'k.nama as komoditas_nama',
                'k.id_wilayah',
                'k.satuan as satuan_nama',
                'k.wujud as komoditas_wujud',
            ]);

        // Cek Base Year (Tahun Paling Awal)
        $baseTahun = DB::table('tahun')->orderBy('tahun', 'asc')->first();
        $isBaseYear = $baseTahun ? ((int) $tahunId === (int) $baseTahun->id_tahun) : true;

        // Untuk tab KONSTAN: harga_produsen harus konstan lintas tahun
        // Ambil harga dari base year, atau dari periode manapun yang sudah ada data
        if ($tipePdrb === 'konstan' && !$isBaseYear) {
            // Step 1: Cari di base year dulu
            $hargaSourceItems = collect();
            if ($baseTahun) {
                $baseLembarKerja = DB::table('lembar_kerja')
                    ->where('wilayah_id', $selectedWilayahId)
                    ->where('id_periode', $periodeId)
                    ->where('id_tahun', $baseTahun->id_tahun)
                    ->where('jenis', $jenis)
                    ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                    ->first();

                if ($baseLembarKerja) {
                    $hargaSourceItems = DB::table('lembar_kerja_item')
                        ->where('lembar_kerja_id', $baseLembarKerja->id)
                        ->where('tipe_pdrb', 'konstan')
                        ->where('harga_produsen', '>', 0)
                        ->get()
                        ->keyBy('komoditas_id');
                }
            }

            // Step 2: Jika base year tidak punya harga, cari dari lembar kerja MANAPUN 
            // yang punya harga_produsen > 0 untuk sub_kategori dan wilayah yang sama
            if ($hargaSourceItems->isEmpty()) {
                $anyLkWithHarga = DB::table('lembar_kerja as lk')
                    ->join('lembar_kerja_item as i', 'i.lembar_kerja_id', '=', 'lk.id')
                    ->where('lk.wilayah_id', $selectedWilayahId)
                    ->where('lk.jenis', $jenis)
                    ->where('lk.id_sub_kategori', $subKategori->id_sub_kategori)
                    ->where('lk.id', '!=', $lembarKerja->id)
                    ->where('i.tipe_pdrb', 'konstan')
                    ->where('i.harga_produsen', '>', 0)
                    ->orderByDesc('lk.id_tahun')
                    ->orderByDesc('lk.id_periode')
                    ->select('i.komoditas_id', DB::raw('MAX(i.harga_produsen) as harga_produsen'),
                             DB::raw('MAX(i.rasio_output_ikut) as rasio_output_ikut'),
                             DB::raw('MAX(i.rasio_konsumsi_antara) as rasio_konsumsi_antara'))
                    ->groupBy('i.komoditas_id')
                    ->get()
                    ->keyBy('komoditas_id');

                $hargaSourceItems = $anyLkWithHarga;
            }

            // Terapkan harga ke itemRows yang masih 0
            foreach ($itemRows as $item) {
                if (isset($hargaSourceItems[$item->komoditas_id])) {
                    $src = $hargaSourceItems[$item->komoditas_id];
                    // Selalu apply harga konstan (harus sama lintas tahun)
                    $item->harga_produsen = $src->harga_produsen;
                    // Rasio juga ikut jika belum diisi
                    if ((float)$item->rasio_output_ikut == 0) {
                        $item->rasio_output_ikut = $src->rasio_output_ikut;
                    }
                    if ((float)$item->rasio_konsumsi_antara == 0) {
                        $item->rasio_konsumsi_antara = $src->rasio_konsumsi_antara;
                    }
                }
            }
        } elseif ($tipePdrb === 'berlaku' && !$isBaseYear && $baseTahun) {
            // Untuk berlaku: hanya propagate rasio dari base year
            $baseLembarKerja = DB::table('lembar_kerja')
                ->where('wilayah_id', $selectedWilayahId)
                ->where('id_periode', $periodeId)
                ->where('id_tahun', $baseTahun->id_tahun)
                ->where('jenis', $jenis)
                ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                ->first();

            if ($baseLembarKerja) {
                $baseItems = DB::table('lembar_kerja_item')
                    ->where('lembar_kerja_id', $baseLembarKerja->id)
                    ->where('tipe_pdrb', $tipePdrb)
                    ->get()
                    ->keyBy('komoditas_id');

                foreach ($itemRows as $item) {
                    if (isset($baseItems[$item->komoditas_id])) {
                        $baseItem = $baseItems[$item->komoditas_id];
                        $item->rasio_output_ikut = $baseItem->rasio_output_ikut;
                        $item->rasio_konsumsi_antara = $baseItem->rasio_konsumsi_antara;
                    }
                }
            }
        }

        // AUTO-PERSIST harga_produsen konstan ke DB (bukan hanya display)
        // Sehingga Rekon LK langsung muncul meski user belum klik Simpan
        if ($tipePdrb === 'konstan' && !$isBaseYear && isset($hargaSourceItems) && !$hargaSourceItems->isEmpty()) {
            $nowPersist = now();
            foreach ($itemRows as $item) {
                if (isset($hargaSourceItems[$item->komoditas_id]) && (float)($item->harga_produsen ?? 0) > 0) {
                    // Hanya update jika DB masih 0
                    DB::table('lembar_kerja_item')
                        ->where('lembar_kerja_id', $lembarKerja->id)
                        ->where('komoditas_id', $item->komoditas_id)
                        ->where('tipe_pdrb', 'konstan')
                        ->where(function ($q) {
                            $q->whereNull('harga_produsen')->orWhere('harga_produsen', '=', 0);
                        })
                        ->update([
                            'harga_produsen' => $item->harga_produsen,
                            'rasio_output_ikut' => $item->rasio_output_ikut,
                            'rasio_konsumsi_antara' => $item->rasio_konsumsi_antara,
                            'updated_at' => $nowPersist,
                        ]);
                }
            }
        }

        return view('lembar_kerja.detail', compact(
            'jenis',
            'jenisLabel',
            'subKategori',
            'periodeList',
            'periodeId',
            'tahunList',
            'tahunId',
            'lembarKerja',
            'itemRows',
            'isProvinsiRole',
            'wilayahList',
            'tipePdrb',
            'isBaseYear'
        ))->with([
                    'selectedWilayahId' => $selectedWilayahId,
                    'template' => PdrbHelper::getTemplate($subKategori->id_sub_kategori)
                ]);
    }
    public function detailKategori($idKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($jenis, ['lapangan_usaha', 'pengeluaran'], true)) {
            $jenis = 'lapangan_usaha';
        }

        $kategori = Kategori::with('subKategori')->findOrFail($idKategori);
        if ($kategori->pendekatan !== $jenis) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Kategori tidak ditemukan untuk pendekatan ini.');
        }

        $jenisLabel = $jenis === 'pengeluaran' ? 'Pengeluaran' : 'Lapangan Usaha';

        return view('lembar_kerja.detail-kategori', compact('jenis', 'jenisLabel', 'kategori'));
    }

    public function saveDetail($idSubKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($jenis, ['lapangan_usaha', 'pengeluaran'], true)) {
            $jenis = 'lapangan_usaha';
        }
        $tipePdrb = $request->get('tipe_pdrb', 'berlaku');
        if (!in_array($tipePdrb, ['berlaku', 'konstan'], true)) {
            $tipePdrb = 'berlaku';
        }

        $user = auth()->user();
        if (!$user || !$user->id_wilayah) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Wilayah user belum ditentukan.');
        }

        $selectedWilayahId = $this->resolveSelectedWilayahId($request, $user, null);

        $subKategori = SubKategori::with('kategori')->findOrFail($idSubKategori);
        if (!$subKategori->kategori || $subKategori->kategori->pendekatan !== $jenis) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Sub kategori tidak ditemukan untuk pendekatan ini.');
        }

        return DB::transaction(function () use ($idSubKategori, $request, $jenis, $tipePdrb, $user, $selectedWilayahId, $subKategori) {
            $lembarKerjaId = (int) $request->input('lembar_kerja_id');
            $lembarKerja = DB::table('lembar_kerja')->where('id', $lembarKerjaId)->first();
            if (!$lembarKerja || (int) $lembarKerja->wilayah_id !== (int) $selectedWilayahId) {
                return redirect()->back()->with('error', 'Lembar kerja tidak valid.');
            }

            $items = $request->input('items', []);
            $now = now();
            $isSectoral = ($idSubKategori >= 2 && $idSubKategori <= 10);
            $columnKeys = ['kuantum', 'harga_produsen', 'nilai_output_utama', 'rasio_output_ikut', 'nilai_output_ikut', 'biaya_perawatan', 'biaya_sebelumnya', 'wip', 'output_adh', 'konsumsi_antara', 'nilai_ntb', 'rasio_konsumsi_antara', 'deflator', 'wip_berlaku'];
            $komoditasHasSatuanColumn = Schema::hasColumn('komoditas', 'satuan');
            $komoditasHasWujudColumn = Schema::hasColumn('komoditas', 'wujud');
            $itemHasDeflator = Schema::hasColumn('lembar_kerja_item', 'deflator');
            $itemHasWipBerlaku = Schema::hasColumn('lembar_kerja_item', 'wip_berlaku');
            $itemHasDataTambahan = Schema::hasColumn('lembar_kerja_item', 'data_tambahan');

            // Load template schema to identify text columns
            $templateName = PdrbHelper::getTemplate($idSubKategori);
            $schemaPath = config_path('lk_templates/' . $templateName . '.json');
            $textColKeys = [];
            if (file_exists($schemaPath)) {
                $schema = json_decode(file_get_contents($schemaPath), true);
                if (isset($schema['columns'])) {
                    foreach ($schema['columns'] as $col) {
                        if (($col['type'] ?? '') === 'text') {
                            $textColKeys[] = $col['key'];
                        }
                    }
                }
            }

            foreach ($items as $komoditasId => $row) {
                $komoditasNama = $this->normalizeKomoditasNama($row['komoditas_nama'] ?? '');
                $satuanValue = trim((string) ($row['satuan_nama'] ?? $row['satuan'] ?? ''));
                $wujudValue = trim((string) ($row['wujud_kegiatan'] ?? $row['wujud'] ?? ''));

                $payload = [
                    'lembar_kerja_id' => $lembarKerjaId,
                    'komoditas_id' => (int) $komoditasId,
                    'kuantum' => $this->toNumber($row['kuantum'] ?? 0),
                    'harga_produsen' => $this->toNumber($row['harga_produsen'] ?? 0),
                    'nilai_output_utama' => $this->scaleJutaToRaw($row['nilai_output_utama'] ?? 0),
                    'rasio_output_ikut' => $this->toNumber($row['rasio_output_ikut'] ?? 0),
                    'nilai_output_ikut' => $this->scaleJutaToRaw($row['nilai_output_ikut'] ?? 0),
                    'biaya_perawatan' => $this->scaleJutaToRaw($row['biaya_perawatan'] ?? 0),
                    'biaya_sebelumnya' => $this->scaleJutaToRaw($row['biaya_sebelumnya'] ?? 0),
                    'wip' => $this->scaleJutaToRaw($row['wip'] ?? 0),
                    'output_adh' => $this->scaleJutaToRaw($row['output_adh'] ?? 0),
                    'konsumsi_antara' => $this->scaleJutaToRaw($row['konsumsi_antara'] ?? 0),
                    'nilai_ntb' => $this->scaleJutaToRaw($row['nilai_ntb'] ?? 0),
                    'rasio_konsumsi_antara' => $this->toNumber($row['rasio_konsumsi_antara'] ?? 0),
                    'tipe_pdrb' => $tipePdrb,
                    'updated_at' => $now,
                ];

                if ($itemHasDeflator) {
                    $payload['deflator'] = $this->toNumber($row['deflator'] ?? 0);
                }
                if ($itemHasWipBerlaku) {
                    $payload['wip_berlaku'] = $this->scaleJutaToRaw($row['wip_berlaku'] ?? 0);
                }

                // --- DYNAMIC DATA PERSISTENCE (META/DATA_TAMBAHAN) ---
                if ($itemHasDataTambahan) {
                    $metaSanitized = [];
                    $hasValues = false;

                    // Capture fields from 'meta' array if present
                    if (isset($row['meta']) && is_array($row['meta'])) {
                        foreach ($row['meta'] as $metaKey => $metaValue) {
                            $val = in_array($metaKey, $textColKeys) ? $metaValue : $this->toNumber($metaValue ?? 0);
                            $metaSanitized[$metaKey] = $val;
                            if ($val != 0 && $val != '0' && $val !== '') $hasValues = true;
                        }
                    }

                    // Capture any other field in $row that isn't a known DB column
                    $excludeFromDynamic = array_merge($columnKeys, [
                        'komoditas_nama', 'satuan_nama', 'wujud', 'meta', 
                        'lembar_kerja_id', 'komoditas_id', 'tipe_pdrb', 'deflator', 'wip_berlaku'
                    ]);
                    
                    foreach ($row as $key => $value) {
                        if (!in_array($key, $excludeFromDynamic) && !is_array($value)) {
                            $val = in_array($key, $textColKeys) ? $value : $this->toNumber($value ?? 0);
                            $metaSanitized[$key] = $val;
                            if ($val != 0 && $val != '0' && $val !== '') $hasValues = true;
                        }
                    }

                    // Only update data_tambahan if it has values OR if we are in 'berlaku' tab
                    // This prevents accidental wipes from 'konstan' tab
                    if ($hasValues || $tipePdrb === 'berlaku') {
                        $payload['data_tambahan'] = json_encode($metaSanitized);
                    }
                }


                DB::table('lembar_kerja_item')->updateOrInsert(
                    [
                        'lembar_kerja_id' => $lembarKerjaId,
                        'komoditas_id' => (int) $komoditasId,
                        'tipe_pdrb' => $tipePdrb,
                    ],
                    array_merge($payload, ['created_at' => $now])
                );

                $updateKomoditas = [];
                if ($komoditasNama !== '') {
                    $updateKomoditas['nama'] = $komoditasNama;
                }
                if ($komoditasHasSatuanColumn && $satuanValue !== '') {
                    $updateKomoditas['satuan'] = $satuanValue;
                }
                if ($komoditasHasWujudColumn && $wujudValue !== '') {
                    $updateKomoditas['wujud'] = $wujudValue;
                }
                if (!empty($updateKomoditas)) {
                    $updateKomoditas['updated_at'] = $now;
                    DB::table('komoditas')
                        ->where('id', (int) $komoditasId)
                        ->update($updateKomoditas);
                }
            }

            $newRows = $request->input('new_rows', []);
            foreach ($newRows as $row) {
                $komoditasNama = $this->normalizeKomoditasNama($row['komoditas_nama'] ?? '');
                if ($komoditasNama === '') {
                    continue;
                }

                $satuanNama = trim((string) ($row['satuan_nama'] ?? $row['satuan'] ?? ''));
                $wujudNama = trim((string) ($row['wujud_kegiatan'] ?? $row['wujud'] ?? ''));

                // Cek komoditas berdasarkan nama DAN id_wilayah
                $komoditas = DB::table('komoditas')
                    ->where('id_wilayah', $selectedWilayahId)
                    ->whereRaw('LOWER(nama) = ?', [$this->normalizeLower($komoditasNama)])
                    ->first();

                if (!$komoditas) {
                    $insertKomoditas = [
                        'nama' => $komoditasNama,
                        'id_wilayah' => $selectedWilayahId,
                        'aktif' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($komoditasHasSatuanColumn) {
                        $insertKomoditas['satuan'] = $satuanNama !== '' ? $satuanNama : '';
                    }
                    if ($komoditasHasWujudColumn) {
                        $insertKomoditas['wujud'] = $wujudNama !== '' ? $wujudNama : '';
                    }

                    $komoditasId = DB::table('komoditas')->insertGetId($insertKomoditas);
                    $komoditas = (object) ['id' => $komoditasId];
                } else {
                    $komoditasId = $komoditas->id;
                    if ($satuanNama !== '' && $komoditasHasSatuanColumn) {
                        DB::table('komoditas')
                            ->where('id', $komoditasId)
                            ->update([
                                'satuan' => $satuanNama,
                                'updated_at' => $now,
                            ]);
                    }
                    if ($wujudNama !== '' && $komoditasHasWujudColumn) {
                        DB::table('komoditas')
                            ->where('id', $komoditasId)
                            ->update([
                                'wujud' => $wujudNama,
                                'updated_at' => $now,
                            ]);
                    }
                }

                $payload = [
                    'lembar_kerja_id' => $lembarKerjaId,
                    'komoditas_id' => (int) $komoditasId,
                    'kuantum' => $this->toNumber($row['kuantum'] ?? 0),
                    'wujud' => $wujudNama ?: ($komoditas->wujud ?? null),
                    'harga_produsen' => $this->toNumber($row['harga_produsen'] ?? 0),
                    'nilai_output_utama' => $this->scaleJutaToRaw($row['nilai_output_utama'] ?? 0),
                    'rasio_output_ikut' => $this->toNumber($row['rasio_output_ikut'] ?? 0),
                    'nilai_output_ikut' => $this->scaleJutaToRaw($row['nilai_output_ikut'] ?? 0),
                    'biaya_perawatan' => $this->scaleJutaToRaw($row['biaya_perawatan'] ?? 0),
                    'biaya_sebelumnya' => $this->scaleJutaToRaw($row['biaya_sebelumnya'] ?? 0),
                    'wip' => $this->scaleJutaToRaw($row['wip'] ?? 0),
                    'output_adh' => $this->scaleJutaToRaw($row['output_adh'] ?? 0),
                    'konsumsi_antara' => $this->scaleJutaToRaw($row['konsumsi_antara'] ?? 0),
                    'nilai_ntb' => $this->scaleJutaToRaw($row['nilai_ntb'] ?? 0),
                    'rasio_konsumsi_antara' => $this->toNumber($row['rasio_konsumsi_antara'] ?? 0),
                    'tipe_pdrb' => $tipePdrb,
                    'updated_at' => $now,
                ];
                if ($itemHasDeflator) {
                    $payload['deflator'] = $this->toNumber($row['deflator'] ?? 0);
                }
                if ($itemHasWipBerlaku) {
                    $payload['wip_berlaku'] = $this->scaleJutaToRaw($row['wip_berlaku'] ?? 0);
                }

                // --- DYNAMIC DATA PERSISTENCE FOR NEW ROWS ---
                $metaSanitized = [];
                $hasValues = false;

                if (isset($row['meta']) && is_array($row['meta'])) {
                    foreach ($row['meta'] as $metaKey => $metaValue) {
                        $val = in_array($metaKey, $textColKeys) ? $metaValue : $this->toNumber($metaValue ?? 0);
                        $metaSanitized[$metaKey] = $val;
                        if ($val != 0 && $val != '0' && $val !== '')
                            $hasValues = true;
                    }
                }

                $excludeFromDynamic = array_merge($columnKeys, ['komoditas_nama', 'satuan_nama', 'satuan', 'wujud', 'wujud_kegiatan', 'meta', 'lembar_kerja_id', 'komoditas_id', 'tipe_pdrb']);
                foreach ($row as $key => $value) {
                    if (!in_array($key, $excludeFromDynamic) && !is_array($value)) {
                        $val = in_array($key, $textColKeys) ? $value : $this->toNumber($value ?? 0);
                        $metaSanitized[$key] = $val;
                        if ($val != 0 && $val != '0' && $val !== '')
                            $hasValues = true;
                    }
                }

                if ($itemHasDataTambahan && ($hasValues || $tipePdrb === 'berlaku')) {
                    $payload['data_tambahan'] = json_encode($metaSanitized);
                }

                DB::table('lembar_kerja_item')->updateOrInsert(
                    [
                        'lembar_kerja_id' => $lembarKerjaId,
                        'komoditas_id' => (int) $komoditasId,
                        'tipe_pdrb' => $tipePdrb,
                    ],
                    array_merge($payload, ['created_at' => $now])
                );

                // PROPAGASI KE TAHUN/PERIODE LAIN
                $this->propagateNewRow($lembarKerjaId, $komoditasId, $payload, $selectedWilayahId, $jenis, $subKategori);
            }

            DB::table('lembar_kerja')->where('id', $lembarKerjaId)->update([
                'updated_by' => $user->id,
                'updated_at' => $now,
            ]);

            // Get base year information for price synchronization
            $baseTahun = DB::table('tahun')->orderBy('tahun', 'asc')->first();
            $isBaseYear = $baseTahun ? ((int) $lembarKerja->id_tahun === (int) $baseTahun->id_tahun) : false;
            
            $baseLkId = null;
            if (!$isBaseYear && $baseTahun) {
                $baseLkId = DB::table('lembar_kerja')
                    ->where('id_tahun', $baseTahun->id_tahun)
                    ->where('wilayah_id', $selectedWilayahId)
                    ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                    ->where('jenis', $jenis)
                    ->value('id');
            }

            // SYNC DATA ANTARA BERLAKU DAN KONSTAN
            $otherTipe = $tipePdrb === 'berlaku' ? 'konstan' : 'berlaku';
            $currentItemsQuery = DB::table('lembar_kerja_item')
                ->where('lembar_kerja_id', $lembarKerjaId)
                ->where('tipe_pdrb', $tipePdrb)
                ->get();

            foreach ($currentItemsQuery as $cItem) {
                // Initialize sync data
                $syncData = [
                    'updated_at' => $now,
                ];

                // Aturan Umum: Data fisik (Kuantum, Wujud, Rasio) disinkronkan dari BERLAKU ke KONSTAN
                if ($tipePdrb === 'berlaku') {
                    $syncData['kuantum'] = $cItem->kuantum;
                    $syncData['wujud'] = $cItem->wujud;
                    $syncData['rasio_output_ikut'] = $cItem->rasio_output_ikut;
                    $syncData['rasio_konsumsi_antara'] = $cItem->rasio_konsumsi_antara;
                }

                // Aturan Khusus Tahun Dasar: Berlaku menyetir Konstan untuk data fisik (Kuantum & Rasio).
                // Namun, Harga Produsen dan Nilai-nilai nominal di Konstan harus tetap kosong (0) 
                // agar user bisa mengisi/paste manual di tab Konstan.
                if ($isBaseYear && $tipePdrb === 'berlaku') {
                    $syncData['kuantum'] = $cItem->kuantum;
                    $syncData['wujud'] = $cItem->wujud;
                    $syncData['rasio_output_ikut'] = $cItem->rasio_output_ikut;
                    $syncData['rasio_konsumsi_antara'] = $cItem->rasio_konsumsi_antara;
                    
                    // Kita tidak menyinkronkan harga_produsen dan nilai nominal lainnya
                    // agar di tab KONSTAN tetap 0 sesuai permintaan.
                }

                $insertDefaults = [];
                
                if ($tipePdrb === 'berlaku' && $otherTipe === 'konstan') {
                    if (!$isBaseYear) {
                        $syncData['wip_berlaku'] = $cItem->wip;
                    }
                    
                    // Fetch existing mode to decide price sync
                    $existingMode = DB::table('lembar_kerja_item')
                        ->where('lembar_kerja_id', $lembarKerjaId)
                        ->where('komoditas_id', $cItem->komoditas_id)
                        ->where('tipe_pdrb', 'konstan')
                        ->value('mode_konstan');

                    if (!$isBaseYear && empty($existingMode)) {
                        // For new rows in Konstan, always prioritize price from base year
                        if ($baseLkId) {
                            $basePrice = DB::table('lembar_kerja_item')
                                ->where('lembar_kerja_id', $baseLkId)
                                ->where('komoditas_id', $cItem->komoditas_id)
                                ->where('tipe_pdrb', 'konstan')
                                ->value('harga_produsen');
                            
                            if ($basePrice !== null && $basePrice != 0) {
                                $insertDefaults['harga_produsen'] = $basePrice;
                            } else {
                                $insertDefaults['harga_produsen'] = $cItem->harga_produsen;
                            }
                        } else {
                            $insertDefaults['harga_produsen'] = $cItem->harga_produsen;
                        }
                        
                        $insertDefaults['deflator'] = 100;
                        $insertDefaults['mode_konstan'] = 'deflasi';
                    }
                }

                // Sync data_tambahan
                $shouldSyncMeta = false;
                if ($tipePdrb === 'berlaku') {
                    $shouldSyncMeta = true;
                } else if ($isBaseYear) {
                    // Sync back to Berlaku only in base year
                    $metaObj = is_string($cItem->data_tambahan) ? json_decode($cItem->data_tambahan, true) : $cItem->data_tambahan;
                    if (!empty($metaObj)) {
                        $hasValues = false;
                        foreach ($metaObj as $v) {
                            if ($v != 0 && $v != '0') {
                                $hasValues = true;
                                break;
                            }
                        }
                        if ($hasValues) $shouldSyncMeta = true;
                    }
                }

                if ($shouldSyncMeta) {
                    $syncData['data_tambahan'] = $cItem->data_tambahan;
                }

                // Use updateOrInsert with defaults for new rows
                DB::table('lembar_kerja_item')->updateOrInsert(
                    [
                        'lembar_kerja_id' => $lembarKerjaId,
                        'komoditas_id' => $cItem->komoditas_id,
                        'tipe_pdrb' => $otherTipe,
                    ],
                    array_merge($insertDefaults, $syncData, [
                        'created_at' => $now
                    ])
                );
            }

            // REKALKULASI OTHER TIPE KARENA PERUBAHAN KUANTUM
            // Step 1: Jika berlaku baru saja disimpan, perbarui nilai wip di konstan dari wip_berlaku
            if ($tipePdrb === 'berlaku' && Schema::hasColumn('lembar_kerja_item', 'deflator')) {
                DB::statement("
                    UPDATE lembar_kerja_item 
                    SET wip = CASE 
                        WHEN deflator IS NOT NULL AND deflator <> 0 
                            THEN (wip_berlaku / deflator) * 100 
                        ELSE 0 
                    END,
                    updated_at = ?
                    WHERE lembar_kerja_id = ?
                      AND tipe_pdrb = 'konstan'
                ", [$now, $lembarKerjaId]);
            }

                // Step 2: Rekalkulasi nilai turunan untuk tipe lain (output_adh, NTB, dll)
                // PENTING: Gunakan Raw Rp (Hapus pembagi 1000000).
                DB::statement("
                    UPDATE lembar_kerja_item 
                    SET 
                        nilai_output_utama = (CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN (kuantum * harga_produsen) / (deflator / 100)
                            ELSE kuantum * harga_produsen
                        END),
                        nilai_output_ikut = (CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN ((kuantum * harga_produsen) / (deflator / 100)) * rasio_output_ikut
                            ELSE (kuantum * harga_produsen) * rasio_output_ikut
                        END),
                        output_adh = (CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN ((kuantum * harga_produsen) / (deflator / 100)) + (((kuantum * harga_produsen) / (deflator / 100)) * rasio_output_ikut)
                            ELSE (kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut)
                        END) + wip,
                        konsumsi_antara = (CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN (((kuantum * harga_produsen) / (deflator / 100)) + (((kuantum * harga_produsen) / (deflator / 100)) * rasio_output_ikut)) * rasio_konsumsi_antara
                            ELSE ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut)) * rasio_konsumsi_antara
                        END),
                        nilai_ntb = ((CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN ((kuantum * harga_produsen) / (deflator / 100)) + (((kuantum * harga_produsen) / (deflator / 100)) * rasio_output_ikut)
                            ELSE (kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut)
                        END) + wip) - ((CASE 
                            WHEN tipe_pdrb = 'konstan' AND mode_konstan = 'deflasi' AND deflator IS NOT NULL AND deflator <> 0
                                THEN (((kuantum * harga_produsen) / (deflator / 100)) + (((kuantum * harga_produsen) / (deflator / 100)) * rasio_output_ikut)) * rasio_konsumsi_antara
                            ELSE ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut)) * rasio_konsumsi_antara
                        END))
                    WHERE lembar_kerja_id = ?
                      AND tipe_pdrb = ?
                      AND (nilai_ntb IS NULL OR nilai_ntb = 0)
                ", [$lembarKerjaId, $otherTipe]);

            if ($isBaseYear) {
                // Ambil target lembar_kerja yang BUKAN base year tetapi masih sub kategori dan wilayah yang sama
                $targetLks = DB::table('lembar_kerja')
                    ->where('wilayah_id', $selectedWilayahId)
                    ->where('jenis', $jenis)
                    ->where('id_sub_kategori', $subKategori->id_sub_kategori)
                    ->where('id_tahun', '!=', $baseTahun->id_tahun)
                    ->pluck('id')
                    ->toArray();

                if (!empty($targetLks)) {
                    // Update semua item di target LK yang memiliki komoditas yang sama
                    foreach ($items as $komoditasId => $row) {
                        $updatePayload = [
                            'rasio_output_ikut' => $this->toNumber($row['rasio_output_ikut'] ?? 0),
                            'rasio_konsumsi_antara' => $this->toNumber($row['rasio_konsumsi_antara'] ?? 0),
                            'updated_at' => $now,
                        ];

                        if ($tipePdrb === 'konstan') {
                            $updatePayloadKonstan = $updatePayload;
                            $updatePayloadKonstan['harga_produsen'] = $this->toNumber($row['harga_produsen'] ?? 0);

                            DB::table('lembar_kerja_item')
                                ->whereIn('lembar_kerja_id', $targetLks)
                                ->where('tipe_pdrb', 'konstan')
                                ->where('komoditas_id', (int) $komoditasId)
                                ->update($updatePayloadKonstan);

                            DB::table('lembar_kerja_item')
                                ->whereIn('lembar_kerja_id', $targetLks)
                                ->where('tipe_pdrb', 'berlaku')
                                ->where('komoditas_id', (int) $komoditasId)
                                ->update($updatePayload);
                        } else {
                            DB::table('lembar_kerja_item')
                                ->whereIn('lembar_kerja_id', $targetLks)
                                ->where('komoditas_id', (int) $komoditasId)
                                ->update($updatePayload);
                        }
                    }

                    foreach ($newRows as $row) {
                        $komoditasNama = $this->normalizeKomoditasNama($row['komoditas_nama'] ?? '');
                        if ($komoditasNama === '')
                            continue;
                        $komoditasInfo = DB::table('komoditas')
                            ->where('id_wilayah', $selectedWilayahId)
                            ->whereRaw('LOWER(nama) = ?', [$this->normalizeLower($komoditasNama)])
                            ->first();

                        if ($komoditasInfo) {
                            $updatePayload = [
                                'rasio_output_ikut' => $this->toNumber($row['rasio_output_ikut'] ?? 0),
                                'rasio_konsumsi_antara' => $this->toNumber($row['rasio_konsumsi_antara'] ?? 0),
                                'updated_at' => $now,
                            ];

                            if ($tipePdrb === 'konstan') {
                                $updatePayloadKonstan = $updatePayload;
                                $updatePayloadKonstan['harga_produsen'] = $this->toNumber($row['harga_produsen'] ?? 0);

                                DB::table('lembar_kerja_item')
                                    ->whereIn('lembar_kerja_id', $targetLks)
                                    ->where('tipe_pdrb', 'konstan')
                                    ->where('komoditas_id', $komoditasInfo->id)
                                    ->update($updatePayloadKonstan);

                                DB::table('lembar_kerja_item')
                                    ->whereIn('lembar_kerja_id', $targetLks)
                                    ->where('tipe_pdrb', 'berlaku')
                                    ->where('komoditas_id', $komoditasInfo->id)
                                    ->update($updatePayload);
                            } else {
                                DB::table('lembar_kerja_item')
                                    ->whereIn('lembar_kerja_id', $targetLks)
                                    ->where('komoditas_id', $komoditasInfo->id)
                                    ->update($updatePayload);
                            }
                        }
                    }

                    // Terapkan kalkulasi dasar pada items yang di-update (output utama, output ikut, ntb) untuk konsistensi di DB
                    $targetLksStr = implode(',', $targetLks);
                    DB::statement("
                        UPDATE lembar_kerja_item 
                        SET 
                            nilai_output_utama = kuantum * harga_produsen,
                            nilai_output_ikut  = (kuantum * harga_produsen) * rasio_output_ikut,
                            output_adh = (kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip,
                            konsumsi_antara = ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip) * rasio_konsumsi_antara,
                            nilai_ntb = ((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip) - (((kuantum * harga_produsen) + ((kuantum * harga_produsen) * rasio_output_ikut) + wip) * rasio_konsumsi_antara)
                        WHERE lembar_kerja_id IN ($targetLksStr)
                    ");
                }
            }

            $this->syncRekonTable($lembarKerjaId);

            return redirect()->back()->with('success', 'Lembar kerja berhasil disimpan.');
        });
    }

    public function deleteItem($idSubKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($jenis, ['lapangan_usaha', 'pengeluaran'], true)) {
            $jenis = 'lapangan_usaha';
        }
        $tipePdrb = $request->get('tipe_pdrb', 'berlaku');
        if (!in_array($tipePdrb, ['berlaku', 'konstan'], true)) {
            $tipePdrb = 'berlaku';
        }

        $user = auth()->user();
        if (!$user || !$user->id_wilayah) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Wilayah user belum ditentukan.');
        }

        $selectedWilayahId = $this->resolveSelectedWilayahId($request, $user, null);

        $subKategori = SubKategori::with('kategori')->findOrFail($idSubKategori);
        if (!$subKategori->kategori || $subKategori->kategori->pendekatan !== $jenis) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Sub kategori tidak ditemukan untuk pendekatan ini.');
        }

        $request->validate([
            'lembar_kerja_id' => 'required|integer',
            'komoditas_id' => 'required|integer',
        ]);

        $lembarKerjaId = (int) $request->input('lembar_kerja_id');
        $komoditasId = (int) $request->input('komoditas_id');

        $lembarKerja = DB::table('lembar_kerja')->where('id', $lembarKerjaId)->first();
        if (!$lembarKerja || (int) $lembarKerja->wilayah_id !== (int) $selectedWilayahId) {
            return redirect()->back()->with('error', 'Lembar kerja tidak valid.');
        }

        // Kumpulkan ID lembar kerja saat ini dan yang setelahnya (future records)
        $targetLembarKerjaIds = DB::table('lembar_kerja')
            ->where('wilayah_id', $selectedWilayahId)
            ->where('jenis', $jenis)
            ->where('id_sub_kategori', $subKategori->id_sub_kategori)
            ->where(function ($query) use ($lembarKerja) {
                // Yang tahunnya lebih besar, atau rincian periode yang lebih besar pada tahun yang sama
                $query->where('id_tahun', '>', $lembarKerja->id_tahun)
                    ->orWhere(function ($q) use ($lembarKerja) {
                    $q->where('id_tahun', '=', $lembarKerja->id_tahun)
                        ->where('id_periode', '>=', $lembarKerja->id_periode); // Termasuk dirinya sendiri
                });
            })
            ->pluck('id')
            ->toArray();

        // Hapus (cascade) pada semua ID tersebut untuk komoditas yang dipilih
        if (!empty($targetLembarKerjaIds)) {
            DB::table('lembar_kerja_item')
                ->whereIn('lembar_kerja_id', $targetLembarKerjaIds)
                ->where('komoditas_id', $komoditasId)
                ->delete();
        }

        $this->syncRekonTable($lembarKerjaId);

        return redirect()->back()->with('success', 'Baris komoditas berhasil dihapus.');
    }

    public function resetDetail($idSubKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        $lembarKerjaId = $request->input('lembar_kerja_id');

        if (!$lembarKerjaId) {
            return redirect()->back()->with('error', 'Pilih lembar kerja terlebih dahulu.');
        }

        DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerjaId)
            ->delete();

        // Tandai sebagai sudah pernah diubah agar tidak kena auto-copy komoditas
        DB::table('lembar_kerja')->where('id', $lembarKerjaId)->update([
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        $this->syncRekonTable($lembarKerjaId);

        return redirect()->back()->with('success', 'Seluruh data pada lembar kerja ini telah dihapus.');
    }

    public function saveKomoditas($idSubKategori, Request $request)
    {
        $jenis = $request->get('jenis', 'lapangan_usaha');
        if (!in_array($jenis, ['lapangan_usaha', 'pengeluaran'], true)) {
            $jenis = 'lapangan_usaha';
        }

        $user = auth()->user();
        if (!$user || !$user->id_wilayah) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Wilayah user belum ditentukan.');
        }

        $subKategori = SubKategori::with('kategori')->findOrFail($idSubKategori);
        if (!$subKategori->kategori || $subKategori->kategori->pendekatan !== $jenis) {
            return redirect()->route('lembar_kerja.index', ['jenis' => $jenis])
                ->with('error', 'Sub kategori tidak ditemukan untuk pendekatan ini.');
        }

        $request->validate([
            'komoditas_rows' => 'nullable|array',
        ]);

        $rows = $request->input('komoditas_rows', []);
        $inserted = 0;
        $komoditasHasSatuanColumn = Schema::hasColumn('komoditas', 'satuan');
        $komoditasHasWujudColumn = Schema::hasColumn('komoditas', 'wujud');

        foreach ($rows as $row) {
            $komoditasNama = $this->normalizeKomoditasNama($row['nama'] ?? '');
            if ($komoditasNama === '') {
                continue;
            }

            $satuanNama = trim((string) ($row['satuan'] ?? ''));
            $wujudNama = trim((string) ($row['wujud'] ?? ''));

            // Cek komoditas berdasarkan nama dan id_wilayah
            $komoditasQuery = DB::table('komoditas')
                ->where('id_wilayah', $user->id_wilayah)
                ->whereRaw('LOWER(nama) = ?', [$this->normalizeLower($komoditasNama)]);

            $komoditas = $komoditasQuery->first();

            if (!$komoditas) {
                $insertKomoditas = [
                    'nama' => $komoditasNama,
                    'id_wilayah' => $user->id_wilayah,
                    'aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($komoditasHasSatuanColumn) {
                    $insertKomoditas['satuan'] = $satuanNama !== '' ? $satuanNama : '';
                }
                if ($komoditasHasWujudColumn) {
                    $insertKomoditas['wujud'] = $wujudNama !== '' ? $wujudNama : '';
                }
                $komoditasId = DB::table('komoditas')->insertGetId($insertKomoditas);
            } else {
                $komoditasId = $komoditas->id;
                if ($satuanNama !== '' && $komoditasHasSatuanColumn) {
                    DB::table('komoditas')
                        ->where('id', $komoditasId)
                        ->update([
                            'satuan' => $satuanNama,
                            'updated_at' => now(),
                        ]);
                }
                if ($wujudNama !== '' && $komoditasHasWujudColumn) {
                    DB::table('komoditas')
                        ->where('id', $komoditasId)
                        ->update([
                            'wujud' => $wujudNama,
                            'updated_at' => now(),
                        ]);
                }
            }

            $inserted++;
        }

        if ($inserted === 0) {
            return redirect()->back()->with('error', 'Tidak ada komoditas yang disimpan.');
        }

        return redirect()->back()->with('success', 'Komoditas berhasil ditambahkan.');
    }

    private function toNumber($value, $max = '9999999999999999999999999.999999'): string
    {
        if ($value === null) {
            return '0';
        }

        $raw = is_string($value) ? trim($value) : (string) $value;

        if ($raw === '') {
            return '0';
        }

        // Smart dot/comma handling
        $dotCount = substr_count($raw, '.');
        $commaCount = substr_count($raw, ',');
        if ($commaCount > 0) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif ($dotCount > 1) {
            $raw = str_replace('.', '', $raw);
        }

        if (preg_match('/^([+-]?\d*\.?\d+)[eE]([+-]?\d+)$/', $raw, $matches)) {
            $mantissa = $matches[1];
            $exp = (int) $matches[2];
            $mantissa = ltrim($mantissa, '+');
            $negative = str_starts_with($mantissa, '-');
            if ($negative) {
                $mantissa = substr($mantissa, 1);
            }

            $parts = explode('.', $mantissa, 2);
            $intPart = $parts[0] ?? '0';
            $decPart = $parts[1] ?? '';
            $digits = $intPart . $decPart;
            $decLen = strlen($decPart);
            $shift = $exp - $decLen;

            if ($shift >= 0) {
                $digits = $digits . str_repeat('0', $shift);
                $result = $digits;
            } else {
                $pos = strlen($digits) + $shift;
                if ($pos <= 0) {
                    $result = '0.' . str_repeat('0', abs($pos)) . $digits;
                } else {
                    $result = substr($digits, 0, $pos) . '.' . substr($digits, $pos);
                }
            }

            $result = ltrim($result, '0');
            if ($result === '' || $result[0] === '.') {
                $result = '0' . $result;
            }
            if ($negative) {
                $result = '-' . $result;
            }

            return $result;
        }

        $raw = (string) $value;
        // Handle Indonesian format: dots are often thousand separators and comma is decimal.
        // But if there are multiple dots, they are definitely thousand separators.
        if (substr_count($raw, '.') > 1 || (strpos($raw, ',') !== false && strpos($raw, '.') !== false)) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else if (strpos($raw, ',') !== false && strpos($raw, '.') === false) {
            // Only comma, likely Indonesian decimal
            $raw = str_replace(',', '.', $raw);
        }

        $raw = preg_replace('/[^0-9\.\-]/', '', $raw);
        if ($raw === '' || $raw === '-' || $raw === '.') {
            return '0';
        }

        // Clamp value to prevent SQL Out of Range
        if (is_numeric($raw)) {
            $num = (float) $raw;
            $maxFloat = (float) $max;
            if ($num > $maxFloat) {
                return $max;
            }
            if ($num < -$maxFloat) {
                return '-' . $max;
            }
        }

        return $raw;
    }

    private function scaleJutaToRaw($value): string
    {
        // Max value for DECIMAL(50,15) is 35 digits before decimal point.
        // We set a very safe clamp for Juta Rp input.
        $num = $this->toNumber($value, '9999999999999999999999999.999999');
        if ($num === '0') {
            return '0';
        }

        if (function_exists('bcmul')) {
            return bcmul($num, '1000000', 6);
        }

        return (string) ((float) $num * 1000000);
    }

    private function normalizeKomoditasNama($value): string
    {
        $name = trim((string) $value);
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s+/', ' ', $name);

        if (function_exists('mb_convert_case')) {
            $lower = mb_convert_case($name, MB_CASE_LOWER, 'UTF-8');
            return mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($name));
    }

    private function normalizeLower($value): string
    {
        $text = trim((string) $value);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }
        return strtolower($text);
    }

    private function getProvinsiWilayahList($user)
    {
        $userWilayah = Wilayah::withNama()
            ->where('wilayah.id_wilayah', $user->id_wilayah)
            ->first();
        $provinsiId = $userWilayah?->id_provinsi ?? $user->id_provinsi ?? null;
        if (!$provinsiId) {
            return collect();
        }

        $kabkota = Wilayah::withNama()
            ->whereIn('wilayah.tipe', ['kabupaten', 'kota'])
            ->where('wilayah.id_provinsi', $provinsiId)
            ->orderBy('nama_wilayah')
            ->get();

        if ($userWilayah && !$kabkota->contains('id_wilayah', $userWilayah->id_wilayah)) {
            return collect([$userWilayah])->merge($kabkota);
        }

        return $kabkota;
    }

    private function resolveSelectedWilayahId(Request $request, $user, $wilayahList): int
    {
        if (!in_array($user->role, ['provinsi', 'provinsi_supervisor'], true)) {
            return (int) $user->id_wilayah;
        }

        $list = $wilayahList ?? $this->getProvinsiWilayahList($user);
        $requestedId = (int) $request->get('wilayah_id');
        if ($requestedId && $list->contains('id_wilayah', $requestedId)) {
            return $requestedId;
        }

        $userWilayahId = (int) $user->id_wilayah;
        if ($userWilayahId && $list->contains('id_wilayah', $userWilayahId)) {
            return $userWilayahId;
        }

        $firstId = (int) ($list->first()->id_wilayah ?? 0);
        return $firstId ?: $userWilayahId;
    }

    private function syncRekonTable($lembarKerjaId)
    {
        $lk = DB::table('lembar_kerja')->where('id', $lembarKerjaId)->first();
        if (!$lk) return;

        // Calculate Sum(nilai_ntb) for this LK
        $totalB = DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerjaId)
            ->where('tipe_pdrb', 'berlaku')
            ->sum('nilai_ntb');

        $totalK = DB::table('lembar_kerja_item')
            ->where('lembar_kerja_id', $lembarKerjaId)
            ->where('tipe_pdrb', 'konstan')
            ->sum('nilai_ntb');

        // Find the rekon entry
        $rekon = DB::table('rekon_lembar_kerja')->where('lembar_kerja_id', $lembarKerjaId)->first();
        if ($rekon) {
            $adjB = (float)($rekon->adj_berlaku ?? 0);
            $adjK = (float)($rekon->adj_konstan ?? 0);

            $finalB = (float)$totalB + $adjB;
            $finalK = (float)$totalK + $adjK;

            DB::table('rekon_lembar_kerja')->where('id', $rekon->id)->update([
                'total_berlaku' => $totalB,
                'total_konstan' => $totalK,
                'final_berlaku' => $finalB,
                'final_konstan' => $finalK,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Menyebarkan (propagate) komoditas baru ke semua tahun/periode lain.
     * Hanya membawa struktur (Nama, Wujud, Satuan), sisanya dikosongkan (0).
     */
    private function propagateNewRow($currentLkId, $komoditasId, $payload, $selectedWilayahId, $jenis, $subKategori)
    {
        $idField = ($jenis === 'lapangan_usaha') ? 'id_sub_kategori' : 'id_kategori';
        $idValue = ($jenis === 'lapangan_usaha') ? $subKategori->id_sub_kategori : $subKategori->id_kategori;

        // Cari semua ID lembar kerja lain
        $allLkIds = DB::table('lembar_kerja')
            ->where('wilayah_id', $selectedWilayahId)
            ->where('jenis', $jenis)
            ->where($idField, $idValue)
            ->where('id', '!=', $currentLkId)
            ->pluck('id');

        if ($allLkIds->isEmpty()) return;

        foreach ($allLkIds as $lkId) {
            foreach (['berlaku', 'konstan'] as $tipe) {
                $item = DB::table('lembar_kerja_item')
                    ->where('lembar_kerja_id', $lkId)
                    ->where('komoditas_id', $komoditasId)
                    ->where('tipe_pdrb', $tipe)
                    ->first();

                if (!$item) {
                    $propData = [
                        'lembar_kerja_id' => $lkId,
                        'komoditas_id' => $komoditasId,
                        'tipe_pdrb' => $tipe,
                        'wujud' => $payload['wujud'] ?? null,
                        'kuantum' => $payload['kuantum'] ?? 0,
                        'harga_produsen' => 0,
                        'nilai_output_utama' => 0,
                        'rasio_output_ikut' => $payload['rasio_output_ikut'] ?? 0,
                        'nilai_output_ikut' => 0,
                        'biaya_perawatan' => 0,
                        'biaya_sebelumnya' => 0,
                        'wip' => 0,
                        'output_adh' => 0,
                        'konsumsi_antara' => 0,
                        'nilai_ntb' => 0,
                        'rasio_konsumsi_antara' => $payload['rasio_konsumsi_antara'] ?? 0,
                        'deflator' => 100,
                        'mode_konstan' => 'deflasi',
                        'data_tambahan' => $payload['data_tambahan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DB::table('lembar_kerja_item')->insert($propData);
                } else {
                    // Update Rasio dan Wujud untuk item yang sudah ada
                    $updateData = [
                        'wujud' => $payload['wujud'] ?? $item->wujud,
                        'rasio_output_ikut' => $payload['rasio_output_ikut'] ?? $item->rasio_output_ikut,
                        'rasio_konsumsi_antara' => $payload['rasio_konsumsi_antara'] ?? $item->rasio_konsumsi_antara,
                        'updated_at' => now(),
                    ];
                    
                    // Jika data_tambahan kosong di target, isi dari sumber
                    if (empty($item->data_tambahan) && !empty($payload['data_tambahan'])) {
                        $updateData['data_tambahan'] = $payload['data_tambahan'];
                    }

                    DB::table('lembar_kerja_item')
                        ->where('id', $item->id)
                        ->update($updateData);
                }
            }
        }
    }
}
