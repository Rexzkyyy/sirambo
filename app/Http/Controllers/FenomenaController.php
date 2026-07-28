<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Schema;

use App\Models\Fenomena;
use App\Models\Kategori;
use App\Models\SubKategori;
use App\Models\Periode;
use App\Models\Tahun;
use App\Models\Wilayah;
use App\Models\KunciJawaban;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Cache;
use App\Models\HistoriFenomena;


class FenomenaController extends Controller
{

    public function index(Request $request)
    {
        $mode = $request->get('mode', 'tahunan');
        $tahun = $request->get('tahun', date('Y'));
        
        $pendekatan = $request->get('pendekatan');
        if (empty($pendekatan)) {
            $pendekatan = $request->get('jenis');
        }
        if (empty($pendekatan)) {
            $pendekatan = 'lapangan_usaha';
        }
        if (!in_array($pendekatan, ['lapangan_usaha', 'pengeluaran'])) {
            $pendekatan = 'lapangan_usaha';
        }
        
        $id_wilayah = $request->get('id_wilayah');
        $user = Auth::user();
        
        $isProvinsi = false;
        if (isset($user->level_user) && $user->level_user === 'provinsi') {
            $isProvinsi = true;
        } elseif (isset($user->level) && $user->level === 'provinsi') {
            $isProvinsi = true;
        } elseif (isset($user->role) && $user->role === 'provinsi') {
            $isProvinsi = true;
        }
        
        if (!$isProvinsi && isset($user->id_wilayah) && $user->id_wilayah) {
            $id_wilayah = $user->id_wilayah;
        }

        // CEK STATUS KUNCI
        $isUploadLocked = $this->isUploadLocked($tahun, $pendekatan, $mode);
        $isAnswerLocked = false;
        
        if ($id_wilayah) {
            try {
                $kunci = KunciJawaban::where('id_wilayah', $id_wilayah)
                    ->where('tahun', $tahun)
                    ->where('pendekatan', $pendekatan)
                    ->where('mode', $mode)
                    ->first();
                $isAnswerLocked = $kunci ? $kunci->is_locked : false;
            } catch (\Exception $e) {
                $isAnswerLocked = false;
            }
        }

        // isLockedActual untuk tampilan tombol/badge (fokus ke wilayah yang dipilih)
        $isLockedActual = $isAnswerLocked;

        // isLocked efektif untuk pemblokiran akses (kab/kot kena keduanya)
        $isLocked = $isUploadLocked || $isAnswerLocked;

        // Buka kunci secara virtual untuk user provinsi agar bisa mengisi/mengedit
        if ($isProvinsi) {
            $isLocked = false;
        }
        
        if ($mode === 'triwulanan') {
            return $this->indexTriwulanan($request, $id_wilayah, $isLocked, $isProvinsi, $pendekatan, $isUploadLocked, $isLockedActual);
        } else {
            return $this->indexTahunan($request, $id_wilayah, $isLocked, $isProvinsi, $pendekatan, $isUploadLocked, $isLockedActual);
        }
    }

