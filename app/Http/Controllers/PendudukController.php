<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use App\Models\Tahun;

class PendudukController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);

        $query = Penduduk::with(['wilayah.kabupaten', 'wilayah.provinsi'])
            ->orderByDesc('tahun')
            ->orderBy('id_wilayah');

        if ($isKabKota && $user->id_wilayah) {
            $query->where('id_wilayah', $user->id_wilayah);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('tahun', (int) $search);
                }
                $q->orWhereHas('wilayah.kabupaten', function ($qq) use ($search) {
                    $qq->where('nama_kabupaten', 'like', '%' . $search . '%');
                })->orWhereHas('wilayah.provinsi', function ($qq) use ($search) {
                    $qq->where('nama_provinsi', 'like', '%' . $search . '%');
                });
            });
        }

        $penduduk = $query->paginate(20)->withQueryString();

        return view('penduduk.index', compact('penduduk'));
    }

    public function create()
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);

        $wilayah = Wilayah::with(['kabupaten', 'provinsi'])
            ->when($isKabKota && $user->id_wilayah, function ($q) use ($user) {
                $q->where('id_wilayah', $user->id_wilayah);
            })
            ->orderBy('tipe')
            ->orderBy('id_wilayah')
            ->get();

        return view('penduduk.create', compact('wilayah'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);
        if ($isKabKota && $user->id_wilayah) {
            $request->merge(['id_wilayah' => $user->id_wilayah]);
        }

        $validated = $request->validate([
            'id_wilayah' => ['required', 'exists:wilayah,id_wilayah'],
            'tahun' => [
                'required',
                'integer',
                'min:1900',
                'max:2100',
                Rule::unique('penduduk')->where(function ($q) use ($request) {
                    return $q->where('id_wilayah', $request->id_wilayah);
                }),
            ],
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        Penduduk::create($validated);

        return redirect()
            ->route('penduduk.index')
            ->with('success', 'Data penduduk berhasil ditambahkan.');
    }

    public function edit(Penduduk $penduduk)
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);
        if ($isKabKota && $user->id_wilayah && (int) $penduduk->id_wilayah !== (int) $user->id_wilayah) {
            abort(403);
        }

        $wilayah = Wilayah::with(['kabupaten', 'provinsi'])
            ->when($isKabKota && $user->id_wilayah, function ($q) use ($user) {
                $q->where('id_wilayah', $user->id_wilayah);
            })
            ->orderBy('tipe')
            ->orderBy('id_wilayah')
            ->get();

        return view('penduduk.edit', compact('penduduk', 'wilayah'));
    }

    public function update(Request $request, Penduduk $penduduk)
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);
        if ($isKabKota && $user->id_wilayah) {
            $request->merge(['id_wilayah' => $user->id_wilayah]);
        }

        $validated = $request->validate([
            'id_wilayah' => ['required', 'exists:wilayah,id_wilayah'],
            'tahun' => [
                'required',
                'integer',
                'min:1900',
                'max:2100',
                Rule::unique('penduduk')
                    ->ignore($penduduk->id)
                    ->where(function ($q) use ($request) {
                        return $q->where('id_wilayah', $request->id_wilayah);
                    }),
            ],
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        $penduduk->update($validated);

        return redirect()
            ->route('penduduk.index')
            ->with('success', 'Data penduduk berhasil diperbarui.');
    }

    public function destroy(Penduduk $penduduk)
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);
        if ($isKabKota && $user->id_wilayah && (int) $penduduk->id_wilayah !== (int) $user->id_wilayah) {
            abort(403);
        }

        $penduduk->delete();

        return redirect()
            ->route('penduduk.index')
            ->with('success', 'Data penduduk berhasil dihapus.');
    }

    public function template()
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Penduduk');

        $tahunList = Tahun::orderBy('tahun')->pluck('tahun')->toArray();
        if (empty($tahunList)) {
            $tahunList = [2021, 2022, 2023, 2024, 2025];
        }

        $headers = array_merge(['id_wilayah', 'wilayah'], $tahunList);
        $sheet->fromArray($headers, null, 'A2');

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

        // Title row
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'TEMPLATE DATA PENDUDUK (MULTI TAHUN)');
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('4F46E5');
        $sheet->getStyle("A1:{$lastCol}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Header row
        $headerRange = "A2:{$lastCol}2";
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('1F2937');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(36);
        for ($i = 3; $i <= count($headers); $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setWidth(14);
        }

        $wilayahRows = Wilayah::with(['kabupaten', 'provinsi'])
            ->when($isKabKota && $user->id_wilayah, function ($q) use ($user) {
                $q->where('id_wilayah', $user->id_wilayah);
            })
            ->orderBy('tipe')
            ->orderBy('id_wilayah')
            ->get()
            ->map(function ($w) use ($tahunList) {
                $row = [$w->id_wilayah, $w->nama_wilayah];
                foreach ($tahunList as $tahun) {
                    $row[] = '';
                }
                return $row;
            })
            ->toArray();

        $lastRow = 2;
        if (!empty($wilayahRows)) {
            $sheet->fromArray($wilayahRows, null, 'A3');
            $lastRow = 2 + count($wilayahRows);
        }

        if ($lastRow >= 2) {
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        for ($row = 3; $row <= $lastRow; $row++) {
            if ($row % 2 === 1) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F8FAFC');
            }
        }

        $sheet->freezePane('C3');
        $sheet->setAutoFilter("A2:{$lastCol}{$lastRow}");

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_penduduk.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename
        );
    }

    public function import(Request $request)
    {
        $user = auth()->user();
        $isKabKota = $user && in_array($user->role, ['kabupaten', 'kota'], true);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $filePath = $request->file('file')->getRealPath();
        $sheet = IOFactory::load($filePath)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerRowIndex = null;
        $colMap = [];
        foreach ($rows as $idx => $row) {
            $values = array_map(fn ($v) => strtolower(trim((string) $v)), $row);
            if (in_array('id_wilayah', $values, true) || in_array('id wilayah', $values, true)) {
                $headerRowIndex = $idx;
                foreach ($row as $col => $name) {
                    $key = strtolower(trim((string) $name));
                    if ($key !== '') {
                        $colMap[$key] = $col;
                    }
                }
                break;
            }
        }

        if (!$headerRowIndex) {
            return back()->withErrors('Header tidak ditemukan. Pastikan ada kolom id_wilayah / wilayah dan kolom tahun.');
        }

        $idCol = $colMap['id_wilayah'] ?? ($colMap['id wilayah'] ?? null);
        $wilayahCol = $colMap['wilayah'] ?? null;

        $yearCols = [];
        foreach ($colMap as $name => $col) {
            if (is_numeric($name)) {
                $yearCols[(int) $name] = $col;
            }
        }
        if (!$idCol && !$wilayahCol) {
            return back()->withErrors('Kolom wajib: id_wilayah atau wilayah.');
        }
        if (empty($yearCols)) {
            return back()->withErrors('Kolom tahun tidak ditemukan.');
        }

        $wilayahMap = Wilayah::with(['kabupaten', 'provinsi'])
            ->get()
            ->mapWithKeys(fn ($w) => [strtolower($w->nama_wilayah) => $w->id_wilayah])
            ->toArray();

        $payload = [];
        $skipped = 0;
        $invalid = 0;
        $blocked = 0;
        $now = now();

        $parseNumber = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
            $str = trim((string) $value);
            if ($str === '') {
                return null;
            }
            $str = str_replace(' ', '', $str);
            if (str_contains($str, '.') && str_contains($str, ',')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } elseif (str_contains($str, ',')) {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Only dots: treat as thousands separator when grouped by 3
                $dotCount = substr_count($str, '.');
                if ($dotCount >= 1) {
                    $parts = explode('.', $str);
                    $lastLen = strlen(end($parts));
                    $allGroups = $lastLen === 3;
                    foreach ($parts as $i => $part) {
                        if ($i === 0) {
                            continue;
                        }
                        if (strlen($part) !== 3) {
                            $allGroups = false;
                            break;
                        }
                    }
                    if ($allGroups) {
                        $str = str_replace('.', '', $str);
                    }
                }
                $str = str_replace(',', '', $str);
            }
            $str = preg_replace('/[^0-9.-]/', '', $str);
            if ($str === '' || $str === '-' || $str === '.' || $str === '-.') {
                return null;
            }
            $num = (float) $str;
            return is_nan($num) ? null : $num;
        };

        foreach ($rows as $idx => $row) {
            if ($idx <= $headerRowIndex) {
                continue;
            }
            $idWilayahRaw = $idCol ? trim((string) ($row[$idCol] ?? '')) : '';
            $wilayahName = $wilayahCol ? trim((string) ($row[$wilayahCol] ?? '')) : '';

            if ($idWilayahRaw === '' && $wilayahName === '') {
                continue;
            }

            $idWilayah = null;
            if ($idWilayahRaw !== '' && is_numeric($idWilayahRaw)) {
                $idWilayah = (int) $idWilayahRaw;
            } elseif ($wilayahName !== '') {
                $idWilayah = $wilayahMap[strtolower($wilayahName)] ?? null;
            }

            if (!$idWilayah) {
                $skipped++;
                continue;
            }

            if ($isKabKota && $user->id_wilayah && (int) $idWilayah !== (int) $user->id_wilayah) {
                $blocked++;
                continue;
            }

            foreach ($yearCols as $year => $col) {
                $val = $row[$col] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                $parsed = $parseNumber($val);
                if ($parsed === null) {
                    $invalid++;
                    continue;
                }
                $payload[] = [
                    'id_wilayah' => $idWilayah,
                    'tahun' => (int) $year,
                    'jumlah' => (int) round($parsed),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($payload)) {
            return back()->withErrors('Tidak ada data valid untuk diimport.');
        }

        $chunks = array_chunk($payload, 1000);
        foreach ($chunks as $chunk) {
            DB::table('penduduk')->upsert(
                $chunk,
                ['id_wilayah', 'tahun'],
                ['jumlah', 'updated_at']
            );
        }

        $message = "Import selesai. Data masuk: " . count($payload) . ". Data invalid: {$invalid}. Wilayah tidak ditemukan: {$skipped}.";
        if ($blocked > 0) {
            $message .= " Wilayah tidak diizinkan: {$blocked}.";
        }

        return redirect()
            ->route('penduduk.index')
            ->with('success', $message);
    }
}
