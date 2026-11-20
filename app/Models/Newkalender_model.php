<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Newkalender_model extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
    protected $primaryKey = 'pegawai_id';

    private array $bagianHistoryCache = [];

    private array $employeeBagianCache = [];

    private array $workHourCache = [];
    
    public function getDataKalenderWithNightShift($pegawaiPin, $bulan)
    {
        // Hitung periode sesuai SP: 26 bulan sebelumnya sampai 25 bulan ini
        $endDate = date('Y-m-25', strtotime($bulan . '-01'));
        $startDate = date('Y-m-d', strtotime($endDate . ' -1 month +1 day'));
        
        // Get employee data
        $pegawai = DB::connection('hris')->table('pegawai')
            ->where('pegawai_pin', $pegawaiPin)
            ->first();
        
        if (!$pegawai) {
            return [];
        }
        
        $dataKalender = [];
        
        // Generate all dates in period
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dataKalender[$currentDate] = [
                'tgl' => $currentDate,
                'shift' => '',
                'jam_masuk_shift' => '',
                'jam_pulang_shift' => '',
                'jam_masuk' => '',
                'jam_pulang' => '',
                'status_khusus' => '',
                'late_seconds' => 0,
                'late_minutes' => 0,
                'late_cutoff_time' => '',
                'late_rule_text' => '',
                'late_basis' => 'regular',
                'lembur_data' => [],
                'is_night_shift' => false,
                'work_duration' => '00:00',
                'jam_masuk_actual' => '',
                'jam_pulang_actual' => '',
                'has_schedule' => false,
                'has_attendance' => false,
                'is_office_shift' => false,
                'is_default_office' => false,
                'consumed_by_previous_night_shift' => false
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        // Get schedule data dasar pegawai
        $this->getScheduleData($pegawaiPin, $startDate, $endDate, $dataKalender);

        // Get attendance data dengan penanganan shift malam
        $this->getAttendanceDataWithNightShift($pegawaiPin, $startDate, $endDate, $dataKalender);

        // Lengkapi fallback office untuk hari yang punya absensi tapi belum punya jadwal
        $this->applyOfficeAttendanceDefaults($pegawaiPin, $dataKalender);
        
        // Get overtime data
        $this->getOvertimeData($pegawaiPin, $startDate, $endDate, $dataKalender);
        
        // Get special status data (cuti, tugas luar, double shift)
        $this->getSpecialStatusData($pegawaiPin, $startDate, $endDate, $dataKalender);

        // Terapkan koreksi presensi dari admin SMART RS sebelum hitung statistik/telat.
        $this->applyAttendanceAdjustments($pegawaiPin, $startDate, $endDate, $dataKalender);

        // Samakan normalisasi lembur dengan kalender SMART RS.
        $this->normalizeOvertimeAgainstSchedule($dataKalender);
        
        // Calculate late and other calculations
        $this->calculateLateAndOther($dataKalender, $this->getTanggalAktifKeterlambatan());
        
        ksort($dataKalender);
        
        return $dataKalender;
    }
    
    private function getScheduleData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        $results = DB::connection('hris')->table('jadwal')
            ->where('pegawai_pin', $pegawaiPin)
            ->whereBetween('tgl', [$startDate, $endDate])
            ->orderBy('tgl')
            ->get(['tgl', 'shift']);

        foreach ($results as $row) {
            $tgl = (string) $row->tgl;
            if (isset($dataKalender[$tgl])) {
                $bagian = $this->resolveBagianAtDate($pegawaiPin, $tgl);
                $workHour = $this->workHourForBagianAndShift($bagian, (string) $row->shift);

                $dataKalender[$tgl]['shift'] = $this->isOfficeBagian($bagian) ? 'Office' : (string) $row->shift;
                $dataKalender[$tgl]['jam_masuk_shift'] = (string) ($workHour->jammasuk ?? '');
                $dataKalender[$tgl]['jam_pulang_shift'] = (string) ($workHour->jampulang ?? '');
                $dataKalender[$tgl]['has_schedule'] = true;
            }
        }

        foreach ($dataKalender as $tgl => &$data) {
            $bagian = $this->resolveBagianAtDate($pegawaiPin, (string) $tgl);
            $data['is_office_shift'] = $this->isOfficeBagian($bagian);
        }
        unset($data);
    }

    private function applyOfficeAttendanceDefaults($pegawaiPin, &$dataKalender)
    {
        foreach ($dataKalender as $tgl => $data) {
            $hasAttendance = !empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual']);
            if (!$hasAttendance) {
                continue;
            }

            if (empty($dataKalender[$tgl]['jam_masuk_shift'])) {
                $bagian = $this->resolveBagianAtDate($pegawaiPin, (string) $tgl);
                $officeShift = $this->workHourForBagianAndShift($bagian, '-');
                if (!$officeShift) {
                    continue;
                }

                $dataKalender[$tgl]['shift'] = 'Office';
                $dataKalender[$tgl]['jam_masuk_shift'] = (string) ($officeShift->jammasuk ?? '08:00:00');
                $dataKalender[$tgl]['jam_pulang_shift'] = (string) ($officeShift->jampulang ?? '16:00:00');
                $dataKalender[$tgl]['has_schedule'] = true;
                $dataKalender[$tgl]['is_default_office'] = true;
                $dataKalender[$tgl]['is_office_shift'] = true;
            }
        }
    }
    
    private function getAttendanceDataWithNightShift($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        $attendanceStart = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $attendanceEnd = date('Y-m-d', strtotime($endDate . ' +1 day'));

        // Get all attendance data termasuk 1 hari sebelum/sesudah untuk shift malam lintas cut off
        $allAttendance = DB::connection('hris')->select("
            SELECT 
                DATE(scan_date) as tgl,
                scan_date,
                inoutmode,
                TIME(scan_date) as jam
            FROM absensi.att_log 
            WHERE pin = ? 
            AND DATE(scan_date) BETWEEN ? AND ?
            AND inoutmode IN (1, 2)
            ORDER BY scan_date ASC
        ", [$pegawaiPin, $attendanceStart, $attendanceEnd]);
        
        // Convert to array for easier processing
        $attendanceArray = array_map(function($item) {
            return (array) $item;
        }, $allAttendance);
        
        // Process shift malam lebih dulu agar checkout tidak tampil ganda
        $this->processNightShift($attendanceArray, $dataKalender, $startDate, $endDate);

        // Process untuk mendapatkan data masuk dan pulang normal
        $this->processNormalAttendance($attendanceArray, $dataKalender);
        
        // Tandai hari yang ada attendance
        foreach ($attendanceArray as $att) {
            $tgl = $att['tgl'];
            if (isset($dataKalender[$tgl])) {
                $dataKalender[$tgl]['has_attendance'] = true;
            }
        }
    }
    
    private function processNormalAttendance($allAttendance, &$dataKalender)
    {
        foreach ($allAttendance as $att) {
            $tgl = $att['tgl'];
            $inoutmode = $att['inoutmode'];
            $jam = $att['jam'];
            
            if (!isset($dataKalender[$tgl])) continue;

            // Skip jika absensi ini sudah dipakai sebagai checkout shift malam hari sebelumnya
            if ($inoutmode == 2 && !empty($dataKalender[$tgl]['consumed_by_previous_night_shift'])) {
                $consumedTime = $dataKalender[$tgl]['night_shift_checkout_time'] ?? '';
                if ($jam === $consumedTime) {
                    continue;
                }
            }

            // Skip jika ini adalah pasangan absensi shift malam di tanggal yang sama
            if ($dataKalender[$tgl]['is_night_shift']) {
                if ($inoutmode == 1 && $jam === ($dataKalender[$tgl]['jam_masuk_actual'] ?? '')) {
                    continue;
                }
                if ($inoutmode == 2 && $jam === ($dataKalender[$tgl]['jam_pulang_actual'] ?? '')) {
                    continue;
                }
            }
            
            // Masuk (inoutmode 1 atau 5)
            if (in_array($inoutmode, [1])) {
                if (empty($dataKalender[$tgl]['jam_masuk_actual']) || $jam < $dataKalender[$tgl]['jam_masuk_actual']) {
                    $dataKalender[$tgl]['jam_masuk_actual'] = $jam;
                    $dataKalender[$tgl]['jam_masuk'] = $jam;
                }
            }
            
            // Pulang (inoutmode 2 atau 6)
            if (in_array($inoutmode, [2])) {
                if (empty($dataKalender[$tgl]['jam_pulang_actual']) || $jam > $dataKalender[$tgl]['jam_pulang_actual']) {
                    $dataKalender[$tgl]['jam_pulang_actual'] = $jam;
                    $dataKalender[$tgl]['jam_pulang'] = $jam;
                }
            }
        }
    }

    private function processNightShift($allAttendance, &$dataKalender, $startDate, $endDate)
    {
        // Group attendance by date untuk memudahkan processing
        $attendanceByDate = [];
        foreach ($allAttendance as $att) {
            $tgl = $att['tgl'];
            if (!isset($attendanceByDate[$tgl])) {
                $attendanceByDate[$tgl] = [];
            }
            $attendanceByDate[$tgl][] = $att;
        }
        
        // Process setiap tanggal untuk identifikasi shift malam
        foreach ($attendanceByDate as $currentDate => $attendance) {
            // Cari check-in terakhir di hari ini yang setelah jam 18:00
            $lastNightCheckIn = null;
            foreach ($attendance as $att) {
                if (in_array($att['inoutmode'], [1]) && strtotime($att['jam']) >= strtotime('18:00:00')) {
                    if ($lastNightCheckIn === null || $att['jam'] > $lastNightCheckIn['jam']) {
                        $lastNightCheckIn = $att;
                    }
                }
            }
            
            // Jika ditemukan check-in malam
            if ($lastNightCheckIn !== null) {
                $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                
                // Cek apakah nextDate masih ada data attendance untuk dicocokkan
                if (isset($attendanceByDate[$nextDate])) {
                    // Cari check-out pertama di hari berikutnya sebelum jam 10:00
                    $firstMorningCheckOut = null;
                    foreach ($attendanceByDate[$nextDate] as $att) {
                        if (in_array($att['inoutmode'], [2]) && strtotime($att['jam']) <= strtotime('10:00:00')) {
                            if ($firstMorningCheckOut === null || $att['jam'] < $firstMorningCheckOut['jam']) {
                                $firstMorningCheckOut = $att;
                            }
                        }
                    }
                    
                    // Jika ditemukan check-out pagi, maka ini adalah shift malam
                    if ($firstMorningCheckOut !== null) {
                        // Tampilkan shift malam di hari check-in jika masih dalam periode kalender
                        if (isset($dataKalender[$currentDate])) {
                            $dataKalender[$currentDate]['is_night_shift'] = true;
                            $dataKalender[$currentDate]['jam_pulang'] = $firstMorningCheckOut['jam'] . ' (esok)';
                            $dataKalender[$currentDate]['jam_pulang_actual'] = $firstMorningCheckOut['jam'];
                            $dataKalender[$currentDate]['jam_masuk_actual'] = $lastNightCheckIn['jam'];
                            $dataKalender[$currentDate]['jam_masuk'] = $lastNightCheckIn['jam'];

                            $checkInDateTime = $lastNightCheckIn['scan_date'];
                            $checkOutDateTime = $firstMorningCheckOut['scan_date'];
                            $workDuration = $this->calculateWorkDuration($checkInDateTime, $checkOutDateTime);
                            $dataKalender[$currentDate]['work_duration'] = $workDuration;
                        }

                        // Tandai checkout pagi sebagai milik shift malam sebelumnya
                        if (isset($dataKalender[$nextDate])) {
                            $dataKalender[$nextDate]['night_shift_checkout_time'] = $firstMorningCheckOut['jam'];
                            $dataKalender[$nextDate]['consumed_by_previous_night_shift'] = true;
                        }
                    }
                }
            }
        }
    }
    
    private function calculateWorkDuration($checkInDateTime, $checkOutDateTime)
    {
        $checkIn = strtotime($checkInDateTime);
        $checkOut = strtotime($checkOutDateTime);
        
        $diff = $checkOut - $checkIn;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    private function calculateLateAndOther(&$dataKalender, ?string $tglAktifKeterlambatan = null)
    {
        foreach ($dataKalender as $tgl => &$data) {
            $data['late_seconds'] = 0;
            $data['late_minutes'] = 0;
            $data['late_cutoff_time'] = '';
            $data['late_rule_text'] = '';
            $data['late_basis'] = 'regular';

            // Hitung keterlambatan jika ada jam masuk actual dan jam masuk shift
            if (!empty($data['jam_masuk_actual']) && !empty($data['jam_masuk_shift'])) {
                $jamMasuk = strtotime($data['jam_masuk_actual']);
                $jamMasukShift = strtotime($data['jam_masuk_shift']);

                $lateThreshold = $jamMasukShift;
                $isRuleActive = !empty($tglAktifKeterlambatan) && $tgl >= $tglAktifKeterlambatan;

                // Setelah tanggal aktif, semua pegawai wajib absen 30 menit sebelum jam kerja.
                if ($isRuleActive) {
                    $lateThreshold = strtotime('-30 minutes', $jamMasukShift);
                    $data['late_basis'] = 'all_minus_30';
                    $data['late_rule_text'] = 'Semua pegawai wajib absen masuk paling lambat 30 menit sebelum jam kerja.';
                } elseif (!empty($data['has_schedule']) && empty($data['is_office_shift'])) {
                    $lateThreshold = strtotime('-30 minutes', $jamMasukShift);
                    $data['late_basis'] = 'shift_minus_30';
                    $data['late_rule_text'] = 'Pegawai shift wajib absen masuk paling lambat 30 menit sebelum jam shift dimulai.';
                } else {
                    $data['late_rule_text'] = 'Pegawai office/non-shift dinilai terlambat jika absen masuk melewati jam masuk shift.';
                }

                $data['late_cutoff_time'] = date('H:i:s', $lateThreshold);

                if ($jamMasuk > $lateThreshold) {
                    $lateSeconds = $jamMasuk - $lateThreshold;
                    $data['late_seconds'] = $lateSeconds;
                    $data['late_minutes'] = (int) floor($lateSeconds / 60);
                }
            }
        }
    }

    private function resolveBagianAtDate($pegawaiPin, ?string $date = null): string
    {
        $pegawaiPin = (string) $pegawaiPin;

        if ($pegawaiPin === '') {
            return '';
        }

        $date = $date ?: date('Y-m-d');

        if (!array_key_exists($pegawaiPin, $this->bagianHistoryCache)) {
            $this->bagianHistoryCache[$pegawaiPin] = DB::connection('hris')->table('pegawai_bagian_history')
                ->where('pegawai_pin', $pegawaiPin)
                ->orderBy('effective_from')
                ->orderBy('id')
                ->get(['bagian_lama', 'bagian_baru', 'effective_from']);
        }

        $history = $this->bagianHistoryCache[$pegawaiPin];

        if ($history->isNotEmpty()) {
            $latest = $history->last(fn ($row) => $row->effective_from <= $date);

            if ($latest) {
                return !empty($latest->bagian_baru) ? (string) $latest->bagian_baru : (string) ($latest->bagian_lama ?? '');
            }

            $earliest = $history->first();
            return !empty($earliest->bagian_lama) ? (string) $earliest->bagian_lama : (string) ($earliest->bagian_baru ?? '');
        }

        if (!array_key_exists($pegawaiPin, $this->employeeBagianCache)) {
            $this->employeeBagianCache[$pegawaiPin] = DB::connection('hris')->table('pegawai')
                ->where('pegawai_pin', $pegawaiPin)
                ->value('bagian');
        }

        return (string) ($this->employeeBagianCache[$pegawaiPin] ?? '');
    }

    private function workHourForBagianAndShift(string $bagian, string $shift): ?object
    {
        if ($bagian === '' || $shift === '') {
            return null;
        }

        $key = $bagian.'|'.$shift;
        if (!array_key_exists($key, $this->workHourCache)) {
            $this->workHourCache[$key] = DB::connection('hris')->table('kelompokjam')
                ->where('bagian', $bagian)
                ->where('shift', $shift)
                ->first(['jammasuk', 'jampulang']);
        }

        return $this->workHourCache[$key];
    }

    private function isOfficeBagian(string $bagian): bool
    {
        return $this->workHourForBagianAndShift($bagian, '-') !== null;
    }

    private function getTanggalAktifKeterlambatan(): ?string
    {
        if (!Schema::connection('hris')->hasTable('keterlambatan')) {
            return null;
        }

        $row = DB::connection('hris')->table('keterlambatan')
            ->whereNotNull('tgl_aktif')
            ->min('tgl_aktif');

        return $row ?: null;
    }
    
    private function getOvertimeData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        if (!Schema::connection('hris')->hasTable('riwayatlembur')) {
            return;
        }

        $results = DB::connection('hris')->select("
            SELECT 
                r.tgl,
                r.jam_in,
                r.jam_out,
                r.durasi,
                COALESCE(l.alasan, '') as alasan,
                r.tipe,
                r.idlembur
            FROM riwayatlembur r
            LEFT JOIN lembur l ON r.idlembur = l.idlembur AND r.pegawai_pin = l.pegawai_pin
            WHERE r.pegawai_pin = ?
            AND r.tgl BETWEEN ? AND ?
            ORDER BY r.tgl, r.jam_in
        ", [$pegawaiPin, $startDate, $endDate]);
        
        foreach ($results as $row) {
            $tgl = $row->tgl;
            if (isset($dataKalender[$tgl])) {
                if (!isset($dataKalender[$tgl]['lembur_data'])) {
                    $dataKalender[$tgl]['lembur_data'] = [];
                }
                
                $dataKalender[$tgl]['lembur_data'][] = [
                    'jam_in' => (string) $row->jam_in,
                    'jam_out' => (string) $row->jam_out,
                    'durasi' => (int) $row->durasi,
                    'alasan' => (string) $row->alasan,
                    'tipe' => (string) $row->tipe,
                    'idlembur' => $row->idlembur,
                    'session_key' => $this->overtimeSessionKey((string) $row->tipe, $row->idlembur, (string) $row->jam_in, (string) $row->jam_out),
                    'idoperasi' => (string) $row->tipe === 'operasi' ? $row->idlembur : null,
                ];
            }
        }
    }

    private function applyAttendanceAdjustments($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        if (!Schema::connection('hris')->hasTable('attendance_adjustments')) {
            return;
        }

        $adjustments = DB::connection('hris')->table('attendance_adjustments')
            ->where('pegawai_pin', $pegawaiPin)
            ->whereBetween('tgl', [$startDate, $endDate])
            ->orderBy('tgl')
            ->get();

        foreach ($adjustments as $adjustment) {
            $tgl = (string) $adjustment->tgl;
            if (!isset($dataKalender[$tgl])) {
                continue;
            }

            $entryType = (string) $adjustment->entry_type;
            $time = !empty($adjustment->time) ? substr((string) $adjustment->time, 0, 8) : '';

            if (in_array($entryType, ['regular_in', 'regular_out'], true)) {
                if ($entryType === 'regular_in') {
                    $dataKalender[$tgl]['jam_masuk_actual'] = $time;
                    $dataKalender[$tgl]['jam_masuk'] = $time;
                } else {
                    $dataKalender[$tgl]['jam_pulang_actual'] = $time;
                    $dataKalender[$tgl]['jam_pulang'] = $time;
                }

                $dataKalender[$tgl]['attendance_adjustment_note'] = (string) ($adjustment->note ?? '');
                continue;
            }

            if (!in_array($entryType, ['overtime_in', 'overtime_out', 'operation_in', 'operation_out'], true)) {
                continue;
            }

            $tipe = str_starts_with($entryType, 'operation_') ? 'operasi' : 'lembur';
            $targetId = $tipe === 'operasi'
                ? (int) ($adjustment->idoperasi ?? $adjustment->idlembur ?? 0)
                : (int) ($adjustment->idlembur ?? 0);
            $targetPrefix = $tipe === 'operasi' ? 'operasi:' : 'lembur:';
            $targetKey = trim((string) ($adjustment->target_key ?? ''));
            $targetSessionKey = str_starts_with($targetKey, $targetPrefix)
                ? substr($targetKey, strlen($targetPrefix))
                : '';
            if (preg_match('/^regular|^[0-9]+$/', $targetSessionKey)) {
                $targetSessionKey = '';
            }

            if ($targetSessionKey === '' && $targetId <= 0) {
                continue;
            }

            foreach ($dataKalender[$tgl]['lembur_data'] as &$lembur) {
                if (($lembur['tipe'] ?? '') !== $tipe) {
                    continue;
                }

                $sessionKey = (string) ($lembur['session_key'] ?? '');
                if ($targetSessionKey !== '' && $sessionKey !== '' && $sessionKey !== $targetSessionKey) {
                    continue;
                }

                $lemburId = $tipe === 'operasi'
                    ? (int) ($lembur['idoperasi'] ?? $lembur['idlembur'] ?? 0)
                    : (int) ($lembur['idlembur'] ?? 0);
                if ($targetSessionKey === '' && $lemburId !== $targetId) {
                    continue;
                }

                if ($entryType === 'overtime_in' || $entryType === 'operation_in') {
                    $lembur['jam_in'] = $time;
                } else {
                    $lembur['jam_out'] = $time;
                }

                if (!empty($lembur['jam_in']) && !empty($lembur['jam_out'])) {
                    $lembur['durasi'] = $this->calculateMinutesBetween($tgl, $lembur['jam_in'], $lembur['jam_out']);
                }

                $lembur['adjustment_note'] = (string) ($adjustment->note ?? '');
            }
            unset($lembur);
        }

        foreach ($dataKalender as &$data) {
            if (!empty($data['lembur_data'])) {
                $data['lembur_data'] = array_values($data['lembur_data']);
            }
        }
    }

    private function overtimeSessionKey(string $type, $idlembur, string $jamIn, string $jamOut): string
    {
        return substr(hash('sha1', implode('|', [
            $type,
            (string) ($idlembur ?? ''),
            substr($jamIn, 0, 8),
            substr($jamOut, 0, 8),
        ])), 0, 40);
    }

    private function calculateMinutesBetween(string $date, string $startTime, string $endTime): float
    {
        $start = Carbon::parse($date.' '.str_replace(' (esok)', '', $startTime));
        $end = Carbon::parse($date.' '.str_replace(' (esok)', '', $endTime));

        if ($end->lt($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    private function normalizeOvertimeAgainstSchedule(array &$dataKalender): void
    {
        foreach ($dataKalender as $date => &$day) {
            if (empty($day['has_schedule']) || empty($day['jam_pulang_shift'])) {
                continue;
            }

            $shiftStart = substr((string) $day['jam_masuk_shift'], 0, 8);
            $shiftEnd = substr((string) $day['jam_pulang_shift'], 0, 8);

            if ($shiftStart === '' || $shiftEnd === '' || $shiftEnd <= $shiftStart) {
                continue;
            }

            foreach ($day['lembur_data'] as &$lembur) {
                if (($lembur['tipe'] ?? '') !== 'lembur' || empty($lembur['jam_in'])) {
                    continue;
                }

                $jamIn = substr((string) $lembur['jam_in'], 0, 8);
                $jamOut = substr((string) ($lembur['jam_out'] ?? ''), 0, 8);

                if ($jamOut !== '' && $jamIn < $shiftStart && $jamOut > $shiftStart) {
                    $lembur['jam_out'] = $shiftStart;
                    $lembur['durasi'] = $this->calculateMinutesBetween(
                        (string) $date,
                        (string) $lembur['jam_in'],
                        $shiftStart
                    );
                    continue;
                }

                if ($jamOut !== '' && $jamIn >= $shiftStart && $jamIn < $shiftEnd && $jamOut <= $shiftEnd) {
                    $lembur['warning'] = 'Lembur tidak diijinkan didalam jam shift.';
                    continue;
                }

                if ($jamIn >= $shiftStart && $jamIn < $shiftEnd) {
                    $lembur['jam_in'] = $shiftEnd;
                    if ($jamOut !== '') {
                        $lembur['durasi'] = $this->calculateMinutesBetween(
                            (string) $date,
                            $shiftEnd,
                            (string) $lembur['jam_out']
                        );
                    }
                }
            }
            unset($lembur);
        }
        unset($day);
    }
    
    private function getSpecialStatusData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        if (Schema::connection('hris')->hasTable('cuti') && Schema::connection('hris')->hasTable('cutihdr')) {
            $cutiResults = DB::connection('hris')->select("
                SELECT
                    C.tglcuti as tgl,
                    'Cuti' as keterangan
                FROM cuti C
                JOIN cutihdr H ON H.idcuti = C.idcuti
                WHERE C.pegawai_pin = ?
                AND C.tglcuti BETWEEN ? AND ?
                AND H.jeniscuti = 'Cuti Tahunan'
                AND C.tglcuti BETWEEN H.tgl_mulai AND H.tgl_selesai
            ", [$pegawaiPin, $startDate, $endDate]);

            foreach ($cutiResults as $row) {
                $tgl = $row->tgl;
                if (isset($dataKalender[$tgl])) {
                    $dataKalender[$tgl]['status_khusus'] = $row->keterangan;
                }
            }
        }

        if (Schema::connection('hris')->hasTable('tugasluar')) {
            $tugasLuarResults = DB::connection('hris')->select("
                SELECT
                    tgltugasluar as tgl,
                    'Tugas Luar' as keterangan
                FROM tugasluar
                WHERE pegawai_pin = ?
                AND tgltugasluar BETWEEN ? AND ?
            ", [$pegawaiPin, $startDate, $endDate]);

            foreach ($tugasLuarResults as $row) {
                $tgl = $row->tgl;
                if (isset($dataKalender[$tgl])) {
                    $dataKalender[$tgl]['status_khusus'] = $row->keterangan;
                }
            }
        }

        if (Schema::connection('hris')->hasTable('doubleshift')) {
            $doubleShiftResults = DB::connection('hris')->select("
                SELECT
                    tglshift as tgl,
                    'Double Shift' as keterangan
                FROM doubleshift
                WHERE pegawai_pin = ?
                AND tglshift BETWEEN ? AND ?
            ", [$pegawaiPin, $startDate, $endDate]);

            foreach ($doubleShiftResults as $row) {
                $tgl = $row->tgl;
                if (isset($dataKalender[$tgl])) {
                    $dataKalender[$tgl]['status_khusus'] = $row->keterangan;
                }
            }
        }
        
        if (Schema::connection('hris')->hasTable('perizinan')) {
            $permitRows = DB::connection('hris')->table('perizinan')
                ->where('pegawai_pin', $pegawaiPin)
                ->where('status', 'disetujui')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('tanggal_mulai', [$startDate, $endDate])
                        ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('tanggal_mulai', '<=', $startDate)
                                ->where('tanggal_selesai', '>=', $endDate);
                        });
                })
                ->get(['tanggal_mulai', 'tanggal_selesai', 'keterangan']);

            foreach ($permitRows as $permit) {
                $current = Carbon::parse($permit->tanggal_mulai)->max(Carbon::parse($startDate));
                $end = Carbon::parse($permit->tanggal_selesai)->min(Carbon::parse($endDate));

                while ($current->lte($end)) {
                    $tgl = $current->toDateString();
                    if (isset($dataKalender[$tgl]) && empty($dataKalender[$tgl]['status_khusus'])) {
                        $dataKalender[$tgl]['status_khusus'] = (string) $permit->keterangan;
                    }

                    $current->addDay();
                }
            }
        }
    }

    // Method untuk mendapatkan summary data dengan penanganan shift malam
    public function getSummaryDataWithNightShift($pegawaiPin, $bulan)
    {
        $dataKalender = $this->getDataKalenderWithNightShift($pegawaiPin, $bulan);
        
        $summary = [
            'total_hari_kerja' => 0,
            'total_hadir' => 0,
            'total_telat' => 0,
            'total_durasi_lembur' => 0,
            'total_durasi_operasi' => 0,
            'total_double_shift' => 0,
            'total_tugas_luar' => 0,
            'total_cuti' => 0,
            'total_shift_malam' => 0,
            'total_jam_kerja' => '00:00'
        ];
        
        $totalMinutes = 0;
        $uniqueWorkDays = [];
        
        foreach ($dataKalender as $date => $data) {
            // Hitung hari kerja unik
            if (!empty($data['jam_masuk_actual']) || !empty($data['status_khusus'])) {
                if (!in_array($date, $uniqueWorkDays)) {
                    $uniqueWorkDays[] = $date;
                    $summary['total_hari_kerja']++;
                }
            }
            
            // Hitung shift malam
            if ($data['is_night_shift']) {
                $summary['total_shift_malam']++;
            }
            
            // Hitung total jam kerja dari durasi shift malam
            if (!empty($data['work_duration']) && $data['work_duration'] != '00:00') {
                list($hours, $minutes) = explode(':', $data['work_duration']);
                $totalMinutes += ($hours * 60) + $minutes;
            }
            
            // Hitung keterlambatan
            if ($data['late_seconds'] > 0) {
                $summary['total_telat']++;
            }
            
            // Hitung lembur dan operasi
            if (!empty($data['lembur_data'])) {
                foreach ($data['lembur_data'] as $lembur) {
                    if (!empty($lembur['warning'])) {
                        continue;
                    }

                    if ($lembur['tipe'] == 'operasi') {
                        $summary['total_durasi_operasi'] += $lembur['durasi'];
                    } else {
                        $summary['total_durasi_lembur'] += $lembur['durasi'];
                    }
                }
            }
            
            // Hitung status khusus
            if (!empty($data['status_khusus'])) {
                $status = strtolower($data['status_khusus']);
                if (strpos($status, 'cuti') !== false) {
                    $summary['total_cuti']++;
                } elseif (strpos($status, 'tugas luar') !== false) {
                    $summary['total_tugas_luar']++;
                } elseif (strpos($status, 'double shift') !== false) {
                    $summary['total_double_shift']++;
                }
            }
        }
        
        // Hitung total hadir (hari dengan jam masuk)
        $summary['total_hadir'] = count(array_filter($dataKalender, function($data) {
            return !empty($data['jam_masuk_actual']);
        }));
        
        // Hitung total jam kerja
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        $summary['total_jam_kerja'] = sprintf('%02d:%02d', $totalHours, $remainingMinutes);
        
        return $summary;
    }

    public function getLateSummaryForPeriod($pegawaiPin, $bulan): array
    {
        $dataKalender = $this->getDataKalenderWithNightShift($pegawaiPin, $bulan);

        $totalSeconds = 0;
        $totalDays = 0;

        foreach ($dataKalender as $data) {
            if (!empty($data['status_khusus']) || empty($data['late_seconds'])) {
                continue;
            }

            $totalSeconds += (int) $data['late_seconds'];
            $totalDays++;
        }

        return [
            'total_seconds' => $totalSeconds,
            'total_minutes' => (int) floor($totalSeconds / 60),
            'total_days' => $totalDays,
        ];
    }

    // Method kompatibilitas
    public function getDataKalender($pegawaiPin, $bulan)
    {
        return $this->getDataKalenderWithNightShift($pegawaiPin, $bulan);
    }
    
    public function getSummaryData($pegawaiPin, $bulan)
    {
        return $this->getSummaryDataWithNightShift($pegawaiPin, $bulan);
    }
}
