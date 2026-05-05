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

    @if(!empty($error))
        <div class="alert alert-danger mb-3">
            {{ $error }}
        </div>
    @endif

    <div class="alert alert-info text-center mb-3">
        <strong>Total Pasien: {{ is_countable($dataPasien) ? count($dataPasien) : 0 }}</strong>
    </div>

    <div class="card mb-3 shadow-sm">
        <div class="card-body p-3">
            <h6 class="text-primary mb-2">📊 Rekap Per Instalasi:</h6>

            @if(!empty($rekapInstalasi) && count($rekapInstalasi) > 0)
                @foreach($rekapInstalasi as $instalasi => $jumlah)
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>{{ $instalasi }}</span>
                        <strong>{{ $jumlah }}</strong>
                    </div>
                @endforeach
            @else
                <p class="mb-0 text-muted">Tidak ada rekap instalasi.</p>
            @endif
        </div>
    </div>

    <div class="card mb-3 shadow-sm">
        <div class="card-body p-3">
            <h6 class="text-primary mb-2">🏥 Rekap Per Kelas:</h6>

            @if(!empty($rekapKelas) && count($rekapKelas) > 0)
                @foreach($rekapKelas as $kelas => $jumlah)
                    <div class="d-flex justify-content-between border-bottom py-1">
                        <span>{{ $kelas }}</span>
                        <strong>{{ $jumlah }}</strong>
                    </div>
                @endforeach
            @else
                <p class="mb-0 text-muted">Tidak ada rekap kelas.</p>
            @endif
        </div>
    </div>

    @if(!empty($dataPasien) && is_countable($dataPasien) && count($dataPasien) > 0)
        @foreach($dataPasien as $pasien)
            @php
                $nama      = $pasien->nama ?? '-';
                $norm      = $pasien->pasien_id ?? '-';
                $noregis   = $pasien->no_registrasi ?? '-';
                $tglraw    = $pasien->tanggal_registrasi ?? null;
                $tglmasuk  = $tglraw ? $tglraw : '-';

                $ruangan   = $pasien->mutasi_kamar_terakhir?->ruangan?->nama;
                $kamar     = $pasien->mutasi_kamar_terakhir?->kamar?->nama;
                $instalasi = $pasien->mutasi_kamar_terakhir?->ruangan?->sub_pelayanan?->nama_instalasi
                            ?? $pasien->label_instalasi
                            ?? '-';

                $kelas     = $pasien->mutasi_kamar_terakhir?->ruangan?->nama
                            ?? $pasien->poliklinik
                            ?? '-';

                $punyaKamar = !empty($ruangan) || !empty($kamar);
            @endphp

            <div class="card mb-2 shadow-sm">
                <div class="card-body p-3">
                    <h6 class="mb-1 text-primary">{{ $nama }}</h6>
                    <small class="text-muted">
                        🆔 No. RM: <strong>{{ $norm }}</strong><br>
                        🔖 No. Regis: {{ $noregis }}<br>
                        🕘 Tanggal Registrasi: {{ $tglmasuk }}<br>
                        🧭 Instalasi: {{ $instalasi }}<br>
                        🏥 Kelas / Poli: {{ $kelas }}<br>

                        @if($punyaKamar)
                            @if($ruangan)
                                🏨 Ruangan: {{ $ruangan }}<br>
                            @endif

                            @if($kamar)
                                🛏️ Kamar: {{ $kamar }}<br>
                            @endif
                        @endif
                    </small>
                </div>
            </div>
        @endforeach
    @else
        <p class="text-center mt-4">Tidak ada data pasien.</p>
    @endif

</div>
@endsection
