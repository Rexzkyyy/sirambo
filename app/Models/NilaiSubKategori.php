<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiSubKategori extends Model
{
    protected $table = 'nilai_sub_kategori';
    protected $primaryKey = 'id_nilai_sub_kategori';
    public $timestamps = false; 

    protected $fillable = [
        'id_sub_kategori',
        'id_tahun',
        'id_periode',
        'nilai'
    ];


    public function subKategori()
    {
        // id_sub_kategori = foreign key di nilai_sub_kategori
        // id_sub_kategori = primary key di sub_kategori
        return $this->belongsTo(SubKategori::class, 'id_sub_kategori', 'id_sub_kategori');
    }

    // Relasi ke Tahun
    public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'id_tahun', 'id_tahun');
    }

    // Relasi ke Periode
    public function periode()
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }
}
