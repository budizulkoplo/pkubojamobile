<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $query = Karyawan::query();
        $query->select('karyawan.*', 'nama_dept');
        $query->join('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept');
        $query->orderBy('nama_lengkap');

        // Ambil kode_dept dari session
        $kodeDeptSession = session('kode_dept');

        // Jika kode_dept bukan 0 (bukan admin), batasi hanya departemennya sendiri
        if ($kodeDeptSession != 0) {
            $query->where('karyawan.kode_dept', $kodeDeptSession);
        }

        // Filter tambahan
        if (!empty($request->nama_karyawan)) {
            $query->where('nama_lengkap', 'like', '%' . $request->nama_karyawan . '%');
        }

        if (!empty($request->kode_dept)) {
            $query->where('karyawan.kode_dept', $request->kode_dept);
        }

        $karyawan = $query->paginate(50);
        $departemen = DB::table('departemen')->get();

        return view('karyawan.index', compact('karyawan', 'departemen'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|unique:karyawan,nik',
            'nama_lengkap' => 'required|string',
            'jabatan' => 'required|string',
            'no_hp' => 'required|string',
            'kode_dept' => 'required|string',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $nik = $request->nik;
        $foto = $request->hasFile('foto') ? $nik . "." . $request->file('foto')->getClientOriginalExtension() : null;

        $data = [
            'nik' => $nik,
            'nama_lengkap' => $request->nama_lengkap,
            'jabatan' => $request->jabatan,
            'no_hp' => $request->no_hp,
            'kode_dept' => $request->kode_dept,
            'foto' => $foto,
            'password' => Hash::make('1234')
        ];

        try {
            $simpan = DB::table('karyawan')->insert($data);
            if ($simpan && $request->hasFile('foto')) {
                $request->file('foto')->storeAs("public/uploads/karyawan/", $foto);
            }
            return Redirect::back()->with(['success' => 'Data Berhasil Disimpan']);
        } catch (\Exception $e) {
            \Log::error("Gagal simpan karyawan: " . $e->getMessage());
            return Redirect::back()->with(['warning' => 'Data Gagal Disimpan: ' . $e->getMessage()]);
        }
    }

    public function edit(Request $request)
    {
        $nik = $request->nik;
        $departemen = DB::table('departemen')->get();
        $karyawan = DB::table('karyawan')->where('nik', $nik)->first();

        return view('karyawan.edit', compact('departemen', 'karyawan'));
    }

    public function update($nik, Request $request)
    {
        $nik = $request->nik;
        $foto = $request->hasFile('foto') ? $nik . "." . $request->file('foto')->getClientOriginalExtension() : $request->old_foto;

        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'jabatan' => $request->jabatan,
            'no_hp' => $request->no_hp,
            'kode_dept' => $request->kode_dept,
            'foto' => $foto,
            'password' => Hash::make('1234')
        ];

        try {
            $update = DB::table('karyawan')->where('nik', $nik)->update($data);
            if ($update && $request->hasFile('foto')) {
                $folder = "public/uploads/karyawan/";
                Storage::delete($folder . $request->old_foto);
                $request->file('foto')->storeAs($folder, $foto);
            }
            return Redirect::back()->with(['success' => 'Data Berhasil Diupdate']);
        } catch (\Exception $e) {
            \Log::error("Gagal update karyawan: " . $e->getMessage());
            return Redirect::back()->with(['warning' => 'Data Gagal Diupdate']);
        }
    }

    public function delete($nik)
    {
        try {
            $karyawan = DB::table('karyawan')->where('nik', $nik)->first();
            $delete = DB::table('karyawan')->where('nik', $nik)->delete();
            if ($delete && $karyawan && $karyawan->foto) {
                Storage::delete("public/uploads/karyawan/" . $karyawan->foto);
            }
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } catch (\Exception $e) {
            \Log::error("Gagal hapus karyawan: " . $e->getMessage());
            return Redirect::back()->with(['warning' => 'Data Gagal Dihapus']);
        }
    }
}
