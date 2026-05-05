@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Kegiatan Security</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Form Pilih Tanggal -->
    <form method="GET" action="{{ route('kegiatan.index') }}" class="mb-3">
        <div class="form-group">
            <input type="date" name="tgl" id="tgl" value="{{ $tanggal }}" class="form-control" onchange="this.form.submit()">
        </div>
    </form>
    @php
        $idUser = Auth::guard('karyawan')->user()->jabatan ?? '';
    @endphp

    @if(in_array($idUser, ['Security', 'IT']))
    <!-- Form Input Kegiatan -->
    <form method="POST" action="{{ route('kegiatan.store') }}">
        @csrf
        <input type="hidden" name="tgl" value="{{ $tanggal }}">
        <div class="form-group mb-2">
            <label for="jam">Jam</label>
            <input type="time" name="jam" class="form-control" required>
        </div>
        <div class="form-group mb-2">
            <label for="kegiatan">Kegiatan</label>
            <textarea name="kegiatan" class="form-control" rows="2" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100"><ion-icon name="save-outline" style="font-size: 1.2rem"></ion-icon>Simpan Kegiatan</button>
    </form>
@endif
    <!-- Tabel Kegiatan Hari Ini -->
    <hr>
    <h4>Kegiatan Security - {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h4>

@foreach($data as $pegawai)
    <div class="card mb-3">
        <div class="card-header">
            <strong>{{ $pegawai['nama_lengkap'] }}</strong>
        </div>
        <div class="card-body p-2">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width: 100px;">Jam</th>
                        <th>Kegiatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pegawai['kegiatan'] as $item)
                        <tr>
                            <td>{{ $item['jam'] }}</td>
                            <td>{{ $item['kegiatan'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

