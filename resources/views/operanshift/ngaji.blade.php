@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Ngaji Shift</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<style>
    .ngaji-wrap { margin-top: 58px; padding: 16px; }
    .info-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .unit-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        background: #e7f7f6;
        color: #078f8a;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .last-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 4px; }
    .muted-small { font-size: 0.86rem; color: #6c757d; }
</style>

<div class="ngaji-wrap">
    @if(! $kelompok)
        <div class="alert alert-warning">
            Data kelompok kerja belum ditemukan untuk akun ini.
        </div>
    @else
        <div class="mb-3">
            <span class="unit-pill">
                <ion-icon name="people-outline"></ion-icon>
                {{ $kelompok->namakelompok }}
            </span>
            <div class="muted-small mt-2">Karu: {{ $kelompok->namakaru }}</div>
        </div>

        <div class="card info-card mb-3">
            <div class="card-body">
                <div class="muted-small mb-1">Bacaan terakhir unit</div>
                @if($latest)
                    <div class="last-title">{{ $latest->surat ?: ($latestSurat['namaLatin'] ?? '-') }}</div>
                    <div>Ayat {{ $latest->ayat }}</div>
                    <div class="muted-small mt-2">
                        Selesai dibaca oleh {{ $latest->pegawai_nama }} pada
                        {{ \Carbon\Carbon::parse($latest->created_at)->translatedFormat('d M Y H:i') }}
                    </div>
                @else
                    <div class="last-title">Belum ada penanda</div>
                    <div class="muted-small">Ngaji Shift akan dimulai dari Al-Fatihah ayat 1.</div>
                @endif
            </div>
        </div>

        <a
            href="{{ route('operan.ngaji.show', ['nomor' => $nextPosition['nomor'], 'ayat' => $nextPosition['ayat']]) }}#ayat-{{ $nextPosition['ayat'] }}"
            class="btn btn-primary w-100"
        >
            <ion-icon name="play-outline"></ion-icon>
            Mulai dari {{ $nextPosition['nomor'] }}:{{ $nextPosition['ayat'] }}
        </a>
    @endif
</div>
@endsection