    /**
     * Get wilayah list with dynamic column name
     */
    private function getWilayahList()
    {
        try {
            if (!Schema::hasTable('wilayah')) {
                return collect([]);
            }
            
            $columns = Schema::getColumnListing('wilayah');
            
            $nameColumn = null;
            $possibleNames = ['nama_wilayah', 'nama', 'nm_wilayah', 'nm_wil', 'wilayah', 'name', 'nm_wil'];
            
            foreach ($possibleNames as $col) {
                if (in_array($col, $columns)) {
                    $nameColumn = $col;
                    break;
                }
            }
            
            $query = Wilayah::whereIn('tipe', ['kabupaten', 'kota']);
            
            if ($nameColumn) {
                $wilayahList = $query->orderBy($nameColumn, 'asc')->get();
            } else {
                $wilayahList = $query->get()->sortBy('id_wilayah');
            }
            
            return $wilayahList;
            
        } catch (\Exception $e) {
            \Log::error('Error fetching wilayah list: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Check if user is provinsi
     */
    private function checkIsProvinsi($user)
    {
        if (isset($user->level_user) && $user->level_user === 'provinsi') {
            return true;
        } elseif (isset($user->level) && $user->level === 'provinsi') {
            return true;
        } elseif (isset($user->role) && $user->role === 'provinsi') {
            return true;
        }
        return false;
    }

    /**
     * Display tahunan mode with wilayah filter
     */
    private function indexTahunan(Request $request, $id_wilayah = null, $isLocked = false, $isProvinsi = false, $pendekatan = null, $isUploadLocked = false, $isLockedActual = false)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        if (empty($pendekatan)) {
            $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        }
        
        $mode = $request->get('mode', 'tahunan');
        
        // Ambil semua kategori dengan subkategori
        $kategoriData = Kategori::where('pendekatan', $pendekatan)
            ->with(['subKategori' => function($q) {
                $q->orderBy('id_sub_kategori');
            }])
            ->orderBy('id_kategori')
            ->get()
            ->filter(function($kategori) {
                return strpos($kategori->nama_kategori, 'Non Migas') === false && 
                    $kategori->nama_kategori != 'Produk Domestik Regional Bruto Non Migas';
            })
            ->map(function($kategori) {
                $kategori->subKategori = $kategori->subKategori->filter(function($sub) {
                    return strpos($sub->nama_sub_kategori, 'Non Migas') === false;
                });
                return $kategori;
            });
        
        // Ambil data fenomena
        $query = Fenomena::with(['kategori', 'subKategori'])
            ->where('tahun', $tahun)
            ->where('pendekatan', $pendekatan)
            ->whereNull('id_periode');
        
        if ($id_wilayah) {
            $query->where('id_wilayah', $id_wilayah);
        }
        
        $fenomena = $query->orderBy('id_kategori')
            ->orderBy('id_sub_kategori')
            ->get();
        
        // Buat map untuk data (termasuk CtoC untuk pengeluaran)
        $fenomenaMap = [];
        foreach ($fenomena as $f) {
            if ($f->id_sub_kategori) {
                $fenomenaMap[$f->id_kategori][$f->id_sub_kategori][$f->jenis_data] = [
                    'nilai' => $f->nilai,
                    'fenomena' => $f->fenomena,
                    'rating' => $f->rating
                ];
            } else {
                $fenomenaMap[$f->id_kategori][$f->jenis_data] = [
                    'nilai' => $f->nilai,
                    'fenomena' => $f->fenomena,
                    'rating' => $f->rating
                ];
            }
        }
        
        $tahunList = Tahun::orderBy('tahun', 'desc')->pluck('tahun');
        if ($tahunList->isEmpty()) {
            $tahunList = collect([$tahun]);
        }
        
        $periodeList = Periode::orderBy('id_periode')->get();
        
        // Ambil daftar wilayah untuk dropdown
        $wilayahList = collect();
        if ($isProvinsi) {
            $wilayahList = $this->getWilayahList();
        }
        


        return view('fenomena.index', compact(
            'kategoriData', 'fenomenaMap', 'tahun', 'tahunList', 
            'pendekatan', 'periodeList', 'wilayahList', 'id_wilayah', 
            'isProvinsi', 'isLocked', 'isLockedActual', 'isUploadLocked'
        ));
    }

    /**
     * Display triwulanan mode with wilayah filter
     */
    private function indexTriwulanan(Request $request, $id_wilayah = null, $isLocked = false, $isProvinsi = false, $pendekatan = null, $isUploadLocked = false, $isLockedActual = false)
    {
        $tahun = $request->get('tahun', date('Y'));
        
        if (empty($pendekatan)) {
            $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        }
        
        $triwulan = $request->get('triwulan', 1);
        
        // Ambil semua kategori dengan subkategori
        $kategoriData = Kategori::where('pendekatan', $pendekatan)
            ->with(['subKategori' => function($q) {
                $q->orderBy('id_sub_kategori');
            }])
            ->orderBy('id_kategori')
            ->get()
            ->filter(function($kategori) {
                return strpos($kategori->nama_kategori, 'Non Migas') === false && 
                    $kategori->nama_kategori != 'Produk Domestik Regional Bruto Non Migas';
            })
            ->map(function($kategori) {
                $kategori->subKategori = $kategori->subKategori->filter(function($sub) {
                    return strpos($sub->nama_sub_kategori, 'Non Migas') === false;
                });
                return $kategori;
            });
        
        $periodeList = Periode::orderBy('id_periode')->get();
        
        $tahunList = Tahun::orderBy('tahun', 'desc')->pluck('tahun');
        if ($tahunList->isEmpty()) {
            $tahunList = collect([$tahun]);
        }
        
        // Ambil data fenomena
        $query = Fenomena::where('tahun', $tahun)
            ->where('pendekatan', $pendekatan)
            ->whereNotNull('id_periode');
        
        if ($id_wilayah) {
            $query->where('id_wilayah', $id_wilayah);
        }
        
        $fenomena = $query->get();
        
        // Build map untuk data
        $fenomenaMap = [];
        foreach ($fenomena as $f) {
            if ($f->id_sub_kategori) {
                if (!isset($fenomenaMap[$f->id_kategori][$f->id_sub_kategori][$f->jenis_data])) {
                    $fenomenaMap[$f->id_kategori][$f->id_sub_kategori][$f->jenis_data] = [];
                }
                $fenomenaMap[$f->id_kategori][$f->id_sub_kategori][$f->jenis_data][$f->id_periode] = [
                    'nilai' => $f->nilai,
                    'fenomena' => $f->fenomena,
                    'rating' => $f->rating
                ];
            } else {
                if (!isset($fenomenaMap[$f->id_kategori][$f->jenis_data])) {
                    $fenomenaMap[$f->id_kategori][$f->jenis_data] = [];
                }
                $fenomenaMap[$f->id_kategori][$f->jenis_data][$f->id_periode] = [
                    'nilai' => $f->nilai,
                    'fenomena' => $f->fenomena,
                    'rating' => $f->rating
                ];
            }
        }
        
        // Ambil daftar wilayah untuk dropdown
        $wilayahList = collect();
        if ($isProvinsi) {
            $wilayahList = $this->getWilayahList();
        }
        


        return view('fenomena.index_triwulanan', compact(
            'kategoriData', 'fenomenaMap', 'tahun', 'triwulan', 'tahunList', 
            'pendekatan', 'periodeList', 'wilayahList', 'id_wilayah', 
            'isLocked', 'isProvinsi', 'isLockedActual', 'isUploadLocked'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
public function create(Request $request)
{
    $mode = $request->get('mode', 'tahunan');
    $tahun = $request->get('tahun', date('Y'));
    $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
    $id_wilayah = $request->get('id_wilayah');
    $tab = $request->get('tab', 'excel');
    
    $user = Auth::user();
    $isProvinsi = $this->checkIsProvinsi($user);
    
    if (!$isProvinsi && isset($user->id_wilayah) && $user->id_wilayah) {
        $id_wilayah = $user->id_wilayah;
    }
    
    // CEK KUNCI JAWABAN (per wilayah)
    $isAnswerLocked = false;
    if ($id_wilayah) {
        $isAnswerLocked = $this->isAnswerLocked($id_wilayah, $tahun, $pendekatan, $mode);
    }
    
    // CEK KUNCI UPLOAD (untuk semua wilayah)
    $isUploadLocked = $this->isUploadLocked($tahun, $pendekatan, $mode);
    
    $wilayahList = collect();
    if ($isProvinsi) {
        $wilayahList = $this->getWilayahList();
    }
    
    $tahunList = Tahun::orderBy('tahun', 'desc')->pluck('tahun');
    if ($tahunList->isEmpty()) {
        $tahunList = collect([date('Y'), date('Y')-1, date('Y')-2]);
    }
    
    return view('fenomena.create', compact(
        'mode', 'tahun', 'pendekatan', 'id_wilayah', 'tab', 
        'isProvinsi', 'wilayahList', 'tahunList', 
        'isAnswerLocked', 'isUploadLocked'  // kirim kedua status lock
    ));
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pendekatan' => 'required|in:lapangan_usaha,pengeluaran',
            'tahun' => 'required|integer|min:2010|max:2030',
            'mode' => 'required|in:tahunan,triwulanan',
            'action' => 'required|in:excel,manual',
            'id_wilayah' => 'required|integer|exists:wilayah,id_wilayah'
        ]);
        
        $user = Auth::user();
        $isProvinsi = $this->checkIsProvinsi($user);
        
        // CEK KUNCI UPLOAD (untuk semua wilayah) - PRIORITAS
        $isUploadLocked = $this->isUploadLocked($request->tahun, $request->pendekatan, $request->mode);
        if ($isUploadLocked && !$isProvinsi) {
            return redirect()->back()->with('error', 'Upload data sedang dikunci oleh provinsi. Silakan hubungi administrator provinsi.');
        }
        
        // CEK KUNCI JAWABAN (per wilayah)
        $isAnswerLocked = $this->isAnswerLocked($request->id_wilayah, $request->tahun, $request->pendekatan, $request->mode);
        if ($isAnswerLocked && !$isProvinsi) {
            return redirect()->back()->with('error', 'Jawaban untuk wilayah ini sudah dikunci. Tidak dapat melakukan perubahan data.');
        }
        
        if ($request->action == 'excel') {
        return $this->importFromExcel($request);
    } else {
        return $this->saveManualData($request);
    }
}

    /**
     * Save manual input data
     */
    private function saveManualData(Request $request)
    {
        $request->validate([
            'manual_data' => 'required|json',
            'id_wilayah' => 'required|integer|exists:wilayah,id_wilayah'
        ]);
        
        $manualData = json_decode($request->manual_data, true);
        $pendekatan = $request->pendekatan;
        $tahun = $request->tahun;
        $mode = $request->mode;
        $id_wilayah = $request->id_wilayah;
        
        $user = Auth::user();
        $isProvinsi = $this->checkIsProvinsi($user);
        
        if (empty($manualData)) {
            return redirect()->back()->with('error', 'Data manual kosong');
        }
        
        DB::beginTransaction();
        
        try {
            $updatedCount = 0;
            $insertedCount = 0;
            
            foreach ($manualData as $data) {
                if (empty($data['id_kategori'])) {
                    continue;
                }
                
                $idKategori = $data['id_kategori'];
                $idSubKategori = $data['id_sub_kategori'] ?? null;
                $idPeriode = $data['id_periode'] ?? null;
                $jenisData = $data['jenis_data'] ?? ($mode === 'tahunan' ? 'Pertumbuhan' : 'q-to-q');
                
                // Rating hanya untuk provinsi
                $rating = null;
                if ($isProvinsi) {
                    $rating = $data['rating'] ?? null;
                } else {
                    $existing = Fenomena::where('id_kategori', $idKategori)
                        ->where('id_sub_kategori', $idSubKategori)
                        ->where('id_periode', $idPeriode)
                        ->where('jenis_data', $jenisData)
                        ->where('tahun', $tahun)
                        ->where('pendekatan', $pendekatan)
                        ->where('id_wilayah', $id_wilayah)
                        ->first();
                    if ($existing) {
                        $rating = $existing->rating;
                    }
                }
                
                // Validasi mode
                if ($mode === 'tahunan' && $idPeriode !== null) continue;
                if ($mode === 'triwulanan' && $idPeriode === null) continue;
                
                $kategori = Kategori::find($idKategori);
                if (!$kategori) continue;
                
                $namaKategori = $kategori->nama_kategori;
                $kodeKategori = $kategori->kode_kategori;
                
                $namaSubKategori = null;
                if ($idSubKategori) {
                    $sub = SubKategori::find($idSubKategori);
                    if ($sub) $namaSubKategori = $sub->nama_sub_kategori;
                }
                
                $level = $namaSubKategori ? 2 : 1;
                $nilai = isset($data['nilai']) ? $this->parseNilai($data['nilai']) : null;
                
                $existingData = Fenomena::where('id_kategori', $idKategori)
                    ->where('id_sub_kategori', $idSubKategori)
                    ->where('id_periode', $idPeriode)
                    ->where('jenis_data', $jenisData)
                    ->where('tahun', $tahun)
                    ->where('pendekatan', $pendekatan)
                    ->where('id_wilayah', $id_wilayah)
                    ->first();
                
                if ($existingData) {
                    $updateData = [
                        'kode_kategori' => $kodeKategori,
                        'nama_kategori' => $namaKategori,
                        'nama_sub_kategori' => $namaSubKategori,
                        'nilai' => $nilai,
                        'fenomena' => $data['fenomena'] ?? null,
                        'level' => $level,
                        'updated_by' => Auth::id(),
                        'updated_at' => now()
                    ];
                    if ($isProvinsi && $rating !== null) {
                        $updateData['rating'] = $rating;
                    }
                    $existingData->update($updateData);
                    $updatedCount++;
                } else {
                    $createData = [
                        'id_kategori' => $idKategori,
                        'id_sub_kategori' => $idSubKategori,
                        'id_periode' => $idPeriode,
                        'id_wilayah' => $id_wilayah,
                        'kode_kategori' => $kodeKategori,
                        'nama_kategori' => $namaKategori,
                        'nama_sub_kategori' => $namaSubKategori,
                        'jenis_data' => $jenisData,
                        'tahun' => $tahun,
                        'nilai' => $nilai,
                        'fenomena' => $data['fenomena'] ?? null,
                        'level' => $level,
                        'pendekatan' => $pendekatan,
                        'created_by' => Auth::id(),
                        'created_at' => now()
                    ];
                    if ($isProvinsi && $rating !== null) {
                        $createData['rating'] = $rating;
                    }
                    Fenomena::create($createData);
                    $insertedCount++;
                }
            }
            
            DB::commit();
            
            return redirect()->route('fenomena.index', [
                'tahun' => $tahun, 'pendekatan' => $pendekatan, 'mode' => $mode, 'id_wilayah' => $id_wilayah
            ])->with('success', "Data berhasil disimpan. Insert: $insertedCount, Update: $updatedCount");
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saveManualData: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Import data from Excel file
     */
private function importFromExcel(Request $request)
{
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls|max:5120',
        'id_wilayah' => 'required|integer|exists:wilayah,id_wilayah'
    ]);
    
    $file = $request->file('excel_file');
    $pendekatan = $request->pendekatan;
    $tahun = $request->tahun;
    $mode = $request->mode;
    $id_wilayah = $request->id_wilayah;
    
    // Ambil nama wilayah
    $wilayah = Wilayah::find($id_wilayah);
    $namaWilayah = $this->getWilayahNama($wilayah);
    $namaFile = $file->getClientOriginalName();
    $ukuranFile = $this->formatFileSize($file->getSize());
    
    try {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Deteksi struktur file untuk triwulanan (baru dengan 13 kolom)
        $firstRow = $rows[0] ?? [];
        $secondRow = $rows[1] ?? [];
        $isTriwulananFile = false;
        
        // Cek baris pertama untuk kata TRIWULAN
        foreach ($firstRow as $cell) {
            if (is_string($cell) && strpos($cell, 'TRIWULAN') !== false) {
                $isTriwulananFile = true;
                break;
            }
        }
        
        // Cek juga baris kedua untuk struktur baru (ada kolom Jenis Data)
        if (!$isTriwulananFile && !empty($secondRow)) {
            $subHeaderText = implode(' ', array_slice($secondRow, 0, 10));
            if (strpos($subHeaderText, 'Jenis Data') !== false && strpos($subHeaderText, 'Nilai') !== false) {
                $isTriwulananFile = true;
            }
        }
        
        // Cek jumlah kolom untuk menentukan struktur
        $totalColumns = count($rows[0] ?? []);
        $isNewTriwulananStructure = ($totalColumns >= 13 && $mode === 'triwulanan');
        
        if ($isTriwulananFile && $mode === 'tahunan') {
            $this->saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, 0, 'failed', 'File Excel terdeteksi sebagai data TRIWULANAN, tetapi Anda memilih mode TAHUNAN.');
            return redirect()->back()->with('error', 'File Excel terdeteksi sebagai data TRIWULANAN, tetapi Anda memilih mode TAHUNAN.');
        }
        
        if (!$isTriwulananFile && $mode === 'triwulanan') {
            $this->saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, 0, 'failed', 'File Excel terdeteksi sebagai data TAHUNAN, tetapi Anda memilih mode TRIWULANAN.');
            return redirect()->back()->with('error', 'File Excel terdeteksi sebagai data TAHUNAN, tetapi Anda memilih mode TRIWULANAN.');
        }
        
        // Hapus header berdasarkan mode
        if ($mode === 'triwulanan') {
            // Untuk struktur baru, hapus 2 baris header
            array_shift($rows); // Hapus baris 1 (header utama)
            array_shift($rows); // Hapus baris 2 (sub header)
        } else {
            array_shift($rows); // Hapus baris 1 untuk tahunan
        }
        
        DB::beginTransaction();
        
        $imported = 0;
        $errors = [];
        $processedItems = [];
        
        $kategoriList = Kategori::where('pendekatan', $pendekatan)->get();
        $kategoriByName = [];
        foreach ($kategoriList as $k) {
            $kategoriByName[$k->nama_kategori] = $k;
        }
        
        $subKategoriList = SubKategori::with('kategori')->get();
        $subKategoriByNama = [];
        foreach ($subKategoriList as $sk) {
            $key = strtolower(trim($sk->nama_sub_kategori));
            $subKategoriByNama[$key] = $sk;
        }
        
        $kategoriUtama = array_keys($kategoriByName);
        
        if ($mode === 'tahunan') {
            $result = $this->processExcelTahunan($rows, $tahun, $pendekatan, $id_wilayah, $kategoriUtama, $kategoriByName, $subKategoriByNama, $errors, $processedItems);
        } else {
            // Kirimkan parameter tambahan untuk mengetahui struktur baru
            $result = $this->processExcelTriwulanan($rows, $tahun, $pendekatan, $id_wilayah, $kategoriUtama, $kategoriByName, $subKategoriByNama, $errors, $processedItems, $isNewTriwulananStructure);
        }
        
        $imported = $result['imported'];
        
        DB::commit();
        
        // Simpan histori sukses
        $keterangan = "Berhasil mengimport " . $imported . " data";
        if (!empty($errors)) {
            $keterangan .= " dengan " . count($errors) . " error";
            $errorMessage = implode("; ", array_slice($errors, 0, 10));
            $this->saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, $imported, 'partial', $errorMessage, $keterangan);
        } else {
            $this->saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, $imported, 'success', null, $keterangan);
        }
        
        $message = "Berhasil mengimport " . $imported . " data";
        
        if (!empty($errors)) {
            return redirect()->route('fenomena.index', [
                'tahun' => $tahun, 'pendekatan' => $pendekatan, 'mode' => $mode, 'id_wilayah' => $id_wilayah
            ])->with('warning', $message . ' dengan ' . count($errors) . ' error')
              ->with('errors', $errors);
        }
        
        return redirect()->route('fenomena.index', [
            'tahun' => $tahun, 'pendekatan' => $pendekatan, 'mode' => $mode, 'id_wilayah' => $id_wilayah
        ])->with('success', $message);
        
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error: ' . $e->getMessage());
        \Log::error('Trace: ' . $e->getTraceAsString());
        
        // Simpan histori gagal
        $this->saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, 0, 'failed', $e->getMessage(), 'Gagal membaca file Excel');
        
