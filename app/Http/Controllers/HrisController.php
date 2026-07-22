<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HrisController extends Controller
{
    public function idCard(): View|\Illuminate\Http\RedirectResponse
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $pegawai = $this->currentPegawai();

        if (!$pegawai) {
            abort(404, 'Data pegawai tidak ditemukan.');
        }

        return view('hris.idcard', [
            'pegawai' => $pegawai,
            'photoUrl' => $this->pegawaiPhotoUrl($pegawai),
            'savedCardUrl' => $this->savedIdCardUrl($pegawai),
            'templateUrl' => asset('idcard/background.jpg'),
        ]);
    }

    public function saveIdCard(Request $request): JsonResponse
    {
        if (!Auth::guard('karyawan')->check()) {
            return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $data = $request->validate([
            'image' => ['required', 'string'],
        ]);

        if (!preg_match('/^data:image\/png;base64,/', $data['image'])) {
            return response()->json(['message' => 'Format gambar ID card tidak valid.'], 422);
        }

        $image = base64_decode(substr($data['image'], strpos($data['image'], ',') + 1), true);

        if ($image === false) {
            return response()->json(['message' => 'Gambar ID card gagal diproses.'], 422);
        }

        $pegawai = $this->currentPegawai();

        if (!$pegawai) {
            return response()->json(['message' => 'Data pegawai tidak ditemukan.'], 404);
        }

        $path = $this->savedIdCardPath($pegawai);
        $absolutePath = $this->smartIdCardAbsolutePath($pegawai);

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        file_put_contents($absolutePath, $image);

        return response()->json([
            'message' => 'ID card berhasil disimpan.',
            'url' => $this->smartStorageUrl($path).'?v='.time(),
        ]);
    }

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

        // Ambil jadwal lembur pada tanggal itu + sesi aktif lintas hari
        $lembur = DB::table('lembur')
            ->where('pegawai_pin', $pin)
            ->where('jenis', 'lembur')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($tanggal): void {
                $query->whereDate('tgllembur', $tanggal)
                    ->orWhere(function ($subQuery): void {
                        $subQuery->whereNotExists(function ($existsQuery) {
                            $existsQuery->select(DB::raw(1))
                                ->from('presensi as p2')
                                ->whereColumn('p2.idlembur', 'lembur.idlembur')
                                ->where('p2.inoutmode', 6);
                        });
                    });
            })
            ->orderByDesc('tgllembur')
            ->orderByDesc('idlembur')
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

    private function queryJadwalOperasiBaru($whereSql = '', array $bindings = [])
    {
        $sql = "
            SELECT
                so.norec::text AS id,
                so.norec::text AS order_id,
                so.noorder,

                so.tglpelayananawal AS tanggal_rencana,
                so.tglpelayananawal AS tgl_rencana_pelaksanaan,
                so.tglpelayananawal::date AS tanggal_rencana_date,
                TO_CHAR(so.tglpelayananawal, 'HH24:MI') AS jam_rencana,

                pd.noregistrasi,
                ps.nocm AS no_rm,
                ps.namapasien AS nama_pasien,
                ps.tgllahir,
                jk.jeniskelamin,

                ru.namaruangan AS ruangan_asal,
                pg.namalengkap AS dokter_operator,

                jo.jenisoperasi,
                ko.namakamarok AS nama_kamar,
                ko.namakamarok AS kamar_operasi,

                so.estimasiwaktuoperasi AS estimasi_durasi,
                so.keteranganorder,
                so.keteranganlainnya AS rencana_tindakan

            FROM strukorder_t so

            LEFT JOIN pasiendaftar_t pd
                ON pd.norec = so.noregistrasifk

            LEFT JOIN pasien_m ps
                ON ps.id = pd.nocmfk

            LEFT JOIN jeniskelamin_m jk
                ON jk.id = ps.objectjeniskelaminfk

            LEFT JOIN ruangan_m ru
                ON ru.id = so.objectruanganfk

            LEFT JOIN pegawai_m pg
                ON pg.id = so.objectpegawaiorderfk

            LEFT JOIN jenisoperasi_m jo
                ON jo.id = so.jenisoperasifk

            LEFT JOIN kamaroperasi_m ko
                ON ko.id = so.objectkamaroperasifk

            WHERE COALESCE(so.statusenabled, TRUE) = TRUE
              AND (
                  so.keteranganorder ILIKE '%jadwal operasi%'
                  OR so.jenisoperasifk IS NOT NULL
                  OR so.objectkamaroperasifk IS NOT NULL
              )
              {$whereSql}

            ORDER BY so.tglpelayananawal ASC
        ";

        return DB::connection('simrs_farmasi')->select($sql, $bindings);
    }

    private function getJadwalOperasiById($id)
    {
        $result = $this->queryJadwalOperasiBaru('AND so.norec::text = ?', [$id]);

        return $result[0] ?? null;
    }

    private function getJadwalOperasiByTanggal($tanggal)
    {
        return collect($this->queryJadwalOperasiBaru('AND so.tglpelayananawal::date = ?', [$tanggal]));
    }

    /**
     * === JADWAL OPERASI PEGAWAI ===
     * Tampilkan daftar operasi yang bisa diabsen oleh pegawai login.
     * Sumber data dari SIMRS Farmasi PostgreSQL.
     */
    public function jadwalOperasi(Request $request)
    {
        if (!Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $tanggal = $request->get('tanggal') ?? date('Y-m-d');
        $hariIni = date('Y-m-d');

        // Ambil data operasi berdasarkan tanggal filter dari SIMRS baru
        $operasi = $this->getJadwalOperasiByTanggal($tanggal);

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

        $operasi = $this->getJadwalOperasiById($id);

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
        $operasi = $this->getJadwalOperasiById($id);
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

        // Ambil sesi lembur aktif terakhir, walaupun IN dibuat di hari sebelumnya
        $lembur = DB::table('lembur')
            ->where('pegawai_pin', $pin)
            ->where('jenis', 'lembur')
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('presensi as p2')
                    ->whereColumn('p2.idlembur', 'lembur.idlembur')
                    ->where('p2.inoutmode', 6);
            })
            ->orderByDesc('tgllembur')
            ->orderByDesc('idlembur')
            ->first();

        // Cek apakah sesi aktif tadi sudah OUT
        $lemburSudahOut = false;
        if ($lembur) {
            $lemburSudahOut = DB::table('presensi')
                ->where('idlembur', $lembur->idlembur)
                ->where('inoutmode', 6)
                ->exists();
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
                return response()->json(['status' => 'error', 'message' => 'Anda belum memiliki sesi Lembur In yang aktif.']);
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

    private function currentPegawai(): ?object
    {
        $user = Auth::guard('karyawan')->user();

        if (!$user) {
            return null;
        }

        $nik = trim((string) ($user->nik ?? ''));
        $pin = trim((string) ($user->pegawai_pin ?? $user->id ?? ''));

        if ($nik === '' && $pin === '') {
            return null;
        }

        return DB::table('pegawai')
            ->where(function ($query) use ($nik, $pin): void {
                if ($nik !== '') {
                    $query->where('nik', $nik);
                }

                if ($pin !== '') {
                    $query->orWhere('pegawai_pin', $pin);
                }
            })
            ->first();
    }

    private function pegawaiPhotoUrl(object $pegawai): string
    {
        $photos = [
            trim((string) ($pegawai->pas_photo ?? '')),
            trim((string) ($pegawai->fotomobile ?? '')),
            trim((string) ($pegawai->gambar ?? '')),
            trim((string) ($pegawai->foto ?? '')),
        ];

        $photo = collect($photos)->first(fn (string $value): bool => $value !== '') ?? '';

        if ($photo === '') {
            return asset('assets/img/icon/logo.png');
        }

        if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
            return $photo;
        }

        if (str_starts_with($photo, '/')) {
            return $photo;
        }

        $smartPhotoPath = $this->smartStoragePublicPath($photo);
        if (is_file($smartPhotoPath)) {
            $mime = mime_content_type($smartPhotoPath) ?: 'image/jpeg';
            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($smartPhotoPath));
        }

        if (Storage::disk('public')->exists('uploads/karyawan/'.$photo)) {
            return Storage::url('uploads/karyawan/'.$photo);
        }

        if (Storage::disk('public')->exists($photo)) {
            return Storage::url($photo);
        }

        return asset('assets/img/icon/logo.png');
    }

    private function savedIdCardPath(object $pegawai): string
    {
        $identifier = (string) ($pegawai->pegawai_id ?? $pegawai->nik ?? $pegawai->pegawai_pin ?? 'pegawai');
        return 'hris/employees/id-cards/pegawai-'.$identifier.'.png';
    }

    private function savedIdCardUrl(object $pegawai): ?string
    {
        $path = $this->savedIdCardPath($pegawai);

        $absolutePath = $this->smartStoragePublicPath($path);

        if (!is_file($absolutePath)) {
            return null;
        }

        return $this->smartStorageUrl($path).'?v='.filemtime($absolutePath);
    }

    private function smartIdCardAbsolutePath(object $pegawai): string
    {
        return $this->smartStoragePublicPath($this->savedIdCardPath($pegawai));
    }

    private function smartStoragePublicPath(string $path = ''): string
    {
        $base = rtrim((string) env('SMART_HRIS_STORAGE_PUBLIC_PATH', base_path('../smartrs/storage/app/public')), DIRECTORY_SEPARATOR);
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($path, '/\\'));

        return $relative === '' ? $base : $base.DIRECTORY_SEPARATOR.$relative;
    }

    private function smartStorageUrl(string $path): string
    {
        $base = rtrim((string) env('SMART_HRIS_URL', 'https://smart.rspkuboja.com'), '/');

        return $base.'/storage/'.ltrim($path, '/');
    }

}
