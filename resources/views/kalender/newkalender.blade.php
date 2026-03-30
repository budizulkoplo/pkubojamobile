@extends('layouts.presensi')

@section('header')
@php
if (!function_exists('secondsToTime')) {
    function secondsToTime(int $seconds): string {
        if ($seconds < 0) $seconds = 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}

if (!function_exists('minutesToTime')) {
    function minutesToTime(int $minutes): string {
        if ($minutes < 0) $minutes = 0;
        return secondsToTime($minutes * 60);
    }
}

if (!function_exists('getHolidays')) {
    function getHolidays($year) {
        $apiUrl = "https://hari-libur-api.vercel.app/api?year=".$year;

        try {
            $response = file_get_contents($apiUrl);
            $holidaysData = json_decode($response, true);

            $nationalHolidays = [];
            foreach ($holidaysData as $holiday) {
                if ($holiday['is_national_holiday']) {
                    $nationalHolidays[$holiday['event_date']] = $holiday['event_name'];
                }
            }

            return $nationalHolidays;
        } catch (Exception $e) {
            error_log('Error fetching holidays: '.$e->getMessage());
            return [];
        }
    }
}

if (!function_exists('isHoliday')) {
    function isHoliday($date, $liburNasional) {
        $dayOfWeek = date('N', strtotime($date));
        return isset($liburNasional[$date]) || $dayOfWeek == 7;
    }
}

$selectedYear = date('Y', strtotime($bulan . '-01'));
$liburNasional = getHolidays($selectedYear);
$selectedMonth = date('m', strtotime($bulan . '-01'));
$liburBulanIni = array_filter($liburNasional, function ($date) use ($selectedMonth) {
    return date('m', strtotime($date)) == $selectedMonth;
}, ARRAY_FILTER_USE_KEY);

$totalTerlambatSeconds = 0;
$totalLemburSeconds = 0;
$totalWorkDays = 0;
$totalCuti = 0;
$totalTugasLuar = 0;
$totalOperasiSeconds = 0;
$totalDoubleShift = 0;

foreach (($dataKalender ?? []) as $date => $data) {
    if (!empty($data['jam_masuk']) || !empty($data['jam_pulang'])) {
        $totalWorkDays++;
    }

    if (empty($data['status_khusus']) && !empty($data['late_seconds'])) {
        $totalTerlambatSeconds += $data['late_seconds'];
    }

    if (!empty($data['lembur_data'])) {
        foreach ($data['lembur_data'] as $lembur) {
            $lemburSeconds = $lembur['durasi'] * 60;
            if ($lemburSeconds > 0 && $lemburSeconds <= 57600) {
                if (($lembur['tipe'] ?? '') == 'operasi') {
                    $totalOperasiSeconds += $lemburSeconds;
                } else {
                    $totalLemburSeconds += $lemburSeconds;
                }
            }
        }
    }

    if (!empty($data['status_khusus'])) {
        $status = strtolower($data['status_khusus']);
        if (strpos($status, 'cuti') !== false) {
            $totalCuti++;
        } elseif (strpos($status, 'tugas luar') !== false || strpos($status, 'dinas luar') !== false) {
            $totalTugasLuar++;
        } elseif (strpos($status, 'double shift') !== false) {
            $totalDoubleShift++;
        }
    }
}

function formatDurasi($durasiDetik) {
    return minutesToTime($durasiDetik);
}

$endDate = date('Y-m-25', strtotime($bulan . '-01'));
$startDate = date('Y-m-d', strtotime($endDate . ' -1 month +1 day'));
$totalHariDenganLembur = 0;
$totalHariDenganAbsensi = 0;

foreach (($dataKalender ?? []) as $date => $data) {
    if (!empty($data['lembur_data'])) {
        $totalHariDenganLembur++;
    }
    if (!empty($data['jam_masuk']) || !empty($data['jam_pulang'])) {
        $totalHariDenganAbsensi++;
    }
}
@endphp

<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Kalender Absensi</div>
    <div class="right"></div>
</div>

<style>
.kalender-cell {
    width: 165px;
    height: 190px;
    padding: 6px;
    font-size: 12px;
    text-align: left;
    vertical-align: top;
    border: 1px solid #dee2e6;
    position: relative;
    background-color: white;
    overflow-y: auto;
}

.shift-box {
    padding: 3px 6px;
    border-radius: 4px;
    font-weight: bold;
    margin-bottom: 6px;
    display: block;
    text-align: center;
    text-transform: capitalize;
    font-size: 11px;
    margin-top: 25px;
}

.shift-belum {
    background-color: #ff4444 !important;
    color: white;
    animation: blinker 1s linear infinite;
}

.shift-office1,
.shift-office2,
.shift-pagibangsal {
    background-color: #07b8b2 !important;
    color: white;
}

.shift-pagi {
    background-color: #00ffbf;
    color: black;
}

.shift-siang {
    background-color: #ffbf00;
    color: black;
}

.shift-malam {
    background-color: #00bfff;
    color: white;
}

.shift-midle {
    background-color: #ff6699;
    color: white;
}

.shift-double {
    background-color: #9966cc;
    color: white;
}

.jam-masuk-late {
    color: red;
    font-weight: bold;
}

.jam-label {
    font-weight: bold;
    font-size: 11px;
}

.absensi-reguler {
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e9ecef;
}

.lembur-section {
    margin-top: 6px;
    padding-top: 6px;
    border-top: 2px dashed #dee2e6;
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 6px;
}

.lembur-header {
    font-weight: bold;
    color: #0066cc;
    font-size: 11px;
    margin-bottom: 4px;
    text-align: center;
    background-color: #e6f7ff;
    padding: 3px;
    border-radius: 3px;
}

.operasi-header {
    font-weight: bold;
    color: #cc0066;
    font-size: 11px;
    margin-bottom: 4px;
    text-align: center;
    background-color: #ffe6f2;
    padding: 3px;
    border-radius: 3px;
}

.lembur-item {
    margin-bottom: 6px;
    padding: 4px;
    background-color: white;
    border-radius: 3px;
    border-left: 3px solid #0066cc;
}

.operasi-item {
    margin-bottom: 6px;
    padding: 4px;
    background-color: white;
    border-radius: 3px;
    border-left: 3px solid #cc0066;
}

.lembur-alasan {
    font-size: 10pt;
    color: #666;
    font-style: italic;
    background-color: #ffffcc;
    padding: 2px 4px;
    border-radius: 2px;
    margin-bottom: 3px;
    display: block;
}

.lembur-detail {
    font-size: 10pt;
    margin-bottom: 2px;
}

.lembur-duration {
    font-weight: bold;
    color: #0066cc;
    font-size: 10pt;
    text-align: center;
    background-color: #e6f7ff;
    padding: 2px 4px;
    border-radius: 2px;
    margin-top: 2px;
}

.operasi-duration {
    font-weight: bold;
    color: #cc0066;
    font-size: 10pt;
    text-align: center;
    background-color: #ffe6f2;
    padding: 2px 4px;
    border-radius: 2px;
    margin-top: 2px;
}

.terlambat {
    color: red;
    font-weight: bold;
    margin-top: 2px;
    font-size: 11px;
    background-color: #fff5f5;
    padding: 2px 4px;
    border-radius: 2px;
    text-align: center;
}

.cutoff-info {
    margin-top: 2px;
    font-size: 10px;
    color: #495057;
    background-color: #eef6ff;
    border-radius: 2px;
    padding: 2px 4px;
    text-align: center;
}

.status-khusus {
    margin-top: 4px;
    padding: 4px;
    background-color: #ffcc00;
    border-radius: 3px;
    font-weight: bold;
    text-align: center;
    font-size: 11px;
    color: #000;
}

.jam-container {
    margin-bottom: 3px;
    font-size: 11px;
    display: flex;
    justify-content: space-between;
}

.jam-time {
    font-size: 10pt;
}

.total-lembur-hari {
    margin-top: 4px;
    padding: 3px;
    background-color: #e6f7ff;
    border-radius: 3px;
    font-weight: bold;
    text-align: center;
    font-size: 10pt;
    color: #0066cc;
    border: 1px dashed #0066cc;
}

.libur-nasional,
.minggu {
    background-color: #fff0f5 !important;
}

.date-label {
    position: absolute;
    top: 4px;
    right: 4px;
    font-weight: bold;
    font-size: 13px;
    background-color: rgba(255,255,255,0.9);
    padding: 2px 6px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    z-index: 10;
}

.holiday-name {
    font-size: 9px;
    color: #000;
    font-style: italic;
    position: absolute;
    bottom: 4px;
    left: 4px;
    right: 4px;
    text-align: center;
    background-color: rgba(255,240,245,0.9);
    padding: 2px 4px;
    border-radius: 3px;
}

.holiday-list {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.holiday-list h5 {
    margin-bottom: 15px;
    color: #333;
    font-size: 16px;
}

.holiday-item {
    display: flex;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px dashed #ddd;
}

.holiday-date {
    font-size: 11pt;
    font-weight: bold;
    width: 120px;
}

.holiday-event {
    flex-grow: 1;
    font-size: 11pt;
    color: #e60000;
    font-style: italic;
    padding: 3px;
    border-radius: 3px;
}

.summary-container {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.summary-box {
    flex: 1;
    min-width: 200px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-title {
    font-size: 13pt;
    font-weight: bold;
    margin-bottom: 10pt;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.summary-value {
    font-size: 15pt;
    font-weight: bold;
    color: #0066cc;
}

.summary-terlambat { color: #e60000; }
.summary-lembur { color: #009900; }
.summary-operasi { color: #cc0066; }
.summary-cuti { color: #ff9900; }
.summary-tugas { color: #9900cc; }
.summary-double { color: #9966cc; }

.debug-info {
    background: #f8f9fa;
    padding: 12px;
    margin: 10pt 0;
    border-radius: 5px;
    font-size: 13px;
}

.tanggal-aktif {
    background-color: #e7f3ff !important;
}

.tanggal-nonaktif {
    background-color: #f8f9fa !important;
    color: #6c757d;
}

.empty-cell {
    color: #999;
    font-style: italic;
    text-align: center;
    padding-top: 30px;
    font-size: 12px;
}

.table th {
    background-color: #f8f9fa;
    font-weight: bold;
    text-align: center;
    font-size: 13px;
    padding: 10pt;
}

.kalender-cell:hover {
    background-color: #f8f9fa !important;
    transition: background-color 0.3s ease;
}

.kalender-cell::-webkit-scrollbar {
    width: 5px;
}

.kalender-cell::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.kalender-cell::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.form-control,
.btn,
.alert {
    font-size: 14px;
}

@keyframes blinker {
    50% { opacity: 0; }
}
</style>
@endsection

@section('content')
<div class="container-fluid" style="margin-top: 70px; padding-bottom: 30px;">
    <form method="post" action="{{ url()->current() }}" class="mb-4">
        @csrf
        <div class="d-flex gap-2 align-items-center">
            <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" required>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
    </form>

    @if(!empty($selectedEmployee))
        <div class="alert alert-info">
            <strong>Pegawai Terpilih:</strong> {{ $selectedEmployee->pegawai_nama ?? '-' }} |
            <strong>PIN:</strong> {{ $selectedEmployee->pegawai_pin ?? '-' }}
        </div>
        <div class="alert alert-warning">
            <strong>Aturan keterlambatan:</strong> pegawai shift wajib absen masuk paling lambat 30 menit sebelum jam shift dimulai untuk operan shift.
            Contoh: shift mulai <strong>08:00</strong>, maka batas absen masuk adalah <strong>07:30</strong>. Jika absen masuk <strong>07:40</strong>, sistem menghitung terlambat <strong>10 menit</strong>.
        </div>
        <div class="alert alert-secondary">
            <strong>Office/non-shift:</strong> bagian dengan shift <strong>-</strong> dihitung telat sesuai <strong>jam masuk shift</strong>, tanpa operan jaga dan tanpa pengurangan 30 menit.
        </div>
    @endif

    @if(!empty($dataKalender))
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Senin</th>
                        <th>Selasa</th>
                        <th>Rabu</th>
                        <th>Kamis</th>
                        <th>Jumat</th>
                        <th>Sabtu</th>
                        <th>Minggu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($weeks as $mingguKe)
                        <tr>
                            @foreach($mingguKe as $tgl)
                                @php
                                    $data = $dataKalender[$tgl] ?? null;
                                    $isHoliday = isHoliday($tgl, $liburNasional);
                                    $dayOfWeek = date('N', strtotime($tgl));
                                    $cellClass = $isHoliday ? 'libur-nasional' : '';
                                    $cellClass .= $dayOfWeek == 7 ? ' minggu' : '';

                                    $tglDay = date('d', strtotime($tgl));
                                    $tglMonth = date('m', strtotime($tgl));
                                    $bulanMonth = date('m', strtotime($bulan . '-01'));
                                    $prevMonth = date('m', strtotime($bulan . '-01 -1 month'));

                                    if (($tglDay >= 26 && $tglMonth == $prevMonth) || ($tglDay <= 25 && $tglMonth == $bulanMonth)) {
                                        $cellClass .= ' tanggal-aktif';
                                    } else {
                                        $cellClass .= ' tanggal-nonaktif';
                                    }

                                    $holidayName = $liburNasional[$tgl] ?? '';
                                    $shiftClass = '';
                                    $showAttendance = false;
                                    $hasAttendanceData = !empty($data['jam_masuk']) || !empty($data['jam_pulang']);
                                    $hasOtherInfo = !empty($data['status_khusus']) || !empty($data['lembur_data']);

                                    $displayShift = '';
                                    if (!empty($data['shift'])) {
                                        $displayShift = $data['shift'];
                                        $shiftLower = strtolower($data['shift']);
                                        switch ($shiftLower) {
                                            case 'office': $shiftClass = 'shift-office1'; break;
                                            case 'office 1': $shiftClass = 'shift-office1'; break;
                                            case 'office 2': $shiftClass = 'shift-office2'; break;
                                            case 'pagi bangsal': $shiftClass = 'shift-pagibangsal'; break;
                                            case 'pagi': $shiftClass = 'shift-pagi'; break;
                                            case 'siang': $shiftClass = 'shift-siang'; break;
                                            case 'malam': $shiftClass = 'shift-malam'; break;
                                            case 'midle': $shiftClass = 'shift-midle'; break;
                                            case 'double':
                                            case 'double shift': $shiftClass = 'shift-double'; break;
                                            default: $shiftClass = 'shift-belum';
                                        }

                                        $displayShift = ucfirst(strtolower($data['shift']));
                                        if (strtolower($displayShift) === 'office' && !$hasAttendanceData) {
                                            $displayShift = '';
                                        }
                                    } elseif (!empty($data['is_office_shift']) && $hasAttendanceData) {
                                        $displayShift = 'Office';
                                        $shiftClass = 'shift-office1';
                                    } elseif ($hasAttendanceData) {
                                        $displayShift = 'Belum ada jadwal';
                                        $shiftClass = 'shift-belum';
                                    }

                                    if (!$hasAttendanceData && !$hasOtherInfo) {
                                        $displayShift = '';
                                    }

                                    $showAttendance = !empty($displayShift) || $hasOtherInfo || $hasAttendanceData;
                                    $lateSeconds = (int) ($data['late_seconds'] ?? 0);
                                @endphp
                                <td class="kalender-cell {{ trim($cellClass) }}" @if($holidayName) title="{{ $holidayName }}" @endif>
                                    <div class="date-label">{{ date('j', strtotime($tgl)) }}</div>

                                    @if($data && $showAttendance)
                                        @if(!empty($displayShift))
                                            <div class="shift-box {{ $shiftClass }}">{{ $displayShift }}</div>
                                        @endif

                                        <div class="absensi-reguler">
                                            @if(!empty($data['jam_masuk']) || !empty($data['jam_pulang']))
                                                <div class="jam-container">
                                                    <span class="jam-label">IN:</span>
                                                    <span class="jam-time">
                                                        @if(!empty($data['jam_masuk']))
                                                            @if($lateSeconds > 0)
                                                                <span class="jam-masuk-late">{{ strip_tags($data['jam_masuk']) }}</span>
                                                            @else
                                                                {{ strip_tags($data['jam_masuk']) }}
                                                            @endif
                                                        @else
                                                            -
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="jam-container">
                                                    <span class="jam-label">OUT:</span>
                                                    <span class="jam-time">{{ !empty($data['jam_pulang']) ? $data['jam_pulang'] : '-' }}</span>
                                                </div>
                                                @if(!empty($data['jam_masuk_shift']))
                                                    <div class="cutoff-info">
                                                        Shift {{ substr($data['jam_masuk_shift'], 0, 5) }}
                                                        | Batas masuk {{ substr(($data['late_cutoff_time'] ?? $data['jam_masuk_shift']), 0, 5) }}
                                                    </div>
                                                @endif
                                            @endif

                                            @if($lateSeconds > 0 && empty($data['status_khusus']))
                                                <div class="terlambat">Terlambat: {{ secondsToTime($lateSeconds) }}</div>
                                                @if(($data['late_basis'] ?? '') === 'shift_minus_30')
                                                    <div class="cutoff-info">Perhitungan: 30 menit sebelum shift dimulai</div>
                                                @endif
                                            @endif
                                        </div>

                                        @if(!empty($data['lembur_data']))
                                            @php
                                                $totalLemburHari = 0;
                                                $totalOperasiHari = 0;
                                                $lemburItems = [];
                                                $operasiItems = [];

                                                foreach ($data['lembur_data'] as $lembur) {
                                                    if (($lembur['tipe'] ?? '') == 'operasi') {
                                                        $operasiItems[] = $lembur;
                                                        $totalOperasiHari += $lembur['durasi'];
                                                    } else {
                                                        $lemburItems[] = $lembur;
                                                        $totalLemburHari += $lembur['durasi'];
                                                    }
                                                }
                                            @endphp
                                            <div class="lembur-section">
                                                @if(!empty($lemburItems))
                                                    <div class="lembur-header">LEMBUR</div>
                                                    @foreach($lemburItems as $lembur)
                                                        <div class="lembur-item">
                                                            @if(!empty($lembur['alasan']))
                                                                <div class="lembur-alasan">{{ $lembur['alasan'] }}</div>
                                                            @endif
                                                            <div class="lembur-detail">
                                                                <span class="jam-label">IN:</span>
                                                                <span class="jam-time">{{ !empty($lembur['jam_in']) ? $lembur['jam_in'] : '-' }}</span>
                                                            </div>
                                                            <div class="lembur-detail">
                                                                <span class="jam-label">OUT:</span>
                                                                <span class="jam-time">{{ !empty($lembur['jam_out']) ? $lembur['jam_out'] : '-' }}</span>
                                                            </div>
                                                            <div class="lembur-duration">Durasi: {{ formatDurasi($lembur['durasi']) }}</div>
                                                        </div>
                                                    @endforeach
                                                @endif

                                                @if(!empty($operasiItems))
                                                    <div class="operasi-header">OPERASI</div>
                                                    @foreach($operasiItems as $lembur)
                                                        <div class="operasi-item">
                                                            @if(!empty($lembur['alasan']))
                                                                <div class="lembur-alasan">{{ $lembur['alasan'] }}</div>
                                                            @endif
                                                            <div class="lembur-detail">
                                                                <span class="jam-label">IN:</span>
                                                                <span class="jam-time">{{ !empty($lembur['jam_in']) ? $lembur['jam_in'] : '-' }}</span>
                                                            </div>
                                                            <div class="lembur-detail">
                                                                <span class="jam-label">OUT:</span>
                                                                <span class="jam-time">{{ !empty($lembur['jam_out']) ? $lembur['jam_out'] : '-' }}</span>
                                                            </div>
                                                            <div class="operasi-duration">Durasi: {{ formatDurasi($lembur['durasi']) }}</div>
                                                        </div>
                                                    @endforeach
                                                @endif

                                                @if(count($data['lembur_data']) > 1)
                                                    <div class="total-lembur-hari">
                                                        @if($totalLemburHari > 0)
                                                            Total Lembur: {{ formatDurasi($totalLemburHari) }}<br>
                                                        @endif
                                                        @if($totalOperasiHari > 0)
                                                            Total Operasi: {{ formatDurasi($totalOperasiHari) }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if(!empty($data['status_khusus']))
                                            <div class="status-khusus">{!! nl2br(e($data['status_khusus'])) !!}</div>
                                        @endif
                                    @else
                                        <div class="empty-cell">-</div>
                                    @endif

                                    @if($holidayName)
                                        <div class="holiday-name">{{ $holidayName }}</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary-container">
            <div class="summary-box">
                <div class="summary-title">Total Hari Kerja</div>
                <div class="summary-value">{{ $totalWorkDays }} hari</div>
            </div>

            @if($totalTerlambatSeconds > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Keterlambatan</div>
                    <div class="summary-value summary-terlambat">{{ secondsToTime($totalTerlambatSeconds) }}</div>
                </div>
            @endif

            @if($totalLemburSeconds > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Lembur</div>
                    <div class="summary-value summary-lembur">{{ secondsToTime($totalLemburSeconds) }}</div>
                </div>
            @endif

            @if($totalOperasiSeconds > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Operasi</div>
                    <div class="summary-value summary-operasi">{{ secondsToTime($totalOperasiSeconds) }}</div>
                </div>
            @endif

            @if($totalCuti > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Cuti</div>
                    <div class="summary-value summary-cuti">{{ $totalCuti }} hari</div>
                </div>
            @endif

            @if($totalTugasLuar > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Tugas Luar</div>
                    <div class="summary-value summary-tugas">{{ $totalTugasLuar }} hari</div>
                </div>
            @endif

            @if($totalDoubleShift > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Double Shift</div>
                    <div class="summary-value summary-double">{{ $totalDoubleShift }} hari</div>
                </div>
            @endif
        </div>

        <div class="debug-info">
            <strong>Informasi Periode:</strong><br>
            - Periode: <strong>{{ date('d F Y', strtotime($startDate)) }} hingga {{ date('d F Y', strtotime($endDate)) }}</strong><br>
            - Cut off periode bulanan: <strong>tanggal 26 bulan sebelumnya s.d. tanggal 25 bulan berjalan</strong><br>
            - Checkout shift malam milik tanggal <strong>25</strong> bulan sebelumnya tidak lagi ditampilkan pada tanggal <strong>26</strong> di periode baru<br>
            - Total hari dalam periode: {{ count($dataKalender) }} hari<br>
            - Hari dengan absensi: {{ $totalHariDenganAbsensi }} hari<br>
            - Hari dengan lembur: {{ $totalHariDenganLembur }} hari<br>
            - Pegawai: {{ $selectedEmployee->pegawai_nama ?? '-' }}
        </div>

        @if(!empty($liburBulanIni))
            <div class="holiday-list">
                <h5>Daftar Hari Libur Bulan {{ date('F Y', strtotime($bulan . '-01')) }}</h5>
                @foreach($liburBulanIni as $date => $event)
                    <div class="holiday-item">
                        <div class="holiday-date">{{ date('d F Y', strtotime($date)) }}</div>
                        <div class="holiday-event">{{ $event }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="alert alert-info">
            <strong>Informasi:</strong> Tidak ada data absensi untuk pegawai dan bulan yang dipilih.
        </div>
    @endif
</div>
@endsection
