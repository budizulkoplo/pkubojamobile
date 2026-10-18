<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class EmployeeKpiPerformanceService
{
    private array $tableCache = [];

    public function calculate(string $pegawaiPin, string $periode, array $calendar): array
    {
        $attendance = $this->attendanceKpi($calendar);
        $religious = [
            'kajian' => ['total' => 0, 'ahad_pagi' => 0, 'senin_pagi' => 0, 'kajian_bulanan' => 0],
            'tadarus' => ['total_sesi' => 0, 'total_ayat' => 0],
            'sholat' => ['total' => 0, 'hari_aktif' => 0, 'jamaah' => 0, 'fardhu' => 0, 'sunnah' => 0],
        ];

        try {
            $employee = DB::connection('hris')->table('pegawai')
                ->where('pegawai_pin', $pegawaiPin)
                ->first(['nik']);
            $religious = $this->religiousKpi($employee->nik ?? null, $periode);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $attendanceScore = min(100, (int) round(
            ($attendance['attendance_rate'] * 0.45)
            + ($attendance['punctuality_rate'] * 0.55)
        ));
        $activityScore = min(100,
            ((int) $religious['kajian']['total'] * 15)
            + ((int) $religious['tadarus']['total_sesi'] * 8)
            + ((int) $religious['sholat']['hari_aktif'] * 3)
        );
        $totalScore = (int) round(($attendanceScore * 0.7) + ($activityScore * 0.3));

        return [
            'score' => [
                'attendance' => $attendanceScore,
                'activity' => $activityScore,
                'total' => $totalScore,
                'label' => match (true) {
                    $totalScore >= 90 => 'Istimewa',
                    $totalScore >= 80 => 'Baik Sekali',
                    $totalScore >= 70 => 'Baik',
                    $totalScore >= 60 => 'Cukup',
                    default => 'Perlu Pendampingan',
                },
            ],
            'attendance' => $attendance,
            'religious' => $religious,
        ];
    }

    private function attendanceKpi(array $calendar): array
    {
        $summary = [
            'scheduled_days' => 0,
            'present_days' => 0,
            'punctuality_days' => 0,
            'on_time_days' => 0,
            'late_days' => 0,
            'late_minutes' => 0,
            'outside_duty_days' => 0,
            'leave_days' => 0,
            'permission_days' => 0,
            'double_shift_days' => 0,
            'overtime_sessions' => 0,
            'overtime_minutes' => 0,
        ];

        foreach ($calendar as $day) {
            $status = strtolower(trim(strip_tags((string) ($day['status_khusus'] ?? ''))));
            $hasAttendance = ! empty($day['jam_masuk_actual']) || ! empty($day['jam_pulang_actual'])
                || ! empty($day['jam_masuk']) || ! empty($day['jam_pulang']);
            $hasSchedule = ! empty($day['has_schedule'])
                || ! empty($day['jam_masuk_shift'])
                || ! empty($day['jam_pulang_shift']);
            $lateMinutes = (int) ($day['late_minutes'] ?? 0);
            $isSpecialDay = $status !== '';

            if ($hasSchedule) {
                $summary['scheduled_days']++;
            }
            if ($hasAttendance) {
                $summary['present_days']++;
            }

            $isPunctualityDay = $hasSchedule && $hasAttendance && ! $isSpecialDay;
            if ($isPunctualityDay) {
                $summary['punctuality_days']++;
                if ($lateMinutes <= 0) {
                    $summary['on_time_days']++;
                } else {
                    $summary['late_days']++;
                    $summary['late_minutes'] += $lateMinutes;
                }
            }

            if (str_contains($status, 'tugas luar') || str_contains($status, 'dinas luar')) {
                $summary['outside_duty_days']++;
            }
            if (str_contains($status, 'cuti')) {
                $summary['leave_days']++;
            }
            if (str_contains($status, 'izin') || str_contains($status, 'sakit')) {
                $summary['permission_days']++;
            }
            if (str_contains($status, 'double shift')) {
                $summary['double_shift_days']++;
            }

            $overtimeRows = collect($day['lembur_data'] ?? [])
                ->filter(fn (array $row): bool => empty($row['warning']));
            $summary['overtime_sessions'] += $overtimeRows->count();
            $summary['overtime_minutes'] += $overtimeRows->sum(
                fn (array $row): int => (int) ($row['durasi'] ?? 0)
            );
        }

        $summary['punctuality_rate'] = $summary['punctuality_days'] > 0
            ? (int) round(($summary['on_time_days'] / $summary['punctuality_days']) * 100)
            : 0;
        $summary['attendance_rate'] = $summary['scheduled_days'] > 0
            ? (int) round(($summary['present_days'] / $summary['scheduled_days']) * 100)
            : 0;

        return $summary;
    }

    private function religiousKpi(?string $nik, string $periode): array
    {
        $empty = [
            'kajian' => ['total' => 0, 'ahad_pagi' => 0, 'senin_pagi' => 0, 'kajian_bulanan' => 0],
            'tadarus' => ['total_sesi' => 0, 'total_ayat' => 0],
            'sholat' => ['total' => 0, 'hari_aktif' => 0, 'jamaah' => 0, 'fardhu' => 0, 'sunnah' => 0],
        ];

        $nik = trim((string) $nik);
        if ($nik === '') {
            return $empty;
        }

        $start = Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m-d', $periode.'-01')->endOfMonth()->toDateString();
        $ahadPagi = $this->ahadPagiCount($nik, $periode);

        if ($this->hasTable('kehadiran_kajian')) {
            $query = DB::connection('hris')
                ->table('kehadiran_kajian as kk')
                ->where('kk.nik', $nik)
                ->whereBetween(DB::raw('date(kk.waktu_scan)'), [$start, $end]);

            if ($this->hasTable('kajian')) {
                $query->leftJoin('kajian as k', 'k.idkajian', '=', 'kk.idkajian');
                $kajianRows = $query->get(['k.rutin']);
            } else {
                $kajianRows = $query->get();
            }

            $empty['kajian']['senin_pagi'] = $kajianRows->where('rutin', '1')->count();
            $empty['kajian']['kajian_bulanan'] = $kajianRows->where('rutin', '!=', '1')->count();
        }
        $empty['kajian']['ahad_pagi'] = $ahadPagi;
        $empty['kajian']['total'] = $ahadPagi
            + $empty['kajian']['senin_pagi']
            + $empty['kajian']['kajian_bulanan'];

        if ($this->hasTable('ngaji')) {
            $query = DB::connection('hris')
                ->table('ngaji')
                ->where('nik', $nik)
                ->whereBetween(DB::raw('date(created_at)'), [$start, $end]);

            if (Schema::connection('hris')->hasColumn('ngaji', 'flag')) {
                $query->where(fn ($query) => $query->whereIn('flag', ['selesai'])->orWhereNull('flag'));
            }

            $rows = $query->get(['ayat']);
            $empty['tadarus']['total_sesi'] = $rows->count();
            $empty['tadarus']['total_ayat'] = $rows->sum(
                fn (object $row): int => max(0, (int) ($row->ayat ?? 0))
            );
        }

        if ($this->hasTable('sholat')) {
            $rows = DB::connection('hris')
                ->table('sholat')
                ->where('nik', $nik)
                ->whereBetween(DB::raw('date(created_at)'), [$start, $end])
                ->get(['created_at', 'jenis', 'jamaah']);

            $empty['sholat']['total'] = $rows->count();
            $empty['sholat']['hari_aktif'] = $rows
                ->map(fn (object $row): string => Carbon::parse($row->created_at)->toDateString())
                ->unique()
                ->count();
            $empty['sholat']['jamaah'] = $rows->where('jamaah', 'ya')->count();
            $empty['sholat']['fardhu'] = $rows->where('jenis', 'fardhu')->count();
            $empty['sholat']['sunnah'] = $rows->where('jenis', 'sunnah')->count();
        }

        return $empty;
    }

    private function ahadPagiCount(string $nik, string $periode): int
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['X-API-KEY' => 'pkuboja2025'])
                ->timeout(5)
                ->get('https://kajian.pcmboja.com/api/kehadiran', [
                    'periode' => $periode,
                    'dept' => 1,
                ]);

            if (! $response->ok()) {
                return 0;
            }

            return collect($response->json('data') ?? [])
                ->filter(fn (array $row): bool => (string) ($row['nik'] ?? '') === $nik)
                ->count();
        } catch (\Throwable $exception) {
            report($exception);

            return 0;
        }
    }

    private function hasTable(string $table): bool
    {
        return $this->tableCache[$table] ??= Schema::connection('hris')->hasTable($table);
    }
}
