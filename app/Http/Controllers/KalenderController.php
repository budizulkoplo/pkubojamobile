<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KalenderController extends Controller
{
    protected $employees;
    protected $pegawaiPin;
    protected $selectedEmployee;

    public function __construct()
    {
        $this->initializeEmployeeData();
    }

    protected function initializeEmployeeData(): void
    {
        $this->pegawaiPin = Auth::guard('karyawan')->user()->id ?? '';
        $this->employees = DB::table('pegawai')
            ->select('pegawai_pin', 'pegawai_nama')
            ->where('bagian', '<>', 'nonaktif')
            ->orderBy('pegawai_nama')
            ->get();

        $this->selectedEmployee = $this->employees->firstWhere('pegawai_pin', $this->pegawaiPin);
    }

    public function index(Request $request)
    {
        return $this->renderKalenderView($request, 'kalender.index');
    }

    public function lembur(Request $request)
    {
        return $this->renderKalenderView($request, 'kalender.lembur');
    }

    public function statistik(Request $request)
    {
        $bulan = $this->validateMonth($request->bulan ?? date('Y-m'));

        $viewData = [
            'employees'   => $this->employees,
            'pegawaiPin'  => $this->pegawaiPin,
            'bulan'       => $bulan,
        ];

        if ($this->selectedEmployee) {
            $data  = $this->prepareKalenderData($bulan);
            $stats = $this->calculateStatistics($data['dataKalender']);

            $startPeriode     = Carbon::parse($bulan . '-26')->subMonth();
            $endPeriode       = Carbon::parse($bulan . '-25');
            $totalHariPeriode = $startPeriode->diffInDays($endPeriode) + 1;

            // Data grafik tren keterlambatan
            $chartLabels = [];
            $chartValues = [];

            foreach ($data['dataKalender'] as $tgl => $row) {
                $chartLabels[] = Carbon::parse($tgl)->translatedFormat('d M');
                if (!empty($row['jam_masuk']) && !empty($row['jam_masuk_shift'])) {
                    $masuk = strtotime(strip_tags($row['jam_masuk']));
                    $shift = strtotime($row['jam_masuk_shift']);
                    $chartValues[] = max(0, round(($masuk - $shift) / 60, 1)); // menit
                } else {
                    $chartValues[] = 0;
                }
            }

            $stats = array_merge([
                'jumlahTepatWaktu'  => 0,
                'jumlahTerlambat'   => 0,
                'jumlahPulangAwal'  => 0,
                'jumlahPulangLambat'=> 0,
            ], $stats);

            $viewData = array_merge($viewData, [
                'selectedEmployee'   => $this->selectedEmployee,
                'dataKalender'       => $data['dataKalender'],
                'terlambatFormatted' => $this->secondsToTime($stats['terlambat'] ?? 0),
                'lemburFormatted'    => $this->secondsToTime($stats['lembur'] ?? 0),
                'totalWorkDays'      => $stats['workDays'] ?? 0,
                'totalCuti'          => $stats['cuti'] ?? 0,
                'totalTugasLuar'     => $stats['tugasLuar'] ?? 0,
                'doubleShift' => $stats['doubleShift'] ?? 0,

                // Rata-rata jam masuk & pulang
                'avgMasukShift'  => $this->formatTimeFromSeconds($stats['avgMasukShift'] ?? null),
                'avgPulangShift' => $this->formatTimeFromSeconds($stats['avgPulangShift'] ?? null),
                'avgMasukActual' => $this->formatTimeFromSeconds($stats['avgMasukActual'] ?? null),
                'avgPulangActual'=> $this->formatTimeFromSeconds($stats['avgPulangActual'] ?? null),

                // Rata-rata selisih (menit)
                'avgSelisihMasuk'=> $stats['avgSelisihMasuk'] ?? null,
                'avgSelisihPulang'=> $stats['avgSelisihPulang'] ?? null,

                // Selisih dalam format waktu
                'diffMasuk' => $this->calculateTimeDifference(
                    $stats['avgMasukActual'] ?? null,
                    $stats['avgMasukShift'] ?? null
                ),
                'diffPulang' => $this->calculateTimeDifference(
                    $stats['avgPulangActual'] ?? null,
                    $stats['avgPulangShift'] ?? null
                ),

                'countShiftDays'     => $stats['countShiftDays'] ?? 0,

                // Data untuk grafik
                'jumlahTepatWaktu'   => $stats['jumlahTepatWaktu'],
                'jumlahTerlambat'    => $stats['jumlahTerlambat'],
                'jumlahPulangAwal'   => $stats['jumlahPulangAwal'],
                'jumlahPulangLambat' => $stats['jumlahPulangLambat'],
                'totalHariPeriode'   => $totalHariPeriode,
                'chartLabels'        => $chartLabels,
                'chartValues'        => $chartValues,
            ]);
        }

        return view('kalender.statistik', $viewData);
    }

    protected function renderKalenderView(Request $request, string $view)
    {
        $bulan = $this->validateMonth($request->bulan ?? date('Y-m'));
        
        $viewData = [
            'employees'  => $this->employees,
            'bulan'      => $bulan,
            'pegawaiPin' => $this->pegawaiPin,
        ];

        if ($this->selectedEmployee) {
            $data  = $this->prepareKalenderData($bulan);
            $stats = $this->calculateStatistics($data['dataKalender']);

            $viewData = array_merge($viewData, [
                'selectedEmployee'   => $this->selectedEmployee,
                'dataKalender'       => $data['dataKalender'],
                'weeks'              => $data['weeks'],
                'liburNasional'      => $data['liburNasional'],
                'liburBulanIni'      => $data['liburBulanIni'],
                'totalTerlambatSeconds'=> $stats['terlambat'],
                'totalLemburSeconds' => $stats['lembur'],
                'totalWorkDays'      => $stats['workDays'],
                'totalCuti'          => $stats['cuti'],
                'totalTugasLuar'     => $stats['tugasLuar'],
            ]);
        }

        return view($view, $viewData);
    }

    protected function validateMonth(string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    protected function prepareKalenderData(string $bulan): array
    {
        $start_date = Carbon::parse($bulan . '-26')->subMonth()->format('Y-m-d');
        $end_date   = Carbon::parse($bulan . '-25')->format('Y-m-d');

        $result = DB::select("CALL spKalenderAbsensiPegawai(?, ?, ?)", [
            $this->pegawaiPin, $start_date, $end_date
        ]);

        $dataKalender = [];
        foreach ($result as $row) {
            $dataKalender[$row->tgl] = (array) $row;
        }

        return [
            'dataKalender' => $dataKalender,
            'weeks'        => $this->generateCalendarWeeks($bulan),
            'liburNasional'=> $this->getNationalHolidays($bulan),
            'liburBulanIni'=> $this->filterHolidaysByMonth($bulan),
        ];
    }

    protected function filterHolidaysByMonth(string $bulan): array
    {
        $holidays = $this->getNationalHolidays($bulan);
        $selectedMonth = date('m', strtotime($bulan));

        return array_filter($holidays, function($key) use ($selectedMonth) {
            return date('m', strtotime($key)) == $selectedMonth;
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function getNationalHolidays(string $bulan): array
    {
        try {
            $year     = date('Y', strtotime($bulan . '-01'));
            $cacheKey = 'national_holidays_' . $year;

            return cache()->remember($cacheKey, now()->addMonth(), function() use ($year) {
                $response = Http::timeout(3)->get("https://hari-libur-api.vercel.app/api", [
                    'year' => $year
                ]);

                return $response->ok() ? $this->parseHolidayResponse($response->json()) : [];
            });
        } catch (\Exception $e) {
            logger()->error("Libur API error: " . $e->getMessage());
            return [];
        }
    }

    protected function parseHolidayResponse(array $holidays): array
    {
        $result = [];
        foreach ($holidays as $holiday) {
            if ($holiday['is_national_holiday']) {
                $result[$holiday['event_date']] = $holiday['event_name'];
            }
        }
        return $result;
    }

    /* ================= STATISTIK ================= */

    protected function calculateStatistics(array $dataKalender): array
    {
        $stats = $this->initializeStats();

        foreach ($dataKalender as $data) {
            $this->processWorkDayStats($stats, $data);
            $this->processLateStats($stats, $data);
            $this->processOvertimeStats($stats, $data);
            $this->processSpecialStatusStats($stats, $data);
            $this->processShiftStats($stats, $data);
            $this->processDoubleShiftStats($stats, $data);
        }

        return $this->calculateAverages($stats);
    }

    protected function initializeStats(): array
    {
        return [
            'terlambat'          => 0,
            'lembur'             => 0,
            'doubleShift'        => 0,
            'workDays'           => 0,
            'cuti'               => 0,
            'tugasLuar'          => 0,
            'totalMasukShift'    => 0,
            'totalPulangShift'   => 0,
            'totalMasukActual'   => 0,
            'totalPulangActual'  => 0,
            'countShiftDays'     => 0,
            'totalSelisihMasuk'  => 0,
            'totalSelisihPulang' => 0,
            'avgMasukShift'      => null,
            'avgPulangShift'     => null,
            'avgMasukActual'     => null,
            'avgPulangActual'    => null,
            'avgSelisihMasuk'    => null,
            'avgSelisihPulang'   => null,
            'jumlahTepatWaktu'   => 0,
            'jumlahTerlambat'    => 0,
            'jumlahPulangAwal'   => 0,
            'jumlahPulangLambat' => 0,
        ];
    }

    protected function processWorkDayStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk']) || !empty($data['jam_pulang'])) {
            $stats['workDays']++;
        }
    }

    protected function processDoubleShiftStats(array &$stats, array $data): void
{
    // cek di kolom status_khusus
    if (!empty($data['status_khusus']) && strtolower(trim($data['status_khusus'])) === 'double shift') {
        $stats['doubleShift']++;
    }
}


    protected function processLateStats(array &$stats, array $data): void
    {
        if (empty($data['status_khusus']) && !empty($data['jam_masuk']) && !empty($data['jam_masuk_shift'])) {
            $masuk = strtotime(strip_tags($data['jam_masuk']));
            $shift = strtotime($data['jam_masuk_shift']);
            if ($masuk > $shift) {
                $stats['terlambat'] += ($masuk - $shift);
                $stats['jumlahTerlambat']++;
            } else {
                $stats['jumlahTepatWaktu']++;
            }
        }
    }

    protected function processOvertimeStats(array &$stats, array $data): void
    {
        if (!empty($data['alasan_lembur']) && !empty($data['lembur_masuk']) && !empty($data['lembur_pulang'])) {
            $stats['lembur'] += $this->calculateOvertimeSeconds(
                $data['lembur_masuk'],
                $data['lembur_pulang']
            );
        }
    }

    protected function processSpecialStatusStats(array &$stats, array $data): void
    {
        if (!empty($data['status_khusus'])) {
            $status = strtolower($data['status_khusus']);
            if (str_contains($status, 'cuti')) {
                $stats['cuti']++;
            } elseif (str_contains($status, 'tugas luar') || str_contains($status, 'dinas luar')) {
                $stats['tugasLuar']++;
            }
        }
    }

    protected function processShiftStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk_shift']) && !empty($data['jam_pulang_shift'])) {
            $stats['countShiftDays']++;

            $shiftMasuk = strtotime($data['jam_masuk_shift']);
            $shiftPulang = strtotime($data['jam_pulang_shift']);

            if (!empty($data['jam_masuk'])) {
                $aktualMasuk = strtotime(strip_tags($data['jam_masuk']));
                $stats['totalSelisihMasuk'] += ($aktualMasuk - $shiftMasuk) / 60;
            }

            if (!empty($data['jam_pulang'])) {
                $aktualPulang   = strtotime(strip_tags($data['jam_pulang']));
                $selisihPulang  = ($aktualPulang - $shiftPulang) / 60;

                $stats['totalSelisihPulang'] += $selisihPulang;

                if ($selisihPulang < 0) {
                    $stats['jumlahPulangAwal']++;
                } elseif ($selisihPulang > 0) {
                    $stats['jumlahPulangLambat']++;
                }
            }
        }
    }

    protected function calculateAverages(array $stats): array
    {
        if ($stats['countShiftDays'] > 0) {
            $stats['avgSelisihMasuk']  = $stats['totalSelisihMasuk'] / $stats['countShiftDays'];
            $stats['avgSelisihPulang'] = $stats['totalSelisihPulang'] / $stats['countShiftDays'];
        } else {
            $stats['avgSelisihMasuk']  = null;
            $stats['avgSelisihPulang'] = null;
        }
        return $stats;
    }

    /* ============== HELPER ============== */

    protected function calculateOvertimeSeconds(string $startTime, string $endTime): int
    {
        try {
            $start = Carbon::parse($startTime);
            $end   = Carbon::parse($endTime);

            if ($end->lt($start)) {
                $end->addDay();
            }

            $seconds = $start->diffInSeconds($end);
            return min($seconds, 57600); // max 16 jam
        } catch (\Exception $e) {
            logger()->error("Overtime calculation error: " . $e->getMessage());
            return 0;
        }
    }

    protected function generateCalendarWeeks(string $bulan): array
    {
        $start = Carbon::parse($bulan . '-26')->subMonth()->startOfWeek();
        $end   = Carbon::parse($bulan . '-25')->endOfWeek()->addDay();

        $weeks = [];
        $currentWeek = [];

        while ($start < $end) {
            $currentWeek[] = $start->format('Y-m-d');
            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
            $start->addDay();
        }

        return $weeks;
    }

    // tampilkan total jam (>=24 jam tidak dipotong)
    protected function secondsToTime(int $seconds): string
    {
        $sign   = $seconds < 0 ? '-' : '';
        $seconds= abs($seconds);

        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }

    // untuk rata-rata jam (selalu 0–23 jam)
    protected function formatTimeFromSeconds(?float $seconds): ?string
    {
        if ($seconds === null) return null;
        $seconds = ((int)$seconds) % 86400;
        return gmdate('H:i', $seconds);
    }

    protected function calculateTimeDifference(?float $actual, ?float $shift): ?string
    {
        if ($actual === null || $shift === null) return null;

        $diff = $actual - $shift;
        $prefix = $diff > 0 ? '+' : ($diff < 0 ? '-' : '±');

        return $prefix . $this->secondsToTime(abs((int)$diff));
    }
}
