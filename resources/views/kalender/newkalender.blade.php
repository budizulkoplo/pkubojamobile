@extends('layouts.presensi')

@section('header')
<?php
if (!function_exists('secondsToTime')) {
    function secondsToTime($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf("%02d:%02d", $hours, $minutes);
    }
}

if (!function_exists('minutesToTime')) {
    function minutesToTime($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d", $hours, $mins);
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
                    $holidayDate = $holiday['event_date'];
                    $nationalHolidays[$holidayDate] = $holiday['event_name'];
                }
            }
            
            return $nationalHolidays;
        } catch (Exception $e) {
            error_log("Error fetching holidays: ".$e->getMessage());
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

// Initialize summary variables
$totalTerlambatSeconds = 0;
$totalLemburSeconds = 0;
$totalOperasiSeconds = 0;
$totalWorkDays = 0;
$totalCuti = 0;
$totalTugasLuar = 0;
$totalDoubleShift = 0;

// Calculate summary data
foreach ($dataKalender as $date => $data) {
    // Count work days (has either check-in or check-out)
    if (!empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual'])) {
        $totalWorkDays++;
    }
    
    // Calculate lateness ONLY if there's no special status
    if (empty($data['status_khusus'])) {
        if (!empty($data['jam_masuk_actual']) && !empty($data['jam_masuk_shift'])) {
            $jamMasuk = $data['jam_masuk_actual'];
            $jamMasukShift = $data['jam_masuk_shift'];
            
            $masukTime = strtotime($jamMasuk);
            $shiftTime = strtotime($jamMasukShift);
            
            if ($masukTime > $shiftTime) {
                $lateSeconds = $masukTime - $shiftTime;
                $totalTerlambatSeconds += $lateSeconds;
            }
        }
    }
    
    // Calculate overtime from lembur_data
    if (!empty($data['lembur_data'])) {
        foreach ($data['lembur_data'] as $lembur) {
            $lemburSeconds = $lembur['durasi'];
            if ($lemburSeconds > 0 && $lemburSeconds <= 57600) {
                if ($lembur['tipe'] == 'operasi') {
                    $totalOperasiSeconds += $lemburSeconds;
                } else {
                    $totalLemburSeconds += $lemburSeconds;
                }
            }
        }
    }
    
    // Count leave and out-of-office assignments
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

// Get holidays data
$selectedYear = date('Y', strtotime($bulan.'-01'));
$liburNasional = getHolidays($selectedYear);
$selectedMonth = date('m', strtotime($bulan.'-01'));
$liburBulanIni = array_filter($liburNasional, function($date) use ($selectedMonth) {
    return date('m', strtotime($date)) == $selectedMonth;
}, ARRAY_FILTER_USE_KEY);

// Function to format durasi
function formatDurasi($durasiDetik) {
    return minutesToTime($durasiDetik);
}
?>
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Kalender Absensi</div>
    <div class="right"></div>
</div>
<!-- * App Header -->

<style>
/* Base Mobile Styles */
.kalender-cell {
    height: 120px;
    position: relative;
    background-color: #fff;
    overflow: hidden;
    font-size: 9px;
    padding: 2px;
    border: 1px solid #e0e0e0;
}

.date-label {
    font-weight: bold;
    font-size: 10px;
    color: #333;
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(255,255,255,0.9);
    padding: 0 3px;
    border-radius: 3px;
    z-index: 2;
}

.cell-content {
    padding-top: 16px;

    overflow-y: auto;
}

/* Shift Box */
.shift-box {
    font-size: 8px !important;
    padding: 2px 4px;
    border-radius: 3px;
    margin: 2px 0;
    display: block;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
    font-weight: bold;
}

/* Time Display */
.jam-container {
    display: flex;
    align-items: center;
    font-size: 8px !important;
    line-height: 1.2;
    margin: 1px 0;
}

.jam-label {
    color: #666;
    display: inline-block;
    width: 14px;
    font-weight: normal;
    flex-shrink: 0;
    text-align: left;
    font-size: 7px;
}

.jam-value {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-family: monospace;
    font-size: 8px;
}

/* Status Indicators */
.terlambat {
    font-size: 7px !important;
    color: #d32f2f;
    margin: 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #ffebee;
    padding: 1px 3px;
    border-radius: 2px;
    text-align: center;
}

/* Lembur Section - Compact */
.lembur-section {
    margin: 3px 0;
    padding: 2px;
    background: #f8f9fa;
    border-radius: 2px;
    border-left: 2px solid #00796b;
}

.lembur-header {
    font-size: 7px !important;
    font-weight: bold;
    color: #00796b;
    text-align: center;
    margin-bottom: 1px;
    background: #e0f2f1;
    padding: 1px 2px;
    border-radius: 1px;
}

.operasi-header {
    font-size: 7px !important;
    font-weight: bold;
    color: #7b1fa2;
    text-align: center;
    margin-bottom: 1px;
    background: #f3e5f5;
    padding: 1px 2px;
    border-radius: 1px;
}

.lembur-item {
    margin: 1px 0;
    padding: 1px 2px;
    background: white;
    border-radius: 1px;
    border-left: 1px solid #00796b;
}

.operasi-item {
    margin: 1px 0;
    padding: 1px 2px;
    background: white;
    border-radius: 1px;
    border-left: 1px solid #7b1fa2;
}

.lembur-alasan {
    font-size: 6px !important;
    color: #666;
    font-style: italic;
    background: #fff9c4;
    padding: 0 2px;
    border-radius: 1px;
    margin-bottom: 1px;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.lembur-detail {
    font-size: 6px !important;
    display: flex;
    align-items: center;
    margin: 0;
}

.lembur-time {
    font-family: monospace;
    font-size: 6px !important;
}

.lembur-duration {
    font-size: 6px !important;
    font-weight: bold;
    text-align: center;
    margin-top: 1px;
    padding: 0 2px;
    border-radius: 1px;
}

.lembur-duration.lembur { 
    color: #00796b; 
    background: #e0f2f1;
}
.lembur-duration.operasi { 
    color: #7b1fa2; 
    background: #f3e5f5;
}

.status-khusus {
    font-size: 7px !important;
    margin: 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #fff3e0;
    padding: 1px 3px;
    border-radius: 2px;
    text-align: center;
    font-weight: bold;
}

/* Special Day Styles */
.libur-nasional {
    background-color: #ffebee !important;
}

.minggu {
    background-color: #fff8e1 !important;
}

.tanggal-nonaktif {
    background-color: #f8f9fa !important;
    color: #6c757d;
}

/* Shift Color Classes */
.shift-office { background-color: #07b8b2; color: white; }
.shift-office1 { background-color: #07b8b2; color: white; }
.shift-office2 { background-color: #07b8b2; color: white; }
.shift-pagibangsal { background-color: #07b8b2; color: white; }
.shift-pagi { background-color: #00ffbf; color: black; }
.shift-siang { background-color: #ffbf00; color: black; }
.shift-malam { background-color: #00bfff; color: white; }
.shift-midle { background-color: #ff6699; color: white; }
.shift-double { background-color: #9966cc; color: white; }
.shift-belum { background-color: #ff4444; color: white; }

/* Table Styles */
.table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.table th, .table td {
    padding: 1px;
    border: 1px solid #e0e0e0;
    vertical-align: top;
}

.table th {
    font-size: 8px;
    padding: 4px 0;
    text-align: center;
    height: 20px;
    background-color: #f8f9fa;
    font-weight: bold;
}

/* Header Days Rotation for Mobile */
.table th span {
    display: inline-block;
    transform: rotate(-45deg);
    transform-origin: left center;
    width: 15px;
    text-align: left;
    position: relative;
    left: 6px;
    font-size: 8px;
}

/* Highlight current day */
.hari-ini {
    box-shadow: inset 0 0 0 2px #2196F3;
    background-color: #e3f2fd !important;
}

/* Holiday Name in Cell */
.holiday-name {
    font-size: 6px;
    color: #d32f2f;
    font-style: italic;
    position: absolute;
    bottom: 1px;
    left: 2px;
    right: 2px;
    text-align: center;
    background: rgba(255,235,238,0.9);
    padding: 0 1px;
    border-radius: 1px;
}

/* Summary Boxes - Horizontal Scroll */
.summary-scroll {
    display: flex;
    overflow-x: auto;
    gap: 6px;
    padding: 8px 0;
    -webkit-overflow-scrolling: touch;
    margin: 10px 0;
}

.summary-box {
    flex: 0 0 auto;
    width: 130px;
    padding: 6px;
    background: white;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: 1px solid #e0e0e0;
}

.summary-title {
    font-size: 9px;
    font-weight: bold;
    margin-bottom: 3px;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 2px;
}

.summary-value {
    font-size: 10px;
    font-weight: bold;
}

/* Color variants for summary values */
.summary-terlambat { color: #d32f2f; }
.summary-lembur { color: #00796b; }
.summary-operasi { color: #7b1fa2; }
.summary-cuti { color: #f57c00; }
.summary-tugas { color: #0288d1; }
.summary-double { color: #5d4037; }

/* Form Styles */
.form-container {
    background: white;
    padding: 8px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 8px;
}

.form-control {
    font-size: 14px;
    height: 42px;
}

.btn {
    font-size: 14px;
    height: 42px;
}

/* Alert Styles */
.alert {
    font-size: 12px;
    padding: 6px;
    margin-bottom: 6px;
}

/* Holiday List */
.holiday-list {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 10px;
    margin-top: 12px;
}

.holiday-item {
    padding: 4px 0;
    border-bottom: 1px solid #f5f5f5;
    font-size: 11px;
}

.holiday-date {
    font-weight: bold;
    color: #333;
    font-size: 10px;
}

.holiday-event {
    color: #666;
    font-size: 10px;
}

/* Empty cell styling */
.empty-cell {
    color: #999;
    font-style: italic;
    text-align: center;
    padding-top: 40px;
    font-size: 8px;
}

/* Lateness highlight */
.jam-masuk-late {
    color: #d32f2f;
    font-weight: bold;
}

/* Total Lembur per Hari */
.total-lembur-hari {
    font-size: 6px !important;
    margin-top: 2px;
    padding: 1px 2px;
    background: #e3f2fd;
    border-radius: 1px;
    text-align: center;
    font-weight: bold;
    color: #1976d2;
    border: 1px dashed #90caf9;
}

/* Mobile Optimizations */
@media (max-width: 576px) {
    .kalender-cell {
        height: 110px;
    }
    
    .summary-box {
        width: 120px;
        padding: 5px;
    }
    
    .holiday-list {
        padding: 8px;
    }
}

@media (max-width: 400px) {
    .kalender-cell {
        height: 100px;
    }
    
    .date-label {
        font-size: 9px;
    }
    
    .shift-box, .jam-container {
        font-size: 7px !important;
    }
    
    .jam-label {
        width: 12px;
    }
    
    .summary-box {
        width: 110px;
    }
    
    .table th {
        font-size: 7px;
    }
}

/* Scrollbar styling */
.summary-scroll::-webkit-scrollbar {
    height: 3px;
}

.summary-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.summary-scroll::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.summary-scroll::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.cell-content::-webkit-scrollbar {
    width: 2px;
}

.cell-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 1px;
}

.cell-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 1px;
}

/* Badge for multiple lembur items */
.lembur-badge {
    font-size: 6px !important;
    background: #00796b;
    color: white;
    padding: 0 3px;
    border-radius: 4px;
    margin-left: 2px;
}

.operasi-badge {
    font-size: 6px !important;
    background: #7b1fa2;
    color: white;
    padding: 0 3px;
    border-radius: 4px;
    margin-left: 2px;
}
</style>
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        <!-- Form Filter -->
        <form method="post" action="{{ url()->current() }}">
            @csrf
            <div class="form-group">
                <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary w-100">
                    <ion-icon name="calendar-outline"></ion-icon> Tampilkan
                </button>
            </div>
        </form>

        <!-- @if($selectedEmployee)
            <div class="alert alert-info">
                <strong>Pegawai:</strong> {{ $selectedEmployee->pegawai_nama }}<br>
                <strong>PIN:</strong> {{ $selectedEmployee->pegawai_pin }}
            </div>
        @endif -->

        @if(!empty($dataKalender))
            <!-- Calendar Table -->
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th><span>Sen</span></th>
                            <th><span>Sel</span></th>
                            <th><span>Rab</span></th>
                            <th><span>Kam</span></th>
                            <th><span>Jum</span></th>
                            <th><span>Sab</span></th>
                            <th><span>Min</span></th>
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
                                        $isToday = date('Y-m-d') == $tgl ? 'hari-ini' : '';
                                        
                                        // Tentukan apakah tanggal termasuk dalam periode aktif (26-25)
                                        $tglDay = date('d', strtotime($tgl));
                                        $tglMonth = date('m', strtotime($tgl));
                                        $bulanMonth = date('m', strtotime($bulan . '-01'));
                                        $prevMonth = date('m', strtotime($bulan . '-01 -1 month'));
                                        
                                        if (!(($tglDay >= 26 && $tglMonth == $prevMonth) || 
                                              ($tglDay <= 25 && $tglMonth == $bulanMonth))) {
                                            $cellClass .= ' tanggal-nonaktif';
                                        }
                                        
                                        $holidayName = $liburNasional[$tgl] ?? '';
                                        
                                        $shiftClass = '';
                                        $lateSeconds = 0;
                                        $showAttendance = false;
                                        
                                        // Default shift untuk non-shift
                                        $displayShift = '';
                                        if (!empty($data['shift'])) {
                                            $displayShift = $data['shift'];
                                            $shiftLower = strtolower($data['shift']);
                                            switch($shiftLower) {
                                                case 'office': $shiftClass = 'shift-office'; break;
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
                                        } elseif (!empty($data['jam_masuk_actual']) || !empty($data['jam_pulang_actual'])) {
                                            $displayShift = 'Belum ada jadwal';
                                            $shiftClass = 'shift-belum';
                                        }
                                        
                                        $showAttendance = 
                                            !empty($displayShift) || 
                                            !empty($data['status_khusus']) || 
                                            !empty($data['jam_masuk_actual']) || 
                                            !empty($data['jam_pulang_actual']) || 
                                            !empty($data['lembur_data']);
                                        
                                        // Calculate lateness only if no special status
                                        if (empty($data['status_khusus']) && !empty($data['jam_masuk_actual']) && !empty($data['jam_masuk_shift'])) {
                                            $jamMasuk = $data['jam_masuk_actual'];
                                            $jamMasukShift = $data['jam_masuk_shift'];
                                            
                                            $masukTime = strtotime($jamMasuk);
                                            $shiftTime = strtotime($jamMasukShift);
                                            
                                            if ($masukTime > $shiftTime) {
                                                $lateSeconds = $masukTime - $shiftTime;
                                            }
                                        }

                                        // Format waktu tanpa detik
                                        $formatWaktu = function($time) {
                                            if (empty($time)) return '-';
                                            try {
                                                return \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('H:i');
                                            } catch (\Exception $e) {
                                                return $time;
                                            }
                                        };

                                        $jamMasuk = $formatWaktu($data['jam_masuk_actual'] ?? '');
                                        $jamPulang = $formatWaktu($data['jam_pulang_actual'] ?? '');
                                        
                                        // Process lembur data
                                        $lemburItems = [];
                                        $operasiItems = [];
                                        $totalLemburHari = 0;
                                        $totalOperasiHari = 0;
                                        
                                        if (!empty($data['lembur_data'])) {
                                            foreach ($data['lembur_data'] as $lembur) {
                                                if ($lembur['tipe'] == 'operasi') {
                                                    $operasiItems[] = $lembur;
                                                    $totalOperasiHari += $lembur['durasi'];
                                                } else {
                                                    $lemburItems[] = $lembur;
                                                    $totalLemburHari += $lembur['durasi'];
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="kalender-cell {{ trim($cellClass) }} {{ $isToday }}">
                                        <div class="date-label">{{ date('j', strtotime($tgl)) }}</div>
                                        <div class="cell-content">
                                            @if($data && $showAttendance)
                                                <!-- Shift Information -->
                                                @if(!empty($displayShift))
                                                    <div class="shift-box {{ $shiftClass }}">{{ $displayShift }}</div>
                                                @endif
                                                
                                                <!-- Jam Masuk/Pulang -->
                                                @if(!empty($jamMasuk) || !empty($jamPulang))
                                                    <div class="jam-container">
                                                        <span class="jam-label">IN:</span> 
                                                        <span class="jam-value">
                                                            @if(!empty($jamMasuk))
                                                                @if($lateSeconds > 0)
                                                                    <span class="jam-masuk-late">{{ $jamMasuk }}</span>
                                                                @else
                                                                    {{ $jamMasuk }}
                                                                @endif
                                                            @else
                                                                -
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="jam-container">
                                                        <span class="jam-label">OUT:</span> 
                                                        <span class="jam-value">
                                                            @if(!empty($jamPulang))
                                                                {{ $jamPulang }}
                                                            @else
                                                                -
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                                
                                                <!-- Late Information -->
                                                @if($lateSeconds > 0 && empty($data['status_khusus']))
                                                    <div class="terlambat">+{{ secondsToTime($lateSeconds) }}</div>
                                                @endif
                                                
                                                <!-- Lembur Section -->
                                                @if(!empty($lemburItems) || !empty($operasiItems))
                                                    <div class="lembur-section">
                                                        @if(!empty($lemburItems))
                                                            <div class="lembur-header">
                                                                LEMBUR 
                                                                @if(count($lemburItems) > 1)
                                                                    <span class="lembur-badge">{{ count($lemburItems) }}</span>
                                                                @endif
                                                            </div>
                                                            @foreach($lemburItems as $index => $lembur)
                                                                <div class="lembur-item">
                                                                    @if(!empty($lembur['alasan']))
                                                                        <div class="lembur-alasan" title="{{ $lembur['alasan'] }}">{{ $lembur['alasan'] }}</div>
                                                                    @endif
                                                                    <div class="lembur-detail">
                                                                        <span class="jam-label">IN:</span>
                                                                        <span class="lembur-time">{{ $formatWaktu($lembur['jam_in'] ?? '-') }}</span>
                                                                    </div>
                                                                    <div class="lembur-detail">
                                                                        <span class="jam-label">OUT:</span>
                                                                        <span class="lembur-time">{{ $formatWaktu($lembur['jam_out'] ?? '-') }}</span>
                                                                    </div>
                                                                    <div class="lembur-duration lembur">{{ formatDurasi($lembur['durasi']) }}</div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                        
                                                        @if(!empty($operasiItems))
                                                            <div class="operasi-header">
                                                                OPERASI 
                                                                @if(count($operasiItems) > 1)
                                                                    <span class="operasi-badge">{{ count($operasiItems) }}</span>
                                                                @endif
                                                            </div>
                                                            @foreach($operasiItems as $index => $lembur)
                                                                <div class="operasi-item">
                                                                    @if(!empty($lembur['alasan']))
                                                                        <div class="lembur-alasan" title="{{ $lembur['alasan'] }}">{{ $lembur['alasan'] }}</div>
                                                                    @endif
                                                                    <div class="lembur-detail">
                                                                        <span class="jam-label">IN:</span>
                                                                        <span class="lembur-time">{{ $formatWaktu($lembur['jam_in'] ?? '-') }}</span>
                                                                    </div>
                                                                    <div class="lembur-detail">
                                                                        <span class="jam-label">OUT:</span>
                                                                        <span class="lembur-time">{{ $formatWaktu($lembur['jam_out'] ?? '-') }}</span>
                                                                    </div>
                                                                    <div class="lembur-duration operasi">{{ formatDurasi($lembur['durasi']) }}</div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                        
                                                        <!-- Total Lembur/Operasi per Hari -->
                                                        @if(count($lemburItems) + count($operasiItems) > 1)
                                                            <div class="total-lembur-hari">
                                                                @if($totalLemburHari > 0)
                                                                    L:{{ minutesToTime($totalLemburHari) }}
                                                                @endif
                                                                @if($totalOperasiHari > 0)
                                                                    @if($totalLemburHari > 0) | @endif
                                                                    O:{{ minutesToTime($totalOperasiHari) }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                <!-- Status Khusus -->
                                                @if(!empty($data['status_khusus']))
                                                    <div class="status-khusus">{{ $data['status_khusus'] }}</div>
                                                @endif
                                            @else
                                                <div class="empty-cell">-</div>
                                            @endif
                                        </div>
                                        
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

            <!-- Summary Boxes - Horizontal Scroll -->
            <div class="summary-scroll">
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
                    <div class="summary-value summary-lembur">{{ minutesToTime($totalLemburSeconds) }}</div>
                </div>
                @endif
                
                @if($totalOperasiSeconds > 0)
                <div class="summary-box">
                    <div class="summary-title">Total Operasi</div>
                    <div class="summary-value summary-operasi">{{ minutesToTime($totalOperasiSeconds) }}</div>
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

            <!-- Daftar Hari Libur -->
            @if(!empty($liburBulanIni))
            <div class="holiday-list">
                <h5>Daftar Hari Libur Bulan {{ date('F Y', strtotime($bulan.'-01')) }}</h5>
                
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
</div>
@endsection