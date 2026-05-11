@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('operan.index') }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Laporan Taqwa</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
@php
    $tanggalCarbon = \Carbon\Carbon::parse($tanggal);
    $ngajiKegiatan = $ngaji->filter(function ($row) {
        if (($row->type ?? null) === 'operan') {
            return ($row->flag ?? null) === 'selesai' || empty($row->flag);
        }

        return in_array($row->type ?? null, ['rutin', 'senin'], true);
    });
    $ngajiLabels = [
        'operan' => 'Tadarus Shift',
        'rutin' => 'Tadarus Rutin',
        'senin' => 'Senin Pagi',
    ];
    $ngajiMeta = [
        'operan' => 'Bacaan operan shift',
        'rutin' => 'Riwayat kegiatan tadarus rutin',
        'senin' => 'Riwayat kegiatan Senin Pagi',
    ];
    $totalFardhu = $sholat->where('jenis', 'fardhu')->count();
    $totalSunnah = $sholat->where('jenis', 'sunnah')->count();
    $totalJamaah = $sholat->where('jamaah', 'ya')->count();
    $totalNgaji = $ngajiKegiatan->count();
    $totalCatatan = $sholat->count() + $totalNgaji;
@endphp

<style>
    .report-wrap { margin-top: 58px; padding: 16px; padding-bottom: 96px; }
    .report-hero {
        border-radius: 16px;
        padding: 18px;
        color: #fff;
        background: linear-gradient(135deg, #078f8a 0%, #0f766e 56%, #115e59 100%);
        box-shadow: 0 18px 40px rgba(15, 118, 110, 0.24);
    }
    .hero-label { font-size: 0.78rem; opacity: 0.86; font-weight: 800; text-transform: uppercase; }
    .hero-date { font-size: 1.18rem; font-weight: 900; margin-top: 4px; }
    .hero-note { font-size: 0.88rem; opacity: 0.9; margin-top: 8px; line-height: 1.45; }
    .filter-card {
        margin-top: 14px;
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .filter-form { display: grid; grid-template-columns: 1fr auto; gap: 8px; }
    .filter-form input {
        min-height: 44px;
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        padding: 8px 10px;
        background: #f8fafc;
        font-weight: 700;
    }
    .filter-form button {
        min-height: 44px;
        border: 0;
        border-radius: 10px;
        padding: 8px 14px;
        background: #078f8a;
        color: #fff;
        font-weight: 800;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }
    .summary-card {
        border-radius: 14px;
        background: #fff;
        padding: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
    }
    .summary-value { font-size: 1.45rem; font-weight: 900; color: #0f172a; }
    .summary-label { color: #64748b; font-size: 0.82rem; font-weight: 700; margin-top: 2px; }
    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 18px 2px 10px;
        color: #0f172a;
        font-weight: 900;
    }
    .section-title ion-icon { color: #078f8a; font-size: 20px; }
    .timeline { display: grid; gap: 10px; }
    .taqwa-item {
        display: grid;
        grid-template-columns: 42px 1fr auto;
        gap: 12px;
        align-items: center;
        border-radius: 14px;
        background: #fff;
        padding: 12px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
    }
    .item-icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #e9f2ff;
        color: #1769e0;
    }
    .item-icon.sunnah { background: #fff7e6; color: #b77904; }
    .item-icon.ngaji { background: #e7f7f6; color: #078f8a; }
    .item-icon.ngaji-rutin { background: #ecfdf5; color: #15803d; }
    .item-icon.ngaji-senin { background: #fff7ed; color: #ea580c; }
    .item-title { font-weight: 900; color: #1f2937; }
    .item-meta { color: #64748b; font-size: 0.82rem; margin-top: 3px; line-height: 1.35; }
    .item-time {
        color: #0f766e;
        background: #ecfdf5;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 0.76rem;
        font-weight: 900;
        align-self: start;
    }
    .empty-state {
        border-radius: 16px;
        background: #fff;
        padding: 24px 18px;
        text-align: center;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
    }
    .empty-icon {
        width: 56px;
        height: 56px;
        display: grid;
        place-items: center;
        margin: 0 auto 10px;
        border-radius: 16px;
        background: #f1f5f9;
        color: #64748b;
    }
    .empty-title { font-weight: 900; color: #334155; }
    .empty-text { color: #64748b; margin-top: 4px; font-size: 0.88rem; line-height: 1.45; }
</style>

<div class="report-wrap">
    <div class="report-hero">
        <div class="hero-label">Catatan Target Taqwa</div>
        <div class="hero-date">{{ $tanggalCarbon->translatedFormat('l, d F Y') }}</div>
        <div class="hero-note">Semoga catatan kecil hari ini menjadi pengingat lembut untuk terus mendekat kepada Allah.</div>
    </div>

    <div class="filter-card">
        <form method="GET" action="{{ route('operan.taqwa.report') }}" class="filter-form">
            <input type="date" name="tanggal" value="{{ $tanggal }}" required>
            <button type="submit">Tampil</button>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-value">{{ $totalCatatan }}</div>
            <div class="summary-label">Total Catatan</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $totalFardhu }}</div>
            <div class="summary-label">Sholat Fardhu</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $totalSunnah }}</div>
            <div class="summary-label">Sunnah & Tahajud</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $totalJamaah }}</div>
            <div class="summary-label">Berjamaah</div>
        </div>
        <div class="summary-card">
            <div class="summary-value">{{ $totalNgaji }}</div>
            <div class="summary-label">Riwayat Tadarus</div>
        </div>
    </div>

    <div class="section-title">
        <ion-icon name="list-outline"></ion-icon>
        <span>Rincian Hari Ini</span>
    </div>

    @if($totalCatatan > 0)
        <div class="timeline">
            @foreach($sholat as $item)
                <div class="taqwa-item">
                    <div class="item-icon {{ $item->jenis === 'sunnah' ? 'sunnah' : '' }}">
                        <ion-icon name="{{ $item->jenis === 'sunnah' ? 'sparkles-outline' : 'moon-outline' }}"></ion-icon>
                    </div>
                    <div>
                        <div class="item-title">{{ $item->nama_sholat }}</div>
                        <div class="item-meta">
                            Sholat {{ ucfirst($item->jenis) }} - {{ $item->jamaah === 'ya' ? 'Jamaah' : 'Sendiri' }}
                        </div>
                    </div>
                    <div class="item-time">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}</div>
                </div>
            @endforeach

            @foreach($ngajiKegiatan as $item)
                @php
                    $ngajiType = $item->type ?? 'operan';
                    $iconClass = $ngajiType === 'rutin' ? 'ngaji-rutin' : ($ngajiType === 'senin' ? 'ngaji-senin' : 'ngaji');
                @endphp
                <div class="taqwa-item">
                    <div class="item-icon {{ $iconClass }}">
                        <ion-icon name="book-outline"></ion-icon>
                    </div>
                    <div>
                        <div class="item-title">{{ $ngajiLabels[$ngajiType] ?? 'Ngaji' }}</div>
                        <div class="item-meta">
                            {{ $ngajiMeta[$ngajiType] ?? 'Riwayat kegiatan ngaji' }} - Sampai {{ $item->surat }} ayat {{ $item->ayat }}
                        </div>
                    </div>
                    <div class="item-time">{{ \Carbon\Carbon::parse($item->created_at)->format('H:i') }}</div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">
                <ion-icon name="leaf-outline" style="font-size: 28px"></ion-icon>
            </div>
            <div class="empty-title">Belum ada catatan</div>
            <div class="empty-text">Pilih tanggal lain atau mulai isi Target Taqwa hari ini.</div>
        </div>
    @endif
</div>
@endsection
