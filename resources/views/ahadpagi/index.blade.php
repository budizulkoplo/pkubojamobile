@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Ahad Pagi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">
    <form method="GET" class="mb-3">
        <div class="form-group mb-2">
            <input type="month" id="bulan" name="bulan" class="form-control" value="{{ $bulan }}">
        </div>
        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
            <ion-icon name="calendar-outline" style="font-size: 1.2rem"></ion-icon>
            <span>Tampilkan</span>
        </button>
    </form>

    <h5 class="mb-3">
        Kehadiran Ahad Pagi - {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
    </h5>

    @if($record->count())
        <div class="listview">
            @foreach($record as $i => $item)
                @php
                    $lokasi = $item['lokasi'] ?? null;
                    $tgl = \Carbon\Carbon::parse($item['tgl_presensi'])->translatedFormat('d F Y');
                    $jam = $item['jam_in'] ?? '-';
                @endphp

                <div class="card mb-2" style="background-color: #e3fcec;">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="mb-1 text-success">🕌 Ahad Pagi #{{ $i + 1 }}</h5>
                                <small class="text-muted d-block">
                                    📅 {{ $tgl }}<br>
                                    🕘 {{ $jam }}
                                </small>
                            </div>
                            <div class="text-end">
                                @if($lokasi)
                                    <small class="text-muted">📍 <strong>{{ $lokasi }}</strong></small>
                                @else
                                    <small class="text-danger">Lokasi tidak tersedia</small>
                                @endif
                            </div>
                        </div>

                        @if($lokasi)
                        <div class="mt-2">
                            <iframe
                                width="100%"
                                height="150"
                                frameborder="0"
                                style="border:0;"
                                src="https://maps.google.com/maps?q={{ urlencode($lokasi) }}&t=&z=15&ie=UTF8&iwloc=&output=embed"
                                allowfullscreen>
                            </iframe>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning text-center mt-3">
            Tidak ada data kehadiran Ahad Pagi bulan ini.
        </div>
    @endif
</div>
@endsection
