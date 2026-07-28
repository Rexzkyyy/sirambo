<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CekSelisihController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->get('jenis', 'pdrb');

        return match ($jenis) {
            'pdrb'     => $this->cekPdrb($request),
            'qtoq'     => $this->cekGrowth($request, 'qtoq'),
            'yony'     => $this->cekGrowth($request, 'yony'),
            'ctoc'     => $this->cekGrowth($request, 'ctoc'),
            'indeks'   => $this->cekGrowth($request, 'indeks'),
            'laju'     => $this->cekGrowth($request, 'laju'),
            'struktur' => $this->cekGrowth($request, 'struktur'),
            default    => abort(404)
        };
    }

    /* ==========================
        CEK SELISIH PDRB
    =========================== */

    private function cekPdrb(Request $request)
    {
        $ctrl = app(RekonsiliasiController::class);

        $reqP0 = clone $request;
        $reqP0->merge(['resume' => null]);

        $reqP1 = clone $request;
        $reqP1->merge(['resume' => 'p1']);

        $p0 = $ctrl->index($reqP0)->getData()['rekonsiliasi'] ?? [];
        $p1 = $ctrl->index($reqP1)->getData()['rekonsiliasi'] ?? [];

        $hasil = $this->hitungSelisihNilai($p0, $p1);

        return view('cekSelisih.index', compact('hasil', 'request'));
    }

    /* ==========================
        CEK SELISIH GROWTH
    =========================== */

    private function cekGrowth(Request $request, $method)
    {
        $ctrl = app(RekonsiliasiController::class);

        $reqP0 = clone $request;
        $reqP0->merge(['resume' => null]);

        $reqP1 = clone $request;
        $reqP1->merge(['resume' => 'p1']);

        $p0 = $ctrl->{$method}($reqP0)->getData()['grouped'] ?? [];
        $p1 = $ctrl->{$method}($reqP1)->getData()['grouped'] ?? [];

        $hasil = $this->hitungSelisihGrowth($p0, $p1);

        return view('cekSelisih.index', compact('hasil', 'request'));
    }

    /* ==========================
        CORE LOGIC SELISIH
    =========================== */

    private function hitungSelisihNilai($p0, $p1)
    {
        $mapP1 = collect($p1)->keyBy('kategori');

        return collect($p0)->map(function ($row) use ($mapP1) {

            $p1Row = $mapP1[$row['kategori']] ?? null;

            return [
                'kategori' => $row['kategori'],
                'provinsi' => round(($row['provinsi'] ?? 0) - ($p1Row['provinsi'] ?? 0), 2),
                'total'    => round(($row['total'] ?? 0) - ($p1Row['total'] ?? 0), 2),
                'kabkota'  => collect($row['kabkota'] ?? [])
                                ->map(fn($v,$k) => round($v - ($p1Row['kabkota'][$k] ?? 0), 2))
                                ->toArray()
            ];
        })->values();
    }

    private function hitungSelisihGrowth($p0, $p1)
    {
        $mapP1 = collect($p1)->keyBy('kategori');

        return collect($p0)->map(function ($row) use ($mapP1) {

            $p1Row = $mapP1[$row['kategori']] ?? null;

            return [
                'kategori' => $row['kategori'],
                'provinsi' => round(($row['provinsi'] ?? 0) - ($p1Row['provinsi'] ?? 0), 2),
                'total_kab' => round(($row['total_kab'] ?? 0) - ($p1Row['total_kab'] ?? 0), 2),
                'kab' => collect($row['kab'] ?? [])
                            ->map(fn($v,$k) => round($v - ($p1Row['kab'][$k] ?? 0), 2))
                            ->toArray()
            ];
        })->values();
    }
}