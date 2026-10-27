@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Daftar Lembur</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filter Periode -->
    <form method="GET" action="{{ route('hris.lembur_index') }}" class="mb-3">
        <label for="periode" class="form-label">Periode Bulan</label>
        <input type="month" name="periode" id="periode" value="{{ $periode }}" class="form-control" onchange="this.form.submit()">
    </form>

    <!-- Tombol Tambah -->
    <div class="d-grid mb-3">
        <a href="{{ route('hris.lembur_create') }}" class="btn btn-primary w-100">
            <ion-icon name="add-circle-outline" style="font-size:1.2rem"></ion-icon>
            Tambah Jadwal Lembur
        </a>
    </div>

    <h5 class="mb-2">Daftar Lembur - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</h5>

    @if($lembur->isEmpty())
        <div class="alert alert-info text-center">Belum ada data lembur pada periode ini.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Tanggal</th>
                        <th>Nama Pegawai</th>
                        <th>Bagian / Jabatan</th>
                        <th>Alasan</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Durasi Lembur</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lembur as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tgllembur)->translatedFormat('d M Y') }}</td>
                            <td>{{ $item->pegawai_nama }}</td>
                            <td>{{ $item->bagian ?: '-' }} / {{ $item->jabatan ?: '-' }}</td>
                            <td>{{ $item->alasan }}</td>
                            <td>{{ $item->jam_lembur_masuk?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $item->jam_lembur_pulang?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $item->durasi_lembur ?? '-' }}</td>
                            <td class="text-center">
                                @if($item->verified_at)
                                    <span class="badge bg-success px-3">Terverifikasi</span>
                                @else
                                    <span class="badge bg-warning text-dark px-3">Belum diverifikasi</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item->verified_at)
                                    <form action="{{ route('hris.lembur_unverify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Batalkan verifikasi lembur ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Batal Verif</button>
                                    </form>
                                @else
                                    <div class="d-flex justify-content-center gap-1">
                                        <form action="{{ route('hris.lembur_verify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Verifikasi lembur ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Verifikasi</button>
                                        </form>
                                        <form action="{{ route('hris.lembur_cancel', $item->idlembur) }}" method="POST" onsubmit="return confirm('Yakin batal lembur?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
