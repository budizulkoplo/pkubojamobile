<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Pegawai extends Authenticatable
{
    protected $table = 'pegawai';
    protected $primaryKey = 'pegawai_id';
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'pegawai_pin',
        'pegawai_nip',
        'nik',
        'pegawai_nama',
        'jabatan',
        'nohp',
        'email',
        'alamat',
        'bagian',
        'password',
        'gambar',
        'peoplepower',
        'nbm',
    ];

    // Digunakan oleh Laravel Auth
    public function getAuthPassword()
    {
        return $this->password;
    }
}