        return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
    }
}

/**
 * Format file size
 */
private function formatFileSize($bytes)
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

    /**
     * Process Excel for tahunan mode (with CtoC support)
     */
/**
 * Process Excel for tahunan mode (with CtoC support)
 */
private function processExcelTahunan($rows, $tahun, $pendekatan, $id_wilayah, $kategoriUtama, $kategoriByName, $subKategoriByNama, &$errors, &$processedItems)
{
    $imported = 0;
    $i = 0;
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    
    // Jenis data untuk tahunan
    $jenisDataList = ['Pertumbuhan', 'Laju Implisit'];
    if ($isPengeluaran) {
        $jenisDataList[] = 'CtoC';
    }
    
    while ($i < count($rows)) {
        $row = $rows[$i];
        if (empty(array_filter($row))) {
            $i++;
            continue;
        }
        
        $rowNumber = $i + 2;
        
        try {
            $namaItemWithJenis = isset($row[0]) ? trim($row[0]) : '';
            if (empty($namaItemWithJenis)) {
                $i++;
                continue;
            }
            
            // Parse nama item dan jenis data dari format "Nama Item (JenisData)"
            preg_match('/^(.*?)\s*\(([^)]+)\)$/', $namaItemWithJenis, $matches);
            
            if (count($matches) >= 3) {
                $cleanNamaItem = trim($matches[1]);
                $jenisData = trim($matches[2]);
                
                // Validasi jenis data
                if (!in_array($jenisData, $jenisDataList)) {
                    $errors[] = "Baris {$rowNumber}: Jenis data '{$jenisData}' tidak dikenal";
                    $i++;
                    continue;
                }
                
                // Cek apakah ini kategori utama
                $isMainCategory = in_array($cleanNamaItem, $kategoriUtama);
                
                if ($isMainCategory) {
                    $kategori = $kategoriByName[$cleanNamaItem] ?? null;
                    if (!$kategori) {
                        $errors[] = "Baris {$rowNumber}: Kategori '{$cleanNamaItem}' tidak ditemukan";
                        $i++;
                        continue;
                    }
                    
                    $nilai = isset($row[2]) ? $row[2] : null;
                    $fenomena = isset($row[3]) ? $row[3] : null;
                    $rating = isset($row[4]) ? $row[4] : null;
                    
                    $key = "kat_{$kategori->id_kategori}_{$jenisData}";
                    $processedItems[] = $key;
                    
                    // PERUBAHAN: Hanya simpan jika ada nilai ATAU ada fenomena
                    if (($nilai !== null && $nilai !== '') || ($fenomena !== null && $fenomena !== '')) {
                        // Cek apakah data sudah ada
                        $existingData = Fenomena::where([
                            'id_kategori' => $kategori->id_kategori,
                            'id_sub_kategori' => null,
                            'tahun' => $tahun,
                            'pendekatan' => $pendekatan,
                            'jenis_data' => $jenisData,
                            'id_periode' => null,
                            'id_wilayah' => $id_wilayah
                        ])->first();
                        
                        $dataToSave = [
                            'kode_kategori' => $kategori->kode_kategori,
                            'nama_kategori' => $kategori->nama_kategori,
                            'nilai' => $this->parseNilai($nilai),
                            'fenomena' => $fenomena,
                            'rating' => $this->parseRating($rating),
                            'level' => 1,
                            'updated_by' => Auth::id(),
                            'updated_at' => now()
                        ];
                        
                        if ($existingData) {
                            $existingData->update($dataToSave);
                        } else {
                            $dataToSave['created_by'] = Auth::id();
                            $dataToSave['created_at'] = now();
                            $dataToSave['id_kategori'] = $kategori->id_kategori;
                            $dataToSave['id_sub_kategori'] = null;
                            $dataToSave['tahun'] = $tahun;
                            $dataToSave['pendekatan'] = $pendekatan;
                            $dataToSave['jenis_data'] = $jenisData;
                            $dataToSave['id_periode'] = null;
                            $dataToSave['id_wilayah'] = $id_wilayah;
                            Fenomena::create($dataToSave);
                        }
                        $imported++;
                    }
                    
                    $i++;
                    
                } else {
                    // Subkategori
                    $searchKey = strtolower(trim($cleanNamaItem));
                    $foundSub = $subKategoriByNama[$searchKey] ?? null;
                    
                    if (!$foundSub) {
                        foreach ($subKategoriByNama as $key => $sub) {
                            if (strpos($key, $searchKey) !== false || strpos($searchKey, $key) !== false) {
                                $foundSub = $sub;
                                break;
                            }
                        }
                    }
                    
                    if ($foundSub) {
                        $kategori = $foundSub->kategori;
                        if ($kategori) {
                            $nilai = isset($row[2]) ? $row[2] : null;
                            $fenomena = isset($row[3]) ? $row[3] : null;
                            $rating = isset($row[4]) ? $row[4] : null;
                            
                            $key = "sub_{$foundSub->id_sub_kategori}_{$jenisData}";
                            $processedItems[] = $key;
                            
                            // PERUBAHAN: Hanya simpan jika ada nilai ATAU ada fenomena
                            if (($nilai !== null && $nilai !== '') || ($fenomena !== null && $fenomena !== '')) {
                                $existingData = Fenomena::where([
                                    'id_kategori' => $kategori->id_kategori,
                                    'id_sub_kategori' => $foundSub->id_sub_kategori,
                                    'tahun' => $tahun,
                                    'pendekatan' => $pendekatan,
                                    'jenis_data' => $jenisData,
                                    'id_periode' => null,
                                    'id_wilayah' => $id_wilayah
                                ])->first();
                                
                                $dataToSave = [
                                    'kode_kategori' => $kategori->kode_kategori,
                                    'nama_kategori' => $kategori->nama_kategori,
                                    'nama_sub_kategori' => $foundSub->nama_sub_kategori,
                                    'nilai' => $this->parseNilai($nilai),
                                    'fenomena' => $fenomena,
                                    'rating' => $this->parseRating($rating),
                                    'level' => 2,
                                    'updated_by' => Auth::id(),
                                    'updated_at' => now()
                                ];
                                
                                if ($existingData) {
                                    $existingData->update($dataToSave);
                                } else {
                                    $dataToSave['created_by'] = Auth::id();
                                    $dataToSave['created_at'] = now();
                                    $dataToSave['id_kategori'] = $kategori->id_kategori;
                                    $dataToSave['id_sub_kategori'] = $foundSub->id_sub_kategori;
                                    $dataToSave['tahun'] = $tahun;
                                    $dataToSave['pendekatan'] = $pendekatan;
                                    $dataToSave['jenis_data'] = $jenisData;
                                    $dataToSave['id_periode'] = null;
                                    $dataToSave['id_wilayah'] = $id_wilayah;
                                    Fenomena::create($dataToSave);
                                }
                                $imported++;
                            }
                            
                            $i++;
                        } else {
                            $errors[] = "Baris {$rowNumber}: Kategori untuk subkategori '{$cleanNamaItem}' tidak ditemukan";
                            $i++;
                        }
                    } else {
                        $errors[] = "Baris {$rowNumber}: Subkategori '{$cleanNamaItem}' tidak ditemukan";
                        $i++;
                    }
                }
            } else {
                $errors[] = "Baris {$rowNumber}: Format nama item tidak valid. Harus 'Nama Item (JenisData)'";
                $i++;
            }
            
        } catch (\Exception $e) {
            $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            $i++;
        }
    }
    
    return ['imported' => $imported, 'processedItems' => $processedItems];
}

    /**
     * Process Excel for triwulanan mode
     */
/**
 * Process Excel for triwulanan mode (new structure with separate Jenis Data column)
 */
/**
 * Process Excel for triwulanan mode (support both old and new structure)
 */
/**
 * Process Excel for triwulanan mode (support both old and new structure)
 */
