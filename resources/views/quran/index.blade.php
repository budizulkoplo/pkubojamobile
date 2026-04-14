@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">QUR'AN</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
{{-- Google Font untuk Arab --}}
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

<style>
    .arab-title {
        font-family: 'Scheherazade New', serif;
        font-size: 1.6rem;
        text-align: right;
        direction: rtl;
    }
    .history-card {
        border-radius: 16px;
        border: 0;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }
    .history-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .history-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.65rem;
        border-radius: 999px;
        font-weight: 700;
    }
    .history-badge.rutin {
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .history-badge.senin {
        background: rgba(253, 126, 20, 0.14);
        color: #fd7e14;
    }
    .history-empty {
        border: 1px dashed #d0d7de;
        border-radius: 14px;
        background: #f8f9fa;
    }
</style>

<div class="p-3" style="margin-top: 40px">
    <h2 class="mb-3">Daftar Surat</h2>

    {{-- Filter pencarian --}}
    <div class="mb-3">
        <input type="text" id="searchSurat" class="form-control" placeholder="Cari surat...">
    </div>

    <div class="mb-4">
        <h5 class="mb-3">Riwayat Bacaan Terakhir</h5>

        @foreach(['rutin' => 'Ngaji Rutin', 'senin' => 'Senin Pagi'] as $type => $label)
            @php($item = $riwayatTerakhir[$type] ?? null)
            @php($hasLink = !empty($item['nomor_surat']))

            @if($item)
                @if($hasLink)
                <a
                    href="{{ route('quran.show', ['nomor' => $item['nomor_surat'], 'ayat' => $item['ayat'], 'type' => $type]) }}"
                    class="history-link mb-3"
                >
                @else
                <div class="history-link mb-3">
                @endif
                    <div class="card history-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="history-badge {{ $type }}">{{ $label }}</span>
                                    <div class="mt-2">
                                        <strong>{{ $item['surat'] }}</strong> ayat {{ $item['ayat'] }}
                                    </div>
                                    <small class="text-muted">
                                        Terakhir dibaca {{ \Carbon\Carbon::parse($item['created_at'])->translatedFormat('d M Y H:i') }}
                                    </small>
                                    @if(!$hasLink)
                                        <div class="small text-danger mt-1">Surat lama belum bisa dibuka otomatis. Silakan pilih surat manual sekali lagi.</div>
                                    @endif
                                </div>
                                <div class="arab-title">
                                    {{ $item['nama_arab'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @if($hasLink)
                </a>
                @else
                </div>
                @endif
            @else
                <div class="history-empty p-3 mb-3">
                    <span class="history-badge {{ $type }}">{{ $label }}</span>
                    <div class="mt-2 text-muted">Belum ada riwayat bacaan tersimpan.</div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="list-group" id="suratList">
        @foreach($surat as $s)
            <a href="{{ route('quran.show', $s['nomor']) }}" class="list-group-item list-group-item-action surat-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $s['namaLatin'] }}</strong> ({{ $s['jumlahAyat'] }} ayat)<br>
                        <small>{{ $s['arti'] }}</small>
                    </div>
                    <div class="arab-title">
                        {{ $s['nama'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>

@include('quran.partials.doa-pagi')

{{-- Script filter pencarian --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchSurat");
    const items = document.querySelectorAll(".surat-item");

    input.addEventListener("keyup", function () {
        const filter = input.value.toLowerCase();
        items.forEach(item => {
            const text = item.innerText.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = "block";
            } else {
                item.style.display = "none";
            }
        });
    });
});
</script>
@endsection
