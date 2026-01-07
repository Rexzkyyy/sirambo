<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;

    protected $fillable = [
        'kode_kategori',
        'nama_kategori'
    ];

       public function nilaiKategori()
    {
        return $this->hasMany(NilaiKategori::class, 'id_kategori', 'id_kategori');
    }

    public function subKategori()
    {
        return $this->hasMany(SubKategori::class, 'id_kategori', 'id_kategori');
    }
}
