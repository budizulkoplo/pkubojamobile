@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-success text-light">
    <div class="left"><a href="javascript:;" class="headerButton goBack"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Remunerasi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3 remuneration-mobile-page" style="margin-top: 40px">
    <div class="text-center mb-3">
        <ion-icon name="calculator-outline" class="text-success remuneration-main-icon"></ion-icon>
        <h5 class="mb-1">Perhitungan KPI Remunerasi</h5>
        <small class="text-muted">Pahami pembentukan skor sebelum melihat slip remunerasi.</small>
    </div>

    <div class="card mb-3 formula-card">
        <div class="card-header"><strong>1. Skor Absensi</strong></div>
        <div class="card-body">
            <p class="mb-2">Skor absensi dihitung dari kehadiran dan ketepatan waktu.</p>
            <div class="formula-box">
                <strong>Skor Absensi = 45% Kehadiran + 55% Ketepatan Waktu</strong>
            </div>
            <small class="text-muted d-block mt-2">Kehadiran = hari hadir ÷ hari terjadwal. Ketepatan waktu = hari masuk tepat waktu ÷ hari yang memiliki jadwal dan absensi.</small>
        </div>
    </div>

    <div class="card mb-3 formula-card">
        <div class="card-header"><strong>2. Skor Aktivitas</strong></div>
        <div class="card-body">
            <p class="mb-2">Skor aktivitas berasal dari kegiatan keagamaan yang tercatat pada periode tersebut.</p>
            <div class="formula-box">
                <strong>Skor Aktivitas = min(100, Kajian × 15 + Tadarus × 8 + Hari Aktif Sholat × 3)</strong>
            </div>
            <small class="text-muted d-block mt-2">Kajian mencakup Ahad pagi, Senin pagi, dan kajian bulanan/lainnya. Skor aktivitas maksimal 100 poin.</small>
        </div>
    </div>

    <div class="card mb-3 formula-card">
        <div class="card-header"><strong>3. Skor KPI Akhir</strong></div>
        <div class="card-body">
            <div class="formula-box">
                <strong>KPI Akhir = 70% Skor Absensi + 30% Skor Aktivitas</strong>
            </div>
            <small class="text-muted d-block mt-2">Kategori: 90+ Istimewa, 80–89 Baik Sekali, 70–79 Baik, 60–69 Cukup, di bawah 60 Perlu Pendampingan.</small>
        </div>
    </div>

    <div class="alert alert-info small">
        Nilai pada slip mengikuti data absensi dan aktivitas yang tercatat pada periode remunerasi. Rincian sumber nilai akan ditampilkan pada slip.
    </div>

    <a href="{{ route('remuneration.slips') }}" class="btn btn-success btn-block remuneration-slip-button">
        <ion-icon name="document-text-outline"></ion-icon> Lihat Slip Remunerasi
    </a>
</div>
@endsection

@push('myscript')
<style>
    .remuneration-main-icon { font-size: 42px; }
    .formula-card { border-left: 4px solid #198754; }
    .formula-card .card-header { background: #f7fbf9; color: #344054; }
    .formula-box { background: #f8fafc; border: 1px solid #dbe5e1; border-left: 3px solid #20a779; border-radius: 4px; color: #495057; padding: 10px; }
    .remuneration-slip-button { display: block; padding: 11px; }
</style>
@endpush
