<form action="/karyawan/{{ $pegawai->nik }}/update" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label>NIK</label>
        <input type="text" class="form-control" value="{{ $pegawai->nik }}" readonly>
    </div>

    <div class="mb-3">
        <label>Nama Lengkap</label>
        <input type="text" class="form-control" value="{{ $pegawai->pegawai_nama }}" readonly>
    </div>

    <div class="mb-3">
        <label>Jabatan</label>
        <input type="text" class="form-control" value="{{ $pegawai->jabatan }}" readonly>
    </div>

    <div class="mb-3">
        <label>No HP</label>
        <input type="text" name="nohp" class="form-control" value="{{ $pegawai->nohp }}">
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ $pegawai->email }}"  autocomplete="off">
    </div>

    <div class="mb-3">
        <label>Alamat</label>
        <textarea name="alamat" class="form-control">{{ $pegawai->alamat }}</textarea>
    </div>

    <div class="mb-3">
        <label>Password (biarkan kosong jika tidak ingin ganti)</label>
        <input type="password" name="password" class="form-control"  autocomplete="off">
    </div>

    <div class="mb-3">
        <label>NBM</label>
        <input type="text" name="nbm" class="form-control" value="{{ $pegawai->nbm }}">
    </div>

    <div class="mb-3">
        <label>Foto Mobile</label>
        <input type="file" name="fotomobile" class="form-control">
        @if ($pegawai->fotomobile)
            <small>Foto lama: {{ $pegawai->fotomobile }}</small>
        @endif
    </div>

    <button class="btn btn-primary" type="submit">Update</button>
</form>
