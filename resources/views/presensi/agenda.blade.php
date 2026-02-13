@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Input Agenda</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 70px">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('presensi.agenda.store') }}">
        @csrf
        <div class="form-group">
            <label>Nama Agenda</label>
            <input type="text" class="form-control" name="namaagenda" required>
        </div>

        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" class="form-control" name="tgl" required>
        </div>

        <div class="form-group">
            <label>Waktu</label>
            <input type="time" class="form-control" name="waktu" required>
        </div>

        <div class="form-group">
            <label>Jenis Kegiatan</label>
            <input type="text" class="form-control" name="jenis" placeholder="contoh: meeting, zoom, seminar, kunjungan dll" required>
        </div>

        <div class="form-group">
            <label>Lokasi</label>
            <input type="text" class="form-control" name="lokasi" required>
        </div>

        <div class="form-group">
            <label>Peserta</label>
            <input type="text" class="form-control" name="peserta" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-3">Simpan</button>
    </form>
</div>
@endsection
