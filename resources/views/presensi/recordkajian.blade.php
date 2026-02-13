@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Kehadiran Kajian</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 50px">
    <form method="GET" class="mb-3">
        <input type="month" id="bulan" name="bulan" class="form-control" value="{{ $bulan }}">
        <button class="btn btn-primary w-100 mt-2"><ion-icon name="school-outline" style="font-size: 1.2rem"></ion-icon>Tampilkan</button>
    </form>

    <h5 class="mb-3">
        Data Kehadiran Bulan {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
    </h5>

    @if($record->count())
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 10%">#</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Nama Kajian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($record as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->waktu_scan)->translatedFormat('d F Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->waktu_scan)->format('H:i') }}</td>
                        <td>{{ $item->namakajian }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-center mt-4">Tidak ada data kehadiran pada bulan ini.</p>
    @endif
</div>
@endsection
