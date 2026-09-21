@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('aset.index') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Detail Aset</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 70px; padding-bottom: 96px !important; background: #f5f7fa; min-height: calc(100vh - 70px);">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- ===================== CARD DETAIL ===================== --}}
    <div class="asset-card mb-3">
        <div class="asset-card-heading">
            <div>
                <span class="eyebrow"><ion-icon name="cube-outline"></ion-icon> Detail Aset</span>
                <h4>{{ $row->namaaset }}</h4>
                <div class="asset-code">
                    <ion-icon name="barcode-outline"></ion-icon> {{ $row->kodeaset }}
                </div>
            </div>
            <span class="condition-badge condition-{{ \Illuminate\Support\Str::slug($row->kondisi ?: 'tanpa-kondisi') }}">
                {{ $row->kondisi ?: 'Tanpa Kondisi' }}
            </span>
        </div>

        <dl class="row mb-0">
            @php
                $fields = [
                    'barcode'             => 'Barcode',
                    'namabarang'          => 'Barang',
                    'namajenis'           => 'Jenis',
                    'tipe_aset'           => 'Tipe',
                    'tahunpengadaan'      => 'Tahun Pengadaan',
                    'merk'                => 'Merk',
                    'type'                => 'Type',
                    'spesifikasi'         => 'Spesifikasi',
                    'kondisi'             => 'Kondisi',
                    'nilaiaset'           => 'Nilai Aset',
                    'tanggalperolehan'    => 'Tanggal Perolehan',
                    'serial_no'           => 'Serial Number',
                    'ipaddress'           => 'IP Address',
                    'namalokasi'          => 'Lokasi',
                    'pic_name'            => 'PIC',
                    'status_verifikasi'   => 'Status Verifikasi',
                    'verifikasi_at'       => 'Waktu Verifikasi',
                    'verifikasi_note'     => 'Catatan Verifikasi',
                ];
            @endphp

            @foreach($fields as $field => $label)
                @php
                    $value = $row->{$field} ?? null;

                    // Format khusus per field
                    if ($field === 'nilaiaset' && $value !== null && $value !== '') {
                        $value = 'Rp ' . number_format((float) $value, 0, ',', '.');
                    } elseif (in_array($field, ['tanggalperolehan', 'verifikasi_at']) && !empty($value)) {
                        try {
                            $value = \Carbon\Carbon::parse($value)->translatedFormat('d F Y H:i');
                        } catch (\Exception $e) {
                            // biarkan nilai asli jika gagal parse
                        }
                    } elseif ($field === 'status_verifikasi' && !empty($value)) {
                        $value = ucwords(str_replace('_', ' ', $value));
                    } elseif (empty($value)) {
                        $value = '-';
                    }
                @endphp
                <dt class="col-5">{{ $label }}</dt>
                <dd class="col-7">{{ $value }}</dd>
            @endforeach
        </dl>
    </div>

    {{-- ===================== AKSI ===================== --}}
    <div class="action-grid mb-3">
        <button type="button" class="asset-action verify-action"
                data-form-target="verify-form"
                aria-controls="verify-form" aria-expanded="false">
            <span class="action-icon"><ion-icon name="checkmark-circle-outline"></ion-icon></span>
            <span><strong>Verifikasi</strong><small>Status &amp; catatan</small></span>
        </button>
        <button type="button" class="asset-action mutate-action"
                data-form-target="mutation-form"
                aria-controls="mutation-form" aria-expanded="false">
            <span class="action-icon"><ion-icon name="swap-horizontal-outline"></ion-icon></span>
            <span><strong>Mutasi</strong><small>Pindah ruangan</small></span>
        </button>
        <button type="button" class="asset-action maintenance-action"
                data-form-target="maintenance-form"
                aria-controls="maintenance-form" aria-expanded="false">
            <span class="action-icon"><ion-icon name="build-outline"></ion-icon></span>
            <span><strong>Maintenance</strong><small>Buat tiket</small></span>
        </button>
    </div>

    {{-- ===================== FORM VERIFIKASI ===================== --}}
    <form id="verify-form" class="collapse asset-form verify-form mb-3"
          method="POST" action="{{ route('aset.verify', $row->idaset) }}">
        @csrf
        <div class="form-heading">
            <span class="form-icon"><ion-icon name="checkmark-circle-outline"></ion-icon></span>
            <div>
                <h5>Verifikasi Aset</h5>
                <small>Status terakhir dan catatan pemeriksaan</small>
            </div>
        </div>

        <div class="form-group">
            <label>Status Terakhir</label>
            <select name="status_verifikasi" class="form-control" required>
                @foreach(['baik' => 'Baik', 'diperbaiki' => 'Diperbaiki', 'rusak' => 'Rusak', 'terjual' => 'Terjual', 'hilang' => 'Hilang', 'tidak_ditemukan' => 'Tidak Ditemukan'] as $value => $label)
                    <option value="{{ $value }}" @selected(($row->status_verifikasi ?: 'baik') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Catatan Pemeriksaan</label>
            <textarea name="verifikasi_note" class="form-control" rows="3"
                      placeholder="Catatan pemeriksaan (opsional)">{{ $row->verifikasi_note }}</textarea>
        </div>

        <button type="submit" class="btn btn-success btn-block mt-3">
            <ion-icon name="checkmark-outline"></ion-icon> Simpan Verifikasi
        </button>
    </form>

    {{-- ===================== FORM MUTASI ===================== --}}
    <form id="mutation-form" class="collapse asset-form mutate-form mb-3"
          method="POST" action="{{ route('aset.mutate', $row->idaset) }}">
        @csrf
        <div class="form-heading">
            <span class="form-icon"><ion-icon name="swap-horizontal-outline"></ion-icon></span>
            <div>
                <h5>Mutasi Aset</h5>
                <small>Pindahkan aset ke ruangan tujuan</small>
            </div>
        </div>

        <input type="hidden" name="location_id" value="{{ $row->idlokasi }}">

        <div class="location-summary">
            <ion-icon name="location-outline"></ion-icon>
            <span>
                <small>Lokasi saat ini</small>
                <strong>{{ $row->namalokasi ?: 'Belum ada lokasi' }}</strong>
            </span>
        </div>

        <div class="form-group">
            <label>Lokasi Tujuan</label>
            <select name="to_location_id" class="form-control" required>
                <option value="">Pilih ruangan tujuan</option>
                @foreach($locations as $location)
                    <option value="{{ $location->idlokasi }}">
                        {{ $location->namalokasi }}{{ $location->pic_name ? ' - PIC: '.$location->pic_name : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Alasan Mutasi</label>
            <textarea name="reason" class="form-control" rows="3"
                      placeholder="Alasan mutasi (opsional)"></textarea>
        </div>

        <button type="submit" class="btn btn-warning btn-block mt-3">
            <ion-icon name="save-outline"></ion-icon> Simpan Mutasi
        </button>
    </form>

    {{-- ===================== FORM MAINTENANCE ===================== --}}
    <form id="maintenance-form" class="collapse asset-form maintenance-form mb-3"
          method="POST" action="{{ route('aset.maintenance', $row->idaset) }}">
        @csrf
        <div class="form-heading">
            <span class="form-icon"><ion-icon name="build-outline"></ion-icon></span>
            <div>
                <h5>Maintenance Aset</h5>
                <small>Buat tiket maintenance dengan status open</small>
            </div>
        </div>

        <div class="ticket-summary">
            <ion-icon name="document-text-outline"></ion-icon>
            <span>Tiket baru akan diproses oleh tim terkait.</span>
        </div>

        <div class="form-group">
            <label>Masalah Aset</label>
            <input name="issue" class="form-control"
                   placeholder="Contoh: Monitor tidak menyala" required>
        </div>

        <div class="form-group">
            <label>Deskripsi Masalah</label>
            <textarea name="description" class="form-control" rows="3"
                      placeholder="Detail masalah atau kondisi aset (opsional)"></textarea>
        </div>

        <button type="submit" class="btn btn-danger btn-block mt-3">
            <ion-icon name="send-outline"></ion-icon> Buat Tiket Maintenance
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-form-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(button.dataset.formTarget);
            const isOpen = target.classList.contains('show');

            document.querySelectorAll('.asset-form').forEach(function (form) {
                form.classList.remove('show');
            });
            document.querySelectorAll('[data-form-target]').forEach(function (action) {
                action.setAttribute('aria-expanded', 'false');
            });

            if (!isOpen) {
                target.classList.add('show');
                button.setAttribute('aria-expanded', 'true');
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<style>
    .asset-page { margin-top: 70px; padding-bottom: 96px !important; background: #f5f7fa; min-height: calc(100vh - 70px); }
    .asset-card, .asset-form { border: 0; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(15, 23, 42, .08); }
    .asset-form { display: none; }
    .asset-form.show { display: block; }
    .asset-card { padding: 18px; }
    .asset-card-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding-bottom: 15px; border-bottom: 1px solid #edf0f4; }
    .eyebrow { display: flex; align-items: center; gap: 5px; color: #708096; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .asset-card h4 { margin: 6px 0 5px; color: #182536; font-size: 19px; line-height: 1.25; }
    .asset-code { display: flex; align-items: center; gap: 5px; color: #2d6ea6; font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
    .condition-badge { flex: 0 0 auto; padding: 6px 9px; border-radius: 999px; background: #e6f7ee; color: #16804b; font-size: 11px; font-weight: 700; }
    .condition-rusak, .condition-dihapuskan { background: #fff0f0; color: #bd3d3d; }
    .condition-renovasi { background: #fff5df; color: #a66a00; }
    .asset-card dl { margin-top: 16px; }
    .asset-card dt, .asset-card dd { margin-bottom: 10px; font-size: 13px; line-height: 1.45; }
    .asset-card dt { color: #7a8798; font-weight: 600; }
    .asset-card dd { color: #263548; overflow-wrap: anywhere; }
    .action-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .asset-action { display: flex; align-items: center; gap: 9px; min-width: 0; padding: 12px 10px; border: 1px solid transparent; border-radius: 12px; background: #fff; text-align: left; box-shadow: 0 4px 14px rgba(15, 23, 42, .07); }
    .asset-action > span:last-child { min-width: 0; }
    .asset-action strong, .asset-action small { display: block; }
    .asset-action strong { color: #253449; font-size: 12px; }
    .asset-action small { margin-top: 2px; color: #8490a0; font-size: 10px; white-space: nowrap; }
    .action-icon, .form-icon { display: grid; place-items: center; flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; font-size: 19px; }
    .verify-action { border-color: #c9edda; }
    .verify-action .action-icon, .verify-form .form-icon { background: #e4f8ed; color: #15935a; }
    .mutate-action { border-color: #f3dfae; }
    .mutate-action .action-icon, .mutate-form .form-icon { background: #fff4d8; color: #b57900; }
    .maintenance-action { border-color: #f2d0d0; }
    .maintenance-action .action-icon, .maintenance-form .form-icon { background: #fff0f0; color: #c14c4c; }
    .asset-form { padding: 16px; }
    .form-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 17px; }
    .form-heading h5 { margin: 0; color: #243348; font-size: 15px; }
    .form-heading small { display: block; margin-top: 3px; color: #8792a1; font-size: 11px; }
    .asset-form label { display: block; margin-bottom: 6px; color: #566579; font-size: 12px; font-weight: 700; }
    .asset-form .form-control { min-height: 44px; border: 1px solid #dce3eb; border-radius: 9px; box-shadow: none; font-size: 13px; }
    .asset-form textarea.form-control { min-height: 82px; padding-top: 11px; }
    .asset-form .form-control:focus { border-color: #279d9b; box-shadow: 0 0 0 3px rgba(39, 157, 155, .12); }
    .location-summary, .ticket-summary { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding: 11px 12px; border-radius: 9px; background: #f5f8fb; color: #65758a; font-size: 12px; }
    .location-summary > ion-icon, .ticket-summary > ion-icon { color: #3283b5; font-size: 20px; }
    .location-summary small, .location-summary strong { display: block; }
    .location-summary small { color: #8491a1; font-size: 10px; }
    .location-summary strong { margin-top: 2px; color: #34465b; font-size: 12px; }
    .form-submit { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; min-height: 44px; border: 0; border-radius: 9px; color: #fff; font-size: 13px; font-weight: 700; }
    .verify-submit { background: #15935a; }
    .mutate-submit { background: #c58a10; }
    .maintenance-submit { background: #c14c4c; }
    @media (max-width: 430px) {
        .asset-card, .asset-form { border-radius: 12px; }
        .asset-card { padding: 14px; }
        .asset-card-heading { gap: 8px; }
        .asset-card h4 { font-size: 16px; }
        .condition-badge { padding: 5px 7px; font-size: 10px; }
        .asset-card dt, .asset-card dd { font-size: 12px; }
        .action-grid { grid-template-columns: 1fr; gap: 8px; }
        .asset-action { min-height: 56px; padding: 10px 12px; }
        .action-icon { width: 36px; height: 36px; }
        .asset-action small { white-space: normal; }
    }
</style>
@endsection