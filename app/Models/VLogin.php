<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class VLogin extends Authenticatable
{
    protected $table = 'v_login';

    public $timestamps = false;

    protected $guarded = [];

    // Jika kamu tidak punya kolom `password`, sesuaikan kolom autentikasinya
    public function getAuthPassword()
    {
        return $this->attributes['password'] ?? null;
    }
}
