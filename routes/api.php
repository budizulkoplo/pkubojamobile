<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/pegawai', function () {

    $key = request()->header('X-API-KEY') 
        ?? request()->query('api_key');

    if ($key !== 'RSPKU-PEGAWAI-2026-JOSJIS') {
        abort(401);
    }

    return DB::table('pegawai')
        ->select('pegawai_id', 'pegawai_nama', 'jabatan')
        ->get();
});

