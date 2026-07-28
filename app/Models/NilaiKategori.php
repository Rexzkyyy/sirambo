<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiKategori extends Model
{
    protected $table = 'nilai_kategori';
    protected $primaryKey = 'id_nilai_kategori';
    public $timestamps = false;

    /**
     * Field yang boleh diisi mass assignment
     */
    protected $fillable = [
        'id_kategori',
        'id_tahun',
        'id_periode',
        'id_wilayah',
        'tipe_pdrb',
        'nilai',
        'tahap_data', // WAJIB
        'updated_at',
    ];

    /**
     * Default value (kalau tidak diisi)
     */
    protected $attributes = [
        'tahap_data' => 'awal',
    ];

    // =============================
    // RELATIONSHIPS
    // =============================

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

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
    }

    // =============================
    // QUERY SCOPES (OPTIONAL, RECOMMENDED)
    // =============================

    /**
     * Scope data awal
     */
    public function scopeAwal($query)
    {
        return $query->where('tahap_data', 'awal');
    }

    /**
     * Scope data hasil rekonsiliasi
     */
    public function scopeRekonsiliasi($query)
    {
        return $query->where('tahap_data', 'rekonsiliasi');
    }
}
