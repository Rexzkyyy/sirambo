<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdrbImportLog extends Model
{
    use HasFactory;

    protected $table = 'pdrb_import_logs';

    protected $fillable = [
        'id_wilayah',
        'id_tahun',
        'id_periode',
        'pendekatan',
        'tipe_import',
        'user_id'
    ];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
    }

    public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'id_tahun', 'id_tahun');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