private function processExcelTriwulanan($rows, $tahun, $pendekatan, $id_wilayah, $kategoriUtama, $kategoriByName, $subKategoriByNama, &$errors, &$processedItems, $isNewStructure = true)
{
    $imported = 0;
    $i = 0;
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    
    $jenisDataList = ['q-to-q', 'y-on-y'];
    if ($isPengeluaran) {
        $jenisDataList[] = 'c-to-c';
    }
    
    // Mapping label ke internal
    $jenisDataMapping = [
        'QtoQ' => 'q-to-q',
        'YonY' => 'y-on-y',
        'CtoC' => 'c-to-c',
        'q-to-q' => 'q-to-q',
        'y-on-y' => 'y-on-y',
        'c-to-c' => 'c-to-c',
        'Q-Q' => 'q-to-q',
        'Y-Y' => 'y-on-y',
        'C-C' => 'c-to-c',
    ];
    
    // Skip jika masih ada header (sudah dihapus di importFromExcel)
    while ($i < count($rows) && empty(array_filter($rows[$i]))) {
        $i++;
    }
    
    while ($i < count($rows)) {
        $row = $rows[$i];
        
        // Skip baris kosong
        if (empty(array_filter($row))) {
            $i++;
            continue;
        }
        
        $rowNumber = $i + 3; // +3 karena 2 baris header sudah dihapus, tapi untuk display error
        
        try {
            if ($isNewStructure) {
                // ===========================================
                // STRUKTUR BARU (dengan kolom Jenis Data terpisah)
                // Kolom A: Nama Kategori/Subkategori
                // Kolom B, E, H, K: Jenis Data
                // Kolom C, F, I, L: Nilai (%)
                // Kolom D, G, J, M: Fenomena
                // ===========================================
                
                $namaItem = isset($row[0]) ? trim($row[0]) : '';
                if (empty($namaItem)) {
                    $i++;
                    continue;
                }
                
                $isMainCategory = in_array($namaItem, $kategoriUtama);
                
                // Proses untuk 4 triwulan
                for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
                    $colJenisData = 1 + (($triwulan - 1) * 3);  // B=1, E=4, H=7, K=10
                    $colNilai = 2 + (($triwulan - 1) * 3);       // C=2, F=5, I=8, L=11
                    $colFenomena = 3 + (($triwulan - 1) * 3);     // D=3, G=6, J=9, M=12
                    
                    // Ambil nilai dari kolom yang sesuai
                    $jenisDataLabel = isset($row[$colJenisData]) ? trim($row[$colJenisData]) : '';
                    $nilai = isset($row[$colNilai]) ? $row[$colNilai] : null;
                    $fenomena = isset($row[$colFenomena]) ? $row[$colFenomena] : null;
                    
                    // PERUBAHAN: Skip jika nilai DAN fenomena kosong
                    if (($nilai === null || $nilai === '') && ($fenomena === null || $fenomena === '')) {
                        continue;
                    }
                    
                    // Mapping jenis data
                    $jenisData = $jenisDataMapping[$jenisDataLabel] ?? null;
                    if (!$jenisData) {
                        $errors[] = "Baris {$rowNumber}, Triwulan {$triwulan}: Jenis data '{$jenisDataLabel}' tidak dikenal";
                        continue;
                    }
                    
                    // Cari kategori atau subkategori
                    if ($isMainCategory) {
                        $kategori = $kategoriByName[$namaItem] ?? null;
                        if (!$kategori) {
                            $errors[] = "Baris {$rowNumber}: Kategori '{$namaItem}' tidak ditemukan";
                            continue;
                        }
                        
                        $key = "kat_{$kategori->id_kategori}_{$jenisData}_tw{$triwulan}";
                        
                        if (!in_array($key, $processedItems)) {
                            $processedItems[] = $key;
                            
                            // Cek apakah data sudah ada
                            $existingData = Fenomena::where([
                                'id_kategori' => $kategori->id_kategori,
                                'id_sub_kategori' => null,
                                'tahun' => $tahun,
                                'pendekatan' => $pendekatan,
                                'jenis_data' => $jenisData,
                                'id_periode' => $triwulan,
                                'id_wilayah' => $id_wilayah
                            ])->first();
                            
                            $dataToSave = [
                                'kode_kategori' => $kategori->kode_kategori,
                                'nama_kategori' => $kategori->nama_kategori,
                                'nilai' => $this->parseNilai($nilai),
                                'fenomena' => $fenomena,
                                'level' => 1,
                                'updated_by' => Auth::id(),
                                'updated_at' => now()
                            ];
                            
                            if ($existingData) {
                                $existingData->update($dataToSave);
                            } else {
                                $dataToSave['created_by'] = Auth::id();
                                $dataToSave['created_at'] = now();
                                $dataToSave['id_kategori'] = $kategori->id_kategori;
                                $dataToSave['id_sub_kategori'] = null;
                                $dataToSave['tahun'] = $tahun;
                                $dataToSave['pendekatan'] = $pendekatan;
                                $dataToSave['jenis_data'] = $jenisData;
                                $dataToSave['id_periode'] = $triwulan;
                                $dataToSave['id_wilayah'] = $id_wilayah;
                                Fenomena::create($dataToSave);
                            }
                            $imported++;
                        }
                    } else {
                        // Cari subkategori
                        $searchKey = strtolower(trim($namaItem));
                        $foundSub = $subKategoriByNama[$searchKey] ?? null;
                        
                        if (!$foundSub) {
                            foreach ($subKategoriByNama as $key => $sub) {
                                if (strpos($key, $searchKey) !== false || strpos($searchKey, $key) !== false) {
                                    $foundSub = $sub;
                                    break;
                                }
                            }
                        }
                        
                        if ($foundSub) {
                            $kategori = $foundSub->kategori;
                            if ($kategori) {
                                $key = "sub_{$foundSub->id_sub_kategori}_{$jenisData}_tw{$triwulan}";
                                
                                if (!in_array($key, $processedItems)) {
                                    $processedItems[] = $key;
                                    
                                    $existingData = Fenomena::where([
                                        'id_kategori' => $kategori->id_kategori,
                                        'id_sub_kategori' => $foundSub->id_sub_kategori,
                                        'tahun' => $tahun,
                                        'pendekatan' => $pendekatan,
                                        'jenis_data' => $jenisData,
                                        'id_periode' => $triwulan,
                                        'id_wilayah' => $id_wilayah
                                    ])->first();
                                    
                                    $dataToSave = [
                                        'kode_kategori' => $kategori->kode_kategori,
                                        'nama_kategori' => $kategori->nama_kategori,
                                        'nama_sub_kategori' => $foundSub->nama_sub_kategori,
                                        'nilai' => $this->parseNilai($nilai),
                                        'fenomena' => $fenomena,
                                        'level' => 2,
                                        'updated_by' => Auth::id(),
                                        'updated_at' => now()
                                    ];
                                    
                                    if ($existingData) {
                                        $existingData->update($dataToSave);
                                    } else {
                                        $dataToSave['created_by'] = Auth::id();
                                        $dataToSave['created_at'] = now();
                                        $dataToSave['id_kategori'] = $kategori->id_kategori;
                                        $dataToSave['id_sub_kategori'] = $foundSub->id_sub_kategori;
                                        $dataToSave['tahun'] = $tahun;
                                        $dataToSave['pendekatan'] = $pendekatan;
                                        $dataToSave['jenis_data'] = $jenisData;
                                        $dataToSave['id_periode'] = $triwulan;
                                        $dataToSave['id_wilayah'] = $id_wilayah;
                                        Fenomena::create($dataToSave);
                                    }
                                    $imported++;
                                }
                            } else {
                                $errors[] = "Baris {$rowNumber}: Kategori untuk subkategori '{$namaItem}' tidak ditemukan";
                            }
                        } else {
                            $errors[] = "Baris {$rowNumber}: Subkategori '{$namaItem}' tidak ditemukan di database";
                        }
                    }
                }
                
            } else {
                // ===========================================
                // STRUKTUR LAMA (format "Nama Item (JenisData)" di kolom A)
                // ===========================================
                $namaItemWithJenis = isset($row[0]) ? trim($row[0]) : '';
                if (empty($namaItemWithJenis)) {
                    $i++;
                    continue;
                }
                
                // Parse nama item dan jenis data dari format "Nama Item (JenisData)"
                preg_match('/^(.*?)\s*\(([^)]+)\)$/', $namaItemWithJenis, $matches);
                
                if (count($matches) >= 3) {
                    $cleanNamaItem = trim($matches[1]);
                    $jenisDataLabel = trim($matches[2]);
                    
                    $jenisData = $jenisDataMapping[$jenisDataLabel] ?? null;
                    if (!$jenisData) {
                        $errors[] = "Baris {$rowNumber}: Jenis data '{$jenisDataLabel}' tidak dikenal";
                        $i++;
                        continue;
                    }
                    
                    $isMainCategory = in_array($cleanNamaItem, $kategoriUtama);
                    
                    if ($isMainCategory) {
                        $kategori = $kategoriByName[$cleanNamaItem] ?? null;
                        if (!$kategori) {
                            $errors[] = "Baris {$rowNumber}: Kategori '{$cleanNamaItem}' tidak ditemukan";
                            $i++;
                            continue;
                        }
                        
                        // Proses 4 triwulan
                        for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
                            $colNilai = 1 + (($triwulan - 1) * 2);
                            $colFenomena = 2 + (($triwulan - 1) * 2);
                            
                            $nilai = isset($row[$colNilai]) ? $row[$colNilai] : null;
                            $fenomena = isset($row[$colFenomena]) ? $row[$colFenomena] : null;
                            
                            // PERUBAHAN: Skip jika nilai DAN fenomena kosong
                            if (($nilai === null || $nilai === '') && ($fenomena === null || $fenomena === '')) {
                                continue;
                            }
                            
                            $key = "kat_{$kategori->id_kategori}_{$jenisData}_tw{$triwulan}";
                            
                            if (!in_array($key, $processedItems)) {
                                $processedItems[] = $key;
                                
                                $existingData = Fenomena::where([
                                    'id_kategori' => $kategori->id_kategori,
                                    'id_sub_kategori' => null,
                                    'tahun' => $tahun,
                                    'pendekatan' => $pendekatan,
                                    'jenis_data' => $jenisData,
                                    'id_periode' => $triwulan,
                                    'id_wilayah' => $id_wilayah
                                ])->first();
                                
                                $dataToSave = [
                                    'kode_kategori' => $kategori->kode_kategori,
                                    'nama_kategori' => $kategori->nama_kategori,
                                    'nilai' => $this->parseNilai($nilai),
                                    'fenomena' => $fenomena,
                                    'level' => 1,
                                    'updated_by' => Auth::id(),
                                    'updated_at' => now()
                                ];
                                
                                if ($existingData) {
                                    $existingData->update($dataToSave);
                                } else {
                                    $dataToSave['created_by'] = Auth::id();
                                    $dataToSave['created_at'] = now();
                                    $dataToSave['id_kategori'] = $kategori->id_kategori;
                                    $dataToSave['id_sub_kategori'] = null;
                                    $dataToSave['tahun'] = $tahun;
                                    $dataToSave['pendekatan'] = $pendekatan;
                                    $dataToSave['jenis_data'] = $jenisData;
                                    $dataToSave['id_periode'] = $triwulan;
                                    $dataToSave['id_wilayah'] = $id_wilayah;
                                    Fenomena::create($dataToSave);
                                }
                                $imported++;
                            }
                        }
                    } else {
                        // Cari subkategori
                        $searchKey = strtolower(trim($cleanNamaItem));
                        $foundSub = $subKategoriByNama[$searchKey] ?? null;
                        
                        if (!$foundSub) {
                            foreach ($subKategoriByNama as $key => $sub) {
                                if (strpos($key, $searchKey) !== false || strpos($searchKey, $key) !== false) {
                                    $foundSub = $sub;
                                    break;
                                }
                            }
                        }
                        
                        if ($foundSub) {
                            $kategori = $foundSub->kategori;
                            if ($kategori) {
                                for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
                                    $colNilai = 1 + (($triwulan - 1) * 2);
                                    $colFenomena = 2 + (($triwulan - 1) * 2);
                                    
                                    $nilai = isset($row[$colNilai]) ? $row[$colNilai] : null;
                                    $fenomena = isset($row[$colFenomena]) ? $row[$colFenomena] : null;
                                    
                                    // PERUBAHAN: Skip jika nilai DAN fenomena kosong
                                    if (($nilai === null || $nilai === '') && ($fenomena === null || $fenomena === '')) {
                                        continue;
                                    }
                                    
                                    $key = "sub_{$foundSub->id_sub_kategori}_{$jenisData}_tw{$triwulan}";
                                    
                                    if (!in_array($key, $processedItems)) {
                                        $processedItems[] = $key;
                                        
                                        $existingData = Fenomena::where([
                                            'id_kategori' => $kategori->id_kategori,
                                            'id_sub_kategori' => $foundSub->id_sub_kategori,
                                            'tahun' => $tahun,
                                            'pendekatan' => $pendekatan,
                                            'jenis_data' => $jenisData,
                                            'id_periode' => $triwulan,
                                            'id_wilayah' => $id_wilayah
                                        ])->first();
                                        
                                        $dataToSave = [
                                            'kode_kategori' => $kategori->kode_kategori,
                                            'nama_kategori' => $kategori->nama_kategori,
                                            'nama_sub_kategori' => $foundSub->nama_sub_kategori,
                                            'nilai' => $this->parseNilai($nilai),
                                            'fenomena' => $fenomena,
                                            'level' => 2,
                                            'updated_by' => Auth::id(),
                                            'updated_at' => now()
                                        ];
                                        
                                        if ($existingData) {
                                            $existingData->update($dataToSave);
                                        } else {
                                            $dataToSave['created_by'] = Auth::id();
                                            $dataToSave['created_at'] = now();
                                            $dataToSave['id_kategori'] = $kategori->id_kategori;
                                            $dataToSave['id_sub_kategori'] = $foundSub->id_sub_kategori;
                                            $dataToSave['tahun'] = $tahun;
                                            $dataToSave['pendekatan'] = $pendekatan;
                                            $dataToSave['jenis_data'] = $jenisData;
                                            $dataToSave['id_periode'] = $triwulan;
                                            $dataToSave['id_wilayah'] = $id_wilayah;
                                            Fenomena::create($dataToSave);
                                        }
                                        $imported++;
                                    }
                                }
                            } else {
                                $errors[] = "Baris {$rowNumber}: Kategori untuk subkategori '{$cleanNamaItem}' tidak ditemukan";
                            }
                        } else {
                            $errors[] = "Baris {$rowNumber}: Subkategori '{$cleanNamaItem}' tidak ditemukan";
                        }
                    }
                } else {
                    $errors[] = "Baris {$rowNumber}: Format nama item tidak valid. Harus 'Nama Item (JenisData)'";
                }
            }
            
            $i++;
            
        } catch (\Exception $e) {
            $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            \Log::error("Error processing row {$rowNumber}: " . $e->getMessage());
            $i++;
        }
    }
    
    return ['imported' => $imported, 'processedItems' => $processedItems];
}
    /**
     * Download template for tahunan mode
     */
