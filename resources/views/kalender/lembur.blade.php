@extends('layouts.presensi')

@section('header')
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Data Lembur</div>
    <div class="right"></div>
</div>
<!-- * App Header -->
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    <form method="post" action="{{ url()->current() }}">
        @csrf
        <div class="form-group mb-2">
            <input type="month" name="bulan" value="{{ $bulan }}" class="form-control" required>
        </div>
        <div class="form-group mb-2">
            <button type="submit" class="btn btn-primary w-100">
                <ion-icon name="finger-print-outline"></ion-icon> Tampilkan
            </button>
        </div>
    </form>

    @if ($selectedEmployee)
        <h6 class="mt-3">Record Lembur {{ $selectedEmployee->pegawai_nama }} - Bulan {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</h6>

        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Lembur In</th>
                        <th>Lembur Out</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dataKalender as $tgl => $item)
                        @continue(empty($item['lembur_masuk']) || empty($item['lembur_pulang']))
                        <tr>
                            <td>{{ $tgl }}</td>
                            <td>{{ $item['lembur_masuk'] }}</td>
                            <td>{{ $item['lembur_pulang'] }}</td>
                            <td>{{ $item['alasan_lembur'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    @endif
</div>
@endsection
