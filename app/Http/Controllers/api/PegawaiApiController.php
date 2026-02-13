<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PegawaiApiController extends Controller
{
    public function index()
    {
        $pegawai = DB::table('pegawai')
            ->select(
                'pegawai_id',
                'pegawai_nama',
                'jabatan'
            )
            ->orderBy('pegawai_id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diambil',
            'data' => $pegawai
        ]);
    }
}
