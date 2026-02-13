@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Inventaris Security</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:50px">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('inventaris.update') }}" method="POST">
        @csrf
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Baik</th>
                    <th>Rusak</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>
                        {{ $item->nama }}
                        <input type="hidden" name="id[]" value="{{ $item->id }}">
                    </td>
                    <td><input type="number" name="baik[]" class="form-control" value="{{ $item->baik }}" min="0"></td>
                    <td><input type="number" name="rusak[]" class="form-control" value="{{ $item->rusak }}" min="0"></td>
                    <td><input type="text" name="keterangan[]" class="form-control" value="{{ $item->keterangan }}"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <button class="btn btn-primary w-100" type="submit"><ion-icon name="save-outline" style="font-size: 1.2rem"></ion-icon> Simpan Inventaris</button>
    </form>
</div>
@endsection
