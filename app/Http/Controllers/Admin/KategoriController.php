<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::all();
        return view('admin.provinsi.kategori.index', compact('kategori'));
    }

    public function create()
    {
        return view('admin.provinsi.kategori.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_kategori' => 'required|unique:kategori,kode_kategori',
            'nama_kategori' => 'required|string',
        ]);

        Kategori::create($request->only(['kode_kategori','nama_kategori']));
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Kategori $kategori)
    {
        return view('admin.provinsi.kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $request->validate([
            'kode_kategori' => 'required|unique:kategori,kode_kategori,' . $kategori->id_kategori,
            'nama_kategori' => 'required|string',
        ]);

        $kategori->update($request->only(['kode_kategori','nama_kategori']));
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return redirect()->route('kategori.index')->with('success', 'Kategori berhasil dihapus');
    }
}
