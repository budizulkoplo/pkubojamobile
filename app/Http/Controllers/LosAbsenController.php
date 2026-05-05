<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LosAbsenController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->input('tgl') ?? Carbon::now()->toDateString();

        $data = DB::table('losabsen as l')
            ->leftJoin('karyawan as p', 'l.pin', '=', 'p.pin') // pegawai yang lupa absen
            ->leftJoin('karyawan as v', 'l.verifymode', '=', 'v.pin') // verifikator
            ->whereDate('l.scan_date', $tanggal)
            ->select(
                'l.*',
                'p.nama_lengkap as nama_pegawai',
                'v.nama_lengkap as nama_verifikator'
            )
            ->orderBy('l.scan_date')
            ->get();

        return view('security.losabsen.index', [
            'data' => $data,
            'tanggal' => $tanggal
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'scan_date' => 'required|date',
            'pin'       => 'required|string',
            'inoutmode' => 'required|in:1,2,5,6',
        ]);

        DB::table('losabsen')->insert([
            'scan_date'   => $request->scan_date,
            'pin'         => $request->pin,
            'verifymode'  => Auth::guard('karyawan')->user()->pin,
            'inoutmode'   => $request->inoutmode,
        ]);

        return redirect()->route('losabsen.index', ['tgl' => date('Y-m-d', strtotime($request->scan_date))])
            ->with('success', 'Data los absensi berhasil disimpan.');
    }
}
