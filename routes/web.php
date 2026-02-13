<?php

use App\Http\Controllers\DepartemenController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KalenderController;
use App\Http\Controllers\KajianController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\AhadPagiController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\LosAbsenController;
use App\Http\Controllers\QuranController;
use App\Http\Controllers\HrisController;
use App\Http\Controllers\NewKalenderController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // Jika sudah login sebagai karyawan, arahkan ke dashboard
    if (Auth::guard('karyawan')->check()) {
        return redirect('/dashboard');
    }

    return view('auth.login');
})->name('login');

// Proses login
Route::post('/proseslogin', [AuthController::class, 'proseslogin']);

Route::middleware(['guest:user' ])->group(function () {
    Route::get('/panel', function () {
        return view('auth.loginadmin'); 
    })->name('loginadmin');
Route::post('/prosesloginadmin', [AuthController::class, 'prosesloginadmin']);    
});

Route::middleware(['auth:karyawan'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'proseslogout'])->name('logout');


    //Presensi
    Route::get('/presensi/create', [PresensiController::class, 'create']);
    Route::post('/presensi/store', [PresensiController::class, 'store']);

    //Edit Profile
    Route::get('/editprofile', [PresensiController::class, 'editprofile']);
    Route::post('presensi/{nik}/updateprofile', [PresensiController::class, 'updateprofile']);

    //Histori
    Route::get('/presensi/histori', [PresensiController::class, 'histori']);
    Route::post('/gethistori', [PresensiController::class, 'gethistori']);

    //Izin
    Route::get('/presensi/izin', [PresensiController::class, 'izin']);
    Route::get('/presensi/buatizin', [PresensiController::class, 'buatizin']);
    Route::post('/presensi/storeizin', [PresensiController::class, 'storeizin']);
    Route::post('/presensi/cekpengajuanizin', [PresensiController::class, 'cekpengajuanizin']);
    Route::match(['get', 'post'], '/kalender', [KalenderController::class, 'index'])->name('kalender.index')->middleware('redirect.future.calendar');
    Route::get('/kalender/lembur', [KalenderController::class, 'lembur'])->name('kalender.lembur');
    Route::post('/kalender/lembur', [KalenderController::class, 'lembur']);
    Route::get('/statistik', [KalenderController::class, 'statistik'])->name('statistik');

    Route::match(['get', 'post'],'/newkalender', [NewKalenderController::class, 'index'])->name('newkalender.index')->middleware('redirect.past.calendar');
    Route::match(['get', 'post'],'/newkalender/lihat', [NewKalenderController::class, 'lihat'])->name('newkalender.lihat');
    Route::post('/newkalender/summary', [NewKalenderController::class, 'getSummaryData'])->name('newkalender.summary');

    // Tambahkan di routes/web.php
    Route::get('/test-kalender', function() {
        $pegawaiPin = Auth::guard('karyawan')->user()->pegawai_pin;
        $bulan = date('Y-m');
        
        $model = new App\Models\Newkalender_model();
        $data = $model->getDataKalenderWithNightShift($pegawaiPin, $bulan);
        
        dd([
            'pegawai_pin' => $pegawaiPin,
            'bulan' => $bulan,
            'data_count' => count($data),
            'sample_data' => array_slice($data, 0, 5)
        ]);
    });

    // Tambahkan route ini untuk cek struktur user
    Route::get('/debug-user', function() {
        $user = Auth::guard('karyawan')->user();
        
        dd([
            'user' => $user,
            'attributes' => $user ? $user->getAttributes() : 'No user',
            'guard' => 'karyawan'
        ]);
    });

    // kehadiran kajian
    Route::get('/scan-qr', function () {
        return view('kehadiran.scan-camera');
    })->name('form.scan.camera');

    Route::get('/presensi/recordkajian', [PresensiController::class, 'recordkajian'])->name('presensi.recordkajian');

    Route::get('/form-scan', [KajianController::class, 'formScan'])->name('form.scan');
    Route::post('/form-scan', [KajianController::class, 'submitScan'])->name('form.scan.submit');
    Route::get('/kehadiran/{idkajian}/{barcode}', [KajianController::class, 'storeKehadiran']);

    // security
    Route::get('/inventaris-security', [SecurityController::class, 'inventarisIndex'])->name('inventaris.index');
    Route::post('/inventaris-security/update', [SecurityController::class, 'inventarisUpdate'])->name('inventaris.update');
    Route::get('/kegiatan-security', [SecurityController::class, 'kegiatanIndex'])->name('kegiatan.index');
    Route::post('/kegiatan-security/store', [SecurityController::class, 'kegiatanStore'])->name('kegiatan.store');

    Route::get('/presensi/agenda', [PresensiController::class, 'agendaForm'])->name('presensi.agenda');
    Route::post('/presensi/agenda', [PresensiController::class, 'storeAgenda'])->name('presensi.agenda.store');
    Route::get('/presensi/agenda/list', [PresensiController::class, 'listAgenda'])->name('presensi.agenda.list');
    Route::delete('/presensi/agenda/{id}/delete', [PresensiController::class, 'deleteAgenda'])->name('presensi.agenda.delete');
    Route::get('/presensi/pasien', [PresensiController::class, 'pasienList'])->name('presensi.pasien');

    // ahadpagi
    Route::get('/ahadpagi', [AhadPagiController::class, 'index'])->name('ahadpagi.index');

    Route::get('/sale', [SaleController::class, 'index'])->name('sale.index');

    // payroll
    Route::get('/payroll', function () {
    return redirect()->route('payroll.index', [date('Y')]);
    })->name('payroll');

    Route::get('/payroll/{tahun}', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/{tahun}/{bulan}', [PayrollController::class, 'detail'])->name('payroll.detail');
    Route::get('/slip-gaji/pdf/{tahun}/{bulan}', [App\Http\Controllers\PayrollController::class, 'downloadPDF'])->name('slip.pdf');

    Route::get('/losabsen', [LosAbsenController::class, 'index'])->name('losabsen.index');
    Route::post('/losabsen', [LosAbsenController::class, 'store'])->name('losabsen.store');

    Route::get('/quran', [QuranController::class, 'index'])->name('quran.index');
    Route::get('/quran/{nomor}', [QuranController::class, 'show'])->name('quran.show');
    Route::post('/quran/mark-rutin', [QuranController::class, 'markRutin'])
     ->name('quran.markRutin');

});

