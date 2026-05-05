@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Transaksi Koperasi</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">
    <form method="GET" class="mb-3">
        <div class="form-group mb-2">
            <input type="month" id="bulan" name="bulan" class="form-control" value="{{ $bulan }}">
        </div>
        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
            <ion-icon name="wallet-outline" class="me-2" style="font-size: 1.3rem;"></ion-icon>
            <span class="fw-semibold">Tampilkan</span>
        </button>
    </form>

    <h5 class="mb-3 d-flex align-items-center">
        <ion-icon name="calendar-outline" class="me-2 text-primary" style="font-size: 1.2rem;"></ion-icon>
        &nbsp;Transaksi Bulan {{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}
    </h5>

    @php $totalKeseluruhan = 0; @endphp

    @if($items->count())
        @foreach($items as $saleId => $group)
            @php
                $subtotal = $group->sum('total');
                $tanggalTransaksi = \Carbon\Carbon::parse($group->first()->tgl_transaksi)->translatedFormat('d M Y');
                $totalKeseluruhan += $subtotal;
            @endphp
            <div class="card mb-3">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <ion-icon name="receipt-outline" class="me-2"></ion-icon>
                        <strong>&nbsp;Nota #{{ $saleId }}</strong>
                    </div>
                    <small class="d-flex align-items-center">
                        <ion-icon name="calendar-number-outline" class="me-1"></ion-icon>
                        &nbsp;{{ $tanggalTransaksi }}
                    </small>
                </div>
                <div class="card-body p-2">
                    @foreach($group as $item)
                        <div class="d-flex justify-content-between border-bottom py-1">
                            <div>
                                <div><strong>{{ $item->name }}</strong></div>
                                <small class="text-muted">
                                    <ion-icon name="pricetag-outline" class="me-1" style="vertical-align: middle;"></ion-icon>
                                    &nbsp;Rp {{ number_format($item->harga, 0, ',', '.') }} x {{ $item->qty }} {{ $item->satuan }}
                                </small>
                            </div>
                            <div class="text-end d-flex align-items-center">
                                <span class="text-success">Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top align-items-center">
                        <div class="d-flex align-items-center">
                            <ion-icon name="receipt-outline" class="me-2 text-dark"></ion-icon>
                            <strong>&nbsp;Total Nota</strong>
                        </div>
                        <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Total keseluruhan --}}
        <div class="alert alert-primary d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex align-items-center">
                <ion-icon name="wallet-outline" class="me-2" style="font-size: 1.5rem;"></ion-icon>
                <strong>&nbsp;Total Belanja Bulan Ini:</strong>
            </div>
            <span class="fs-5 fw-bold">Rp {{ number_format($totalKeseluruhan, 0, ',', '.') }}</span>
        </div>
    @else
        <div class="alert alert-warning text-center mt-3">
            Tidak ada transaksi pada bulan ini.
        </div>
    @endif
</div>
@endsection
