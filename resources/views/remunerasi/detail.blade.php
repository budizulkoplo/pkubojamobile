@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left"><a href="javascript:;" class="headerButton goBack"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Slip Remunerasi</div>
    <div class="right"></div>
</div>
@endsection

@php
    $details = is_array($slip->kpi_details ?? null) ? $slip->kpi_details : [];
    $score = $details['score'] ?? [];
    $attendance = $details['attendance'] ?? [];
    $religious = $details['religious'] ?? [];
    $kajian = $religious['kajian'] ?? [];
    $tadarus = $religious['tadarus'] ?? [];
    $sholat = $religious['sholat'] ?? [];
    $attendanceRate = (float) ($attendance['attendance_rate'] ?? 0);
    $punctualityRate = (float) ($attendance['punctuality_rate'] ?? 0);
    $kajianTotal = (int) ($kajian['total'] ?? 0);
    $tadarusSessions = (int) ($tadarus['total_sesi'] ?? 0);
    $activePrayerDays = (int) ($sholat['hari_aktif'] ?? 0);
    $activityRaw = ($kajianTotal * 15) + ($tadarusSessions * 8) + ($activePrayerDays * 3);
    $money = fn ($value): string => 'Rp '.number_format((float) $value, 0, ',', '.');
@endphp

@section('content')
<div class="p-3 remuneration-slip-mobile" style="margin-top: 40px">
    <div class="text-center mb-3">
        <img src="{{ asset('assets/img/logopku.png') }}" alt="Logo" class="slip-logo">
        <h6 class="mt-2 mb-0">RS PKU Muhammadiyah Boja</h6>
        <small class="text-muted">Slip Remunerasi - {{ \Carbon\Carbon::parse($periode.'-01')->locale('id')->isoFormat('MMMM Y') }}</small>
    </div>

    <div class="mb-3 p-2 bg-light rounded">
        <strong>{{ $slip->pegawai_nama ?: '-' }}</strong><br>
        NIP: {{ $slip->pegawai_nip ?: '-' }}<br>
        Jabatan: {{ $slip->jabatan ?: '-' }}<br>
        Kelompok: {{ $slip->group_name ?: '-' }}{{ $slip->is_ojt ? ' (OJT 50%)' : '' }}
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary text-white">KINERJA / KPI</div>
        <div class="card-body p-0">
            <table class="table table-sm table-borderless mb-0">
                <tr><td>Skor KPI</td><td class="text-end"><strong>{{ $score['total'] ?? $slip->score_total }}</strong> ({{ $score['label'] ?? '-' }})</td></tr>
                <tr><td>Skor Absensi</td><td class="text-end">{{ $score['attendance'] ?? $slip->score_attendance }}</td></tr>
                <tr><td>Skor Aktivitas</td><td class="text-end">{{ $score['activity'] ?? $slip->score_activity }}</td></tr>
                <tr><td>Hadir / Terjadwal</td><td class="text-end">{{ $attendance['present_days'] ?? 0 }} / {{ $attendance['scheduled_days'] ?? 0 }}</td></tr>
                <tr><td>Ketepatan waktu</td><td class="text-end">{{ $attendance['punctuality_rate'] ?? 0 }}%</td></tr>
                <tr><td>Keterlambatan</td><td class="text-end">{{ $attendance['late_days'] ?? 0 }} hari / {{ $attendance['late_minutes'] ?? 0 }} menit</td></tr>
                <tr><td>Kajian total</td><td class="text-end">{{ $kajianTotal }} kegiatan</td></tr>
                <tr><td class="sub-row">- Ahad pagi</td><td class="text-end">{{ $kajian['ahad_pagi'] ?? 0 }}</td></tr>
                <tr><td class="sub-row">- Senin pagi</td><td class="text-end">{{ $kajian['senin_pagi'] ?? 0 }}</td></tr>
                <tr><td class="sub-row">- Kajian bulanan/lainnya</td><td class="text-end">{{ $kajian['kajian_bulanan'] ?? 0 }}</td></tr>
                <tr><td>Tadarus</td><td class="text-end">{{ $tadarus['total_sesi'] ?? 0 }} sesi</td></tr>
                <tr><td>Sholat / hari aktif</td><td class="text-end">{{ $sholat['total'] ?? 0 }} / {{ $sholat['hari_aktif'] ?? 0 }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light">DASAR PERHITUNGAN SKOR</div>
        <div class="card-body p-2 small">
            <p class="mb-2"><strong>Absensi:</strong> 45% × {{ number_format($attendanceRate, 0) }}% kehadiran + 55% × {{ number_format($punctualityRate, 0) }}% ketepatan waktu = <strong>{{ $score['attendance'] ?? $slip->score_attendance }}</strong>.</p>
            <p class="mb-2"><strong>Aktivitas:</strong> {{ $kajianTotal }} kajian × 15 + {{ $tadarusSessions }} tadarus × 8 + {{ $activePrayerDays }} hari aktif sholat × 3 = <strong>{{ min(100, $activityRaw) }}</strong> poin.</p>
            <p class="mb-0"><strong>KPI akhir:</strong> 70% × skor absensi + 30% × skor aktivitas = <strong>{{ $score['total'] ?? $slip->score_total }}</strong>.</p>
        </div>
    </div>

    <div class="card border-success mb-3">
        <div class="card-body text-center">
            <h6 class="text-success">TOTAL REMUNERASI</h6>
            <h4 class="text-success fw-bold mb-0">{{ $money($slip->remuneration_amount) }}</h4>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted">Diterbitkan {{ $slip->published_at ? \Carbon\Carbon::parse($slip->published_at)->format('d/m/Y H:i') : '-' }}</small>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()"><ion-icon name="print-outline"></ion-icon> Print</button>
    </div>
</div>
@endsection

@push('myscript')
<style>
    .remuneration-slip-mobile .slip-logo { max-height: 50px; max-width: 150px; }
    .remuneration-slip-mobile .card-header { font-size: 12px; font-weight: 700; }
    .remuneration-slip-mobile table td { padding: 6px 10px; }
    .remuneration-slip-mobile table td:last-child { font-weight: 600; }
    .remuneration-slip-mobile .sub-row { padding-left: 22px; color: #6c757d; font-size: 12px; }
    @media print {
        .appHeader, .bottom-nav, .bottomMenuDrawer, .bottomMenuOverlay, button { display: none !important; }
        .remuneration-slip-mobile { margin-top: 0 !important; }
    }
</style>
@endpush
