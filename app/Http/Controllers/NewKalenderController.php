<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Newkalender_model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NewKalenderController extends Controller
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
        $user = Auth::guard('karyawan')->user();
        
        if ($user) {
            // Gunakan id sebagai pegawai_pin
            $this->pegawaiPin = $user->id;
            
            \Log::info('User data', [
                'user_id' => $user->id,
                'nama_lengkap' => $user->nama_lengkap,
                'pegawai_pin' => $this->pegawaiPin
            ]);
        } else {
            \Log::warning('No user found with karyawan guard');
            $this->pegawaiPin = null;
        }

        $this->employees = DB::table('pegawai')
            ->select('pegawai_pin', 'pegawai_nama')
            ->where('bagian', '<>', 'nonaktif')
            ->orderBy('pegawai_nama')
            ->get();

        $this->selectedEmployee = $this->employees->firstWhere('pegawai_pin', $this->pegawaiPin);
        
        \Log::info('Employee initialization', [
            'pegawai_pin' => $this->pegawaiPin,
            'selected_employee' => $this->selectedEmployee ? $this->selectedEmployee->pegawai_nama : 'Not found',
            'total_employees' => $this->employees->count()
        ]);

        // Jika tidak ditemukan di tabel pegawai, buat manual
        if (!$this->selectedEmployee && $user) {
            $this->selectedEmployee = (object) [
                'pegawai_pin' => $this->pegawaiPin,
                'pegawai_nama' => $user->nama_lengkap
            ];
            \Log::info('Created manual employee record', [
                'pegawai_pin' => $this->pegawaiPin,
                'pegawai_nama' => $user->nama_lengkap
            ]);
        }
    }

    public function index(Request $request)
    {
        return $this->renderKalenderView($request, 'kalender.newkalender');
    }

    protected function renderKalenderView(Request $request, string $view)
    {
        $bulan = $this->validateMonth($request->bulan ?? date('Y-m'));
        
        $viewData = [
            'employees'  => $this->employees,
            'bulan'      => $bulan,
            'pegawaiPin' => $this->pegawaiPin,
        ];

        if ($this->selectedEmployee && $this->pegawaiPin) {
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
                'totalDoubleShift'   => $stats['doubleShift'],
            ]);
        } else {
            \Log::warning('Cannot render kalender - missing employee data', [
                'has_selected_employee' => !is_null($this->selectedEmployee),
                'has_pegawai_pin' => !is_null($this->pegawaiPin)
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
        \Log::info('Preparing kalender data', [
            'pegawai_pin' => $this->pegawaiPin,
            'bulan' => $bulan
        ]);

        try {
            $kalenderModel = new Newkalender_model();
            $dataKalender = $kalenderModel->getDataKalenderWithNightShift($this->pegawaiPin, $bulan);
            
            \Log::info('Model data result', [
                'data_count' => count($dataKalender),
                'first_5_dates' => array_slice(array_keys($dataKalender), 0, 5)
            ]);

            return [
                'dataKalender' => $dataKalender,
                'weeks'        => $this->generateCalendarWeeks($bulan),
                'liburNasional'=> $this->getNationalHolidays($bulan),
                'liburBulanIni'=> $this->filterHolidaysByMonth($bulan),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in prepareKalenderData: ' . $e->getMessage());
            return [
                'dataKalender' => [],
                'weeks'        => [],
                'liburNasional'=> [],
                'liburBulanIni'=> [],
            ];
        }
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
            $year = date('Y', strtotime($bulan . '-01'));
            $apiUrl = "https://hari-libur-api.vercel.app/api?year=" . $year;
            
            $response = file_get_contents($apiUrl);
            $holidaysData = json_decode($response, true);
            
            $nationalHolidays = [];
            foreach ($holidaysData as $holiday) {
                if ($holiday['is_national_holiday']) {
                    $holidayDate = $holiday['event_date'];
                    $nationalHolidays[$holidayDate] = $holiday['event_name'];
                }
            }
            
            \Log::info('Holidays fetched', ['count' => count($nationalHolidays)]);
            return $nationalHolidays;
        } catch (\Exception $e) {
            \Log::error('Error fetching holidays: ' . $e->getMessage());
            return [];
        }
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
            $this->processDoubleShiftStats($stats, $data);
        }

        \Log::info('Statistics calculated', $stats);
        return $stats;
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
        ];
    }

    protected function processWorkDayStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual'])) {
            $stats['workDays']++;
        }
    }

    protected function processDoubleShiftStats(array &$stats, array $data): void
    {
        if (!empty($data['status_khusus']) && strtolower(trim($data['status_khusus'])) === 'double shift') {
            $stats['doubleShift']++;
        }
    }

    protected function processLateStats(array &$stats, array $data): void
    {
        if (empty($data['status_khusus']) && !empty($data['jam_masuk_actual']) && !empty($data['jam_masuk_shift'])) {
            $masuk = strtotime($data['jam_masuk_actual']);
            $shift = strtotime($data['jam_masuk_shift']);
            if ($masuk > $shift) {
                $stats['terlambat'] += ($masuk - $shift);
            }
        }
    }

    protected function processOvertimeStats(array &$stats, array $data): void
    {
        if (!empty($data['lembur_data'])) {
            foreach ($data['lembur_data'] as $lembur) {
                $stats['lembur'] += $lembur['durasi'];
            }
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

        \Log::info('Calendar weeks generated', [
            'weeks_count' => count($weeks),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d')
        ]);

        return $weeks;
    }

    protected function secondsToTime(int $seconds): string
    {
        $sign   = $seconds < 0 ? '-' : '';
        $seconds= abs($seconds);

        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }
}