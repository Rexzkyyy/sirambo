<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdrbImportLogController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\PdrbImportLog::with(['wilayah', 'tahun', 'periode', 'user']);

        if ($request->filled('id_wilayah')) {
            $query->where('id_wilayah', $request->id_wilayah);
        }

        if ($request->filled('id_tahun')) {
            $query->where('id_tahun', $request->id_tahun);
        }

        if ($request->filled('id_periode')) {
            $query->where('id_periode', $request->id_periode);
        }

        if ($request->filled('pendekatan')) {
            $query->where('pendekatan', $request->pendekatan);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('start_time')) {
            $query->whereTime('created_at', '>=', $request->start_time);
        }

        if ($request->filled('end_time')) {
            $query->whereTime('created_at', '<=', $request->end_time);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $wilayahs = \App\Models\Wilayah::query()->whereIn('wilayah.tipe', ['kabupaten', 'kota'])->withNama()->get();
        $tahuns = \App\Models\Tahun::orderBy('tahun', 'desc')->get();
        $periodes = \App\Models\Periode::all();

        return view('pdrb.import_logs', compact('logs', 'wilayahs', 'tahuns', 'periodes'));
    }
}
