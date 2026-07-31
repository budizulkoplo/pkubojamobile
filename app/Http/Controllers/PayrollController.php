<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    private const EDITABLE_FIELDS = [
        'jmlabsensi',
        'jmlterlambat',
        'konversilembur',
        'konversioperasi',
        'cuti',
        'tugasluar',
        'totalharikerja',
        'doubleshift',
        'jmlrujukan',
        'tunjRujukan',
        'uangMakan',
        'kehadiranVal',
        'tugasluarVal',
        'lemburVal',
        'operasiVal',
        'doubleshiftVal',
        'jumlah',
        'zis',
        'qurban',
        'potransport',
        'infaqPdm',
        'infaqTerlambat',
        'potongan',
        'grandtotal',
    ];

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
        $rekap = $this->enrichPayrollSummary($rekap, $periode);

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
                DB::raw('0 as qurban'),
                DB::raw('0 as potransport'),
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
                DB::raw('(SELECT uangmakan FROM nominaldasar LIMIT 1) as uangmakan'),
                DB::raw('(SELECT MIN(tgl_aktif) FROM keterlambatan WHERE tgl_aktif IS NOT NULL) as tgl_aktif_keterlambatan')
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
                DB::raw('(SELECT MIN(tgl_aktif) FROM keterlambatan WHERE tgl_aktif IS NOT NULL) as tgl_aktif_keterlambatan'),
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
        $rekap = $this->enrichPayrollSummary($rekap, $periode);

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

    private function enrichPayrollSummary(array $rekap, string $periode): array
    {
        $rekap = array_merge($rekap, $this->calculatePayroll($rekap, $periode));

        if (($rekap['use_new_system'] ?? false) && ! empty($rekap['pegawai_pin'])) {
            $rekap = $this->applyPublishedDraftOverrides($rekap, $periode);
        }

        return $rekap;
    }

    private function calculatePayroll(array $row, string $periode): array
    {
        $jmlabsensi = (int) ($row['jmlabsensi'] ?? 0);
        $kehadiranNominal = (float) ($row['kehadiran'] ?? 0);
        $gajipokok = (float) ($row['gajipokok'] ?? 0);
        $lemburNominal = ! empty($row['lemburkhusus']) && $row['lemburkhusus'] > 0 ? (float) $row['lemburkhusus'] : $kehadiranNominal;
        $isTraining = $gajipokok <= 0;
        $isHarian = (string) ($row['harian'] ?? '0') === '1';
        $isDirektur = (string) ($row['direktur'] ?? '0') === '1';
        $tunjRujukan = (int) ($row['jmlrujukan'] ?? 0) * (float) ($row['rujukan'] ?? 0);
        $uangMakan = $jmlabsensi * (float) ($row['uangmakan'] ?? 0);

        if ($isDirektur || $isHarian || $isTraining) {
            $uangMakan = 0;
        }

        $kehadiranVal = $jmlabsensi * $kehadiranNominal;
        $tugasluarVal = (int) ($row['tugasluar'] ?? 0) * $lemburNominal;
        $lemburVal = (int) ($row['konversilembur'] ?? 0) * $lemburNominal;
        $operasiVal = (int) ($row['konversioperasi'] ?? 0) * $lemburNominal;
        $doubleshiftVal = (int) ($row['doubleshift'] ?? 0) * $lemburNominal;
        $lateMinutes = (int) ($row['jmlterlambat'] ?? 0);
        $infaqTerlambat = $this->lateInfaq($lateMinutes, $uangMakan, $row['tgl_aktif_keterlambatan'] ?? null, $periode);

        if ($isHarian) {
            $jumlah = $kehadiranVal;
            $zis = round($jumlah * 0.025);
            $infaqPdm = 0;
            $bpjs = (float) ($row['bpjskes'] ?? 0);
            $bpjstk = (float) ($row['bpjstk'] ?? 0);
            $potongan = $zis + $infaqPdm + $infaqTerlambat + $bpjs + $bpjstk + (float) ($row['pph21'] ?? 0) + (float) ($row['koperasi'] ?? 0);
        } elseif ($isTraining) {
            $jumlah = $kehadiranVal + $tugasluarVal;
            $zis = $infaqPdm = $bpjs = $bpjstk = $potongan = 0;
        } else {
            $jumlah = $gajipokok + (float) ($row['tunjstruktural'] ?? 0) + (float) ($row['tunjkeluarga'] ?? 0) + (float) ($row['tunjfungsional'] ?? 0) + (float) ($row['tunjapotek'] ?? 0) + $tunjRujukan + $uangMakan + $kehadiranVal + $tugasluarVal + $lemburVal + $operasiVal + $doubleshiftVal;
            $bpjs = (float) ($row['bpjskes'] ?? 0);
            $bpjstk = (float) ($row['bpjstk'] ?? 0);
            $zis = round($jumlah * 0.025);
            $infaqPdm = round($gajipokok * 0.01);
            $potongan = $zis + $infaqPdm + $infaqTerlambat + $bpjs + $bpjstk + (float) ($row['pph21'] ?? 0) + (float) ($row['koperasi'] ?? 0);
        }

        return compact('tunjRujukan', 'uangMakan', 'kehadiranVal', 'tugasluarVal', 'lemburVal', 'operasiVal', 'doubleshiftVal', 'jumlah', 'zis', 'infaqPdm', 'infaqTerlambat', 'bpjs', 'bpjstk', 'potongan') + [
            'grandtotal' => $jumlah - $potongan,
            'late_formatted' => sprintf('%02d:%02d', intdiv(max(0, $lateMinutes), 60), max(0, $lateMinutes) % 60),
        ];
    }

    private function applyPublishedDraftOverrides(array $rekap, string $periode): array
    {
        if (! Schema::hasTable('payroll_drafts')) {
            return $rekap;
        }

        $overridesJson = DB::table('payroll_drafts')
            ->where('periode', $periode)
            ->where('pegawai_pin', $rekap['pegawai_pin'])
            ->where('status', 'published')
            ->value('overrides');

        foreach ($this->decodeOverrides($overridesJson) as $field => $value) {
            if (in_array($field, self::EDITABLE_FIELDS, true)) {
                $rekap[$field] = $value;
            }
        }

        return $rekap;
    }

    private function decodeOverrides($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function lateInfaq(int $lateMinutes, float $uangMakan, ?string $activeDate, string $periode): int
    {
        if (! $activeDate || date('Y-m-25', strtotime($periode . '-01')) < $activeDate) {
            return 0;
        }

        $chargeable = max(0, $lateMinutes - 10);
        $rate = match (true) {
            $chargeable <= 0 => 0,
            $chargeable <= 30 => 0.06,
            $chargeable <= 60 => 0.12,
            $chargeable <= 90 => 0.18,
            $chargeable <= 120 => 0.24,
            default => 0.30,
        };

        return (int) round($uangMakan * $rate);
    }
}
