@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Operan Shift</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<style>
    .operan-wrap { margin-top: 58px; padding: 16px; }
    .menu-card {
        display: block;
        border: 0;
        border-radius: 14px;
        background: #fff;
        color: inherit;
        text-decoration: none;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .menu-card .card-body { display: flex; gap: 14px; align-items: center; padding: 18px; }
    .menu-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #e7f7f6;
        color: #078f8a;
        flex: 0 0 auto;
    }
    .menu-icon ion-icon { font-size: 26px; }
    .menu-title { font-weight: 800; margin-bottom: 4px; }
    .menu-subtitle { color: #6c757d; font-size: 0.86rem; line-height: 1.35; }
</style>

<div class="operan-wrap">
    <a href="{{ route('operan.ngaji') }}" class="menu-card">
        <div class="card-body">
            <div class="menu-icon">
                <ion-icon name="book-outline"></ion-icon>
            </div>
            <div>
                <div class="menu-title">Ngaji Shift</div>
                <div class="menu-subtitle">Lanjutkan bacaan Quran bersama dalam satu kelompok kerja.</div>
            </div>
        </div>
    </a>
</div>
@endsection
