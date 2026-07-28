<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    // =============================
    // LIST KABUPATEN / KOTA
    // =============================
    public function index()
    {
        $wilayah = Wilayah::with(['kabupaten'])
            ->whereIn('tipe', ['kabupaten', 'kota'])
            ->orderBy('id_wilayah')
            ->get();

        return view('wilayah.index', compact('wilayah'));
    }

    // =============================
    // DETAIL WILAYAH
    // =============================
    public function show($idWilayah)
    {
        $wilayah = Wilayah::with(['kabupaten', 'provinsi'])
            ->where('id_wilayah', $idWilayah)
            ->firstOrFail();

        return view('wilayah.show', compact('wilayah'));
    }

    // =============================
    // AMBIL ID_WILAYAH PROVINSI
    // =============================
    public function getProvinsiWilayah($idProvinsi)
    {
        $provinsi = Wilayah::where('tipe', 'provinsi')
            ->where('id_provinsi', $idProvinsi)
            ->firstOrFail();

        return response()->json([
            'id_wilayah' => $provinsi->id_wilayah
        ]);
    }

    // =============================
    // LIST KAB / KOTA BY PROVINSI
    // =============================
    public function getKabKotaByProvinsi($idProvinsi)
    {
        $kabkota = Wilayah::with('kabupaten')
            ->where('id_provinsi', $idProvinsi)
            ->whereIn('tipe', ['kabupaten', 'kota'])
            ->get()
            ->map(function ($w) {
                return [
                    'id_wilayah' => $w->id_wilayah,
                    'nama'       => $w->nama_wilayah, // accessor dari model
                    'tipe'       => $w->tipe,
                ];
            });

        return response()->json($kabkota);
    }
}
