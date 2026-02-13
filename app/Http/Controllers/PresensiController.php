<?php

namespace App\Http\Controllers;

use App\Models\Pengajuanizin;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PresensiController extends Controller
{
    public function create()
    {
        $hariini = date("Y-m-d");
        $nik = Auth::guard('karyawan')->user()->nik;
        $cek = DB::table('presensi')->where('tgl_presensi', $hariini)->where('nik', $nik)->count();
        return view('presensi.create', compact('cek'));
    }

    public function store(Request $request)
{
    // Validasi input
    $validated = $request->validate([
        'judul' => 'required|string|max:255',
        'pemateri' => 'required|string|max:255',
        'image' => 'required|string',
        'lokasi' => 'required|string'
    ]);

    // Data user dan waktu
    $nik = Auth::guard('karyawan')->user()->nik;
    $tgl_presensi = date("Y-m-d");
    $jam = date("H:i:s");
    
    // Cek apakah sudah ada absen hari ini
    $cek = DB::table('presensi')
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
            'judul' => $request->judul,
            'pemateri' => $request->pemateri,
            'tgl_presensi' => $tgl_presensi,
            'jam_in' => $jam,
            'foto_in' => $fileName,
            'lokasi' => $request->lokasi,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        // Simpan ke database
        $simpan = DB::table('presensi')->insert($data);
        
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

    public function editprofile()
{
    $nik = Auth::guard('karyawan')->user()->nik;
    $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
    return view('presensi.editprofile', compact('pegawai'));
}


    public function updateprofile(Request $request)
{
    $nik = Auth::guard('karyawan')->user()->nik;

    $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
    if (!$pegawai) {
        return back()->with(['error' => 'Data tidak ditemukan.']);
    }

    // Data yang boleh diupdate
    $data = [
        'nohp'   => $request->no_hp,
        'email'  => $request->email,
        'alamat' => $request->alamat,
        'nbm'    => $request->nbm
    ];

    // Kalau password diisi
    if (!empty($request->password)) {
        $data['password'] = Hash::make($request->password);
    }

    // Proses upload foto
    if ($request->hasFile('foto')) {
        $foto = $nik . '.' . $request->file('foto')->getClientOriginalExtension();
        $request->file('foto')->storeAs('uploads/karyawan', $foto, 'public'); // folder tetap "karyawan"
        $data['fotomobile'] = $foto;
    } else {
        $data['fotomobile'] = $pegawai->fotomobile;
    }

    // Update ke tabel pegawai
    $update = DB::table('pegawai')->where('nik', $nik)->update($data);

    if ($update) {
        return back()->with(['success' => 'Profil berhasil diperbarui.']);
    } else {
        return back()->with(['error' => 'Profil gagal diperbarui.']);
    }
}



public function histori()
{
    $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
    return view('presensi.histori', compact('namabulan'));
}

public function gethistori(Request $request){
    $bulan = $request->bulan;
    $tahun = $request->tahun;
    $nik = Auth::guard('karyawan')->user()->nik;

    $histori = DB::table('presensi')
    ->whereRaw('MONTH(tgl_presensi)="'.$bulan.'"')
    ->whereRaw('YEAR(tgl_presensi)="'.$tahun.'"')
    ->where('nik',$nik)
    ->orderBy('tgl_presensi')
    ->get();

    return view('presensi.gethistori', compact('histori'));
}
    public function izin()
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $dataizin = DB::table('pengajuan_izin')->where('nik', $nik)->get();
        return view('presensi.izin', compact('dataizin'));
    }

    public function buatizin()
    {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin = $request->tgl_izin;
        $status = $request->status;
        $keterangan = $request->keterangan;

        $data = [
            'nik' => $nik,
            'tgl_izin' => $tgl_izin,
            'status' => $status,
            'keterangan' => $keterangan
        ];

        $simpan = DB::table('pengajuan_izin')->insert($data);

        if( $simpan) {
            return redirect('/presensi/izin')->with(['success' => 'Data Berhasil Disimpan']);
        } else {
            return redirect('/presensi/izin')->with(['error' => 'Data Gagal Disimpan']);
        }
    }

    public function monitoring()
    {
        return view('presensi.monitoring');
    }

    public function getpresensi(Request $request)
    {
        $tanggal = $request->tanggal;
        $kode_dept = session('kode_dept');

        $presensi = DB::table('presensi')
            ->select('presensi.*', 'nama_lengkap', 'nama_dept')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->join('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->where('tgl_presensi', $tanggal);

        if ($kode_dept != 0) {
            $presensi->where('karyawan.kode_dept', $kode_dept);
        }

        $presensi = $presensi->get();

        return view('presensi.getpresensi', compact('presensi'));
    }

    public function tampilkanpeta(Request $request)
    {
        $id = $request->id;
        $presensi = DB::table('presensi')->where('id', $id)
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->first();

        return view('presensi.showmap', compact('presensi'));
    }

    public function laporan()
    {
        $kode_dept = session('kode_dept');
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];

        $karyawan = DB::table('karyawan');
        if ($kode_dept != 0) {
            $karyawan->where('kode_dept', $kode_dept);
        }
        $karyawan = $karyawan->orderBy('nama_lengkap')->get();

        return view('presensi.laporan', compact('namabulan', 'karyawan'));
    }


    public function cetaklaporan(Request $request)
    {
        $kode_dept = session('kode_dept');
        $nik = $request->nik;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];

        $karyawan = DB::table('karyawan')
            ->join('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->where('nik', $nik);

        if ($kode_dept != 0) {
            $karyawan->where('karyawan.kode_dept', $kode_dept);
        }

        $karyawan = $karyawan->first();

        if (!$karyawan) {
            return redirect()->back()->with(['warning' => 'Data tidak ditemukan atau tidak sesuai departemen']);
        }

        $presensi = DB::table('presensi')
            ->where('nik', $nik)
            ->whereRaw('MONTH(tgl_presensi) = ?', [$bulan])
            ->whereRaw('YEAR(tgl_presensi) = ?', [$tahun])
            ->orderBy('tgl_presensi')
            ->get();

        if (isset($_POST['exportexel'])) {
            $time = date("d-M-Y H:i:s");
            header("Content-type: application/vnd-ms-exel");
            header("Content-Disposition: attachment; filename=Laporan Presensi Ahad Pagi RS PKU $time.xls");
            return view('presensi.cetaklaporanexel', compact('bulan', 'tahun', 'namabulan', 'karyawan', 'presensi'));
        }

        return view('presensi.cetaklaporan', compact('bulan', 'tahun', 'namabulan', 'karyawan', 'presensi'));
    }


    public function rekap()
    {
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];
        return view('presensi.rekap', compact('namabulan'));
    }

    public function cetakrekap(Request $request)
    {
        $kode_dept = session('kode_dept');
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $namabulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni","Juli", "Agustus","September", "Oktober", "November", "Desember"];

        $rekap = DB::table('presensi')
            ->select('presensi.nik', 'karyawan.nama_lengkap', 'presensi.tgl_presensi', 'presensi.jam_in')
            ->join('karyawan', 'presensi.nik', '=', 'karyawan.nik')
            ->whereRaw('MONTH(tgl_presensi) = ?', [$bulan])
            ->whereRaw('YEAR(tgl_presensi) = ?', [$tahun])
            ->whereRaw('DAYOFWEEK(tgl_presensi) = 1');

        if ($kode_dept != 0) {
            $rekap->where('karyawan.kode_dept', $kode_dept);
        }

        $rekap = $rekap->orderBy('presensi.tgl_presensi', 'ASC')->get();

        if (isset($_POST['exportexel'])) {
            $time = date("d-M-Y H:i:s");
            header("Content-type: application/vnd-ms-exel");
            header("Content-Disposition: attachment; filename=Rekap Presensi Ahad Pagi RS PKU $time.xls");
        }

        return view('presensi.cetakrekap', compact('bulan', 'tahun', 'namabulan', 'rekap'));
    }

    public function izinsakit(Request $request)
    {
        $kode_dept = session('kode_dept');

        $query = Pengajuanizin::query();
        $query->select('karyawan.id', 'tgl_izin', 'pengajuan_izin.nik', 'nama_lengkap', 'jabatan', 'status', 'status_approved', 'keterangan');
        $query->join('karyawan', 'pengajuan_izin.nik', '=', 'karyawan.nik');

        if (!empty($request->dari) && !empty($request->sampai)) {
            $query->whereBetween('tgl_izin', [$request->dari, $request->sampai]);
        }

        if (!empty($request->nik)) {
            $query->where('pengajuan_izin.nik', $request->nik);
        }

        if (!empty($request->nama_lengkap)) {
            $query->where('nama_lengkap', 'like', '%'. $request->nama_lengkap. '%');
        }

        if ($request->status_approved === '0' || $request->status_approved === '1' || $request->status_approved === '2') {
            $query->where('status_approved', $request->status_approved);
        }

        if ($kode_dept != 0) {
            $query->where('karyawan.kode_dept', $kode_dept);
        }

        $query->orderBy('tgl_izin', 'desc');
        $izinsakit = $query->paginate(50);
        $izinsakit->appends($request->all());

        return view('presensi.izinsakit', compact('izinsakit'));
    }

    public function approvedizinsakit(Request $request){
        $status_approved = $request->status_approved;
        $id_izinsakit_form = $request->id_izinsakit_form;
        $update = DB::table('pengajuan_izin')->where('id', $id_izinsakit_form)->update([
            'status_approved' => $status_approved
        ]);
        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Di Update']);
        }
    }

    public function batalkanizinsakit($id)
    {
        $update = DB::table('pengajuan_izin')->where('id', $id)->update([
            'status_approved' => 0
        ]);
        if ($update) {
            return Redirect::back()->with(['success' => 'Data Berhasil Di Update']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Di Update']);
        }
    }
    
    public function deleteizinsakit($id)
    {
        $delete = DB::table('pengajuan_izin')->where('id', $id)->delete();
        if ($delete) {
            return Redirect::back()->with(['success' => 'Data Berhasil Dihapus']);
        } else {
            return Redirect::back()->with(['warning' => 'Data Gagal Dihapus']);
        }
    }

    public function cekpengajuanizin(Request $request)
    {
        $tgl_izin = $request->tgl_izin;
        $nik = Auth::guard('karyawan')->user()->nik;
        
        $cek = DB::table('pengajuan_izin')->where('nik', $nik)->where('tgl_izin', $tgl_izin)->count();;
        return $cek;
    }

    public function recordkajian(Request $request)
    {
        $nik = Auth::guard('karyawan')->user()->nik;

        // Ambil bulan dari request atau default bulan ini
        $bulan = $request->bulan ?? date('Y-m');

        // Hitung range tanggal awal dan akhir bulan
        $startDate = Carbon::parse($bulan . '-01')->startOfMonth()->toDateTimeString();
$endDate   = Carbon::parse($bulan . '-01')->endOfMonth()->endOfDay()->toDateTimeString();


        $record = DB::table('kehadiran_kajian')
            ->select(
                'idkajian',
                'waktu_scan',
                'nik',
                'nama',
                DB::raw('(SELECT namakajian FROM kajian a WHERE a.idkajian = kehadiran_kajian.idkajian) as namakajian')
            )
            ->where('nik', $nik)
            ->whereDate('waktu_scan', '>=', $startDate)
->whereDate('waktu_scan', '<=', $endDate)

            ->orderBy('waktu_scan', 'desc')
            ->get();

        return view('presensi.recordkajian', compact('record', 'bulan'));
    }

    public function agendaForm()
    {
        return view('presensi.agenda');
    }

    public function storeAgenda(Request $request)
    {
        $request->validate([
            'namaagenda' => 'required|string|max:255',
            'tgl'        => 'required|date',
            'waktu'      => 'required',
            'jenis'      => 'required|string|max:100',
            'lokasi'     => 'required|string|max:255',
            'peserta'    => 'required|string|max:255',
        ]);

        $user = Auth::guard('karyawan')->user();

        DB::table('agenda')->insert([
            'namaagenda' => $request->namaagenda,
            'tgl'        => $request->tgl,
            'waktu'      => $request->waktu,
            'jenis'      => $request->jenis,
            'lokasi'     => $request->lokasi,
            'peserta'    => $request->peserta,
            'nik'        => $user->nik,
            'creator'    => $user->nama_lengkap,
        ]);

        return redirect()->back()->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function listAgenda(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');

        // Validasi format bulan
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = date('Y-m');
        }

        $agenda = DB::table('agenda')
            ->whereYear('tgl', date('Y', strtotime($bulan)))
            ->whereMonth('tgl', date('m', strtotime($bulan)))
            ->orderBy('tgl')
            ->orderBy('waktu')
            ->get();

        return view('presensi.list_agenda', compact('agenda', 'bulan'));
    }

    public function deleteAgenda($id)
    {
        $userNik = Auth::guard('karyawan')->user()->nik;

        $agenda = DB::table('agenda')->where('id', $id)->first();

        if (!$agenda) {
            return redirect()->back()->with('error', 'Agenda tidak ditemukan.');
        }

        if ($agenda->nik !== $userNik) {
            return redirect()->back()->with('error', 'Anda tidak berhak menghapus agenda ini.');
        }

        DB::table('agenda')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Agenda berhasil dihapus.');
    }

    public function pasienList()
    {
    $urlLogin  = 'http://192.168.100.231/login';
    $urlPasien = 'http://192.168.100.231/emr/pasien?default_pelayanan=&status_pelayanan=0&instalasi=&q=&is_today=false&my_pasien=false';

    $username = 'dzulfikar';
    $password = 'm45uk147853';

    $cookieFile = tempnam(sys_get_temp_dir(), 'cookie_');
    $ch = curl_init();

    try {
        // Ambil CSRF Token
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlLogin,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Gagal menghubungi server login.');
        }

        preg_match('/name="_token" value="(.+?)"/', $response, $matches);
        $token = $matches[1] ?? '';
        if (!$token) {
            throw new \Exception('Token CSRF tidak ditemukan.');
        }

        // Login ke SIMRS
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlLogin,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                '_token'   => $token,
                'username' => $username,
                'password' => $password,
            ]),
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);

        // Ambil Data Pasien
        curl_setopt_array($ch, [
            CURLOPT_URL => $urlPasien,
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 25,
        ]);

        $response = curl_exec($ch);

    } catch (\Exception $e) {
        curl_close($ch);
        unlink($cookieFile);
        return redirect()->back()->with('error', $e->getMessage());
    }

    curl_close($ch);
    unlink($cookieFile);

    $dataPasien = json_decode($response);
    if (!$dataPasien || !is_array($dataPasien)) {
        return redirect()->back()->with('error', 'Data pasien tidak valid atau kosong.');
    }

    // Rekap
    $rekapInstalasi = [];
    $rekapKelas = [];

    foreach ($dataPasien as $pasien) {
        $instalasi = $pasien->mutasi_kamar_terakhir->ruangan->sub_pelayanan->nama_instalasi
            ?? $pasien->label_instalasi
            ?? 'Tidak diketahui';

        $kelas = $pasien->mutasi_kamar_terakhir->ruangan->nama
            ?? 'Tanpa Kelas';

        $rekapInstalasi[$instalasi] = ($rekapInstalasi[$instalasi] ?? 0) + 1;
        $rekapKelas[$kelas] = ($rekapKelas[$kelas] ?? 0) + 1;
    }

    return view('presensi.pasien', [
        'dataPasien'     => $dataPasien,
        'rekapInstalasi' => $rekapInstalasi,
        'rekapKelas'     => $rekapKelas
    ]);
}

public function getPegawaiAPI(Request $request)
{
    try {
        // Query data pegawai
        $pegawai = DB::table('pegawai')
            ->select(
                'nik as pegawai_id',
                'nama_lengkap as pegawai_nama',
                'jabatan'
            )
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        // Format response
        $response = [
            'status' => 'success',
            'message' => 'Data pegawai berhasil diambil',
            'data' => $pegawai,
            'total' => $pegawai->count(),
            'timestamp' => now()->toDateTimeString()
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

}
