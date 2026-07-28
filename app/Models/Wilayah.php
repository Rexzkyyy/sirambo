<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wilayah extends Model
{
    protected $table = 'wilayah';
    protected $primaryKey = 'id_wilayah';
    public $timestamps = false;

    protected $fillable = [
        'tipe',          // provinsi | kabupaten | kota
        'id_provinsi',
        'id_kabupaten',
    ];

    protected $appends = ['nama_wilayah'];

    /* =====================
     | RELATIONS
     ===================== */

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'id_provinsi', 'id_provinsi');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Kabupaten::class, 'id_kabupaten', 'id_kabupaten');
    }

    /* =====================
     | ACCESSOR
     ===================== */

    public function getNamaWilayahAttribute()
    {
        if ($this->tipe === 'provinsi') {
            return $this->provinsi?->nama_provinsi;
        }

        if (in_array($this->tipe, ['kabupaten', 'kota'])) {
            return $this->kabupaten?->nama_kabupaten;
        }

        return '-';
    }

    /* =====================
     | SCOPES
     ===================== */

    public function scopeProvinsi($query)
    {
        return $query->where('wilayah.tipe', 'provinsi');
    }

    public function scopeKabupaten($query)
    {
        return $query->where('wilayah.tipe', 'kabupaten');
    }

    public function scopeKota($query)
    {
        return $query->where('wilayah.tipe', 'kota');
    }

    public function scopeByProvinsi($query, $provinsiId)
    {
        return $query->where('wilayah.id_provinsi', $provinsiId);
    }

    /* =====================
     | OPTIONAL: QUERY SCOPE UNTUK SELECT
     ===================== */

    public function scopeWithNama($query)
    {
        return $query
            ->leftJoin('kabupaten', 'kabupaten.id_kabupaten', '=', 'wilayah.id_kabupaten')
            ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'wilayah.id_provinsi')
            ->select(
                'wilayah.*',
                DB::raw("
                    CASE 
                        WHEN wilayah.tipe = 'provinsi' THEN provinsi.nama_provinsi
                        ELSE kabupaten.nama_kabupaten
                    END AS nama_wilayah
                ")
            );
    }
}
