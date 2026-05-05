<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecurityController extends Controller
{
    // Tampilkan halaman inventaris
    public function inventarisIndex()
    {
        $data = DB::table('inventaris_security')->get();
        return view('security.inventaris.index', compact('data'));
    }

    // Update data inventaris
    public function inventarisUpdate(Request $request)
    {
        foreach ($request->id as $key => $id) {
            DB::table('inventaris_security')
                ->where('id', $id)
                ->update([
                    'baik'       => $request->baik[$key],
                    'rusak'      => $request->rusak[$key],
                    'keterangan' => $request->keterangan[$key],
                ]);
        }

        return redirect()->route('inventaris.index')->with('success', 'Data inventaris berhasil diperbarui.');
    }

    public function kegiatanIndex(Request $request)
{
    $tanggal = $request->input('tgl') ?? Carbon::now()->toDateString();

    $records = DB::table('kegiatan_security as ks')
        ->join('karyawan as k', 'ks.nik', '=', 'k.nik')
        ->whereDate('ks.tgl', $tanggal)
        ->select('ks.jam', 'ks.kegiatan', 'ks.nik', 'k.nama_lengkap')
        ->orderBy('ks.nik')
        ->orderBy('ks.jam')
        ->get();

    // Kelompokkan data berdasarkan nik
    $dataPerNik = [];
    foreach ($records as $row) {
        $nik = $row->nik;
        if (!isset($dataPerNik[$nik])) {
            $dataPerNik[$nik] = [
                'nik' => $nik,
                'nama_lengkap' => $row->nama_lengkap,
                'kegiatan' => []
            ];
        }

        $dataPerNik[$nik]['kegiatan'][] = [
            'jam' => $row->jam,
            'kegiatan' => $row->kegiatan
        ];
    }

    return view('security.kegiatan.index', [
        'data' => $dataPerNik,
        'tanggal' => $tanggal
    ]);
}


    public function kegiatanStore(Request $request)
    {
        $request->validate([
            'tgl'       => 'required|date',
            'jam'       => 'required',
            'kegiatan'  => 'required|string|max:255',
        ]);

        DB::table('kegiatan_security')->insert([
            'tgl'      => $request->tgl,
            'jam'      => $request->jam,
            'kegiatan' => $request->kegiatan,
            'nik'      => Auth::guard('karyawan')->user()->nik,
        ]);

        return redirect()->route('kegiatan.index', ['tgl' => $request->tgl])
            ->with('success', 'Kegiatan berhasil disimpan.');
    }
}
