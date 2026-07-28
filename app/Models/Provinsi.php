<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinsi extends Model
{
    protected $table = 'provinsi';
    protected $primaryKey = 'id_provinsi'; // <--- harus sesuai tabel
    public $timestamps = false;
    protected $fillable = ['nama_provinsi'];

    // Relasi ke Kabupaten
    public function kabupaten()
    {
        return $this->hasMany(Kabupaten::class, 'id_provinsi', 'id_provinsi');
    }
}
