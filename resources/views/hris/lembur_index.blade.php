@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Jadwal Lembur</div>
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

    <!-- Filter Tanggal -->
    <form method="GET" action="{{ route('hris.lembur_index') }}" class="mb-3">
        <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
    </form>

    <!-- Tombol Tambah -->
    <div class="d-grid mb-3">
        <a href="{{ route('hris.lembur_create') }}" class="btn btn-primary w-100">
            <ion-icon name="add-circle-outline" style="font-size:1.2rem"></ion-icon>
            Tambah Jadwal Lembur
        </a>
    </div>

    <h5 class="mb-2">Daftar Jadwal Lembur - {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h5>

    @if($lembur->isEmpty())
        <div class="alert alert-info text-center">Belum ada jadwal lembur hari ini.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Nama Pegawai</th>
                        <th>Alasan</th>
                        <th style="width: 120px;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lembur as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->pegawai_nama }}</td>
                        <td>{{ $item->alasan }}</td>
                        <td class="text-center">

                        @if($item->verified_at)
                            <span class="badge bg-success px-3">Verified</span>
                        @else
                            <div class="d-flex justify-content-center gap-1">

                                <form action="{{ route('hris.lembur_verify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Verifikasi lembur ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <ion-icon name="checkmark-circle-outline" style="font-size:1.2rem"></ion-icon>
                                    </button>
                                </form>

                                <form action="{{ route('hris.lembur_cancel', $item->idlembur) }}" method="POST" onsubmit="return confirm('Yakin batal lembur?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <ion-icon name="close-circle-outline" style="font-size:1.2rem"></ion-icon>
                                    </button>
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
