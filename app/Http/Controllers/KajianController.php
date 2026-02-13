<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class KajianController extends Controller
{
    public function formScan()
    {
        return view('kehadiran.form');
    }

    public function submitScan(Request $request)
    {
        $request->validate([
            'idkajian' => 'required',
            'barcode' => 'required',
        ]);

        return redirect()->route('kehadiran.scan', [
            'idkajian' => $request->input('idkajian'),
            'barcode' => $request->input('barcode'),
        ]);
    }

    /**
     * Proses scan QR Code dan catat kehadiran
     */

    public function storeKehadiran(Request $request, $idkajian, $barcode)
    {
        // Validasi login dan guard
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::guard('karyawan')->user();
        $nik  = $user->nik;
        $nama = $user->nama_lengkap;

        // Cek kajian
        $kajian = DB::table('kajian')->where('idkajian', $idkajian)->first();
        if (!$kajian) {
            abort(404, 'Kajian tidak ditemukan.');
        }

        // Cek barcode duplikat
        $barcodeExists = DB::table('kehadiran_kajian')
            ->where('idkajian', $idkajian)
            ->where('barcodeuniq', $barcode)
            ->exists();

        if ($barcodeExists) {
            return view('kehadiran.result', [
                'status' => 'duplikat_barcode',
                'kajian' => $kajian,
                'barcode'=>$barcode,
                'message' => 'Barcode ini sudah digunakan.',
            ]);
        }

        // Cek apakah user sudah scan hari ini
        $alreadyScanned = DB::table('kehadiran_kajian')
            ->where('idkajian', $idkajian)
            ->where('nik', $nik)
            ->whereDate('waktu_scan', Carbon::now()->toDateString())
            ->exists();

        if ($alreadyScanned) {
            return view('kehadiran.result', [
                'status' => 'duplikat_user',
                'kajian' => $kajian,
                'barcode'=>$barcode,
                'message' => 'Anda sudah melakukan scan hari ini untuk kajian ini.',
            ]);
        }

        // Simpan ke database
        DB::table('kehadiran_kajian')->insert([
            'idkajian'    => $idkajian,
            'barcodeuniq' => $barcode,
            'nik'         => $nik,
            'nama'        => $nama,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'waktu_scan'  => now(),
        ]);

        return view('kehadiran.result', [
            'status' => 'sukses',
            'kajian' => $kajian,
            'barcode'=>$barcode,
            'message' => 'Kehadiran berhasil dicatat.',
        ]);
    }

}
