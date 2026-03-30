<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $pin = auth()->user()->id;
        $tahun = $request->get('tahun') ?? now()->year;

        // Ambil data bulan berdasarkan periode
        $data = $this->getPayrollMonths($pin, $tahun);

        $tahunList = $this->getPayrollYears();

        if ($tahunList->isEmpty()) {
            $tahunList = collect([now()->year]);
        }

        return view('payroll.index', compact('data', 'tahun', 'tahunList'));
    }

    private function getPayrollMonths($pin, $tahun)
    {
        // Cek jika ada data di payroll (sistem baru) untuk tahun ini
        $newSystemData = DB::table('payroll')
            ->selectRaw("SUBSTRING(periode, 6, 2) as bulan")
            ->where('pegawai_pin', $pin)
            ->where(DB::raw('LEFT(periode, 4)'), $tahun)
            ->whereExists(function ($query) use ($pin) {
                $query->select(DB::raw(1))
                    ->from('mastergaji')
                    ->whereColumn('mastergaji.pegawai_pin', 'payroll.pegawai_pin')
                    ->where('mastergaji.verifikasi', '1')
                    ->whereRaw("DATE_FORMAT(mastergaji.tglaktif, '%Y-%m') <= payroll.periode");
            })
            ->groupBy('bulan')
            ->pluck('bulan')
            ->toArray();

        // Cek jika ada data di penggajian (sistem lama) untuk tahun ini
        $oldSystemData = DB::table('penggajian')
            ->selectRaw("SUBSTRING(periode, 6, 2) as bulan")
            ->where('pegawai_pin', $pin)
            ->where(DB::raw('LEFT(periode, 4)'), $tahun)
            ->whereExists(function ($query) use ($pin) {
                $query->select(DB::raw(1))
                    ->from('mastergaji')
                    ->whereColumn('mastergaji.pegawai_pin', 'penggajian.pegawai_pin')
                    ->where('mastergaji.verifikasi', '1')
                    ->whereRaw("DATE_FORMAT(mastergaji.tglaktif, '%Y-%m') <= penggajian.periode");
            })
            ->groupBy('bulan')
            ->pluck('bulan')
            ->toArray();

        // Gabungkan dan urutkan bulan
        $allMonths = array_unique(array_merge($newSystemData, $oldSystemData));
        sort($allMonths);

        // Konversi ke collection
        $data = collect();
        foreach ($allMonths as $bulan) {
            $data->push((object)['bulan' => $bulan]);
        }

        return $data;
    }

    private function getPayrollYears()
    {
        $newSystemYears = DB::table('payroll')
            ->selectRaw('DISTINCT CAST(LEFT(periode, 4) AS UNSIGNED) as tahun')
            ->pluck('tahun');

        $oldSystemYears = DB::table('penggajian')
            ->selectRaw('DISTINCT CAST(LEFT(periode, 4) AS UNSIGNED) as tahun')
            ->pluck('tahun');

        return $newSystemYears
            ->merge($oldSystemYears)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    public function detail($tahun, $bulan)
    {
        $pin = auth()->user()->id;
        $periode = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        
        // Tentukan sistem yang digunakan berdasarkan periode
        $useNewSystem = $this->shouldUseNewSystem($periode);
        
        if ($useNewSystem) {
            $rekap = $this->getPayrollDataNewSystem($pin, $periode);
        } else {
            $rekap = $this->getPayrollDataOldSystem($pin, $periode);
        }

        if (!$rekap) {
            return redirect()->back()->with('warning', 'Data slip tidak ditemukan untuk periode ini.');
        }

        // Hitung jumlah rujukan
        $jmlrujukan = $this->getRujukanCount($pin, $tahun, $bulan);

        $rekap = (array) $rekap;
        $rekap['jmlrujukan'] = $jmlrujukan;
        $rekap['use_new_system'] = $useNewSystem; // Flag untuk view

        $site = [
            'icon' => 'logopku.png',
            'namaweb' => 'RS PKU Muhammadiyah Boja',
        ];

        return view('payroll.detail', [
            'rekap'   => $rekap,
            'periode' => $periode,
            'site'    => $site,
        ]);
    }

    private function shouldUseNewSystem($periode)
    {
        // Jika periode >= 2025-12, gunakan sistem baru
        return $periode >= '2025-12';
    }

    private function getPayrollDataNewSystem($pin, $periode)
    {
        $masterGajiSubquery = DB::raw("
            (
                SELECT m1.*
                FROM mastergaji m1
                JOIN (
                    SELECT pegawai_pin, MAX(tglaktif) AS tglaktif
                    FROM mastergaji
                    WHERE verifikasi = '1'
                    AND DATE_FORMAT(tglaktif, '%Y-%m') <= '{$periode}'
                    GROUP BY pegawai_pin
                ) m2
                ON m1.pegawai_pin = m2.pegawai_pin
                AND m1.tglaktif = m2.tglaktif
            ) as mg
        ");

        return DB::table('payroll as p')
            ->join('pegawai', 'p.pegawai_pin', '=', 'pegawai.pegawai_pin')
            ->leftJoin($masterGajiSubquery, 'mg.pegawai_pin', '=', 'pegawai.pegawai_pin')
            ->select(
                'p.periode',
                'pegawai.pegawai_nip',
                'pegawai.pegawai_pin',
                'pegawai.pegawai_nama',
                'pegawai.nohp',
                'pegawai.email',
                'pegawai.jabatan',

                'mg.gajipokok',
                'mg.tunjstruktural',
                'mg.tunjfungsional',
                'mg.tunjkeluarga',
                'mg.tunjapotek',
                'mg.kehadiran',
                'mg.pph21',
                'mg.lemburkhusus',
                'mg.bpjstk',
                'mg.bpjskes',
                'mg.koperasi',
                'mg.direktur',
                'mg.harian',

                'p.jmlabsensi',
                'p.jmlterlambat',
                'p.konversilembur',
                'p.konversioperasi',
                'p.cuti',
                'p.tugasluar',
                'p.totalharikerja',
                'p.doubleshift',

                DB::raw('(SELECT rujukan FROM nominaldasar LIMIT 1) as rujukan'),
                DB::raw('(SELECT uangmakan FROM nominaldasar LIMIT 1) as uangmakan')
            )
            ->where('p.periode', $periode)
            ->where('p.pegawai_pin', $pin)
            ->first();
    }

    private function getPayrollDataOldSystem($pin, $periode)
    {
        $periodeEnd = \Carbon\Carbon::createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();

        return DB::table('penggajian')
            ->join('pegawai', 'penggajian.pegawai_pin', '=', 'pegawai.pegawai_pin')
            ->leftJoin('mastergaji', function($join) use ($periodeEnd) {
                $join->on('mastergaji.pegawai_pin', '=', 'pegawai.pegawai_pin')
                    ->where('mastergaji.verifikasi', '1')
                    ->whereDate('mastergaji.tglaktif', '<=', $periodeEnd);
            })
            ->select(
                'penggajian.periode',
                'pegawai.pegawai_nip',
                'pegawai.pegawai_pin',
                'pegawai.pegawai_nama',
                'pegawai.nohp',
                'pegawai.email',
                'pegawai.jabatan',
                'mastergaji.gajipokok',
                'mastergaji.tunjstruktural',
                'mastergaji.tunjfungsional',
                'mastergaji.tunjkeluarga',
                'mastergaji.tunjapotek',
                'mastergaji.kehadiran',
                'mastergaji.pph21',
                'mastergaji.lemburkhusus',
                'penggajian.jmlabsensi',
                'penggajian.jmlterlambat',
                'penggajian.konversilembur',
                DB::raw('0 as konversioperasi'), // Default 0 untuk sistem lama
                'penggajian.doubleshift',
                'penggajian.cuti',
                'penggajian.tugasluar',
                'penggajian.totalharikerja',
                DB::raw('(SELECT rujukan FROM nominaldasar LIMIT 1) as rujukan'),
                DB::raw('(SELECT uangmakan FROM nominaldasar LIMIT 1) as uangmakan'),
                DB::raw('(SELECT koperasi FROM nominaldasar LIMIT 1) as koperasi'),
                DB::raw('(SELECT bpjs FROM nominaldasar LIMIT 1) as bpjstk'),
                'mastergaji.verifikasi'
            )
            ->where('penggajian.periode', $periode)
            ->where('penggajian.pegawai_pin', $pin)
            ->first();
    }

    private function getRujukanCount($pin, $tahun, $bulan)
    {
        return DB::table('rujukan')
            ->where('pegawai_pin', $pin)
            ->whereMonth('tglrujukan', $bulan)
            ->whereYear('tglrujukan', $tahun)
            ->count();
    }

    public function downloadPDF($tahun, $bulan)
    {
        $pin = auth()->user()->id;
        $periode = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        
        // Tentukan sistem yang digunakan berdasarkan periode
        $useNewSystem = $this->shouldUseNewSystem($periode);
        
        if ($useNewSystem) {
            $rekap = $this->getPayrollDataNewSystem($pin, $periode);
        } else {
            $rekap = $this->getPayrollDataOldSystem($pin, $periode);
        }

        if (!$rekap) {
            return redirect()->back()->with('warning', 'Data slip tidak ditemukan.');
        }

        // Hitung jumlah rujukan
        $jmlrujukan = $this->getRujukanCount($pin, $tahun, $bulan);

        $rekap = (array) $rekap;
        $rekap['jmlrujukan'] = $jmlrujukan;
        $rekap['use_new_system'] = $useNewSystem;

        $site = [
            'icon' => 'logopku.png',
            'namaweb' => 'RS PKU Muhammadiyah Boja',
        ];

        // Gunakan view yang berbeda untuk PDF berdasarkan sistem
        $pdfView = $useNewSystem ? 'payroll.slip-pdf-new' : 'payroll.slip-pdf-old';
        
        $pdf = Pdf::loadView($pdfView, [
            'rekap'   => $rekap,
            'periode' => $periode,
            'site'    => $site,
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->download('Slip-Gaji-'.$rekap['pegawai_nama'].'-'.$periode.'.pdf');
    }
}
