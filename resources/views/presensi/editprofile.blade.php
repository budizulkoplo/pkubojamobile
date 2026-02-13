@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Edit Profile</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="row" style="margin-top:4rem">
    <div class="col"></div>
    @php
        $messagesuccess =  Session::get('success');
        $messageerror =  Session::get('error');
    @endphp
    @if($messagesuccess)
    <div class="alert alert-success w-100">{{ $messagesuccess }}</div>
    @endif
    @if($messageerror)
    <div class="alert alert-danger w-100">{{ $messageerror }}</div>
    @endif
</div>

<form action="/presensi/{{ $pegawai->nik }}/updateprofile" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="col">

        <div class="form-group boxed">
            <label>NIK</label>
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{ $pegawai->nik }}" name="nik" readonly>
            </div>
        </div>

        <div class="form-group boxed">
            <label>Nama Lengkap</label>
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{ $pegawai->pegawai_nama }}" readonly>
            </div>
        </div>

        <div class="form-group boxed">
            <label>Jabatan</label>
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{ $pegawai->jabatan }}" readonly>
            </div>
        </div>

        <div class="form-group boxed">
            <label>No HP</label>
            <div class="input-wrapper">
                <input type="text" class="form-control" value="{{ $pegawai->nohp }}" name="no_hp" placeholder="No. HP">
            </div>
        </div>

        <div class="form-group boxed">
            <label>Email</label>
            <div class="input-wrapper">
                <input type="email" class="form-control" value="{{ $pegawai->email }}" name="email" placeholder="Email">
            </div>
        </div>

        <div class="form-group boxed">
            <label>Alamat</label>
            <div class="input-wrapper">
                <textarea class="form-control" name="alamat" rows="2" placeholder="Alamat">{{ $pegawai->alamat }}</textarea>
            </div>
        </div>

        <div class="form-group boxed">
            <label>Password (Biarkan kosong jika tidak diubah)</label>
            <div class="input-wrapper">
                <input type="password" class="form-control" name="password" placeholder="Password">
            </div>
        </div>

        <div class="form-group boxed">
            <label>NBM</label>
            <div class="input-wrapper">
                <input type="text" class="form-control" name="nbm" value="{{ $pegawai->nbm }}" placeholder="NBM">
            </div>
        </div>

        <div class="form-group boxed">
            <label>Upload Foto</label>
            <div class="custom-file-upload" id="fileUpload1">
                <input type="file" name="foto" id="fileuploadInput" accept=".png, .jpg, .jpeg">
                <label for="fileuploadInput">
                    <span>
                        <strong>
                        <ion-icon name="cloud-upload-outline"></ion-icon>
                        <i>Tap to Upload</i>
                        </strong>
                    </span>
                </label>
                <input type="hidden" name="old_foto" value="{{ $pegawai->fotomobile }}">
            </div>
        </div>

        <div class="form-group boxed mt-2">
            <div class="input-wrapper">
                <button type="submit" class="btn btn-primary btn-block">
                    <ion-icon name="refresh-outline"></ion-icon>
                    Update
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
