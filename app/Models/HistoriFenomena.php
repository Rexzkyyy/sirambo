<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriFenomena extends Model
{
    protected $table = 'histori_fenomena';
    
    protected $fillable = [
        'id_wilayah',
        'nama_wilayah',
        'tahun',
        'pendekatan',
        'mode',
        'nama_file',
        'ukuran_file',
        'jumlah_data',
        'keterangan',
        'status',
        'error_message',
        'ip_address',
        'user_agent',
        'uploaded_by',
        'uploaded_at'
    ];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'jumlah_data' => 'integer'
    ];
    
    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}