<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiKategori extends Model
{
    protected $table = 'nilai_kategori';
    protected $primaryKey = 'id_nilai_kategori';
    public $timestamps = false;

    protected $fillable = [
        'id_kategori',
        'id_tahun',
        'id_periode',
        'nilai'
    ];

    public function kategori()
    {
        
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

     public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'id_tahun', 'id_tahun');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }
}
