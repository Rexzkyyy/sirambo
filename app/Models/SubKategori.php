<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubKategori extends Model
{
    protected $table = 'sub_kategori';
    protected $primaryKey = 'id_sub_kategori';
    public $timestamps = false;

    protected $fillable = [
        'id_kategori',
        'nama_sub_kategori'
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function nilaiSubKategori()
    {
        return $this->hasMany(NilaiSubKategori::class, 'id_sub_kategori', 'id_sub_kategori');
    }
}
