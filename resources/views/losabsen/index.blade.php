@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Los Absensi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Form Pilih Tanggal -->
    <form method="GET" action="{{ route('losabsen.index') }}" class="mb-3">
        <div class="form-group">
            <input type="date" name="tgl" id="tgl" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
    </form>

    <form method="POST" action="{{ route('losabsen.store') }}">
        @csrf
        <input type="hidden" name="pin" value="{{ $pin }}">
        <div class="form-group mb-2">
            <label for="scan_date">Tanggal & Jam (Scan Date)</label>
            <input type="datetime-local" name="scan_date" class="form-control" required>
        </div>

        <div class="form-group mb-2">
            <label for="inoutmode">In/Out Mode</label>
            <select name="inoutmode" class="form-control" required>
                <option value="0">Masuk</option>
                <option value="1">Pulang</option>
                <option value="5">Lembur Masuk</option>
                <option value="6">Lembur Pulang</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <ion-icon name="save-outline" style="font-size: 1.2rem"></ion-icon> Simpan Los Absensi
        </button>
    </form>

    <!-- Tabel Riwayat Los Absensi -->
    <hr>
    <h4>Riwayat Los Absensi - {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h4>

    <div class="card">
        <div class="card-body p-2">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>PIN</th>
                        <th>Verify Mode</th>
                        <th>In/Out Mode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($item->scan_date)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->pin }}</td>
                        <td>{{ $item->verifymode }}</td>
                        <td>{{ $item->inoutmode }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">Belum ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
