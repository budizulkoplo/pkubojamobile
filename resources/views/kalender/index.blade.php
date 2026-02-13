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

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Base Styles */
    .table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .table th, .table td {
        padding: 4px;
        border: 1px solid #e0e0e0;
        vertical-align: top;
    }
    
    .kalender-cell {
        height: 80px;
        position: relative;
        background-color: #fff;
        overflow: hidden;
        font-size: 10px;
    }
    
    /* Date Label */
    .date-label {
        font-weight: bold;
        font-size: 10px;
        color: #333;
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(255,255,255,0.8);
        padding: 0 3px;
        border-radius: 3px;
    }
    
    /* Shift Indicator - Full Width */
    .shift-box {
        font-size: 8px !important;
        padding: 1px 3px;
        border-radius: 2px;
        margin: 2px 0;
        display: block;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: center;
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
        width: 16px;
        font-weight: normal;
        flex-shrink: 0;
        text-align: left;
    }
    
    .jam-value {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: monospace;
    }
    
    /* Content Wrapper */
    .cell-content {
        padding: 2px;
        padding-top: 18px;
    }
    
    /* Status Indicators */
    .terlambat {
        font-size: 7px !important;
        color: #d32f2f;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Lembur Indicator */
    .lembur-indicator {
        font-size: 7px !important;
        color: #00796b;
        font-weight: bold;
        margin-top: 2px;
        text-align: center;
    }
    
    .status-khusus {
        font-size: 7px !important;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Special Day Styles */
    .libur-nasional {
        background-color: #ffebee;
    }
    
    .minggu {
        background-color: #fff8e1;
    }
    
    /* Shift Color Classes */
    .shift-office { background-color: #07b8b2; color: #ffffff}
    .shift-office1 { background-color: #07b8b2; }
    .shift-office2 { background-color: #07b8b2; }
    .shift-pagibangsal { background-color: #07b8b2; }
    .shift-pagi { background-color: #00ffbf; }
    .shift-siang { background-color: #ffbf00; }
    .shift-malam { background-color: #00bfff; }
    .shift-midle { background-color: #ff6699; }
    
    /* Header Days */
    .table th {
        font-size: 10px;
        padding: 6px 2px;
        text-align: center;
        height: 30px;
    }
    
    /* Summary Container - Dihapus */
    
    /* Holiday List */
    .holiday-list {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
    
    .holiday-item {
        padding: 8px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .holiday-date {
        font-weight: bold;
        color: #333;
    }
    
    .holiday-event {
        color: #666;
        font-size: 14px;
    }
    
    /* Mobile Optimizations */
    @media (max-width: 576px) {
        .kalender-cell {
            height: 70px;
        }
        
        .date-label {
            font-size: 9px;
        }
        
        .shift-box, .jam-container {
            font-size: 7px !important;
        }
        
        .jam-label {
            width: 14px;
        }
        
        .holiday-list {
            padding: 12px;
        }
    }
    
    @media (max-width: 400px) {
        .kalender-cell {
            height: 65px;
        }
        
        .date-label {
            font-size: 8px;
        }
        
        .table th {
            font-size: 8px;
            padding: 4px 1px;
        }
        
        /* Rotate day headers for extra narrow screens */
        .table th span {
            display: inline-block;
            transform: rotate(-45deg);
            transform-origin: left center;
            width: 20px;
            text-align: left;
            position: relative;
            left: 8px;
        }
    }
    
    /* Highlight current day */
    .hari-ini {
        box-shadow: inset 0 0 0 2px #2196F3;
    }
</style>
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        <div class="form-container">
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
        </div>

        @if (!empty($dataKalender))
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th><span>Senin</span></th>
                            <th><span>Selasa</span></th>
                            <th><span>Rabu</span></th>
                            <th><span>Kamis</span></th>
                            <th><span>Jumat</span></th>
                            <th><span>Sabtu</span></th>
                            <th><span>Minggu</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeks as $mingguKe)
                            <tr>
                                @foreach ($mingguKe as $tgl)
                                    @php
                                        $data = $dataKalender[$tgl] ?? null;
                                        $dayOfWeek = \Carbon\Carbon::parse($tgl)->dayOfWeekIso;
                                        $isHoliday = isset($liburNasional[$tgl]) || $dayOfWeek == 7;
                                        $cellClass = ($isHoliday ? 'libur-nasional' : '') . ($dayOfWeek == 7 ? ' minggu' : '');
                                        $isToday = \Carbon\Carbon::parse($tgl)->isToday() ? 'hari-ini' : '';

                                        $shiftClass = '';
                                        if (!empty($data['shift'])) {
                                            $shiftMap = [
                                                'office' => 'shift-office',
                                                'office 1' => 'shift-office1',
                                                'office 2' => 'shift-office2',
                                                'pagi bangsal' => 'shift-pagibangsal',
                                                'pagi' => 'shift-pagi',
                                                'siang' => 'shift-siang',
                                                'malam' => 'shift-malam',
                                                'midle' => 'shift-midle'
                                            ];
                                            $shiftKey = strtolower($data['shift']);
                                            $shiftClass = $shiftMap[$shiftKey] ?? '';
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

                                        $jamMasuk = $formatWaktu(strip_tags($data['jam_masuk'] ?? ''));
                                        $jamPulang = $formatWaktu($data['jam_pulang'] ?? '');
                                        $jamShift = $formatWaktu($data['jam_masuk_shift'] ?? '');
                                        
                                        $lateSeconds = (empty($data['status_khusus']) && $jamMasuk && $jamShift && strtotime($jamMasuk) > strtotime($jamShift)) 
                                            ? strtotime($jamMasuk) - strtotime($jamShift) 
                                            : 0;

                                        $showAttendance = $data && (
                                            !empty($data['shift']) ||
                                            !empty($data['status_khusus']) ||
                                            !empty($data['jam_masuk']) ||
                                            !empty($data['jam_pulang']) ||
                                            !empty($data['lembur_masuk']) ||
                                            !empty($data['lembur_pulang']) ||
                                            !empty($data['alasan_lembur'])
                                        );
                                    @endphp
                                    <td class="kalender-cell {{ $cellClass }} {{ $isToday }}">
                                        <div class="date-label">{{ \Carbon\Carbon::parse($tgl)->format('j') }}</div>
                                        <div class="cell-content">
                                            @if ($showAttendance)
                                                @if (!empty($data['shift']) || !empty($data['jam_masuk']) || !empty($data['jam_pulang']))
                                                    <div class="shift-box {{ $shiftClass }}">{{ ucfirst(strtolower($data['shift'] ?? '-')) }}</div>
                                                @endif

                                                <div class="jam-container">
                                                    <span class="jam-label">IN:</span>
                                                    <span class="jam-value">{!! $jamMasuk ? ($lateSeconds > 0 ? '<span class="jam-masuk-late">'.$jamMasuk.'</span>' : $jamMasuk) : '-' !!}</span>
                                                </div>
                                                <div class="jam-container">
                                                    <span class="jam-label">OUT:</span>
                                                    <span class="jam-value">{{ $jamPulang }}</span>
                                                </div>

                                                @if ($lateSeconds > 0 && empty($data['status_khusus']))
                                                    <div class="terlambat">Terlambat: {{ secondsToTime($lateSeconds) }}</div>
                                                @endif

                                                @if (!empty($data['alasan_lembur']))
                                                    <div class="lembur-indicator">LEMBUR</div>
                                                @endif

                                                @if (!empty($data['status_khusus']))
                                                    <div class="status-khusus"><span class="bg-info px-1">{!! nl2br(strip_tags($data['status_khusus'], '<br>')) !!}</span></div>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="holiday-list">
                <h5>Daftar Hari Libur Bulan {{ \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</h5>
                @forelse ($liburBulanIni as $date => $event)
                    <div class="holiday-item">
                        <div class="holiday-date">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</div>
                        <div class="holiday-event">{{ $event }}</div>
                    </div>
                @empty
                    <p>Tidak ada hari libur nasional pada bulan ini.</p>
                @endforelse
            </div>
        @elseif (request()->isMethod('post'))
            <div class="alert alert-warning">Tidak ada data absensi yang ditemukan untuk pegawai dan periode yang dipilih.</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {
        $('.select2').select2({
            placeholder: 'Pilih Pegawai',
            width: '100%'
        });
        
        // Highlight hari ini
        $('.kalender-cell.hari-ini').css('background-color', '#e3f2fd');
    });
</script>
@endsection