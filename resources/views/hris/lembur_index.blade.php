@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Daftar Lembur</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<style>
    .overtime-page {
        max-width: 1120px;
        margin: 51px auto 0;
        padding: 16px 14px calc(110px + env(safe-area-inset-bottom));
    }

    .overtime-alert {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 5px 16px rgba(27, 48, 73, .06);
    }

    .overtime-toolbar {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        padding: 16px;
        margin-bottom: 22px;
        background: #fff;
        border: 1px solid #edf1f5;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(27, 48, 73, .06);
    }

    .overtime-period {
        flex: 1;
        min-width: 0;
        margin: 0;
    }

    .overtime-period label {
        margin-bottom: 7px;
        color: #64748b;
        font-size: .82rem;
        font-weight: 600;
    }

    .overtime-period input {
        min-height: 46px;
        border-color: #e2e8f0;
        border-radius: 12px;
        color: #1e293b;
        font-weight: 500;
    }

    .overtime-add-button {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 18px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #08b8b2, #079f9a);
        box-shadow: 0 6px 14px rgba(7, 184, 178, .2);
        color: #fff;
        font-weight: 600;
        white-space: nowrap;
    }

    .overtime-add-button:hover,
    .overtime-add-button:focus {
        background: #078f8a;
        color: #fff;
    }

    .overtime-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 0 2px 14px;
    }

    .overtime-heading h2 {
        margin: 0;
        color: #172b4d;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .overtime-heading p {
        margin: 3px 0 0;
        color: #7b8798;
        font-size: .8rem;
    }

    .overtime-count {
        padding: 6px 10px;
        border-radius: 999px;
        background: #e8f8f7;
        color: #078f8a;
        font-size: .75rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .overtime-empty {
        padding: 34px 18px;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        background: #fff;
        color: #64748b;
        text-align: center;
    }

    .overtime-empty ion-icon {
        display: block;
        margin: 0 auto 10px;
        color: #08aaa5;
        font-size: 2rem;
    }

    .overtime-cards {
        display: grid;
        gap: 12px;
    }

    .overtime-card {
        padding: 15px;
        overflow: hidden;
        border: 1px solid #e8edf2;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(27, 48, 73, .055);
    }

    .overtime-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .overtime-employee {
        min-width: 0;
    }

    .overtime-employee h3 {
        margin: 0;
        color: #172b4d;
        font-size: .98rem;
        font-weight: 700;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .overtime-employee p {
        margin: 4px 0 0;
        color: #7b8798;
        font-size: .76rem;
        line-height: 1.35;
    }

    .overtime-status {
        flex: 0 0 auto;
        padding: 6px 9px;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .overtime-status.is-verified {
        background: #e9f8ef;
        color: #198754;
    }

    .overtime-status.is-pending {
        background: #fff5dd;
        color: #946200;
    }

    .overtime-date {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        color: #53647a;
        font-size: .8rem;
        font-weight: 600;
    }

    .overtime-date ion-icon {
        color: #08aaa5;
        font-size: 1rem;
    }

    .overtime-reason {
        margin-top: 12px;
        padding: 11px 12px;
        border-radius: 12px;
        background: #f7f9fc;
    }

    .overtime-label {
        display: block;
        margin-bottom: 3px;
        color: #8491a3;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .overtime-reason p {
        margin: 0;
        color: #334155;
        font-size: .82rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .overtime-attendance {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px;
        margin-top: 12px;
    }

    .overtime-time {
        min-width: 0;
        padding: 10px;
        border: 1px solid #edf1f5;
        border-radius: 12px;
        background: #fff;
    }

    .overtime-time-value {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 7px;
    }

    .overtime-time strong {
        display: inline-block;
        color: #24364b;
        font-size: .8rem;
        font-weight: 700;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .overtime-photo-thumb {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        padding: 0;
        overflow: hidden;
        border: 2px solid #fff;
        border-radius: 11px;
        background: #e8f1f5;
        box-shadow: 0 0 0 1px #dce5eb, 0 3px 8px rgba(27, 48, 73, .12);
        cursor: zoom-in;
    }

    .overtime-photo-thumb img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .overtime-desktop-photo {
        width: 38px;
        height: 38px;
        border-radius: 9px;
    }

    .overtime-photo-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 18px;
        background: #101820;
        color: #fff;
    }

    .overtime-photo-modal .modal-header {
        border: 0;
    }

    .overtime-photo-modal .modal-title {
        font-size: .95rem;
        font-weight: 600;
    }

    .overtime-photo-modal .btn-close {
        filter: invert(1);
    }

    .overtime-photo-modal .modal-body {
        display: flex;
        min-height: 200px;
        align-items: center;
        justify-content: center;
        padding: 0 14px 16px;
    }

    .overtime-photo-modal img {
        display: block;
        max-width: 100%;
        max-height: 72vh;
        border-radius: 12px;
        object-fit: contain;
    }

    .overtime-time small {
        display: block;
        margin-top: 2px;
        color: #8a96a7;
        font-size: .68rem;
    }

    .overtime-duration {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        background: #eaf8f7;
        color: #087f7b;
        font-size: .8rem;
    }

    .overtime-duration span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }

    .overtime-duration strong {
        text-align: right;
    }

    .overtime-actions {
        display: flex;
        gap: 8px;
        margin-top: 13px;
    }

    .overtime-actions form {
        flex: 1;
        margin: 0;
    }

    .overtime-actions .btn {
        min-height: 42px;
        width: 100%;
        border-radius: 11px;
        font-size: .8rem;
        font-weight: 600;
    }

    .overtime-desktop-table {
        overflow: hidden;
        border: 1px solid #e8edf2;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 7px 20px rgba(27, 48, 73, .055);
    }

    .overtime-desktop-table .table {
        margin: 0;
    }

    .overtime-desktop-table th {
        padding: 12px 10px;
        border-bottom: 0;
        color: #34445a;
        font-size: .8rem;
        white-space: nowrap;
    }

    .overtime-desktop-table td {
        padding: 11px 10px;
        color: #475569;
        font-size: .82rem;
        vertical-align: middle;
    }

    @media (max-width: 767.98px) {
        .overtime-page {
            padding: 14px 12px calc(105px + env(safe-area-inset-bottom));
        }

        .overtime-toolbar {
            display: grid;
            gap: 11px;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 16px;
        }

        .overtime-add-button {
            width: 100%;
        }
    }

    @media (min-width: 768px) {
        .overtime-cards {
            display: none;
        }
    }

    @media (max-width: 767.98px) {
        .overtime-desktop-table {
            display: none;
        }
    }
</style>

<div class="overtime-page">

    @if(session('success'))
        <div class="alert alert-success overtime-alert">{{ session('success') }}</div>
    @elseif(session('error'))
        <div class="alert alert-danger overtime-alert">{{ session('error') }}</div>
    @endif

    <div class="overtime-toolbar">
        <form method="GET" action="{{ route('hris.lembur_index') }}" class="overtime-period">
            <label for="periode">Periode Bulan</label>
            <input type="month" name="periode" id="periode" value="{{ $periode }}" class="form-control" onchange="this.form.submit()">
        </form>
        <a href="{{ route('hris.lembur_create') }}" class="btn overtime-add-button">
            <ion-icon name="add-circle-outline" style="font-size:1.2rem"></ion-icon>
            Tambah Jadwal Lembur
        </a>
    </div>

    <div class="overtime-heading">
        <div>
            <h2>Daftar Lembur</h2>
            <p>Periode {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</p>
        </div>
        <span class="overtime-count">{{ $lembur->count() }} data</span>
    </div>

    @if($lembur->isEmpty())
        <div class="overtime-empty">
            <ion-icon name="calendar-clear-outline"></ion-icon>
            <strong>Belum ada data lembur</strong>
            <div class="small mt-1">Pilih periode lain atau tambahkan jadwal lembur.</div>
        </div>
    @else
        <div class="overtime-cards">
            @foreach($lembur as $item)
                    <article class="overtime-card">
                        <div class="overtime-card-header">
                            <div class="overtime-employee">
                                <h3>{{ $item->pegawai_nama }}</h3>
                                <p>{{ $item->bagian ?: '-' }} · {{ $item->jabatan ?: '-' }}</p>
                            </div>
                            @if($item->verified_at)
                                <span class="overtime-status is-verified">Terverifikasi</span>
                            @else
                                <span class="overtime-status is-pending">Belum diverifikasi</span>
                            @endif
                        </div>

                        <div class="overtime-date">
                            <ion-icon name="calendar-outline"></ion-icon>
                            {{ \Carbon\Carbon::parse($item->tgllembur)->translatedFormat('d F Y') }}
                        </div>

                        <div class="overtime-reason">
                            <span class="overtime-label">Alasan lembur</span>
                            <p>{{ $item->alasan ?: '-' }}</p>
                        </div>

                        <div class="overtime-attendance">
                            <div class="overtime-time">
                                <span class="overtime-label">Jam masuk</span>
                                <div class="overtime-time-value">
                                    <strong>{{ $item->jam_lembur_masuk?->translatedFormat('H:i') ?? '-' }}</strong>
                                    @if($item->foto_lembur_masuk)
                                        <button type="button" class="overtime-photo-thumb" data-bs-toggle="modal" data-bs-target="#overtimePhotoModal" data-photo-url="{{ $item->foto_lembur_masuk }}" data-photo-title="Foto masuk - {{ $item->pegawai_nama }}" aria-label="Lihat foto masuk {{ $item->pegawai_nama }}">
                                            <img src="{{ $item->foto_lembur_masuk }}" alt="Thumbnail foto masuk {{ $item->pegawai_nama }}" loading="lazy">
                                        </button>
                                    @endif
                                </div>
                                @if($item->jam_lembur_masuk)
                                    <small>{{ $item->jam_lembur_masuk->translatedFormat('d M Y') }}</small>
                                @endif
                            </div>
                            <div class="overtime-time">
                                <span class="overtime-label">Jam pulang</span>
                                <div class="overtime-time-value">
                                    <strong>{{ $item->jam_lembur_pulang?->translatedFormat('H:i') ?? '-' }}</strong>
                                    @if($item->foto_lembur_pulang)
                                        <button type="button" class="overtime-photo-thumb" data-bs-toggle="modal" data-bs-target="#overtimePhotoModal" data-photo-url="{{ $item->foto_lembur_pulang }}" data-photo-title="Foto pulang - {{ $item->pegawai_nama }}" aria-label="Lihat foto pulang {{ $item->pegawai_nama }}">
                                            <img src="{{ $item->foto_lembur_pulang }}" alt="Thumbnail foto pulang {{ $item->pegawai_nama }}" loading="lazy">
                                        </button>
                                    @endif
                                </div>
                                @if($item->jam_lembur_pulang)
                                    <small>{{ $item->jam_lembur_pulang->translatedFormat('d M Y') }}</small>
                                @endif
                            </div>
                        </div>

                        <div class="overtime-duration">
                            <span><ion-icon name="time-outline"></ion-icon> Durasi lembur</span>
                            <strong>{{ $item->durasi_lembur ?? 'Belum lengkap' }}</strong>
                        </div>

                        <div class="overtime-actions">
                            @if($item->verified_at)
                                <form action="{{ route('hris.lembur_unverify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Batalkan verifikasi lembur ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-warning">Batal Verif</button>
                                </form>
                            @else
                                <form action="{{ route('hris.lembur_verify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Verifikasi lembur ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Verifikasi</button>
                                </form>
                                <form action="{{ route('hris.lembur_cancel', $item->idlembur) }}" method="POST" onsubmit="return confirm('Yakin batal lembur?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </article>
            @endforeach
        </div>

        <div class="overtime-desktop-table table-responsive">
            <table class="table table-sm align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Nama Pegawai</th>
                            <th>Bagian / Jabatan</th>
                            <th>Alasan</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Durasi Lembur</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lembur as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tgllembur)->translatedFormat('d M Y') }}</td>
                                <td>{{ $item->pegawai_nama }}</td>
                                <td>{{ $item->bagian ?: '-' }} / {{ $item->jabatan ?: '-' }}</td>
                                <td>{{ $item->alasan ?: '-' }}</td>
                                <td>
                                    <div class="overtime-time-value">
                                        <span>{{ $item->jam_lembur_masuk?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                        @if($item->foto_lembur_masuk)
                                            <button type="button" class="overtime-photo-thumb overtime-desktop-photo" data-bs-toggle="modal" data-bs-target="#overtimePhotoModal" data-photo-url="{{ $item->foto_lembur_masuk }}" data-photo-title="Foto masuk - {{ $item->pegawai_nama }}" aria-label="Lihat foto masuk {{ $item->pegawai_nama }}">
                                                <img src="{{ $item->foto_lembur_masuk }}" alt="Thumbnail foto masuk {{ $item->pegawai_nama }}" loading="lazy">
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="overtime-time-value">
                                        <span>{{ $item->jam_lembur_pulang?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                        @if($item->foto_lembur_pulang)
                                            <button type="button" class="overtime-photo-thumb overtime-desktop-photo" data-bs-toggle="modal" data-bs-target="#overtimePhotoModal" data-photo-url="{{ $item->foto_lembur_pulang }}" data-photo-title="Foto pulang - {{ $item->pegawai_nama }}" aria-label="Lihat foto pulang {{ $item->pegawai_nama }}">
                                                <img src="{{ $item->foto_lembur_pulang }}" alt="Thumbnail foto pulang {{ $item->pegawai_nama }}" loading="lazy">
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $item->durasi_lembur ?? 'Belum lengkap' }}</td>
                                <td class="text-center">
                                    @if($item->verified_at)
                                        <span class="badge bg-success px-3">Terverifikasi</span>
                                    @else
                                        <span class="badge bg-warning text-dark px-3">Belum diverifikasi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->verified_at)
                                        <form action="{{ route('hris.lembur_unverify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Batalkan verifikasi lembur ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Batal Verif</button>
                                        </form>
                                    @else
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="{{ route('hris.lembur_verify', $item->idlembur) }}" method="POST" onsubmit="return confirm('Verifikasi lembur ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Verifikasi</button>
                                            </form>
                                            <form action="{{ route('hris.lembur_cancel', $item->idlembur) }}" method="POST" onsubmit="return confirm('Yakin batal lembur?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </table>
        </div>
    @endif
</div>

<div class="modal fade overtime-photo-modal" id="overtimePhotoModal" tabindex="-1" aria-labelledby="overtimePhotoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="overtimePhotoModalTitle">Foto presensi lembur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <img id="overtimePhotoModalImage" src="" alt="Foto presensi lembur">
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('overtimePhotoModal');
        const image = document.getElementById('overtimePhotoModalImage');
        const title = document.getElementById('overtimePhotoModalTitle');

        if (!modal || !image || !title) {
            return;
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            image.src = trigger.dataset.photoUrl;
            image.alt = trigger.dataset.photoTitle;
            title.textContent = trigger.dataset.photoTitle;
        });

        modal.addEventListener('hidden.bs.modal', function () {
            image.removeAttribute('src');
        });
    });
</script>
@endsection
