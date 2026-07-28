<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'id_wilayah',
        'sso_id',
        'nip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function wilayah()
{
    return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
}

// Relasi shortcut untuk nama wilayah
public function namaWilayah()
{
    return $this->hasOneThrough(
        Provinsi::class,     // jika tipe provinsi
        Wilayah::class,
        'id_wilayah',        // foreign key Wilayah di users
        'id_provinsi',       // foreign key Provinsi
        'id',                // local key users.id_wilayah
        'id_provinsi'        // local key Wilayah.id_provinsi
    );
}

    public function provinsi()
{
    return $this->belongsTo(Provinsi::class, 'id_provinsi', 'id');
}

public function kabupaten()
{
    return $this->belongsTo(Kabupaten::class, 'id_kabupaten', 'id_kabupaten');
}

}

