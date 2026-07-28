<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekonLembarKerjaLog extends Model
{
    use HasFactory;

    protected $table = 'rekon_lembar_kerja_log';

    protected $fillable = [
        'rekon_lembar_kerja_id',
        'field_type',
        'nilai_lama',
        'nilai_baru',
        'updated_by',
    ];

    protected $casts = [
        'nilai_lama' => 'decimal:4',
        'nilai_baru' => 'decimal:4',
    ];

    public function rekonLembarKerja()
    {
        return $this->belongsTo(RekonLembarKerja::class, 'rekon_lembar_kerja_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