/**
 * Download template for tahunan mode (tanpa kolom rating)
 */
private function downloadTemplateTahunan($pendekatan, $tahun)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template Tahunan');
    
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    $jenisDataList = ['Pertumbuhan', 'Laju Implisit'];
    if ($isPengeluaran) {
        $jenisDataList[] = 'CtoC';
    }
    
    // HEADER - Hanya 4 kolom (tanpa Rating)
    $headers = [
        'A1' => 'Kategori / Sub Kategori',
        'B1' => 'Jenis Data',
        'C1' => 'Nilai (%)',
        'D1' => 'Fenomena',
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // STYLE HEADER
    $sheet->getStyle('A1:D1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ]);
    
    // DATA - FILTER NON MIGAS
    $kategori = Kategori::where('pendekatan', $pendekatan)
        ->with('subKategori')
        ->orderBy('id_kategori')
        ->get()
        ->filter(function($item) {
            return strpos($item->nama_kategori, 'Non Migas') === false && 
                   $item->nama_kategori != 'Produk Domestik Regional Bruto Non Migas';
        });
    
    $row = 2;
    
    foreach ($kategori as $kat) {
        foreach ($jenisDataList as $jenisData) {
            $sheet->setCellValue("A{$row}", $kat->nama_kategori . " ({$jenisData})");
            $sheet->setCellValue("B{$row}", $jenisData);
            $row++;
        }
        
        foreach ($kat->subKategori as $sub) {
            if (strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                continue;
            }
            
            foreach ($jenisDataList as $jenisData) {
                $sheet->setCellValue("A{$row}", $sub->nama_sub_kategori . " ({$jenisData})");
                $sheet->setCellValue("B{$row}", $jenisData);
                $row++;
            }
        }
    }
    
    // BORDER
    $sheet->getStyle("A1:D" . ($row - 1))->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // ZEBRA
    for ($i = 2; $i < $row; $i++) {
        if ($i % 2 == 0) {
            $sheet->getStyle("A{$i}:D{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']]
            ]);
        }
    }
    
    // WIDTH
    $sheet->getColumnDimension('A')->setWidth(50);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(60);
    
    $sheet->freezePane('A2');
    
    // DOWNLOAD
    $filename = "template_tahunan_{$pendekatan}_{$tahun}.xlsx";
    
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

    /**
     * Download template for triwulanan mode
     */
private function downloadTemplateTriwulanan($pendekatan, $tahun)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Template Triwulanan');
    
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    $jenisDataList = ['QtoQ', 'YonY'];
    if ($isPengeluaran) {
        $jenisDataList[] = 'CtoC';
    }
    
    // ===========================================
    // HEADER BARIS 1
    // ===========================================
    $sheet->setCellValue('A1', 'KATEGORI / SUBKATEGORI');
    $sheet->getStyle('A1')->getFont()->setBold(true);
    
    $triwulanList = ['I', 'II', 'III', 'IV'];
    $startCol = 'B';
    
    foreach ($triwulanList as $triwulan) {
        $endCol = chr(ord($startCol) + 2); // +2 karena 3 kolom per triwulan (Jenis, Nilai, Fenomena)
        $sheet->setCellValue($startCol . '1', "TRIWULAN {$triwulan}/{$tahun}");
        $sheet->mergeCells($startCol . '1:' . $endCol . '1');
        $sheet->getStyle($startCol . '1')->getFont()->setBold(true);
        $startCol = chr(ord($startCol) + 3);
    }
    
    // ===========================================
    // HEADER BARIS 2 (Sub header)
    // ===========================================
    $sheet->setCellValue('A2', '');
    $sheet->setCellValue('B2', 'Jenis Data');
    $sheet->setCellValue('C2', 'Nilai (%)');
    $sheet->setCellValue('D2', 'FENOMENA');
    $sheet->setCellValue('E2', 'Jenis Data');
    $sheet->setCellValue('F2', 'Nilai (%)');
    $sheet->setCellValue('G2', 'FENOMENA');
    $sheet->setCellValue('H2', 'Jenis Data');
    $sheet->setCellValue('I2', 'Nilai (%)');
    $sheet->setCellValue('J2', 'FENOMENA');
    $sheet->setCellValue('K2', 'Jenis Data');
    $sheet->setCellValue('L2', 'Nilai (%)');
    $sheet->setCellValue('M2', 'FENOMENA');
    
    // ===========================================
    // STYLE HEADER
    // ===========================================
    $sheet->getStyle('A1:M2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // ===========================================
    // AMBIL DATA KATEGORI DAN SUBKATEGORI
    // ===========================================
    $kategori = Kategori::where('pendekatan', $pendekatan)
        ->with('subKategori')
        ->orderBy('id_kategori')
        ->get()
        ->filter(function($item) {
            return strpos($item->nama_kategori, 'Non Migas') === false && 
                   $item->nama_kategori != 'Produk Domestik Regional Bruto Non Migas';
        });
    
    $row = 3;
    
    foreach ($kategori as $kat) {
        // Baris untuk kategori utama
        $sheet->setCellValue("A{$row}", $kat->nama_kategori);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        
        // Isi untuk setiap triwulan (kosong, user akan mengisi)
        $startCol = 'B';
        for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
            // Kolom Jenis Data (bisa diisi user)
            // Kolom Nilai
            // Kolom Fenomena
            $startCol = chr(ord($startCol) + 3);
        }
        $row++;
        
        // Baris untuk subkategori
        foreach ($kat->subKategori as $sub) {
            if (strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                continue;
            }
            
            $sheet->setCellValue("A{$row}", $sub->nama_sub_kategori);
            $row++;
        }
    }
    
    // ===========================================
    // MENAMBAHKAN BARIS CONTOH / TEMPLATE JENIS DATA
    // ===========================================
    // Ini akan membuat baris terpisah untuk setiap Jenis Data
    // Reset row untuk membuat baris per jenis data
    $row = 3;
    $newRows = [];
    
    foreach ($kategori as $kat) {
        foreach ($jenisDataList as $jenisData) {
            $newRows[] = [
                'kategori' => $kat->nama_kategori,
                'jenis_data' => $jenisData,
                'is_sub' => false,
                'sub_kategori' => null
            ];
        }
        
        foreach ($kat->subKategori as $sub) {
            if (strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                continue;
            }
            foreach ($jenisDataList as $jenisData) {
                $newRows[] = [
                    'kategori' => $sub->nama_sub_kategori,
                    'jenis_data' => $jenisData,
                    'is_sub' => true,
                    'sub_kategori' => $sub->nama_sub_kategori
                ];
            }
        }
    }
    
    // Clear sheet dari baris 3 ke bawah
    for ($i = 3; $i <= 1000; $i++) {
        $sheet->removeRow($i);
    }
    
    // Tulis ulang dengan struktur baru
    $currentRow = 3;
    foreach ($newRows as $item) {
        $sheet->setCellValue("A{$currentRow}", $item['kategori']);
        
        // Set Jenis Data untuk setiap triwulan
        $colJenisTW1 = 'B';
        $colNilaiTW1 = 'C';
        $colFenomenaTW1 = 'D';
        $colJenisTW2 = 'E';
        $colNilaiTW2 = 'F';
        $colFenomenaTW2 = 'G';
        $colJenisTW3 = 'H';
        $colNilaiTW3 = 'I';
        $colFenomenaTW3 = 'J';
        $colJenisTW4 = 'K';
        $colNilaiTW4 = 'L';
        $colFenomenaTW4 = 'M';
        
        // Isi Jenis Data untuk setiap triwulan
        $sheet->setCellValue($colJenisTW1 . $currentRow, $item['jenis_data']);
        $sheet->setCellValue($colJenisTW2 . $currentRow, $item['jenis_data']);
        $sheet->setCellValue($colJenisTW3 . $currentRow, $item['jenis_data']);
        $sheet->setCellValue($colJenisTW4 . $currentRow, $item['jenis_data']);
        
        // Styling untuk baris
        if (!$item['is_sub']) {
            $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
        }
        
        $sheet->getStyle($colJenisTW1 . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colJenisTW2 . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colJenisTW3 . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colJenisTW4 . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $currentRow++;
    }
    
    $lastRow = $currentRow - 1;
    
    // ===========================================
    // STYLE DATA (Borders)
    // ===========================================
    $sheet->getStyle("A1:M{$lastRow}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // Alignment kolom A (rata kiri)
    $sheet->getStyle('A3:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
    // Alignment kolom Nilai (C, F, I, L) - rata kanan
    $sheet->getStyle('C3:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('F3:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('I3:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('L3:L' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Alignment kolom Fenomena (D, G, J, M) - rata kiri wrap text
    $sheet->getStyle('D3:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('G3:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('J3:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('M3:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    
    // ===========================================
    // LEBAR KOLOM
    // ===========================================
    $sheet->getColumnDimension('A')->setWidth(35);
    $sheet->getColumnDimension('B')->setWidth(12);  // Jenis Data TW1
    $sheet->getColumnDimension('C')->setWidth(15);  // Nilai TW1
    $sheet->getColumnDimension('D')->setWidth(45);  // Fenomena TW1
    $sheet->getColumnDimension('E')->setWidth(12);  // Jenis Data TW2
    $sheet->getColumnDimension('F')->setWidth(15);  // Nilai TW2
    $sheet->getColumnDimension('G')->setWidth(45);  // Fenomena TW2
    $sheet->getColumnDimension('H')->setWidth(12);  // Jenis Data TW3
    $sheet->getColumnDimension('I')->setWidth(15);  // Nilai TW3
    $sheet->getColumnDimension('J')->setWidth(45);  // Fenomena TW3
    $sheet->getColumnDimension('K')->setWidth(12);  // Jenis Data TW4
    $sheet->getColumnDimension('L')->setWidth(15);  // Nilai TW4
    $sheet->getColumnDimension('M')->setWidth(45);  // Fenomena TW4
    
    // FREEZE PANE
    $sheet->freezePane('B3');
    
    // ===========================================
    // ZEBRA STRIPING
    // ===========================================
    for ($i = 3; $i <= $lastRow; $i++) {
        if ($i % 2 == 0) {
            $sheet->getStyle("A{$i}:M{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]
            ]);
        }
    }
    
    // ===========================================
    // SHEET PETUNJUK
    // ===========================================
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Petunjuk');
    
    $sheet2->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE TRIWULANAN');
    $sheet2->mergeCells('A1:E1');
    $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet2->getStyle('A1')->getAlignment()->setHorizontal('center');
    
    $petunjuk = [
        ['TAHUN', $tahun],
        ['PENDEKATAN', ucfirst(str_replace('_', ' ', $pendekatan))],
        ['JENIS DATA', implode(', ', $jenisDataList)],
        ['', ''],
        ['STRUKTUR FILE:', ''],
        ['- Kolom A:', 'Kategori / Sub Kategori (telah terisi)'],
        ['- Kolom B, E, H, K:', 'Jenis Data (QtoQ, YonY, CtoC) - sudah terisi sesuai baris'],
        ['- Kolom C, F, I, L:', 'Nilai (%) untuk Triwulan I, II, III, IV'],
        ['- Kolom D, G, J, M:', 'Fenomena untuk Triwulan I, II, III, IV'],
        ['', ''],
        ['CARA PENGISIAN:', ''],
        ['1. Setiap kategori/subkategori memiliki 3 baris (QtoQ, YonY, dan CtoC jika Pengeluaran)', ''],
        ['2. Isi kolom NILAI (%) dengan angka (contoh: 11,08 atau 5.5)', ''],
        ['3. Isi kolom FENOMENA dengan deskripsi narasi ekonomi', ''],
        ['4. Jangan hapus atau mengubah struktur baris yang sudah ada', ''],
        ['5. Kolom JENIS DATA sudah terisi, jangan diubah', ''],
    ];
    
    $rowPetunjuk = 3;
    foreach ($petunjuk as $p) {
        $sheet2->setCellValue("A{$rowPetunjuk}", $p[0]);
        $sheet2->setCellValue("B{$rowPetunjuk}", $p[1]);
        $rowPetunjuk++;
    }
    
    $sheet2->getColumnDimension('A')->setWidth(25);
    $sheet2->getColumnDimension('B')->setWidth(70);
    
    // Styling petunjuk
    $sheet2->getStyle('A3:A' . ($rowPetunjuk - 1))->getFont()->setBold(true);
    $sheet2->getStyle('A1:E1')->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
        'font' => ['color' => ['rgb' => 'FFFFFF']]
    ]);
    
    // ===========================================
    // DOWNLOAD FILE
    // ===========================================
    $filename = "template_triwulanan_{$pendekatan}_{$tahun}.xlsx";
    
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

    /**
     * Toggle lock status for a wilayah
     */
    public function toggleLock(Request $request)
    {
        try {
            $request->validate([
                'id_wilayah' => 'required|integer|exists:wilayah,id_wilayah',
                'tahun' => 'required|integer',
                'pendekatan' => 'required|in:lapangan_usaha,pengeluaran',
                'mode' => 'required|in:tahunan,triwulanan'
            ]);
            
            $user = Auth::user();
            $isProvinsi = $this->checkIsProvinsi($user);
            
            if (!$isProvinsi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya user provinsi yang dapat mengunci jawaban.'
                ], 403);
            }
            
            $existing = KunciJawaban::where('id_wilayah', $request->id_wilayah)
                ->where('tahun', $request->tahun)
                ->where('pendekatan', $request->pendekatan)
                ->where('mode', $request->mode)
                ->first();
            
            if ($existing) {
                $existing->is_locked = !$existing->is_locked;
                $existing->locked_by = Auth::id();
                $existing->locked_at = now();
                $existing->save();
            } else {
                KunciJawaban::create([
                    'id_wilayah' => $request->id_wilayah,
                    'tahun' => $request->tahun,
                    'pendekatan' => $request->pendekatan,
                    'mode' => $request->mode,
                    'is_locked' => true,
                    'locked_by' => Auth::id(),
                    'locked_at' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Status kunci berhasil diubah'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in toggleLock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export data to Excel
     */
public function export(Request $request)
{
    try {
        $tahun = $request->get('tahun', date('Y'));
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        $mode = $request->get('mode', 'tahunan');
        $idWilayah = $request->get('id_wilayah');
        
        if ($idWilayah) {
            $wilayah = Wilayah::find($idWilayah);
            $namaWilayah = $this->getWilayahNama($wilayah);
        } else {
            $namaWilayah = 'Semua_Wilayah';
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        if ($mode === 'triwulanan') {
            return $this->exportTriwulanan($spreadsheet, $sheet, $tahun, $pendekatan, $idWilayah, $namaWilayah);
        } else {
            return $this->exportTahunan($spreadsheet, $sheet, $tahun, $pendekatan, $idWilayah, $namaWilayah);
        }
        
    } catch (\Exception $e) {
        \Log::error('Export error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        return back()->with('error', 'Gagal mengexport data: ' . $e->getMessage());
    }
}

/**
 * Export data for tahunan mode
 */
/**
 * Export data for tahunan mode (with rating column)
 */
/**
 * Export data for tahunan mode (tanpa kolom rating)
 */
private function exportTahunan($spreadsheet, $sheet, $tahun, $pendekatan, $idWilayah, $namaWilayah)
{
    $title = "Data_Fenomena_Tahunan_{$pendekatan}_{$tahun}";
    $sheet->setTitle(substr(str_replace(' ', '_', $title), 0, 31));
    
    // Header info
    $sheet->setCellValue('A1', 'LAPORAN DATA FENOMENA PDRB TAHUNAN');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Pendekatan: ' . ucfirst(str_replace('_', ' ', $pendekatan)));
    $sheet->mergeCells('A2:E2');
    $sheet->setCellValue('A3', 'Tahun: ' . $tahun);
    $sheet->mergeCells('A3:E3');
    $sheet->setCellValue('A4', 'Wilayah: ' . $namaWilayah);
    $sheet->mergeCells('A4:E4');
    
    $startRow = 6;
    
    // Table headers - TANPA kolom rating (hanya 5 kolom)
    $headers = ['No', 'Kategori / Sub Kategori', 'Jenis Data', 'Nilai', 'Fenomena'];
    $colLetters = ['A', 'B', 'C', 'D', 'E'];
    $colWidths = [8, 45, 25, 15, 65];
    
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    
    foreach ($headers as $index => $header) {
        $col = $colLetters[$index];
        $sheet->setCellValue($col . $startRow, $header);
        $sheet->getStyle($col . $startRow)->applyFromArray($headerStyle);
        $sheet->getColumnDimension($col)->setWidth($colWidths[$index]);
    }
    
    // Query data tahunan
    $query = Fenomena::with(['kategori', 'subKategori'])
        ->where('tahun', $tahun)
        ->where('pendekatan', $pendekatan)
        ->whereNull('id_periode');
    
    if ($idWilayah) {
        $query->where('id_wilayah', $idWilayah);
    }
    
    $data = $query->orderBy('id_kategori')
        ->orderBy('id_sub_kategori')
        ->get();
    
    $row = $startRow + 1;
    $no = 1;
    
    $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
    
    foreach ($data as $item) {
        $namaItem = $item->subKategori ? $item->subKategori->nama_sub_kategori : ($item->kategori->nama_kategori ?? '-');
        $jenisData = $item->jenis_data ?? '-';
        $nilai = ($item->nilai !== null) ? number_format((float)$item->nilai, 2, ',', '.') : '0,00';
        $fenomena = $item->fenomena ?? '-';
        
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, $namaItem);
        $sheet->setCellValue('C' . $row, $jenisData);
        $sheet->setCellValue('D' . $row, $nilai);
        $sheet->setCellValue('E' . $row, $fenomena);
        
        // Style
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
        
        $row++;
        $no++;
    }
    
    // Footer
    $footerRow = $row + 1;
    $sheet->setCellValue('A' . $footerRow, 'Total Data: ' . ($no - 1));
    $sheet->mergeCells('A' . $footerRow . ':E' . $footerRow);
    $sheet->getStyle('A' . $footerRow)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    
    $filename = "Data_Fenomena_Tahunan_{$namaWilayah}_{$tahun}.xlsx";
    
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Export data for triwulanan mode (horizontal format with rating per quarter)
 */
/**
 * Export data for triwulanan mode (horizontal format tanpa kolom rating)
 */
private function exportTriwulanan($spreadsheet, $sheet, $tahun, $pendekatan, $idWilayah, $namaWilayah)
{
    $title = "Data_Fenomena_Triwulanan_{$pendekatan}_{$tahun}";
    $sheet->setTitle(substr(str_replace(' ', '_', $title), 0, 31));
    
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    $jenisDataList = ['q-to-q' => 'QtoQ', 'y-on-y' => 'YonY'];
    if ($isPengeluaran) {
        $jenisDataList['c-to-c'] = 'CtoC';
    }
    
    // Header info
    $sheet->setCellValue('A1', 'LAPORAN DATA FENOMENA PDRB TRIWULANAN');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Pendekatan: ' . ucfirst(str_replace('_', ' ', $pendekatan)));
    $sheet->mergeCells('A2:J2');
    $sheet->setCellValue('A3', 'Tahun: ' . $tahun);
    $sheet->mergeCells('A3:J3');
    $sheet->setCellValue('A4', 'Wilayah: ' . $namaWilayah);
    $sheet->mergeCells('A4:J4');
    
    $startRow = 6;
    
    // ===========================================
    // HEADER BARIS 1
    // ===========================================
    $sheet->setCellValue('A' . $startRow, 'KATEGORI / SUBKATEGORI');
    $sheet->mergeCells('A' . $startRow . ':A' . ($startRow + 1));
    
    $triwulanList = ['I', 'II', 'III', 'IV'];
    $startCol = 'B';
    
    foreach ($triwulanList as $triwulan) {
        $endCol = chr(ord($startCol) + 1);
        $sheet->setCellValue($startCol . $startRow, "TRIWULAN {$triwulan}/{$tahun}");
        $sheet->mergeCells($startCol . $startRow . ':' . $endCol . $startRow);
        $startCol = chr(ord($startCol) + 2);
    }
    
    // ===========================================
    // HEADER BARIS 2 (Nilai dan Fenomena - TANPA RATING)
    // ===========================================
    $rowHeader2 = $startRow + 1;
    $sheet->setCellValue('A' . $rowHeader2, '');
    $sheet->setCellValue('B' . $rowHeader2, 'Nilai (%)');
    $sheet->setCellValue('C' . $rowHeader2, 'FENOMENA');
    $sheet->setCellValue('D' . $rowHeader2, 'Nilai (%)');
    $sheet->setCellValue('E' . $rowHeader2, 'FENOMENA');
    $sheet->setCellValue('F' . $rowHeader2, 'Nilai (%)');
    $sheet->setCellValue('G' . $rowHeader2, 'FENOMENA');
    $sheet->setCellValue('H' . $rowHeader2, 'Nilai (%)');
    $sheet->setCellValue('I' . $rowHeader2, 'FENOMENA');
    
    // STYLE HEADER
    $sheet->getStyle('A' . $startRow . ':I' . $rowHeader2)->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E88E5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    
    // ===========================================
    // AMBIL DATA KATEGORI DAN SUBKATEGORI
    // ===========================================
    $kategoriData = Kategori::where('pendekatan', $pendekatan)
        ->with('subKategori')
        ->orderBy('id_kategori')
        ->get()
        ->filter(function($item) {
            return strpos($item->nama_kategori, 'Non Migas') === false && 
                   $item->nama_kategori != 'Produk Domestik Regional Bruto Non Migas';
        })
        ->map(function($kategori) {
            $kategori->subKategori = $kategori->subKategori->filter(function($sub) {
                return strpos($sub->nama_sub_kategori, 'Non Migas') === false;
            });
            return $kategori;
        });
    
    // Ambil data fenomena untuk semua triwulan
    $query = Fenomena::with(['kategori', 'subKategori'])
        ->where('tahun', $tahun)
        ->where('pendekatan', $pendekatan)
        ->whereNotNull('id_periode');
    
    if ($idWilayah) {
        $query->where('id_wilayah', $idWilayah);
    }
    
    $fenomena = $query->get();
    
    // Build map untuk data
    $fenomenaMap = [];
    foreach ($fenomena as $f) {
        if ($f->id_sub_kategori) {
            $fenomenaMap[$f->id_kategori][$f->id_sub_kategori][$f->jenis_data][$f->id_periode] = [
                'nilai' => $f->nilai,
                'fenomena' => $f->fenomena
            ];
        } else {
            $fenomenaMap[$f->id_kategori][$f->jenis_data][$f->id_periode] = [
                'nilai' => $f->nilai,
                'fenomena' => $f->fenomena
            ];
        }
    }
    
    $row = $rowHeader2 + 1;
    
    foreach ($kategoriData as $kat) {
        foreach ($jenisDataList as $jenisKey => $jenisLabel) {
            // Baris untuk kategori utama
            $sheet->setCellValue("A{$row}", $kat->nama_kategori . " ({$jenisLabel})");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            
            // Isi data untuk 4 triwulan
            for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
                $colNilai = 1 + (($triwulan - 1) * 2);      // B=1, D=3, F=5, H=7
                $colFenomena = 2 + (($triwulan - 1) * 2);    // C=2, E=4, G=6, I=8
                
                $nilai = isset($fenomenaMap[$kat->id_kategori][$jenisKey][$triwulan]['nilai']) 
                    ? number_format($fenomenaMap[$kat->id_kategori][$jenisKey][$triwulan]['nilai'], 2, ',', '.')
                    : '';
                $fenomenaText = isset($fenomenaMap[$kat->id_kategori][$jenisKey][$triwulan]['fenomena']) 
                    ? $fenomenaMap[$kat->id_kategori][$jenisKey][$triwulan]['fenomena']
                    : '';
                
                $sheet->setCellValue(chr(65 + $colNilai) . $row, $nilai);
                $sheet->setCellValue(chr(65 + $colFenomena) . $row, $fenomenaText);
            }
            
            $row++;
        }
        
        // Subkategori
        foreach ($kat->subKategori as $sub) {
            foreach ($jenisDataList as $jenisKey => $jenisLabel) {
                $sheet->setCellValue("A{$row}", $sub->nama_sub_kategori . " ({$jenisLabel})");
                
                for ($triwulan = 1; $triwulan <= 4; $triwulan++) {
                    $colNilai = 1 + (($triwulan - 1) * 2);
                    $colFenomena = 2 + (($triwulan - 1) * 2);
                    
                    $nilai = isset($fenomenaMap[$kat->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['nilai']) 
                        ? number_format($fenomenaMap[$kat->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['nilai'], 2, ',', '.')
                        : '';
                    $fenomenaText = isset($fenomenaMap[$kat->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['fenomena']) 
                        ? $fenomenaMap[$kat->id_kategori][$sub->id_sub_kategori][$jenisKey][$triwulan]['fenomena']
                        : '';
                    
                    $sheet->setCellValue(chr(65 + $colNilai) . $row, $nilai);
                    $sheet->setCellValue(chr(65 + $colFenomena) . $row, $fenomenaText);
                }
                
                $row++;
            }
        }
    }
    
    // ===========================================
    // STYLE DATA
    // ===========================================
    $lastRow = $row - 1;
    $sheet->getStyle("A" . ($rowHeader2 + 1) . ":I{$lastRow}")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ]);
    
    // Alignment untuk kolom Nilai (B, D, F, H) - rata kanan
    $sheet->getStyle('B' . ($rowHeader2 + 1) . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('D' . ($rowHeader2 + 1) . ':D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('F' . ($rowHeader2 + 1) . ':F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('H' . ($rowHeader2 + 1) . ':H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    // Alignment untuk kolom Fenomena (C, E, G, I) - rata kiri dengan wrap text
    $sheet->getStyle('C' . ($rowHeader2 + 1) . ':C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('E' . ($rowHeader2 + 1) . ':E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('G' . ($rowHeader2 + 1) . ':G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    $sheet->getStyle('I' . ($rowHeader2 + 1) . ':I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
    
    // Alignment untuk kolom A
    $sheet->getStyle('A' . ($rowHeader2 + 1) . ':A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
    // LEBAR KOLOM
    $sheet->getColumnDimension('A')->setWidth(45);
    $sheet->getColumnDimension('B')->setWidth(15);  // Nilai TW1
    $sheet->getColumnDimension('C')->setWidth(45);  // Fenomena TW1
    $sheet->getColumnDimension('D')->setWidth(15);  // Nilai TW2
    $sheet->getColumnDimension('E')->setWidth(45);  // Fenomena TW2
    $sheet->getColumnDimension('F')->setWidth(15);  // Nilai TW3
    $sheet->getColumnDimension('G')->setWidth(45);  // Fenomena TW3
    $sheet->getColumnDimension('H')->setWidth(15);  // Nilai TW4
    $sheet->getColumnDimension('I')->setWidth(45);  // Fenomena TW4
    
    // FREEZE PANE
    $sheet->freezePane('B' . ($rowHeader2 + 1));
    
    // ZEBRA STRIPING
    for ($i = $rowHeader2 + 1; $i <= $lastRow; $i++) {
        if ($i % 2 == 0) {
            $sheet->getStyle("A{$i}:I{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]
            ]);
        }
    }
    
    $filename = "Data_Fenomena_Triwulanan_{$namaWilayah}_{$tahun}.xlsx";
    
    if (ob_get_length()) ob_end_clean();
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Get wilayah nama with dynamic column
 */
private function getWilayahNama($wilayah)
{
    $possibleNames = ['nama_wilayah', 'nama', 'nm_wilayah', 'nm_wil', 'wilayah', 'name', 'nm_wil'];
    
    foreach ($possibleNames as $col) {
        if (isset($wilayah->$col) && !empty($wilayah->$col)) {
            return $wilayah->$col;
        }
    }
    
    return 'Wilayah ' . $wilayah->id_wilayah;
}

    /**
     * Ranking method
     */
public function ranking(Request $request)
{
    $tahun = $request->get('tahun', date('Y'));
    $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
    $mode = $request->get('mode', 'tahunan');
    $triwulan = $request->get('triwulan', 'all');
    
    // Ambil wilayah dengan kolom nama dinamis
    $wilayahList = $this->getWilayahList();
    
    $kategoriData = Kategori::with('subKategori')
        ->where('pendekatan', $pendekatan)
        ->get();
    
    $rankingData = [];
    $totalMaxRating = 0;
    
    $isPengeluaran = ($pendekatan == 'pengeluaran');
    
    // Tentukan jenis data berdasarkan mode
    if ($mode === 'tahunan') {
        // Untuk mode tahunan - TANPA CtoC
        $jenisDataList = ['Pertumbuhan', 'Laju Implisit'];
        // CtoC TIDAK termasuk untuk tahunan
    } else {
        // Untuk mode triwulanan
        $jenisDataList = ['q-to-q', 'y-on-y'];
        if ($isPengeluaran) {
            $jenisDataList[] = 'c-to-c';
        }
    }
    
    foreach ($kategoriData as $kategori) {
        if (strpos($kategori->nama_kategori, 'Non Migas') !== false || 
            $kategori->nama_kategori == 'Produk Domestik Regional Bruto Non Migas') {
            continue;
        }
        
        // Rating untuk kategori utama (masing-masing jenis data punya rating)
        foreach ($jenisDataList as $jenisData) {
            $totalMaxRating += 5;
        }
        
        // Rating untuk subkategori
        foreach ($kategori->subKategori as $sub) {
            if (strpos($sub->nama_sub_kategori, 'Non Migas') !== false) {
                continue;
            }
            foreach ($jenisDataList as $jenisData) {
                $totalMaxRating += 5;
            }
        }
    }
    
    // Untuk mode triwulanan dengan semua triwulan, kalikan dengan 4
    if ($mode === 'triwulanan' && $triwulan === 'all') {
        $totalMaxRating = $totalMaxRating * 4;
    }
    
    foreach ($wilayahList as $wilayah) {
        $totalRating = 0;
        $ratingsByQuarter = [];
        
        $query = Fenomena::where('id_wilayah', $wilayah->id_wilayah)
            ->where('tahun', $tahun)
            ->where('pendekatan', $pendekatan)
            ->whereNotNull('rating');
        
        if ($mode === 'tahunan') {
            $query->whereNull('id_periode');
            // Untuk tahunan, hanya ambil Pertumbuhan dan Laju Implisit (CtoC tidak dihitung)
            $query->whereIn('jenis_data', ['Pertumbuhan', 'Laju Implisit']);
        } else {
            $query->whereNotNull('id_periode');
            if ($triwulan !== 'all') {
                $query->where('id_periode', $triwulan);
            }
        }
        
        $ratings = $query->get();
        
        foreach ($ratings as $rating) {
            $totalRating += $rating->rating;
            
            if ($mode === 'triwulanan' && $rating->id_periode) {
                $quarter = $rating->id_periode;
                $ratingsByQuarter[$quarter] = ($ratingsByQuarter[$quarter] ?? 0) + $rating->rating;
            }
        }
        
        $percentage = $totalMaxRating > 0 ? round(($totalRating / $totalMaxRating) * 100, 2) : 0;
        
        $rankingData[] = [
            'id_wilayah' => $wilayah->id_wilayah,
            'nama_wilayah' => $this->getWilayahNama($wilayah),
            'total_rating' => $totalRating,
            'max_rating' => $totalMaxRating,
            'percentage' => $percentage,
            'ratings_by_quarter' => $ratingsByQuarter,
        ];
    }
    
    // Urutkan berdasarkan total rating (tertinggi ke terendah)
    usort($rankingData, function($a, $b) {
        return $b['total_rating'] <=> $a['total_rating'];
    });
    
    // Tambahkan peringkat
    foreach ($rankingData as $index => &$data) {
        $data['rank'] = $index + 1;
    }
    
    $tahunSekarang = date('Y');
    $tahunList = Tahun::where('tahun', '<=', $tahunSekarang)
        ->orderBy('tahun', 'desc')
        ->pluck('tahun')
        ->toArray();
    
    if (empty($tahunList)) {
        $tahunList = range($tahunSekarang - 5, $tahunSekarang);
        $tahunList = array_reverse($tahunList);
    }
    
    $triwulanList = [
        'all' => 'Semua Triwulan',
        1 => 'Triwulan I',
        2 => 'Triwulan II',
        3 => 'Triwulan III',
        4 => 'Triwulan IV',
    ];
    
    return view('fenomena.ranking', compact(
        'rankingData', 'tahun', 'pendekatan', 'mode',
        'triwulan', 'tahunList', 'triwulanList', 'kategoriData', 'totalMaxRating'
    ));
}

    public function downloadTemplate(Request $request)
    {
        $mode = $request->get('mode', 'tahunan');
        $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
        $tahun = $request->get('tahun', date('Y'));
        
        if ($mode === 'triwulanan') {
            return $this->downloadTemplateTriwulanan($pendekatan, $tahun);
        } else {
            return $this->downloadTemplateTahunan($pendekatan, $tahun);
        }
    }

    // Helper methods
    private function checkIsLocked($id_wilayah, $tahun, $pendekatan, $mode)
    {
        $kunci = KunciJawaban::where('id_wilayah', $id_wilayah)
            ->where('tahun', $tahun)
            ->where('pendekatan', $pendekatan)
            ->where('mode', $mode)
            ->first();
        return $kunci ? $kunci->is_locked : false;
    }
    
    private function parseNilai($value)
    {
        if ($value === null || $value === '') return null;
        $value = trim(str_replace(',', '.', $value));
        return is_numeric($value) ? (float) $value : null;
    }
    
    private function parseRating($value)
    {
        if ($value === null || $value === '') return null;
        $rating = intval($value);
        return ($rating >= 1 && $rating <= 5) ? $rating : null;
    }

    public function toggleUploadLock(Request $request)
{
    try {
        $user = Auth::user();
        $isProvinsi = $this->checkIsProvinsi($user);
        
        if (!$isProvinsi) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya user provinsi yang dapat mengunci upload.'
            ], 403);
        }
        
        $tahun = $request->tahun;
        $pendekatan = $request->pendekatan;
        $mode = $request->mode;
        
        // Buat key cache untuk lock upload
        $cacheKey = "upload_lock_{$tahun}_{$pendekatan}_{$mode}";
        
        // Cek status saat ini
        $isLocked = Cache::get($cacheKey, false);
        
        if ($isLocked) {
            // Buka kunci
            Cache::forget($cacheKey);
            $message = "Upload data untuk semua wilayah telah DIBUKA.";
            $newStatus = false;
        } else {
            // Kunci upload untuk semua wilayah
            Cache::put($cacheKey, true, now()->addDays(30)); // berlaku 30 hari
            $message = "Upload data untuk semua wilayah telah DIKUNCI. Kabupaten/Kota tidak dapat mengupload data.";
            $newStatus = true;
        }
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'is_locked' => $newStatus
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error in toggleUploadLock: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Check if upload is locked for ALL wilayah
 */
private function isUploadLocked($tahun, $pendekatan, $mode)
{
    $cacheKey = "upload_lock_{$tahun}_{$pendekatan}_{$mode}";
    return Cache::get($cacheKey, false);
}

/**
 * Rename existing checkIsLocked to clarify it's for answer lock
 */
private function isAnswerLocked($id_wilayah, $tahun, $pendekatan, $mode)
{
    $kunci = KunciJawaban::where('id_wilayah', $id_wilayah)
        ->where('tahun', $tahun)
        ->where('pendekatan', $pendekatan)
        ->where('mode', $mode)
        ->first();
    return $kunci ? $kunci->is_locked : false;
}



/**
 * Show histori upload page
 */
public function historiFenomena(Request $request)
{
    $mode = $request->get('mode', 'tahunan');
    $tahun = $request->get('tahun', date('Y'));
    $pendekatan = $request->get('pendekatan', 'lapangan_usaha');
    $id_wilayah = $request->get('id_wilayah');
    
    $user = Auth::user();
    $isProvinsi = $this->checkIsProvinsi($user);
    $isKabKota = !$isProvinsi && isset($user->id_wilayah);
    
    // Query histori
    $query = HistoriFenomena::with(['wilayah', 'user'])
        ->when($mode, function($q) use ($mode) {
            return $q->where('mode', $mode);
        })
        ->when($tahun, function($q) use ($tahun) {
            return $q->where('tahun', $tahun);
        })
        ->when($pendekatan, function($q) use ($pendekatan) {
            return $q->where('pendekatan', $pendekatan);
        });
    
    // Filter berdasarkan level user
    if ($isKabKota) {
        $query->where('id_wilayah', $user->id_wilayah);
    } elseif ($id_wilayah) {
        $query->where('id_wilayah', $id_wilayah);
    }
    
    $histori = $query->orderBy('uploaded_at', 'desc')
        ->paginate(20)
        ->appends($request->all());
    
    // Data untuk filter
    $tahunList = Tahun::orderBy('tahun', 'desc')->pluck('tahun');
    if ($tahunList->isEmpty()) {
        $tahunList = collect([date('Y'), date('Y')-1, date('Y')-2]);
    }
    
    $wilayahList = collect();
    if ($isProvinsi) {
        $wilayahList = $this->getWilayahList();
    } elseif ($isKabKota) {
        $wilayah = Wilayah::find($user->id_wilayah);
        if ($wilayah) {
            $wilayahList = collect([$wilayah]);
        }
    }
    
    // Statistik
    $statistics = [
        'total_upload' => $query->count(),
        'total_data' => $query->sum('jumlah_data'),
        'success_count' => $query->where('status', 'success')->count(),
        'failed_count' => $query->where('status', 'failed')->count(),
    ];
    
    return view('fenomena.histori_fenomena', compact(
        'histori', 'mode', 'tahun', 'pendekatan', 'id_wilayah',
        'tahunList', 'wilayahList', 'isProvinsi', 'statistics'
    ));
}

/**
 * Save upload history
 */
private function saveUploadHistory($id_wilayah, $namaWilayah, $tahun, $pendekatan, $mode, $namaFile, $ukuranFile, $jumlahData, $status = 'success', $errorMessage = null, $keterangan = null)
{
    try {
        HistoriFenomena::create([
            'id_wilayah' => $id_wilayah,
            'nama_wilayah' => $namaWilayah,
            'tahun' => $tahun,
            'pendekatan' => $pendekatan,
            'mode' => $mode,
            'nama_file' => $namaFile,
            'ukuran_file' => $ukuranFile,
            'jumlah_data' => $jumlahData,
            'keterangan' => $keterangan,
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now()
        ]);
    } catch (\Exception $e) {
        \Log::error('Gagal menyimpan histori: ' . $e->getMessage());
    }
}
/**
 * Get detail histori for modal
 */
public function detailHistori($id)
{
    try {
        $histori = HistoriFenomena::with(['user', 'wilayah'])->find($id);
        
        if (!$histori) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $histori->id,
                'uploaded_at' => $histori->uploaded_at->format('d/m/Y H:i:s'),
                'nama_wilayah' => $histori->nama_wilayah,
                'nama_file' => $histori->nama_file,
                'ukuran_file' => $histori->ukuran_file,
                'mode' => ucfirst($histori->mode),
                'tahun' => $histori->tahun,
                'pendekatan' => str_replace('_', ' ', $histori->pendekatan),
                'jumlah_data' => $histori->jumlah_data,
                'status' => $histori->status,
                'keterangan' => $histori->keterangan,
                'error_message' => $histori->error_message,
                'ip_address' => $histori->ip_address,
                'uploader_name' => $histori->user->name ?? 'Unknown'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

}