<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class HrisController extends Controller
{
    /**
     * === INDEX LEMBUR ===
     * Tampilkan data lembur per tanggal (default: hari ini)
     * dan hanya anggota yang berada di bawah karu login.
     */
    public function indexLembur(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user   = Auth::guard('karyawan')->user();
        $pinKaru = $user->id;

        // Ambil SEMUA data karu milik user login
        $karuList = DB::table('karu')
            ->where('pin', $pinKaru)
            ->get();

        if ($karuList->isEmpty()) {
            return abort(403, 'Anda bukan karu atau tidak memiliki akses verifikasi lembur.');
        }

        // Ambil semua ID karu (bisa lebih dari 1)
        $idKaruList = $karuList->pluck('idkaru')->toArray();

        // Ambil semua anggota dari semua idkaru (distinct supaya tidak duplikat)
        $anggotaPins = DB::table('karupegawai')
            ->whereIn('idkaru', $idKaruList)
            ->pluck('pegawai_pin')
            ->unique()
            ->toArray();

        // Tanggal filter
        $tanggal = $request->get('tanggal') ?? Carbon::now()->toDateString();

        // Ambil data lembur berdasarkan tanggal dan anggota karu
        $lembur = DB::table('lembur as l')
            ->join('pegawai as p', 'l.pegawai_pin', '=', 'p.pegawai_pin')
            ->whereIn('l.pegawai_pin', $anggotaPins)
            ->whereDate('l.tgllembur', $tanggal)
            ->where('l.jenis', 'lembur')
            ->select('l.*', 'p.pegawai_nama', 'p.bagian', 'p.jabatan')
            ->orderBy('p.pegawai_nama', 'asc')
            ->get();

        return view('hris.lembur_index', [
            'karu'    => $karuList, 
            'tanggal' => $tanggal,
            'lembur'  => $lembur,
        ]);
    }

    /**
     * Form input jadwal lembur — hanya untuk anggota karu login
     */
    public function createLembur()
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::guard('karyawan')->user();
        $pinKaru = $user->id;

        // Ambil semua karu milik user
        $karuList = DB::table('karu')
            ->where('pin', $pinKaru)
            ->get();

        if ($karuList->isEmpty()) {
            abort(403, 'Anda bukan karu atau tidak memiliki akses.');
        }

        // Ambil semua idkaru
        $idKaruList = $karuList->pluck('idkaru')->toArray();

        // Ambil semua anggota dari semua karu
        $anggota = DB::table('karupegawai as kp')
            ->join('pegawai as p', 'kp.pegawai_pin', '=', 'p.pegawai_pin')
            ->whereIn('kp.idkaru', $idKaruList)
            ->select('p.pegawai_pin', 'p.pegawai_nama', 'p.jabatan', 'p.bagian')
            ->orderBy('p.pegawai_nama', 'asc')
            ->get();

        return view('hris.form_lembur', [
            'anggota' => $anggota,
        ]);
    }

    /**
     * Simpan jadwal lembur
     */
    public function storeLembur(Request $request)
    {
        $request->validate([
            'pegawai_pin' => 'required|integer',
            'tgllembur'   => 'required|date',
            'alasan'      => 'required|string|max:255',
        ]);

        // Pastikan pegawai terdaftar di bawah karu login
        $user = Auth::guard('karyawan')->user();
        $pinKaru = $user->id;

        $karu = DB::table('karu')->where('pin', $pinKaru)->first();
        if (!$karu) abort(403, 'Anda tidak memiliki akses.');

        $anggota = DB::table('karupegawai')
            ->where('idkaru', $karu->idkaru)
            ->where('pegawai_pin', $request->pegawai_pin)
            ->exists();

        if (!$anggota) {
            return redirect()->back()->with('error', 'Pegawai tersebut bukan anggota Anda.');
        }

        // Cegah duplikat lembur pada hari yang sama
        $exists = DB::table('lembur')
            ->where('pegawai_pin', $request->pegawai_pin)
            ->whereDate('tgllembur', $request->tgllembur)
            ->where('jenis', 'lembur')
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Pegawai ini sudah dijadwalkan lembur pada tanggal tersebut.');
        }

        // Simpan data lembur
        DB::table('lembur')->insert([
            'pegawai_pin' => $request->pegawai_pin,
            'tgllembur'   => $request->tgllembur,
            'alasan'      => $request->alasan,
            'jenis'       => 'lembur',
            'created_at'  => Carbon::now(),
        ]);

        return redirect()->route('hris.lembur_index')
            ->with('success', 'Jadwal lembur berhasil dibuat.');
    }

    public function cancel($id)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::guard('karyawan')->user();
        $pinKaru = $user->id;

        // Pastikan yang login adalah karu
        $karu = DB::table('karu')->where('pin', $pinKaru)->first();
        if (!$karu) {
            abort(403, 'Anda tidak memiliki akses membatalkan jadwal lembur.');
        }

        // Ambil data lembur
        $lembur = DB::table('lembur')->where('idlembur', $id)->first();
        if (!$lembur) {
            return redirect()->back()->with('error', 'Data lembur tidak ditemukan.');
        }

        // Pastikan lembur tersebut adalah milik anggota karu login
        $anggota = DB::table('karupegawai')
            ->where('idkaru', $karu->idkaru)
            ->where('pegawai_pin', $lembur->pegawai_pin)
            ->exists();

        if (!$anggota) {
            abort(403, 'Anda tidak berhak membatalkan lembur pegawai ini.');
        }

        // Hapus lembur
        DB::table('lembur')->where('idlembur', $id)->delete();

        return redirect()->back()->with('success', 'Jadwal lembur berhasil dibatalkan.');
    }

    /**
     * === JADWAL LEMBUR PEGAWAI ===
     * Menampilkan daftar lembur yang ditugaskan ke pegawai login hari ini.
     */
    public function jadwalLembur(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;
        $tanggal = $request->get('tanggal') ?? date('Y-m-d');

        // Ambil semua jadwal lembur pegawai pada tanggal itu
        $lembur = DB::table('lembur')
            ->where('pegawai_pin', $pin)
            ->whereDate('tgllembur', $tanggal)
            ->where('jenis', 'lembur')
            ->get();

        // Ambil semua ID lembur
        $lemburIds = $lembur->pluck('idlembur')->toArray();

        // Jika tidak ada lembur hari itu, kirimkan kosong saja
        if (empty($lemburIds)) {
            return view('hris.jadwal_lembur', [
                'lembur'    => $lembur,
                'tanggal'   => $tanggal,
                'presensi'  => [],
            ]);
        }

        // FIX: Ambil presensi lembur berdasarkan idlembur (bukan tanggal)
        // Agar lembur OUT lintas hari tetap terbaca
        $presensi = DB::table('presensi')
            ->where('nik', $pin)
            ->whereIn('idlembur', $lemburIds)
            ->whereIn('inoutmode', [5, 6]) // 5 = in, 6 = out
            ->select('idlembur', 'inoutmode')
            ->orderBy('tgl_presensi', 'desc')
            ->orderBy('jam_in', 'desc')    // ← gunakan kolom yang ada
            ->get()
            ->groupBy('idlembur')
            ->map(function ($item) {
                return $item->first()->inoutmode; // ambil mode terakhir
            });

        return view('hris.jadwal_lembur', [
            'lembur'    => $lembur,
            'tanggal'   => $tanggal,
            'presensi'  => $presensi,
        ]);
    }


    public function formAbsenLembur($idlembur, $mode)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;

        // Ambil data lembur + nama pegawai
        $lembur = DB::table('lembur')
            ->join('pegawai', 'lembur.pegawai_pin', '=', 'pegawai.pegawai_pin')
            ->select('lembur.*', 'pegawai.pegawai_nama')
            ->where('lembur.idlembur', $idlembur)
            ->where('lembur.pegawai_pin', $pin)
            ->first();

        if (!$lembur) {
            return redirect()->route('hris.jadwal_lembur')->with('error', 'Data lembur tidak ditemukan.');
        }

        $modeText = $mode == 'in' ? 'Lembur In' : 'Lembur Out';

        return view('hris.form_absen_lembur', [
            'lembur' => $lembur,
            'mode' => $mode,
            'modeText' => $modeText
        ]);
    }

    public function storeAbsenLembur(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
        }

        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;

        $idlembur   = $request->idlembur;
        $lokasi     = $request->lokasi;
        $image      = $request->image;
        $inoutmode  = $request->mode == 'in' ? 5 : 6;
        $tgl_presensi = date('Y-m-d');
        $jam = date('H:i:s');

        // Validasi gambar
        if (!$image) {
            return response()->json(['status' => 'error', 'message' => 'Gambar tidak ditemukan.']);
        }

        // Pastikan lembur valid
        $lembur = DB::table('lembur')
            ->where('idlembur', $idlembur)
            ->where('pegawai_pin', $pin)
            ->first();

        if (!$lembur) {
            return response()->json(['status' => 'error', 'message' => 'Data lembur tidak valid.']);
        }

        // Cek apakah sudah ada absen lembur in/out
        $cek = DB::table('presensi')
            ->where('nik', $pin)
            ->where('idlembur', $idlembur)
            ->where('inoutmode', $inoutmode)
            ->first();

        if ($cek) {
            $msg = $inoutmode == 5 ? 'Lembur masuk' : 'Lembur keluar';
            return response()->json(['status' => 'error', 'message' => "Anda sudah melakukan $msg hari ini."]);
        }

        // Jika out, pastikan sudah lembur in sebelumnya
        if ($inoutmode == 6) {
            $cekIn = DB::table('presensi')
                ->where('nik', $pin)
                ->where('idlembur', $idlembur)
                ->where('inoutmode', 5)
                ->first();
            if (!$cekIn) {
                return response()->json(['status' => 'error', 'message' => 'Anda belum melakukan Lembur In.']);
            }
        }

        // Proses simpan foto base64
        $image_parts = explode(";base64,", $image);
        if (count($image_parts) < 2) {
            return response()->json(['status' => 'error', 'message' => 'Format gambar tidak valid.']);
        }

        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $pin . '-' . $tgl_presensi . '-' . ($inoutmode == 5 ? 'lembur_in' : 'lembur_out') . '.png';
        $filePath = 'uploads/absensi/' . $fileName;

        // Simpan data ke DB
        $data = [
            'nik'           => $pin,
            'idlembur'      => $idlembur,
            'tgl_presensi'  => $tgl_presensi,
            'jam_in'        => $jam,
            'inoutmode'     => $inoutmode,
            'foto_in'       => $fileName,
            'lokasi'        => $lokasi,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        DB::table('presensi')->insert($data);
        Storage::disk('public')->put($filePath, $image_base64);

        $pesan = $inoutmode == 5 ? 'Lembur In berhasil dicatat!' : 'Lembur Out berhasil dicatat!';
        return response()->json(['status' => 'success', 'message' => $pesan]);
    }

    public function getAbsensiLock()
    {
        $lock = DB::table('absensilock')->latest('id')->first();

        if (!$lock) {
            return response()->json(['status' => 'error', 'message' => 'Data area absensi belum diset.']);
        }

        return response()->json([
            'status' => 'success',
            'lokasi' => $lock->lokasi,
            'radius' => $lock->radius
        ]);
    }

        /**
     * === JADWAL OPERASI PEGAWAI ===
     * Tampilkan daftar operasi yang bisa diabsen oleh pegawai login.
     * Sumber data dari view: vwjadwaloperasi
     */
    public function jadwalOperasi(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $tanggal = $request->get('tanggal') ?? date('Y-m-d');
        $hariIni = date('Y-m-d');

        // Ambil data operasi berdasarkan tanggal filter
        $operasi = DB::table('vwjadwaloperasi')
            ->whereDate('tanggal_rencana', $tanggal)
            ->orderBy('tanggal_rencana', 'asc')
            ->get();

        // Ambil data presensi operasi (in/out)
        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;

        $presensi = DB::table('presensi')
            ->select('idoperasi', DB::raw('MAX(inoutmode) as inoutmode'))
            ->where('nik', $pin)
            ->whereDate('tgl_presensi', $tanggal)
            ->whereIn('inoutmode', [3, 4])
            ->groupBy('idoperasi')
            ->pluck('inoutmode', 'idoperasi');


        return view('hris.jadwal_operasi', [
            'operasi'   => $operasi,
            'tanggal'   => $tanggal,
            'hariIni'   => $hariIni,
            'presensi'  => $presensi
        ]);
    }


    /**
     * === FORM ABSEN OPERASI ===
     * Form absen untuk operasi in / out
     */
    public function formAbsenOperasi($id, $mode)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $operasi = DB::table('vwjadwaloperasi')
            ->where('id', $id)
            ->first();

        if (!$operasi) {
            return redirect()->route('hris.jadwal_operasi')->with('error', 'Data operasi tidak ditemukan.');
        }

        $modeText = $mode == 'in' ? 'Operasi In' : 'Operasi Out';

        return view('hris.form_absen_operasi', [
            'operasi' => $operasi,
            'mode' => $mode,
            'modeText' => $modeText
        ]);
    }

    /**
     * === STORE ABSEN OPERASI ===
     * Simpan foto dan lokasi absensi (in/out) operasi
     */
    public function storeAbsenOperasi(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
        }

        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;

        $id         = $request->id;
        $no_rm      = $request->no_rm;
        $lokasi     = $request->lokasi;
        $image      = $request->image;
        $inoutmode  = $request->mode == 'in' ? 3 : 4;
        $tgl_presensi = date('Y-m-d');
        $jam = date('H:i:s');

        if (!$image) {
            return response()->json(['status' => 'error', 'message' => 'Gambar tidak ditemukan.']);
        }

        // Validasi data operasi
        $operasi = DB::table('vwjadwaloperasi')->where('id', $id)->first();
        if (!$operasi) {
            return response()->json(['status' => 'error', 'message' => 'Data operasi tidak valid.']);
        }

        // Cek apakah sudah absen in/out
        $cek = DB::table('presensi')
            ->where('nik', $pin)
            ->where('idoperasi', $id)
            ->where('inoutmode', $inoutmode)
            ->first();

        if ($cek) {
            $msg = $inoutmode == 3 ? 'Operasi In' : 'Operasi Out';
            return response()->json(['status' => 'error', 'message' => "Anda sudah melakukan $msg hari ini."]);
        }

        // Jika Out, pastikan sudah In
        if ($inoutmode == 4) {
            $cekIn = DB::table('presensi')
                ->where('nik', $pin)
                ->where('idoperasi', $id)
                ->where('inoutmode', 3)
                ->first();
            if (!$cekIn) {
                return response()->json(['status' => 'error', 'message' => 'Anda belum melakukan Operasi In.']);
            }
        }

        // Proses simpan foto base64
        $image_parts = explode(";base64,", $image);
        if (count($image_parts) < 2) {
            return response()->json(['status' => 'error', 'message' => 'Format gambar tidak valid.']);
        }

        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $pin . '-' . $tgl_presensi . '-' . ($inoutmode == 3 ? 'operasi_in' : 'operasi_out') . '-' . $id . '.png';
        $filePath = 'uploads/absensi/' . $fileName;

        // Simpan ke tabel presensi
        DB::table('presensi')->insert([
            'nik'           => $pin,
            'idoperasi'     => $id,
            'no_rm'         => $no_rm,
            'tgl_presensi'  => $tgl_presensi,
            'jam_in'        => $jam,
            'inoutmode'     => $inoutmode,
            'foto_in'       => $fileName,
            'lokasi'        => $lokasi,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        Storage::disk('public')->put($filePath, $image_base64);

        $pesan = $inoutmode == 3 ? 'Operasi In berhasil dicatat!' : 'Operasi Out berhasil dicatat!';
        return response()->json(['status' => 'success', 'message' => $pesan]);
    }

    public function radiologi()
    {
        $hariini = date("Y-m-d");
        $nik = Auth::guard('karyawan')->user()->nik;
        $cek = DB::table('radiologi')->where('tgl_presensi', $hariini)->where('nik', $nik)->count();
        return view('hris.radiologi', compact('cek'));
    }


    public function radiologistore(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'kegiatan' => 'required|string|max:255',
            'image' => 'required|string',
            'lokasi' => 'required|string'
        ]);

        // Data user dan waktu
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_presensi = date("Y-m-d");
        $jam = date("H:i:s");
        
        // Cek apakah sudah ada absen hari ini
        $cek = DB::table('radiologi')
            ->where('tgl_presensi', $tgl_presensi)
            ->where('nik', $nik)
            ->count();
            
        if ($cek > 0) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Anda sudah melakukan absen masuk hari ini.'
            ]);
        }

        // Proses penyimpanan gambar
        try {
            $image_parts = explode(";base64,", $request->image);
            if (count($image_parts) < 2) {
                throw new \Exception('Format gambar tidak valid');
            }
            
            $image_base64 = base64_decode($image_parts[1]);
            if (!$image_base64) {
                throw new \Exception('Gagal mendecode gambar');
            }
            
            $formatName = $nik . "-" . $tgl_presensi . "-" . time();
            $fileName = "presensi_" . $formatName . ".png";
            $filePath = 'uploads/absensi/' . $fileName;
            
            // Simpan gambar ke storage
            Storage::disk('public')->put($filePath, $image_base64);
            
            // Data untuk disimpan
            $data = [
                'nik' => $nik,
                'kegiatan' => $request->kegiatan,
                'tgl_presensi' => $tgl_presensi,
                'jam_in' => $jam,
                'foto_in' => $fileName,
                'lokasi' => $request->lokasi,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Simpan ke database
            $simpan = DB::table('radiologi')->insert($data);
            
            if (!$simpan) {
                // Hapus gambar yang sudah disimpan jika gagal insert database
                Storage::disk('public')->delete($filePath);
                throw new \Exception('Gagal menyimpan data presensi');
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Absensi berhasil dicatat',
                'type' => 'in'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function ajukanLembur()
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        return view('hris.form_ajukan_lembur');
    }


    public function storeAjukanLembur(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
        }

        $user = Auth::guard('karyawan')->user();
        $pin  = $user->id;

        $lokasi       = $request->lokasi;
        $image        = $request->image;
        $alasan       = trim($request->alasan);
        $inoutmode    = $request->mode == 'in' ? 5 : 6; // 5 = lembur in, 6 = lembur out
        $tgl_presensi = date('Y-m-d');
        $jam          = date('H:i:s');

        if (!$image) {
            return response()->json(['status' => 'error', 'message' => 'Gambar tidak ditemukan.']);
        }

        // ✅ Alasan wajib saat IN
        if ($inoutmode == 5 && !$alasan) {
            return response()->json(['status' => 'error', 'message' => 'Alasan lembur wajib diisi.']);
        }

        // ✅ Ambil lembur terakhir hari ini
        $lembur = DB::table('lembur')
            ->where('pegawai_pin', $pin)
            ->whereDate('tgllembur', $tgl_presensi)
            ->whereNull('deleted_at')
            ->orderBy('idlembur', 'desc')
            ->first();

        // ✅ Cek apakah lembur terakhir sudah OUT
        $lemburSudahOut = false;
        if ($lembur) {
            $cekOut = DB::table('presensi')
                ->where('idlembur', $lembur->idlembur)
                ->where('inoutmode', 6)
                ->first();

            $lemburSudahOut = $cekOut ? true : false;
        }

        // ================================
        // ✅ PROSES IN
        // ================================
        if ($inoutmode == 5) {

            // ❌ Jika lembur sebelumnya belum OUT → larang IN baru
            if ($lembur && !$lemburSudahOut) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda masih dalam sesi lembur sebelumnya. Silakan Lembur Out dahulu.'
                ]);
            }

            // ✅ Buat lembur baru
            $idlembur = DB::table('lembur')->insertGetId([
                'pegawai_pin' => $pin,
                'tgllembur'   => $tgl_presensi,
                'alasan'      => $alasan,
                'jenis'       => 'lembur',
                'created_at'  => now(),
                'updated_at'  => now()
            ]);
        }

        // ================================
        // ✅ PROSES OUT
        // ================================
        if ($inoutmode == 6) {

            if (!$lembur) {
                return response()->json(['status' => 'error', 'message' => 'Anda belum melakukan Lembur In.']);
            }

            if ($lemburSudahOut) {
                return response()->json(['status' => 'error', 'message' => 'Lembur sebelumnya sudah selesai. Silakan Lembur In lagi.']);
            }

            $idlembur = $lembur->idlembur;
        }

        // ✅ Cegah duplikat IN/OUT pada lembur aktif
        $cekPresensi = DB::table('presensi')
            ->where('nik', $pin)
            ->where('idlembur', $idlembur)
            ->where('inoutmode', $inoutmode)
            ->first();

        if ($cekPresensi) {
            return response()->json(['status' => 'error', 'message' => 'Presensi lembur sudah tercatat.']);
        }

        // ✅ Simpan Foto Base64
        try {
            $image_parts = explode(";base64,", $image);
            $image_base64 = base64_decode($image_parts[1]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Format gambar tidak valid.']);
        }

        // ✅ Foto unik per lembur per mode
        $fileName = "{$pin}-{$tgl_presensi}-{$idlembur}-".($inoutmode == 5 ? 'in' : 'out').".png";
        $filePath = 'uploads/absensi/'.$fileName;

        Storage::disk('public')->put($filePath, $image_base64);

        // ✅ Insert presensi
        DB::table('presensi')->insert([
            'nik'           => $pin,
            'idlembur'      => $idlembur,
            'tgl_presensi'  => $tgl_presensi,
            'jam_in'        => $jam,
            'inoutmode'     => $inoutmode,
            'foto_in'       => $fileName,
            'lokasi'        => $lokasi,
            'created_at'    => now(),
        ]);

        $pesan = $inoutmode == 5 ? 'Lembur In berhasil dicatat!' : 'Lembur Out berhasil dicatat!';
        return response()->json(['status' => 'success', 'message' => $pesan]);
    }

    public function verifyLembur($id)
    {
        DB::table('lembur')
            ->where('idlembur', $id)
            ->update([
                'verified_at' => Carbon::now()
            ]);

        return back()->with('success', 'Lembur berhasil diverifikasi.');
    }

}
