@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Update Pasien</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 50px">

    <div class="alert alert-info text-center mb-3">
    <strong>Total Pasien: {{ count($dataPasien) }}</strong>
</div>

<div class="card mb-3 shadow-sm">
    <div class="card-body p-3">
        <h6 class="text-primary mb-2">📊 Rekap Per Instalasi:</h6>
        @foreach($rekapInstalasi as $instalasi => $jumlah)
            <div class="d-flex justify-content-between border-bottom py-1">
                <span>{{ $instalasi }}</span>
                <strong>{{ $jumlah }}</strong>
            </div>
        @endforeach
    </div>
</div>

<div class="card mb-3 shadow-sm">
    <div class="card-body p-3">
        <h6 class="text-primary mb-2">🏥 Rekap Per Kelas:</h6>
        @foreach($rekapKelas as $kelas => $jumlah)
            <div class="d-flex justify-content-between border-bottom py-1">
                <span>{{ $kelas }}</span>
                <strong>{{ $jumlah }}</strong>
            </div>
        @endforeach
    </div>
</div>


    @if(count($dataPasien))
        @foreach($dataPasien as $pasien)
            @php
                $nama      = $pasien->nama ?? '-';
                $norm      = $pasien->pasien_id ?? '-';
                $noregis   = $pasien->no_registrasi ?? '-';
                $tglmasuk  = $pasien->tanggal_registrasi ? \Carbon\Carbon::parse($pasien->tanggal_registrasi)->translatedFormat('d F Y H:i') : '-';

                $ruangan   = $pasien->mutasi_kamar_terakhir->ruangan->nama ?? null;
                $kamar     = $pasien->mutasi_kamar_terakhir->kamar->nama ?? null;
                $instalasi = $pasien->mutasi_kamar_terakhir->ruangan->sub_pelayanan->nama_instalasi
                            ?? ($pasien->label_instalasi ?? '-');

                $punyaKamar = $ruangan && $kamar;
            @endphp

            <div class="card mb-2 shadow-sm">
                <div class="card-body p-3">
                    <h6 class="mb-1 text-primary">{{ $nama }}</h6>
                    <small class="text-muted">
                        🆔 No. RM: <strong>{{ $norm }}</strong><br>
                        🔖 No. Regis: {{ $noregis }}<br>
                        🕘 Masuk: {{ $tglmasuk }}<br>

                        @if($punyaKamar)
                            🏥 Kelas: {{ $ruangan }}<br>
                            🛏️ Kamar: {{ $kamar }}<br>
                        @endif

                        🧭 Instalasi: {{ $instalasi }}
                    </small>
                </div>
            </div>
        @endforeach
    @else
        <p class="text-center mt-4">Tidak ada data pasien.</p>
    @endif

</div>
@endsection
