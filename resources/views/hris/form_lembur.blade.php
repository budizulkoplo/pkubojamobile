@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('hris.lembur_index') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Input Jadwal Lembur</div>
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

    <form method="POST" action="{{ route('hris.lembur_store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="pegawai_pin">Pilih Anggota</label>
            <select name="pegawai_pin" id="pegawai_pin" class="form-control" required>
                <option value="">-- Pilih Pegawai --</option>
                @foreach($anggota as $a)
                    <option value="{{ $a->pegawai_pin }}">
                        {{ $a->pegawai_nama }} - {{ $a->bagian }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="tgllembur">Tanggal Lembur</label>
            <input type="date" name="tgllembur" id="tgllembur" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="alasan">Alasan</label>
            <input type="text" name="alasan" id="alasan" class="form-control" placeholder="Contoh: pelayanan poli" required>
        </div>

        <button type="submit" class="btn btn-success w-100">
            <ion-icon name="save-outline" style="font-size:1.2rem"></ion-icon> Simpan Jadwal Lembur
        </button>
    </form>

</div>
@endsection
