@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-warning text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Payroll</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-4 d-flex flex-column align-items-center justify-content-center text-center" style="margin-top: 70px;">
    <ion-icon name="construct-outline" style="font-size: 4rem; color: #ffc107;"></ion-icon>
    <h4 class="mt-3">Fitur Sedang Dalam Pengerjaan</h4>
    <p class="text-muted">Kami sedang menyiapkan halaman payroll untuk Anda. Silakan kembali lagi nanti.</p>
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-primary mt-3">
        <ion-icon name="arrow-back-outline"></ion-icon> Kembali
    </a>
</div>
@endsection
