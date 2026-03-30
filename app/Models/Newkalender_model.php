<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Newkalender_model extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
    protected $primaryKey = 'pegawai_id';
    
    public function getDataKalenderWithNightShift($pegawaiPin, $bulan)
    {
        // Hitung periode sesuai SP: 26 bulan sebelumnya sampai 25 bulan ini
        $endDate = date('Y-m-25', strtotime($bulan . '-01'));
        $startDate = date('Y-m-d', strtotime($endDate . ' -1 month +1 day'));
        
        // Get employee data
        $pegawai = DB::table('pegawai')
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
                'night_shift_check_out' => '',
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
        
        // Calculate late and other calculations
        $this->calculateLateAndOther($dataKalender);
        
        ksort($dataKalender);
        
        return $dataKalender;
    }
    
    private function getScheduleData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        // 1. Cek bagian pegawai dan tentukan apakah menggunakan Office shift
        $pegawai = DB::select("
            SELECT p.bagian 
            FROM pegawai p 
            WHERE p.pegawai_pin = ?
        ", [$pegawaiPin]);
        
        $bagian = $pegawai[0]->bagian ?? '';
        
        $kelompokJam = DB::select("
            SELECT shift, jammasuk, jampulang 
            FROM kelompokjam 
            WHERE bagian = ?
        ", [$bagian]);
        
        $isOfficeShift = ($kelompokJam[0]->shift ?? '') == '-';
        $defaultJamMasuk = $kelompokJam[0]->jammasuk ?? '08:00:00';
        $defaultJamPulang = $kelompokJam[0]->jampulang ?? '16:00:00';

        // 2. Query jadwal dari tabel jadwal
        $results = DB::select("
            SELECT 
                j.tgl,
                j.shift,
                k.jammasuk as jam_masuk_shift,
                k.jampulang as jam_pulang_shift
            FROM jadwal j
                LEFT JOIN kelompokjam k ON j.shift = k.shift 
                join pegawai p on j.pegawai_pin=p.pegawai_pin
                and p.bagian=k.bagian
            WHERE j.pegawai_pin = ?
            AND j.tgl BETWEEN ? AND ?
            ORDER BY j.tgl
        ", [$pegawaiPin, $startDate, $endDate]);
        
        // 3. Process jadwal yang ada
        foreach ($results as $row) {
            $tgl = $row->tgl;
            if (isset($dataKalender[$tgl])) {
                // Apply Office shift logic
                if ($isOfficeShift) {
                    $dataKalender[$tgl]['shift'] = 'Office';
                    $dataKalender[$tgl]['jam_masuk_shift'] = $defaultJamMasuk;
                    $dataKalender[$tgl]['jam_pulang_shift'] = $defaultJamPulang;
                } else {
                    $dataKalender[$tgl]['shift'] = $row->shift;
                    $dataKalender[$tgl]['jam_masuk_shift'] = $row->jam_masuk_shift;
                    $dataKalender[$tgl]['jam_pulang_shift'] = $row->jam_pulang_shift;
                }
                $dataKalender[$tgl]['has_schedule'] = true;
            }
        }
        
        // 4. Khusus untuk Office shift, set jadwal untuk semua hari yang ada absensi
        if ($isOfficeShift) {
            foreach ($dataKalender as $tgl => $data) {
                $hasAttendance = !empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual']);
                
                // Jika ada absensi dan belum ada jadwal, set Office sebagai default
                if ($hasAttendance && empty($data['has_schedule'])) {
                    $dataKalender[$tgl]['shift'] = 'Office';
                    $dataKalender[$tgl]['jam_masuk_shift'] = $defaultJamMasuk;
                    $dataKalender[$tgl]['jam_pulang_shift'] = $defaultJamPulang;
                    $dataKalender[$tgl]['has_schedule'] = true;
                    $dataKalender[$tgl]['is_default_office'] = true;
                }
            }
        }
        
        // 5. Tandai semua data kalender dengan info office shift
        foreach ($dataKalender as $tgl => $data) {
            $dataKalender[$tgl]['is_office_shift'] = $isOfficeShift;
        }
    }

    private function applyOfficeAttendanceDefaults($pegawaiPin, &$dataKalender)
    {
        $pegawai = DB::select("
            SELECT p.bagian
            FROM pegawai p
            WHERE p.pegawai_pin = ?
        ", [$pegawaiPin]);

        $bagian = $pegawai[0]->bagian ?? '';
        if ($bagian === '') {
            return;
        }

        $officeShift = DB::select("
            SELECT shift, jammasuk, jampulang
            FROM kelompokjam
            WHERE bagian = ? AND shift = '-'
            LIMIT 1
        ", [$bagian]);

        if (empty($officeShift)) {
            return;
        }

        $defaultJamMasuk = $officeShift[0]->jammasuk ?? '08:00:00';
        $defaultJamPulang = $officeShift[0]->jampulang ?? '16:00:00';

        foreach ($dataKalender as $tgl => $data) {
            $hasAttendance = !empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual']);
            if (!$hasAttendance) {
                continue;
            }

            if (empty($dataKalender[$tgl]['jam_masuk_shift'])) {
                $dataKalender[$tgl]['shift'] = 'Office';
                $dataKalender[$tgl]['jam_masuk_shift'] = $defaultJamMasuk;
                $dataKalender[$tgl]['jam_pulang_shift'] = $defaultJamPulang;
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
        $allAttendance = DB::select("
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
                            $dataKalender[$currentDate]['night_shift_check_out'] = $firstMorningCheckOut['jam'];
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
                            $dataKalender[$nextDate]['has_night_shift_checkout'] = true;
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
    
    private function calculateLateAndOther(&$dataKalender)
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

                // Pegawai shift wajib absen 30 menit sebelum shift dimulai untuk operan shift
                if (!empty($data['has_schedule']) && empty($data['is_office_shift'])) {
                    $lateThreshold = strtotime('-30 minutes', $jamMasukShift);
                    $data['late_basis'] = 'shift_minus_30';
                    $data['late_rule_text'] = 'Pegawai shift wajib absen masuk paling lambat 30 menit sebelum jam shift dimulai untuk operan shift.';
                } else {
                    $data['late_rule_text'] = 'Pegawai office/non-shift dinilai terlambat jika absen masuk melewati jam masuk shift.';
                }

                $data['late_cutoff_time'] = date('H:i:s', $lateThreshold);

                if ($jamMasuk > $lateThreshold) {
                    $lateSeconds = $jamMasuk - $lateThreshold;
                    $data['late_seconds'] = $lateSeconds;
                    $data['late_minutes'] = (int) floor($lateSeconds / 60);
                    
                    // Format jam masuk dengan warna merah jika terlambat
                    $data['jam_masuk'] = '<span style="color:red;">' . $data['jam_masuk_actual'] . '</span>';
                }
            }
        }
    }
    
    private function getOvertimeData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        $results = DB::select("
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
                    'jam_in' => $row->jam_in,
                    'jam_out' => $row->jam_out,
                    'durasi' => $row->durasi,
                    'alasan' => $row->alasan,
                    'tipe' => $row->tipe,
                    'idlembur' => $row->idlembur
                ];
            }
        }
    }
    
    private function getSpecialStatusData($pegawaiPin, $startDate, $endDate, &$dataKalender)
    {
        // Get cuti data
        $cutiResults = DB::select("
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
        
        // Get tugas luar data
        $tugasLuarResults = DB::select("
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
        
        // Get double shift data
        $doubleShiftResults = DB::select("
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
        
        // Get other perizinan data
        
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
