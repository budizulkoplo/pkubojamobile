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
<link rel="stylesheet" href="https://fonts.cdnfonts.com/css/kfgqpc-hafs-uthmanic-script">

<style>
    .ngaji-reader { margin-top: 48px; padding: 16px; }
    .arab-text {
        font-family: 'KFGQPC HAFS Uthmanic Script', 'UthmanicHafs', 'Scheherazade New', 'Amiri', 'Traditional Arabic', serif;
        font-size: 2rem;
        line-height: 4.4rem;
        direction: rtl;
        font-weight: 400;
        text-align: right;
        letter-spacing: 0;
        color: #111827;
        word-spacing: 8px;
        margin-bottom: 0.75rem;
    }
    .surah-arabic-title {
        font-family: 'KFGQPC HAFS Uthmanic Script', 'UthmanicHafs', 'Scheherazade New', 'Amiri', 'Traditional Arabic', serif;
        font-weight: 400;
        line-height: 3rem;
    }
    .ayah-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.15rem;
        height: 2.15rem;
        margin-right: 0.45rem;
        border: 2px solid #c8a349;
        border-radius: 50%;
        background:
            radial-gradient(circle at center, #fffaf0 0 48%, transparent 49%),
            conic-gradient(from 22deg, #f7d985, #fff7d7, #d8aa3c, #fff7d7, #f7d985);
        color: #7c5f13;
        font-family: 'Times New Roman', serif;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1;
        vertical-align: middle;
        box-shadow: 0 2px 5px rgba(124, 95, 19, 0.18), inset 0 0 0 3px #fff8df;
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
    .form-check {
        margin: 0 10px;
    }
    @media (max-width: 576px) {
        .ngaji-reader { padding: 12px; }
        .arab-text {
            font-size: 1.95rem;
            line-height: 4.2rem;
            word-spacing: 7px;
        }
        .last-read-label { font-size: 0.64rem; }
    }
</style>

@php
    $latestAyat = ($latest && (int) $latest->idsurat === (int) $surat['nomor']) ? (int) $latest->ayat : null;
@endphp

<div class="ngaji-reader">
    <div class="text-center mb-3">
        <h2 class="mb-0">{{ $surat['namaLatin'] }} <small class="text-muted">({{ $surat['arti'] }})</small></h2>
        <h3 class="mt-2 surah-arabic-title">{{ $surat['nama'] }}</h3>
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

    <div class="d-flex justify-content-center mb-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeFull" value="full" checked>
            <label class="form-check-label" for="modeFull">Lengkap</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeArab" value="arab">
            <label class="form-check-label" for="modeArab">Arab saja</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="modeArabTerjemah" value="arab_terjemah">
            <label class="form-check-label" for="modeArabTerjemah">Arab + Terjemahan</label>
        </div>
    </div>

    <div class="text-center mb-4">
        <button id="fontIncrease" class="btn btn-outline-primary btn-sm me-2">Perbesar+</button>
        <button id="fontDecrease" class="btn btn-outline-primary btn-sm">Perkecil-</button>
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
                    <h3 class="arab-text flex-grow-1 ms-3">
                        {{ $a['teksArab'] }}
                        <span class="ayah-number">{{ $a['nomorAyat'] }}</span>
                    </h3>
                </div>

                <div class="latin mb-2 d-none">
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
    const radios = document.querySelectorAll('input[name="mode"]');
    const cards = document.querySelectorAll(".ayat-card");
    const arabTexts = document.querySelectorAll(".arab-text");
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
    let fontSize = 2;

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
            event.preventDefault();
            handleJumpAyat(event);
        }
    });

    radios.forEach(radio => {
        radio.addEventListener("change", function () {
            const mode = this.value;

            cards.forEach(card => {
                const latin = card.querySelector(".latin");
                const terjemah = card.querySelector(".terjemah");
                const audio = card.querySelector(".ayat-audio");

                latin.style.display = mode === "full" ? "block" : "none";
                terjemah.style.display = mode === "arab" ? "none" : "block";
                audio.style.display = mode === "arab" ? "none" : "block";
            });
        });
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

    document.getElementById("fontIncrease").addEventListener("click", () => {
        fontSize += 0.2;
        arabTexts.forEach(text => text.style.fontSize = fontSize + "rem");
    });

    document.getElementById("fontDecrease").addEventListener("click", () => {
        if (fontSize <= 1.2) {
            return;
        }

        fontSize -= 0.2;
        arabTexts.forEach(text => text.style.fontSize = fontSize + "rem");
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
