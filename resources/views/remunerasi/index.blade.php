@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-success text-light">
    <div class="left"><a href="javascript:;" class="headerButton goBack"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Remunerasi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3 remuneration-mobile-page" style="margin-top: 40px">
    <form method="GET" class="mb-3">
        <label for="remuneration-year">Pilih Tahun</label>
        <select name="tahun" id="remuneration-year" class="form-control" onchange="this.form.submit()">
            @foreach ($tahunList as $item)
                <option value="{{ $item }}" @selected((int) $item === (int) $tahun)>Tahun {{ $item }}</option>
            @endforeach
        </select>
    </form>

    <h5 class="mb-3"><ion-icon name="cash-outline" class="text-success"></ion-icon> Slip Remunerasi Tahun {{ $tahun }}</h5>

    @forelse ($slips as $slip)
        @php $bulanText = \Carbon\Carbon::parse($slip->periode.'-01')->locale('id')->isoFormat('MMMM'); @endphp
        <a href="{{ route('remuneration.detail', ['tahun' => substr($slip->periode, 0, 4), 'bulan' => substr($slip->periode, 5, 2)]) }}" class="card mb-2 remuneration-card">
            <div class="card-body p-2 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 text-success">Slip Remunerasi - {{ $bulanText }}</h5>
                    <small class="text-muted">Periode {{ $bulanText }} {{ substr($slip->periode, 0, 4) }}</small><br>
                    <small>Skor KPI: <strong>{{ rtrim(rtrim(number_format((float) $slip->score_total, 2, ',', '.'), '0'), ',') }}</strong> · {{ $slip->group_name ?: 'Tanpa kelompok' }}</small>
                </div>
                <div class="text-right">
                    <strong class="text-success">Rp {{ number_format((float) $slip->remuneration_amount, 0, ',', '.') }}</strong><br>
                    <ion-icon name="chevron-forward-outline" class="text-muted"></ion-icon>
                </div>
            </div>
        </a>
    @empty
        <div class="alert alert-warning text-center">Belum ada slip remunerasi yang diterbitkan untuk tahun ini.</div>
    @endforelse
</div>
@endsection

@push('myscript')
<style>
    .remuneration-card { background: #effcf5; border-left: 4px solid #198754; }
    .remuneration-card h5 { font-size: 15px; }
    .remuneration-card small { line-height: 1.6; }
</style>
@endpush
