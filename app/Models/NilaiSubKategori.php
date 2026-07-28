<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiSubKategori extends Model
{
    protected $table = 'nilai_sub_kategori';
    protected $primaryKey = 'id_nilai_sub_kategori';
    public $timestamps = false;

    /**
     * Field yang boleh diisi mass assignment
     */
    protected $fillable = [
        'id_sub_kategori',
        'id_tahun',
        'id_periode',
        'id_wilayah',
        'tipe_pdrb',
        'nilai',
        'tahap_data', // ✅ WAJIB
    ];

    /**
     * Default value
     */
    protected $attributes = [
        'tahap_data' => 'awal',
    ];

    // =============================
    // RELATIONSHIPS
    // =============================

    public function subKategori()
    {
        return $this->belongsTo(
            SubKategori::class,
            'id_sub_kategori',
            'id_sub_kategori'
        );
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
        return $this->belongsTo(
            \App\Models\Wilayah::class,
            'id_wilayah',
            'id_wilayah'
        );
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
