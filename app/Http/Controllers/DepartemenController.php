<?php

namespace App\Http\Controllers;

use App\Models\Departemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class DepartemenController extends Controller
{
    public function index(Request $request)
    {
        $nama_dept = $request->nama_dept;
        $kodeDeptSession = session('kode_dept');

        $query = Departemen::query();
        $query->select('*');

        if (!empty($nama_dept)) {
            $query->where('nama_dept', 'like', '%' . $nama_dept . '%');
        }

        // Batasi jika bukan admin
        if ($kodeDeptSession != 0) {
            $query->where('kode_dept', $kodeDeptSession);
        }

        $departemen = $query->get();
        return view('departemen.index', compact('departemen'));
    }

    public function store(Request $request)
    {
        // Batasi akses hanya untuk admin
        if (session('kode_dept') != 0) {
            return redirect()->back()->with(['warning' => 'Anda tidak memiliki izin untuk menambah data']);
        }

        $kode_dept = $request->kode_dept;
        $nama_dept = $request->nama_dept;

        $data = [
            'kode_dept' => $kode_dept,
            'nama_dept' => $nama_dept
        ];

        $simpan = DB::table('departemen')->insert($data);
        if ($simpan) {
            return Redirect::back()->with(['success' => 'Data Berhasil Disimpan']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Disimpan']);
        }
    }

    public function edit(Request $request)
    {
        // Batasi akses hanya untuk admin
        if (session('kode_dept') != 0) {
            return redirect()->back()->with(['warning' => 'Anda tidak memiliki izin untuk mengedit data']);
        }

        $kode_dept = $request->kode_dept;
        $departemen = DB::table('departemen')->where('kode_dept', $kode_dept)->first();
        return view('departemen.edit', compact('departemen'));
    }

    public function update($kode_dept, Request $request)
    {
        // Batasi akses hanya untuk admin
        if (session('kode_dept') != 0) {
            return redirect()->back()->with(['warning' => 'Anda tidak memiliki izin untuk mengupdate data']);
        }

        $nama_dept = $request->nama_dept;
        $data = [
            'nama_dept' => $nama_dept
        ];

        $update = DB::table('departemen')->where('kode_dept', $kode_dept)->update($data);
        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Diupdate']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Diupdate']);
        }
    }

    public function delete($kode_dept)
    {
        // Batasi akses hanya untuk admin
        if (session('kode_dept') != 0) {
            return redirect()->back()->with(['warning' => 'Anda tidak memiliki izin untuk menghapus data']);
        }

        $hapus = DB::table('departemen')->where('kode_dept', $kode_dept)->delete();
        if ($hapus) {
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Dihapus']);
        }
    }
}
