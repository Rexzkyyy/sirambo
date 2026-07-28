<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdrbLock extends Model
{
    protected $table = 'pdrb_locks';

    protected $fillable = [
        'id_wilayah',
        'pendekatan',
        'is_locked',
        'lock_deadline',
        'locked_by',
        'locked_at'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
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

    /**
     * Cek apakah import PDRB terkunci untuk pendekatan tertentu.
     * Bisa dicek secara global (id_wilayah = 0) atau spesifik wilayah.
     */
    public static function isLocked($pendekatan, $idWilayah = 0)
    {
        // 1. Cek kunci GLOBAL (berlaku untuk semua)
        $globalLock = self::where('id_wilayah', 0)
            ->where('pendekatan', $pendekatan)
            ->first();

        if ($globalLock) {
            if ($globalLock->is_locked) return true;
            if ($globalLock->lock_deadline && now()->greaterThan($globalLock->lock_deadline)) {
                return true;
            }
        }

        // 2. Jika tidak ada kunci global atau global terbuka, cek kunci SPESIFIK wilayah
        if ($idWilayah && $idWilayah != 0) {
            $specLock = self::where('id_wilayah', $idWilayah)
                ->where('pendekatan', $pendekatan)
                ->first();

            if ($specLock) {
                if ($specLock->is_locked) return true;
                if ($specLock->lock_deadline && now()->greaterThan($specLock->lock_deadline)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ambil record lock untuk pendekatan dan wilayah tertentu.
     */
    public static function getLockRecord($pendekatan, $idWilayah = 0)
    {
        return self::where('id_wilayah', $idWilayah)
            ->where('pendekatan', $pendekatan)
            ->first();
    }
}
