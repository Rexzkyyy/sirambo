<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekonLembarKerja extends Model
{
    use HasFactory;

    protected $table = 'rekon_lembar_kerja';

    protected $fillable = [
        'lembar_kerja_id',
        'status',
        'total_berlaku',
        'total_konstan',
        'adj_berlaku',
        'adj_konstan',
        'final_berlaku',
        'final_konstan',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'total_berlaku' => 'string',
        'total_konstan' => 'string',
        'adj_berlaku' => 'string',
        'adj_konstan' => 'string',
        'final_berlaku' => 'string',
        'final_konstan' => 'string',
    ];

    public function lembarKerja()
    {
        return $this->belongsTo(LembarKerja::class, 'lembar_kerja_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