Route::prefix('hris')->group(function () {
    // Lembur
    Route::get('/lembur', [HrisController::class, 'indexLembur'])->name('hris.lembur_index');
    Route::get('/lembur/create', [HrisController::class, 'createLembur'])->name('hris.lembur_create');
    Route::post('/lembur/store', [HrisController::class, 'storeLembur'])->name('hris.lembur_store');
    Route::delete('/lembur/{id}/cancel', [HrisController::class, 'cancel'])->name('hris.lembur_cancel');
    Route::get('/lembur/ajukan', [HrisController::class, 'ajukanLembur'])->name('hris.ajukan_lembur');
    Route::post('/lembur/ajukan/store', [HrisController::class, 'storeAjukanLembur'])->name('hris.ajukan_lembur_store');
    Route::post('/lembur/{id}/verify', [HrisController::class,'verifyLembur'])
    ->name('hris.lembur_verify');

    // Jadwal lembur untuk pegawai
    Route::get('/jadwallembur', [HrisController::class, 'jadwalLembur'])->name('hris.jadwal_lembur');
    Route::get('/lembur/absen/{idlembur}/{mode}', [HrisController::class, 'formAbsenLembur'])->name('hris.form_lembur_absen');
    Route::post('/lembur/absen/store', [HrisController::class, 'storeAbsenLembur'])->name('hris.lembur_absen_store');
    Route::get('/hris/absensilock', [HrisController::class, 'getAbsensiLock'])->name('hris.absensilock.get');

    // Jadwal Operasi & Absen Operasi
    Route::get('/operasi', [HrisController::class, 'jadwalOperasi'])->name('hris.jadwal_operasi');
    Route::get('/operasi/absen/{no_rm}/{mode}', [HrisController::class, 'formAbsenOperasi'])->name('hris.operasi_absen_form');
    Route::post('/operasi/absen/store', [HrisController::class, 'storeAbsenOperasi'])->name('hris.operasi_absen_store');

    Route::get('/radiologi', [HrisController::class, 'radiologi'])->name('hris.radiologi');
    Route::post('/radiologi/store', [HrisController::class, 'radiologistore'])->name('hris.radiologi_store');
});

Route::middleware(['auth:user'])->group(function () {
    Route::get('/proseslogoutadmin', [AuthController::class, 'proseslogoutadmin']);
    Route::get('/panel/dashboardadmin', [DashboardController::class, 'dashboardadmin']);

    //karyawan
    Route::get('/karyawan', [KaryawanController::class, 'index']);
    Route::post('/karyawan/store', [KaryawanController::class, 'store']);
    Route::post('/karyawan/edit', [KaryawanController::class, 'edit']);
    Route::post('/karyawan/{nik}/update', [KaryawanController::class, 'update']);
    Route::post('/karyawan/{nik}/delete', [KaryawanController::class, 'delete']);

    //Departemen
    Route::get('/departemen', [DepartemenController::class, 'index']);
    Route::post('/departemen/store', [DepartemenController::class, 'store']);
    Route::post('/departemen/edit', [DepartemenController::class, 'edit']);
    Route::post('/departemen/{kode_dept}/update', [DepartemenController::class, 'update']);
    Route::post('/departemen/{kode_dept}/delete', [DepartemenController::class, 'delete']);
    
    //monitoring
    Route::get('/presensi/monitoring', [PresensiController::class, 'monitoring']);
    Route::post('/getpresensi', [PresensiController::class, 'getpresensi']);
    
    //show map
    Route::post('/tampilkanpeta', [PresensiController::class, 'tampilkanpeta']);
    
    //laporan
    Route::get('/presensi/laporan', [PresensiController::class, 'laporan']);
    Route::post('/presensi/cetaklaporan', [PresensiController::class, 'cetaklaporan']);
    
    //rekap
    Route::get('/presensi/rekap', [PresensiController::class, 'rekap']);
    Route::post('/presensi/cetakrekap', [PresensiController::class, 'cetakrekap']);
    
    //izin sakit
    Route::get('/presensi/izinsakit', [PresensiController::class, 'izinsakit']);
    Route::post('/presensi/approvedizinsakit', [PresensiController::class, 'approvedizinsakit']);
    Route::get('/presensi/{id}/batalkanizinsakit', [PresensiController::class, 'batalkanizinsakit']);
    Route::delete('/presensi/{id}/deleteizinsakit', [PresensiController::class, 'deleteizinsakit']);
});