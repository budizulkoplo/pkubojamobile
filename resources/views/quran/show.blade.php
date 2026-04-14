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
<link href="https://fonts.googleapis.com/css2?family=Scheherazade+New&display=swap" rel="stylesheet">

<style>
    .arab-text {
        font-family: 'Scheherazade New', serif;
        font-size: 1.6rem;
        line-height: 2.6rem;
        text-align: right;
        direction: rtl;
    }
    .ayat-card {
        border-radius: 12px;
        position: relative;
        transition: all 0.3s ease;
        scroll-margin-top: 88px;
    }
    .ayat-card.marked-senin {
        border: 2px solid #fd7e14;
        box-shadow: 0 0 10px rgba(253, 126, 20, 0.35);
    }
    .ayat-card.marked-rutin {
        border: 2px solid #198754;
        box-shadow: 0 0 10px rgba(25, 135, 84, 0.35);
    }
    .ayat-card.focus-target {
        animation: pulseFocus 1.5s ease 2;
    }
    @keyframes pulseFocus {
        0% { transform: scale(1); }
        50% { transform: scale(1.01); }
        100% { transform: scale(1); }
    }
    .last-read-label {
        position: absolute;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        white-space: nowrap;
        z-index: 5;
    }
    .last-read-label.senin {
        top: -12px;
        left: 12px;
        color: #fff;
        background: #fd7e14;
    }
    .last-read-label.rutin {
        top: -12px;
        right: 12px;
        color: #fff;
        background: #198754;
    }
    .choice-btn {
        padding: 12px;
        margin: 10px 0;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
        font-weight: 700;
    }
    .choice-btn.senin {
        background-color: rgba(253, 126, 20, 0.12);
        color: #fd7e14;
    }
    .choice-btn.rutin {
        background-color: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .choice-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .form-check {
        margin: 0 10px;
    }
    .shortcut-card {
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, #f8fafc, #eef6ff);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }
    .history-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .history-pill.senin {
        background: rgba(253, 126, 20, 0.12);
        color: #fd7e14;
    }
    .history-pill.rutin {
        background: rgba(25, 135, 84, 0.12);
        color: #198754;
    }
    .scroll-helper {
        position: fixed;
        right: 12px;
        bottom: 148px;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: auto;
    }
    .scroll-helper-btn {
        width: 46px;
        height: 46px;
        border: 0;
        border-radius: 50%;
        background: rgba(13, 110, 253, 0.92);
        color: #fff;
        font-size: 1.15rem;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.24);
        touch-action: manipulation;
    }
    .scroll-helper-btn:active {
        transform: scale(0.96);
    }
    @media (max-width: 576px) {
        .last-read-label {
            font-size: 0.64rem;
        }
        .scroll-helper {
            right: 8px;
            bottom: 138px;
        }
        .scroll-helper-btn {
            width: 42px;
            height: 42px;
        }
    }
</style>

@php
    $riwayatSenin = $riwayat['senin'] ?? null;
    $riwayatRutin = $riwayat['rutin'] ?? null;
    $riwayatMap = [
        'senin' => $riwayatSenin['ayat'] ?? null,
        'rutin' => $riwayatRutin['ayat'] ?? null,
    ];
@endphp

<div class="p-3" style="margin-top: 40px" id="quranPageTop">
    <div class="text-center mb-4">
        <h1 class="mb-0">{{ $surat['namaLatin'] }} <small class="text-muted">({{ $surat['arti'] }})</small></h1>
        <h2 class="mt-2" style="font-family: 'Scheherazade New', serif;">{{ $surat['nama'] }}</h2>
        <p class="mb-1"><strong>Jumlah Ayat:</strong> {{ $surat['jumlahAyat'] }}</p>
        <p><strong>Tempat Turun:</strong> {{ $surat['tempatTurun'] }}</p>
    </div>

    <div class="card shortcut-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="text-muted small">Masukkan nomor ayat.</div>
                </div>
                <div class="d-flex gap-2">
                    <input type="number" min="1" max="{{ $surat['jumlahAyat'] }}" id="jumpAyatInput" class="form-control" placeholder="Contoh: 18">
                    <button type="button" id="jumpAyatButton" class="btn btn-primary">Buka</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mb-4">
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
    <div class="card mb-3 shadow-sm ayat-card"
         id="ayat-{{ $a['nomorAyat'] }}"
         data-ayat="{{ $a['nomorAyat'] }}"
         onclick="handleAyatClick({{ $a['nomorAyat'] }})">
        <div class="card-body">
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

            <span class="last-read-label senin d-none">Senin Pagi</span>
            <span class="last-read-label rutin d-none">Ngaji Rutin</span>
        </div>
    </div>
    @endforeach

    <div class="d-flex justify-content-between mt-4">
        @if($surat['suratSebelumnya'])
            <a href="{{ route('quran.show', $surat['suratSebelumnya']['nomor']) }}" class="btn btn-outline-secondary">
                &larr; {{ $surat['suratSebelumnya']['namaLatin'] }}
            </a>
        @else
            <div></div>
        @endif

        @if($surat['suratSelanjutnya'])
            <a href="{{ route('quran.show', $surat['suratSelanjutnya']['nomor']) }}" class="btn btn-outline-primary">
                {{ $surat['suratSelanjutnya']['namaLatin'] }} &rarr;
            </a>
        @endif
    </div>

    <div class="text-center mt-4">
        <a href="/quran" class="btn btn-dark">Kembali ke Daftar Surat</a>
    </div>

    <div id="quranPageBottom" style="height: 1px;"></div>
</div>

<div class="scroll-helper">
    <button
        type="button"
        class="scroll-helper-btn"
        aria-label="Geser ke atas"
        onclick="window.handleScrollHelper(-1, event)"
        ontouchstart="window.handleScrollHelper(-1, event)"
    >↑</button>
    <button
        type="button"
        class="scroll-helper-btn"
        aria-label="Geser ke bawah"
        onclick="window.handleScrollHelper(1, event)"
        ontouchstart="window.handleScrollHelper(1, event)"
    >↓</button>
</div>

@include('quran.partials.doa-pagi')

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Jenis Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Pilih penanda untuk ayat yang baru dibaca.</p>
                <div class="choice-btn senin" data-type="senin">Senin Pagi</div>
                <div class="choice-btn rutin" data-type="rutin">Ngaji Rutin</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalElement = document.getElementById("activityModal");
    const radios = document.querySelectorAll('input[name="mode"]');
    const cards = document.querySelectorAll(".ayat-card");
    const arabTexts = document.querySelectorAll(".arab-text");
    const audios = document.querySelectorAll(".ayat-audio");
    const activityModal = new bootstrap.Modal(modalElement);
    const jumpAyatInput = document.getElementById("jumpAyatInput");
    const jumpAyatButton = document.getElementById("jumpAyatButton");
    const suratInfo = {
        idsurat: {{ (int) $surat['nomor'] }},
        surat: {!! json_encode($surat['namaLatin'], JSON_UNESCAPED_UNICODE) !!},
        jumlahAyat: {{ (int) $surat['jumlahAyat'] }}
    };
    const targetAyat = {{ (int) $targetAyat }};
    const targetType = {!! json_encode($targetType) !!};
    const hashAyat = window.location.hash.startsWith("#ayat-")
        ? parseInt(window.location.hash.replace("#ayat-", ""), 10)
        : 0;
    const state = {
        senin: {{ $riwayatMap['senin'] ? (int) $riwayatMap['senin'] : 'null' }},
        rutin: {{ $riwayatMap['rutin'] ? (int) $riwayatMap['rutin'] : 'null' }}
    };

    let currentAyat = null;
    let fontSize = 1.6;

    window.handleAyatClick = function(no) {
        currentAyat = no;
        activityModal.show();
    };

    function getCard(ayat) {
        return document.querySelector(`.ayat-card[data-ayat='${ayat}']`);
    }

    function renderMarkers() {
        cards.forEach(card => {
            card.classList.remove("marked-senin", "marked-rutin");
            card.querySelector(".last-read-label.senin").classList.add("d-none");
            card.querySelector(".last-read-label.rutin").classList.add("d-none");
        });

        ["senin", "rutin"].forEach(type => {
            const ayat = state[type];
            const card = getCard(ayat);

            if (!card) {
                return;
            }

            card.classList.add(type === "senin" ? "marked-senin" : "marked-rutin");
            card.querySelector(`.last-read-label.${type}`).classList.remove("d-none");
        });
    }

    function goToAyat(ayat) {
        const targetCard = getCard(ayat);

        if (!targetCard) {
            return false;
        }

        jumpAyatInput.value = ayat;
        targetCard.scrollIntoView({ behavior: "smooth", block: "start" });
        setTimeout(() => {
            window.scrollBy({ top: -110, behavior: "auto" });
        }, 120);
        history.replaceState(null, "", `#ayat-${ayat}`);
        targetCard.classList.add("focus-target");
        setTimeout(() => targetCard.classList.remove("focus-target"), 2200);

        return true;
    }

    function saveMarker(ayatNo, activityType) {
        fetch("{{ route('quran.markRutin') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                idsurat: suratInfo.idsurat,
                surat: suratInfo.surat,
                ayat: ayatNo,
                type: activityType
            })
        })
        .then(async res => {
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || "Gagal menyimpan penanda.");
            }

            return data;
        })
        .catch(err => {
            console.error(err);
            alert("Penanda belum berhasil disimpan. Silakan coba lagi.");
        });
    }

    document.querySelectorAll(".choice-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const activityType = this.dataset.type;
            state[activityType] = currentAyat;
            renderMarkers();
            activityModal.hide();
            saveMarker(currentAyat, activityType);
        });
    });

    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener("click", function(event) {
            event.preventDefault();
            activityModal.hide();
        });
    });

    modalElement.addEventListener("click", function(event) {
        if (event.target === modalElement) {
            activityModal.hide();
        }
    });

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

                if (mode === "full") {
                    latin.style.display = "block";
                    terjemah.style.display = "block";
                    audio.style.display = "block";
                } else if (mode === "arab") {
                    latin.style.display = "none";
                    terjemah.style.display = "none";
                    audio.style.display = "none";
                } else {
                    latin.style.display = "none";
                    terjemah.style.display = "block";
                    audio.style.display = "block";
                }
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

    function scrollPage(direction) {
        window.scrollBy({
            top: window.innerHeight * 0.8 * direction,
            behavior: "smooth"
        });
    }
    window.handleScrollHelper = function(direction, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        scrollPage(direction);
        return false;
    };

    renderMarkers();

    const ayatTujuan = hashAyat || targetAyat || (targetType && state[targetType] ? state[targetType] : 0);

    function ensureGoToAyat(ayat, attemptsLeft = 5) {
        if (!ayat || attemptsLeft < 1) {
            return;
        }

        const ok = goToAyat(ayat);
        if (!ok) {
            setTimeout(() => ensureGoToAyat(ayat, attemptsLeft - 1), 400);
        }
    }

    if (ayatTujuan > 0) {
        setTimeout(() => ensureGoToAyat(ayatTujuan, 6), 300);
        window.addEventListener("load", () => setTimeout(() => ensureGoToAyat(ayatTujuan, 6), 500), { once: true });
        window.addEventListener("pageshow", () => setTimeout(() => ensureGoToAyat(ayatTujuan, 6), 500), { once: true });
    }
});
</script>

@endsection
