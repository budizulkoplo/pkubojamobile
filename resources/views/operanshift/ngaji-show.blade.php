@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Ngaji Shift</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

<style>
    .ngaji-reader { margin-top: 48px; padding: 16px; }
    .arab-text {
        font-family: 'Scheherazade New', serif;
        font-size: 1.7rem;
        line-height: 2.8rem;
        text-align: right;
        direction: rtl;
    }
    .ayat-card {
        border-radius: 12px;
        position: relative;
        transition: all 0.25s ease;
        scroll-margin-top: 88px;
    }
    .ayat-card.latest-operan {
        border: 2px solid #078f8a;
        box-shadow: 0 0 10px rgba(7, 143, 138, 0.28);
    }
    .ayat-card.focus-target {
        animation: pulseFocus 1.4s ease 2;
    }
    @keyframes pulseFocus {
        0% { transform: scale(1); }
        50% { transform: scale(1.01); }
        100% { transform: scale(1); }
    }
    .last-read-label {
        position: absolute;
        top: -12px;
        right: 12px;
        color: #fff;
        background: #078f8a;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        white-space: nowrap;
        z-index: 5;
    }
    .reader-toolbar {
        border: 0;
        border-radius: 14px;
        background: #f8fafc;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
</style>

@php
    $latestAyat = ($latest && (int) $latest->idsurat === (int) $surat['nomor']) ? (int) $latest->ayat : null;
@endphp

<div class="ngaji-reader">
    <div class="text-center mb-3">
        <h2 class="mb-0">{{ $surat['namaLatin'] }} <small class="text-muted">({{ $surat['arti'] }})</small></h2>
        <h3 class="mt-2" style="font-family: 'Scheherazade New', serif;">{{ $surat['nama'] }}</h3>
        <div class="text-muted small">{{ $kelompok->namakelompok }}</div>
    </div>

    <div class="card reader-toolbar mb-3">
        <div class="card-body">
            <div class="d-flex gap-2">
                <input type="number" min="1" max="{{ $surat['jumlahAyat'] }}" id="jumpAyatInput" class="form-control" value="{{ $targetAyat }}" placeholder="Ayat">
                <button type="button" id="jumpAyatButton" class="btn btn-primary">Buka</button>
            </div>
            <div class="text-muted small mt-2">
                Sesi ini mulai dari {{ $startSurat['namaLatin'] ?? ('Surat ' . $startPosition['nomor']) }} ayat {{ $startPosition['ayat'] }}.
                Tap ayat terakhir yang selesai dibaca untuk menyimpan operan.
            </div>
        </div>
    </div>

    @foreach($surat['ayat'] as $a)
        <div
            class="card mb-3 shadow-sm ayat-card {{ $latestAyat === (int) $a['nomorAyat'] ? 'latest-operan' : '' }}"
            id="ayat-{{ $a['nomorAyat'] }}"
            data-ayat="{{ $a['nomorAyat'] }}"
            onclick="handleAyatClick({{ $a['nomorAyat'] }})"
        >
            <div class="card-body">
                @if($latestAyat === (int) $a['nomorAyat'])
                    <span class="last-read-label">Terakhir Dibaca</span>
                @endif

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-primary px-3 py-2">Ayat {{ $a['nomorAyat'] }}</span>
                    <h3 class="arab-text flex-grow-1 ms-3">{{ $a['teksArab'] }}</h3>
                </div>

                <div class="latin mb-2">
                    <p><em>{{ $a['teksLatin'] }}</em></p>
                </div>
                <div class="terjemah">
                    <p>{{ $a['teksIndonesia'] }}</p>
                </div>

                <audio controls preload="none" class="ayat-audio w-100" data-ayat="{{ $a['nomorAyat'] }}" onclick="event.stopPropagation()">
                    <source src="{{ $a['audio']['05'] }}" type="audio/mpeg">
                    Browser anda tidak mendukung pemutar audio.
                </audio>
            </div>
        </div>
    @endforeach

    <div class="d-flex justify-content-between mt-4">
        @if($surat['suratSebelumnya'])
            <a href="{{ route('operan.ngaji.show', $surat['suratSebelumnya']['nomor']) }}" class="btn btn-outline-secondary">
                &larr; {{ $surat['suratSebelumnya']['namaLatin'] }}
            </a>
        @else
            <div></div>
        @endif

        @if($surat['suratSelanjutnya'])
            <a href="{{ route('operan.ngaji.show', $surat['suratSelanjutnya']['nomor']) }}" class="btn btn-outline-primary">
                {{ $surat['suratSelanjutnya']['namaLatin'] }} &rarr;
            </a>
        @endif
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('operan.ngaji') }}" class="btn btn-dark">Kembali ke Ngaji Shift</a>
    </div>
