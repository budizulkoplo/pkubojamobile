@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left"><a href="{{ route('aset.index') }}" class="headerButton"><ion-icon name="chevron-back-outline"></ion-icon></a></div>
    <div class="pageTitle">Detail Aset</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    <div class="card p-3 mb-3">
        <h4>{{ $row->namaaset }}</h4>
        <div class="text-muted mb-3">{{ $row->kodeaset }}</div>
        <dl class="row mb-0">
            @foreach(['barcode' => 'Barcode', 'namabarang' => 'Barang', 'namajenis' => 'Jenis', 'tipe_aset' => 'Tipe', 'tahunpengadaan' => 'Tahun Pengadaan', 'merk' => 'Merk', 'type' => 'Type', 'spesifikasi' => 'Spesifikasi', 'kondisi' => 'Kondisi', 'nilaiaset' => 'Nilai Aset', 'tanggalperolehan' => 'Tanggal Perolehan', 'serial_no' => 'Serial Number', 'ipaddress' => 'IP Address', 'namalokasi' => 'Lokasi', 'pic_name' => 'PIC', 'status_verifikasi' => 'Status Verifikasi', 'verifikasi_at' => 'Waktu Verifikasi', 'verifikasi_note' => 'Catatan Verifikasi'] as $field => $label)
                <dt class="col-5">{{ $label }}</dt><dd class="col-7">{{ $row->{$field} ?? '-' }}</dd>
            @endforeach
        </dl>
    </div>

    <div class="d-grid gap-2 mb-3">
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#verify-form"><ion-icon name="checkmark-circle-outline"></ion-icon> Verifikasi</button>
        <button class="btn btn-warning" data-bs-toggle="collapse" data-bs-target="#mutation-form"><ion-icon name="swap-horizontal-outline"></ion-icon> Mutasi</button>
        <button class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#maintenance-form"><ion-icon name="build-outline"></ion-icon> Maintenance</button>
    </div>

    <form id="verify-form" class="collapse card p-3 mb-3" method="POST" action="{{ route('aset.verify', $row->idaset) }}">
        @csrf
        <h5>Verifikasi aset</h5>
        <select name="status_verifikasi" class="form-control mb-2" required><option value="baik">Baik</option><option value="diperbaiki">Diperbaiki</option><option value="rusak">Rusak</option><option value="hilang">Hilang</option><option value="tidak_ditemukan">Tidak ditemukan</option></select>
        <textarea name="verifikasi_note" class="form-control mb-2" placeholder="Catatan (opsional)"></textarea>
        <button class="btn btn-success w-100">Simpan Verifikasi</button>
    </form>
    <form id="mutation-form" class="collapse card p-3 mb-3" method="POST" action="{{ route('aset.mutate', $row->idaset) }}">
        @csrf
        <h5>Mutasi aset</h5>
        <input type="hidden" name="location_id" value="{{ $row->idlokasi }}">
        <select name="to_location_id" class="form-control mb-2" required><option value="">Pilih lokasi tujuan</option>@foreach($locations as $location)<option value="{{ $location->idlokasi }}">{{ $location->namalokasi }}</option>@endforeach</select>
        <textarea name="reason" class="form-control mb-2" placeholder="Alasan mutasi (opsional)"></textarea>
        <button class="btn btn-warning w-100">Simpan Mutasi</button>
    </form>
    <form id="maintenance-form" class="collapse card p-3 mb-3" method="POST" action="{{ route('aset.maintenance', $row->idaset) }}">
        @csrf
        <h5>Maintenance aset</h5>
        <input name="issue" class="form-control mb-2" placeholder="Masalah aset" required>
        <textarea name="description" class="form-control mb-2" placeholder="Detail masalah (opsional)"></textarea>
        <button class="btn btn-danger w-100">Buat Permintaan Maintenance</button>
    </form>
</div>
@endsection
