<?php
// app/Models/Fenomena.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fenomena extends Model
{
    use HasFactory;

    protected $table = 'fenomena';
    protected $primaryKey = 'id_fenomena';
    
    protected $fillable = [
        'id_kategori',
        'id_sub_kategori',
        'id_periode',
        'id_wilayah', // <-- TAMBAHKAN UNTUK FILTER WILAYAH
        'kode_kategori',
        'nama_kategori',
        'nama_sub_kategori',
        'jenis_data',
        'tahun',
        'nilai',
        'fenomena',
        'rating', // <-- TAMBAHKAN UNTUK KOLOM RATING
        'level',
        'pendekatan',
        'created_by'
    ];

    protected $casts = [
        'tahun' => 'integer',
        'nilai' => 'float',
        'rating' => 'integer', // <-- CAST UNTUK RATING
        'level' => 'integer'
    ];

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    // Relasi ke SubKategori
    public function subKategori()
    {
        return $this->belongsTo(SubKategori::class, 'id_sub_kategori', 'id_sub_kategori');
    }

    // Relasi ke Periode
    public function periode()
    {
        return $this->belongsTo(Periode::class, 'id_periode', 'id_periode');
    }

    // Relasi ke Wilayah (KABUPATEN/KOTA) <-- TAMBAHKAN
    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
    }

    // Relasi ke User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // Scope untuk lapangan usaha
    public function scopeLapanganUsaha($query)
    {
        return $query->where('pendekatan', 'lapangan_usaha');
    }

    // Scope untuk pengeluaran
    public function scopePengeluaran($query)
    {
        return $query->where('pendekatan', 'pengeluaran');
    }

    // Scope berdasarkan tahun
    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    // Scope berdasarkan level
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }
    
    // Scope untuk data tahunan (id_periode is null)
    public function scopeTahunan($query)
    {
        return $query->whereNull('id_periode');
    }
    
    // Scope untuk data triwulanan (id_periode is not null)
    public function scopeTriwulanan($query)
    {
        return $query->whereNotNull('id_periode');
    }
    
    // Scope berdasarkan periode/triwulan
    public function scopePeriode($query, $idPeriode)
    {
        return $query->where('id_periode', $idPeriode);
    }

    // Scope berdasarkan wilayah <-- TAMBAHKAN
    public function scopeWilayah($query, $idWilayah)
    {
        return $query->where('id_wilayah', $idWilayah);
    }

    // Scope untuk menampilkan data yang belum dikunci <-- TAMBAHKAN
    public function scopeNotLocked($query, $tahun, $pendekatan, $mode)
    {
        return $query->whereNotExists(function ($subquery) use ($tahun, $pendekatan, $mode) {
            $subquery->select('id_kunci')
                ->from('kunci_jawaban')
                ->whereRaw('kunci_jawaban.id_wilayah = fenomena.id_wilayah')
                ->where('kunci_jawaban.tahun', $tahun)
                ->where('kunci_jawaban.pendekatan', $pendekatan)
                ->where('kunci_jawaban.mode', $mode)
                ->where('kunci_jawaban.is_locked', true);
        });
    }

    // Accessor untuk format nilai dengan persen <-- TAMBAHKAN
    public function getNilaiFormattedAttribute()
    {
        if ($this->nilai === null) return '-';
        return number_format($this->nilai, 2, ',', '.') . '%';
    }

    // Accessor untuk label rating <-- TAMBAHKAN
    public function getRatingLabelAttribute()
    {
        if ($this->rating === null) return '-';
        
        $labels = [
            1 => 'Sangat Rendah',
            2 => 'Rendah',
            3 => 'Sedang',
            4 => 'Tinggi',
            5 => 'Sangat Tinggi'
        ];
        
        return $labels[$this->rating] ?? $this->rating;
    }

    // Accessor untuk warna rating <-- TAMBAHKAN
    public function getRatingColorAttribute()
    {
        if ($this->rating === null) return 'gray';
        
        $colors = [
            1 => 'red',
            2 => 'orange',
            3 => 'yellow',
            4 => 'blue',
            5 => 'green'
        ];
        
        return $colors[$this->rating] ?? 'gray';
    }

    // Accessor untuk menentukan apakah data ini dapat diedit <-- TAMBAHKAN
    public function getIsEditableAttribute()
    {
        // Cek apakah wilayah ini dikunci
        $isLocked = KunciJawaban::isLocked(
            $this->id_wilayah,
            $this->tahun,
            $this->pendekatan,
            $this->id_periode ? 'triwulanan' : 'tahunan'
        );
        
        return !$isLocked;
    }

    // Method untuk duplikasi data ke tahun berikutnya <-- TAMBAHKAN
    public function duplicateToYear($newTahun)
    {
        $newFenomena = $this->replicate();
        $newFenomena->tahun = $newTahun;
        $newFenomena->created_by = auth()->id();
        $newFenomena->save();
        
        return $newFenomena;
    }

    // Static method untuk bulk insert/update <-- TAMBAHKAN
    public static function bulkUpdateOrCreate($data, $uniqueKeys)
    {
        return self::updateOrCreate($data, $uniqueKeys);
    }

    // Scope untuk mendapatkan data dengan rating tertentu <-- TAMBAHKAN
    public function scopeRating($query, $rating)
    {
        if (is_array($rating)) {
            return $query->whereIn('rating', $rating);
        }
        return $query->where('rating', $rating);
    }

    // Scope untuk mendapatkan data dengan rating di atas nilai tertentu <-- TAMBAHKAN
    public function scopeRatingMin($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Scope untuk mendapatkan data dengan rating di bawah nilai tertentu <-- TAMBAHKAN
    public function scopeRatingMax($query, $maxRating)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    // Scope berdasarkan jenis data <-- TAMBAHKAN
    public function scopeJenisData($query, $jenisData)
    {
        return $query->where('jenis_data', $jenisData);
    }

    // Scope untuk mengambil data yang memiliki fenomena (tidak kosong) <-- TAMBAHKAN
    public function scopeHasFenomena($query)
    {
        return $query->whereNotNull('fenomena')->where('fenomena', '!=', '');
    }

    // Scope untuk mengambil data yang memiliki nilai <-- TAMBAHKAN
    public function scopeHasNilai($query)
    {
        return $query->whereNotNull('nilai');
    }

    // Scope untuk mengambil data yang memiliki rating <-- TAMBAHKAN
    public function scopeHasRating($query)
    {
        return $query->whereNotNull('rating');
    }
}