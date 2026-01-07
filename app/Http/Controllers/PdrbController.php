<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;


use App\Models\{
    Kategori, SubKategori, Tahun, Periode,
    NilaiKategori, NilaiSubKategori
};

use Illuminate\Http\Request;
class PdrbController extends Controller
{
    public function index(){
        return view('pdrb.index',[
            'kategori'=>Kategori::all(),
            'sub'=>SubKategori::all(),
            'tahun'=>Tahun::all(),
            'periode'=>Periode::all()
        ]);
    }

    public function storeKategori(Request $r){
        NilaiKategori::create($r->all());
        return back()->with('success','Nilai kategori tersimpan');
    }

   public function storeSub(Request $r)
    {
        $tahun = $r->id_tahun;
        $periode = $r->id_periode;

        // $r->nilai adalah array [id_sub_kategori => nilai]
        foreach($r->nilai as $subId => $nilai) {
            NilaiSubKategori::create([
                'id_sub_kategori' => $subId,
                'id_tahun'        => $tahun,
                'id_periode'      => $periode,
                'nilai'           => $nilai
            ]);
        }

        return back()->with('success','Semua nilai sub kategori tersimpan');
    }
public function hasil(Request $request)
{
    $tahun     = Tahun::orderBy('tahun')->get();
    $periode   = Periode::orderBy('id_periode')->get();
    $kategori  = Kategori::all();
    $sub       = SubKategori::all();

    $selectedTahun   = $request->id_tahun;
    $selectedPeriode = $request->id_periode;

    // Query nilai kategori
    $nilaiKategori = NilaiKategori::when($request->has('scope_kabupaten'), function ($q) use ($request) {
            $q->where('id_kabupaten', $request->scope_kabupaten);
        })
        ->when($selectedTahun, function ($q) use ($selectedTahun) {
            $q->where('id_tahun', $selectedTahun);
        })
        ->when($selectedPeriode, function ($q) use ($selectedPeriode) {
            $q->where('id_periode', $selectedPeriode);
        })
        ->get();

    // Query nilai sub kategori
    $nilaiSub = NilaiSubKategori::when($request->has('scope_kabupaten'), function ($q) use ($request) {
            $q->where('id_kabupaten', $request->scope_kabupaten);
        })
        ->when($selectedTahun, function ($q) use ($selectedTahun) {
            $q->where('id_tahun', $selectedTahun);
        })
        ->when($selectedPeriode, function ($q) use ($selectedPeriode) {
            $q->where('id_periode', $selectedPeriode);
        })
        ->get();

    return view('pdrb.hasil', compact(
        'tahun',
        'periode',
        'kategori',
        'sub',
        'nilaiKategori',
        'nilaiSub',
        'selectedTahun',
        'selectedPeriode'
    ));
}


    public function hasilPerTahun(Request $request)
    {
        $tahun = Tahun::all();
        $kategori = Kategori::all();
        $sub = SubKategori::all();
        $periode = Periode::orderBy('id_periode')->get(); // TW1–TW4

        $selectedTahun = $request->id_tahun;

        $nilaiSub = NilaiSubKategori::when($selectedTahun, function ($q) use ($selectedTahun) {
            $q->where('id_tahun', $selectedTahun);
        })->get();

        return view('pdrb.hasilpertahun', compact(
            'tahun',
            'kategori',
            'sub',
            'periode',
            'nilaiSub',
            'selectedTahun'
        ));
    }

}

