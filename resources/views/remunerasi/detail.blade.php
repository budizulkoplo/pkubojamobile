@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-success text-light">
    <div class="left"><a href="javascript:;" class="headerButton goBack"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Slip Remunerasi</div>
    <div class="right"></div>
</div>
@endsection

@php
    $money = fn ($value): string => 'Rp '.number_format((float) $value, 0, ',', '.');
    $details = is_array($slip->kpi_details ?? null) ? $slip->kpi_details : [];
    $score = $details['score'] ?? [];
    $attendance = $details['attendance'] ?? [];
    $religious = $details['religious'] ?? [];
    $kajian = $religious['kajian'] ?? [];
    $tadarus = $religious['tadarus'] ?? [];
    $sholat = $religious['sholat'] ?? [];
@endphp

@section('content')
<div class="p-3 remuneration-slip-mobile" style="margin-top: 40px">
    <div class="card mb-3">
        <div class="card-body text-center py-3">
            <h5 class="mb-1">RS PKU Muhammadiyah Boja</h5>
            <h4 class="text-success mb-1">Slip Remunerasi</h4>
            <small class="text-muted">Periode {{ \Carbon\Carbon::parse($periode.'-01')->locale('id')->isoFormat('MMMM Y') }}</small>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="identity-row"><span>Nama</span><strong>{{ $slip->pegawai_nama }}</strong></div>
            <div class="identity-row"><span>NIP / PIN</span><strong>{{ $slip->pegawai_nip ?: '-' }} / {{ $slip->pegawai_pin }}</strong></div>
            <div class="identity-row"><span>Profesi</span><strong>{{ $slip->profesi ?: '-' }}</strong></div>
            <div class="identity-row"><span>Jabatan</span><strong>{{ $slip->jabatan ?: '-' }}</strong></div>
            <div class="identity-row"><span>Kelompok</span><strong>{{ $slip->group_name ?: '-' }}</strong></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Ringkasan Performance / KPI</strong></div>
        <div class="card-body">
            <div class="kpi-total text-center mb-3">
                <small class="text-muted">Skor KPI Akhir</small>
                <div>{{ $score['total'] ?? $slip->score_total }}</div>
                <small>{{ $score['label'] ?? '-' }}</small>
            </div>
            <div class="row text-center">
                <div class="col-6"><div class="kpi-metric"><small>Skor Absensi</small><strong>{{ $score['attendance'] ?? $slip->score_attendance }}</strong></div></div>
                <div class="col-6"><div class="kpi-metric"><small>Skor Aktivitas</small><strong>{{ $score['activity'] ?? $slip->score_activity }}</strong></div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Nilai Pembentuk KPI</strong></div>
        <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between"><span>Hari hadir</span><strong>{{ $attendance['present_days'] ?? 0 }} / {{ $attendance['scheduled_days'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Ketepatan waktu</span><strong>{{ $attendance['punctuality_rate'] ?? 0 }}%</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Keterlambatan</span><strong>{{ $attendance['late_days'] ?? 0 }} hari / {{ $attendance['late_minutes'] ?? 0 }} menit</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Lembur / double shift</span><strong>{{ $attendance['overtime_sessions'] ?? 0 }} / {{ $attendance['double_shift_days'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Kajian</span><strong>{{ $kajian['total'] ?? 0 }}</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Tadarus</span><strong>{{ $tadarus['total_sesi'] ?? 0 }} sesi</strong></div>
            <div class="list-group-item d-flex justify-content-between"><span>Sholat / hari aktif</span><strong>{{ $sholat['total'] ?? 0 }} / {{ $sholat['hari_aktif'] ?? 0 }}</strong></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Nominal Remunerasi</strong></div>
        <div class="card-body">
            <div class="identity-row"><span>Total skor kelompok</span><strong>{{ number_format((float) $slip->group_score_total, 0, ',', '.') }}</strong></div>
            <div class="identity-row"><span>Jasa unit</span><strong>{{ $money($slip->unit_service_amount) }}</strong></div>
            <hr>
            <div class="identity-row total-row"><span>Remunerasi diterima</span><strong>{{ $money($slip->remuneration_amount) }}</strong></div>
        </div>
    </div>

    <p class="text-muted small text-center">Slip diterbitkan {{ $slip->published_at ? \Carbon\Carbon::parse($slip->published_at)->format('d/m/Y H:i') : '-' }}.</p>
</div>
@endsection

@push('myscript')
<style>
    .remuneration-slip-mobile .identity-row { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; }
    .remuneration-slip-mobile .identity-row span { color: #6c757d; }
    .remuneration-slip-mobile .identity-row strong { text-align: right; }
    .remuneration-slip-mobile .kpi-total { color: #198754; font-weight: 700; }
    .remuneration-slip-mobile .kpi-total div { font-size: 42px; line-height: 1; }
    .remuneration-slip-mobile .kpi-metric { background: #f4fbf7; border-radius: 8px; padding: 10px; }
    .remuneration-slip-mobile .kpi-metric small { color: #6c757d; display: block; }
    .remuneration-slip-mobile .kpi-metric strong { color: #198754; font-size: 24px; }
    .remuneration-slip-mobile .total-row strong { color: #198754; font-size: 18px; }
</style>
@endpush
