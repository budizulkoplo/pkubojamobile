@extends('layouts.presensi')

@section('header')
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:history.back()" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Scan Kehadiran Kajian</div>
    <div class="right"></div>
</div>
<!-- * App Header -->
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    <form method="POST" action="{{ route('form.scan.submit') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="idkajian" class="form-label">ID Kajian</label>
            <input type="text" class="form-control" id="idkajian" name="idkajian" placeholder="Masukkan ID Kajian" value="{{ old('idkajian') }}" required>
            @error('idkajian')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="barcode" class="form-label">Kode Barcode / QR</label>
            <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Scan atau masukkan barcode" value="{{ old('barcode') }}" required autofocus>
            @error('barcode')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group mb-2">
            <button type="submit" class="btn btn-success w-100">
                <ion-icon name="qr-code-outline"></ion-icon> Submit Kehadiran
            </button>
        </div>
    </form>
</div>
@endsection
