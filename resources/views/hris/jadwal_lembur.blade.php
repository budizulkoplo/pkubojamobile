@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Jadwal Lembur Saya</div>
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
    <form method="GET" action="{{ route('hris.jadwal_lembur') }}" class="mb-3">
        <div class="form-group">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
    </form>

    <h5 class="mb-3">Jadwal Lembur - {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h5>

    <div class="mb-3 text-end">
        <a href="{{ route('hris.ajukan_lembur') }}" class="btn btn-primary w-100">
            <ion-icon name="add-circle-outline"></ion-icon> Ajukan Lembur
        </a>
    </div>

    @if($lembur->isEmpty())
        <div class="alert alert-info text-center">
            Belum ada data lembur anda hari ini.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th style="width:60%">Lembur</th>
                        <th style="width:40%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lembur as $item)
                        @php
                            $mode = $presensi[$item->idlembur] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $item->alasan }}</td>
                            <td class="text-center">
                                @if(!$mode)
                                    <a href="{{ route('hris.form_lembur_absen', ['idlembur' => $item->idlembur, 'mode' => 'in']) }}" 
                                       class="btn btn-success btn-sm">
                                        <ion-icon name="log-in-outline"></ion-icon> Lembur In
                                    </a>
                                @elseif($mode == 5)
                                    <a href="{{ route('hris.form_lembur_absen', ['idlembur' => $item->idlembur, 'mode' => 'out']) }}" 
                                       class="btn btn-danger btn-sm">
                                        <ion-icon name="log-out-outline"></ion-icon> Lembur Out
                                    </a>
                                @else
                                    <span class="badge bg-secondary px-3">Lembur Selesai</span>
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
