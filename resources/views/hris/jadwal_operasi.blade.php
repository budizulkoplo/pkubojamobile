@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Jadwal Operasi Saya</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter tanggal --}}
    <form method="GET" action="{{ route('hris.jadwal_operasi') }}" class="mb-3">
        <div class="form-group">
            <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
    </form>

    <h5 class="mb-3">Jadwal Operasi - {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h5>

    @if($operasi->isEmpty())
        <div class="alert alert-info text-center">
            Tidak ada jadwal operasi pada tanggal ini.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No RM</th>  
                        <th>Pasien</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operasi as $item)
                        @php
                            $mode = $presensi[$item->id] ?? null;
                            $isToday = ($tanggal == $hariIni);
                        @endphp
                        <tr>
                            <td>{{ $item->no_rm }}</td>
                            <td>{{ $item->nama_pasien }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_rencana)->format('d-m-Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_rencana)->format('H:i') }}</td>
                            <td class="text-center">
                                @if(!$mode)
                                    {{-- Belum absen in --}}
                                    @if($isToday)
                                        <a href="{{ route('hris.operasi_absen_form', [$item->id, 'in']) }}" 
                                        class="btn btn-success btn-sm">
                                            <ion-icon name="log-in-outline"></ion-icon> Operasi In
                                        </a>
                                    @else
                                        <span class="badge bg-secondary">Hanya untuk hari ini</span>
                                    @endif

                                @elseif($mode == 3)
                                    {{-- Sudah absen in → tombol out SELALU muncul walau bukan hari ini --}}
                                    <a href="{{ route('hris.operasi_absen_form', [$item->id, 'out']) }}" 
                                    class="btn btn-danger btn-sm">
                                        <ion-icon name="log-out-outline"></ion-icon> Operasi Out
                                    </a>

                                @else
                                    {{-- Sudah selesai --}}
                                    <span class="badge bg-success px-3">Selesai</span>
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