</div>

<div class="modal fade" id="confirmAyatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Simpan Operan Ngaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Simpan operan dari
                <strong>{{ $startSurat['namaLatin'] ?? ('Surat ' . $startPosition['nomor']) }} ayat {{ $startPosition['ayat'] }}</strong>
                sampai
                <strong>{{ $surat['namaLatin'] }} ayat <span id="selectedAyatText"></span></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="saveAyatButton">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById("confirmAyatModal");
    const confirmModal = new bootstrap.Modal(modalElement);
    const selectedAyatText = document.getElementById("selectedAyatText");
    const saveAyatButton = document.getElementById("saveAyatButton");
    const jumpAyatInput = document.getElementById("jumpAyatInput");
    const jumpAyatButton = document.getElementById("jumpAyatButton");
    const audios = document.querySelectorAll(".ayat-audio");
    const suratInfo = {
        idsurat: {{ (int) $surat['nomor'] }},
        surat: {!! json_encode($surat['namaLatin'], JSON_UNESCAPED_UNICODE) !!},
        jumlahAyat: {{ (int) $surat['jumlahAyat'] }}
    };
    const startInfo = {
        idsurat: {{ (int) $startPosition['nomor'] }},
        surat: {!! json_encode($startSurat['namaLatin'] ?? ('Surat ' . $startPosition['nomor']), JSON_UNESCAPED_UNICODE) !!},
        ayat: {{ (int) $startPosition['ayat'] }}
    };

    let currentAyat = null;

    window.handleAyatClick = function(no) {
        currentAyat = no;
        selectedAyatText.textContent = no;
        confirmModal.show();
    };

    function getCard(ayat) {
        return document.querySelector(`.ayat-card[data-ayat='${ayat}']`);
    }

    function goToAyat(ayat) {
        const targetCard = getCard(ayat);
        if (!targetCard) {
            return false;
        }

        jumpAyatInput.value = ayat;
        targetCard.scrollIntoView({ behavior: "smooth", block: "start" });
        setTimeout(() => window.scrollBy({ top: -110, behavior: "auto" }), 120);
        history.replaceState(null, "", `#ayat-${ayat}`);
        targetCard.classList.add("focus-target");
        setTimeout(() => targetCard.classList.remove("focus-target"), 2200);
        return true;
    }

    function handleJumpAyat(event) {
        if (event) {
            event.preventDefault();
        }

        const ayat = parseInt(jumpAyatInput.value, 10);
        if (!ayat || ayat < 1 || ayat > suratInfo.jumlahAyat) {
            alert(`Masukkan nomor ayat antara 1 sampai ${suratInfo.jumlahAyat}.`);
            return;
        }

        goToAyat(ayat);
    }

    function saveMarker() {
        if (!currentAyat) {
            return;
        }

        saveAyatButton.disabled = true;
        saveAyatButton.textContent = "Menyimpan...";

        fetch("{{ route('operan.ngaji.mark') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                start_idsurat: startInfo.idsurat,
                start_surat: startInfo.surat,
                start_ayat: startInfo.ayat,
                idsurat: suratInfo.idsurat,
                surat: suratInfo.surat,
                ayat: currentAyat
            })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || "Gagal menyimpan operan.");
            }
            window.location.assign(data.redirect || "{{ route('operan.ngaji') }}");
        })
        .catch(err => {
            console.error(err);
            alert("Operan belum berhasil disimpan. Silakan coba lagi.");
            saveAyatButton.disabled = false;
            saveAyatButton.textContent = "Simpan";
        });
    }

    saveAyatButton.addEventListener("click", saveMarker);
    jumpAyatButton.addEventListener("click", handleJumpAyat);
    jumpAyatButton.addEventListener("touchend", handleJumpAyat, { passive: false });
    jumpAyatInput.addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            handleJumpAyat(event);
        }
    });

    audios.forEach((audio, index) => {
        audio.addEventListener("ended", () => {
            const next = audios[index + 1];
            if (next) {
                next.play();
                next.closest(".ayat-card").scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    });

    const hashAyat = window.location.hash.startsWith("#ayat-")
        ? parseInt(window.location.hash.replace("#ayat-", ""), 10)
        : 0;
    const targetAyat = hashAyat || {{ (int) $targetAyat }};

    if (targetAyat > 0) {
        setTimeout(() => goToAyat(targetAyat), 300);
        window.addEventListener("load", () => setTimeout(() => goToAyat(targetAyat), 500), { once: true });
    }
});
</script>
@endsection
