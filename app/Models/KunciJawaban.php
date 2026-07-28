<?php
// app/Models/KunciJawaban.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunciJawaban extends Model
{
    use HasFactory;

    protected $table = 'kunci_jawaban';
    protected $primaryKey = 'id_kunci';

    protected $fillable = [
        'id_wilayah',
        'tahun',
        'pendekatan',
        'mode',
        'is_locked',
        'locked_by',
        'locked_at',
        'lock_deadline'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'tahun' => 'integer',
        'locked_at' => 'datetime',
        'lock_deadline' => 'datetime'
    ];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
    }

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by', 'id');
    }

    public static function isLocked($id_wilayah, $tahun, $pendekatan, $mode)
    {
        $kunci = self::where('id_wilayah', $id_wilayah)
            ->where('tahun', $tahun)
            ->where('pendekatan', $pendekatan)
            ->where('mode', $mode)
            ->first();

        if (!$kunci) return false;

        // Jika is_locked true, maka terkunci secara manual
        if ($kunci->is_locked) return true;

        // Jika ada deadline dan waktu sekarang sudah lewat deadline, maka terkunci otomatis
        if ($kunci->lock_deadline && now()->greaterThan($kunci->lock_deadline)) {
            return true;
        }

        return false;
    }
}