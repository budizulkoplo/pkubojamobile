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
<!-- Uthmanic Script Font -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/alif-type/quran-fonts@master/fonts/uthmanic-hafs.css">

<style>
    /* Global Style */
    body {
        background: #f9fafb;
    }
    
    .arab-text {
        font-family: 'UthmanicHafs', 'Scheherazade New', 'Amiri', 'Traditional Arabic', serif;
        font-size: 2rem;
        line-height: 3.2rem;
        direction: rtl;
        font-weight: 500;
        text-align: right;
        letter-spacing: 0;
        color: #1e293b;
        word-spacing: 4px;
        margin-bottom: 0.5rem;
    }
    
    /* Verse Card Style */
    .ayat-card {
        border-radius: 20px;
        background: #ffffff;
        transition: all 0.25s ease;
        scroll-margin-top: 90px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.03), 0 2px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f6;
        position: relative;
    }
    
    .ayat-card:hover {
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }
    
    /* Nomor Ayat: Lingkaran Oranye di Belakang Teks Arab */
    .verse-number-badge {
        display: inline-block;
        background-color: #f97316;
        color: white;
        font-size: 1rem;
        font-weight: bold;
        min-width: 2rem;
        height: 2rem;
        line-height: 2rem;
        text-align: center;
        border-radius: 9999px;
        margin-left: 12px;
        margin-right: 6px;
        vertical-align: middle;
        box-shadow: 0 2px 6px rgba(249, 115, 22, 0.3);
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }
    
    /* Wrapper untuk baris arab + nomor */
    .arabic-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    
    .arabic-content {
        flex: 1;
        text-align: right;
    }
    
    /* Terjemahan Style */
    .terjemah {
        font-size: 1rem;
        line-height: 1.5rem;
        color: #334155;
        border-top: 1px dashed #e2e8f0;
        padding-top: 0.75rem;
        margin-top: 0.25rem;
    }
    
    .terjemah p {
        margin-bottom: 0;
        text-align: justify;
    }
    
    /* Audio Player */
    .ayat-audio {
        width: 100%;
        margin-top: 12px;
        border-radius: 32px;
        height: 38px;
    }
    
    /* Last Read Labels */
    .last-read-label {
        position: absolute;
        font-size: 0.68rem;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 40px;
        letter-spacing: 0.3px;
        z-index: 10;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .last-read-label.senin {
        top: -12px;
        left: 16px;
        background: #fd7e14;
        color: white;
    }
    
    .last-read-label.rutin {
        top: -12px;
        right: 16px;
        background: #198754;
        color: white;
    }
    
    /* Marker untuk kartu yang memiliki penanda */
    .ayat-card.marked-senin {
        border-left: 6px solid #fd7e14;
        border-right: 1px solid #eef2f6;
    }
    
    .ayat-card.marked-rutin {
        border-left: 6px solid #198754;
        border-right: 1px solid #eef2f6;
    }
    
    .ayat-card.focus-target {
        animation: gentlePulse 0.9s ease 3;
        background: #fffaf2;
    }
    
    @keyframes gentlePulse {
        0% { background: #ffffff; box-shadow: 0 8px 20px rgba(0,0,0,0.03); }
        50% { background: #fff1e0; box-shadow: 0 8px 20px rgba(253,126,20,0.15); }
        100% { background: #ffffff; }
    }
    
    /* Shortcut card & controls */
    .shortcut-card {
        border: 0;
        border-radius: 28px;
        background: linear-gradient(145deg, #ffffff, #f8f9fc);
        box-shadow: 0 12px 22px -12px rgba(0, 0, 0, 0.1);
    }
    
    .choice-btn {
        padding: 14px;
        margin: 12px 0;
        border-radius: 60px;
        text-align: center;
        cursor: pointer;
        font-weight: 700;
        transition: 0.2s;
        border: 1px solid transparent;
    }
    
    .choice-btn.senin {
        background: #fff1e6;
        color: #fd7e14;
        border-color: #ffd8c2;
    }
    
    .choice-btn.rutin {
        background: #e9f6ef;
        color: #198754;
        border-color: #c3e6d4;
    }
    
    .choice-btn:hover {
        transform: scale(0.98);
        filter: brightness(0.96);
    }
    
    .btn-outline-secondary, .btn-outline-primary {
        border-radius: 40px;
        padding: 8px 20px;
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .arab-text {
            font-size: 1.7rem;
            line-height: 2.6rem;
        }
        .verse-number-badge {
            font-size: 0.85rem;
            min-width: 1.8rem;
            height: 1.8rem;
            line-height: 1.8rem;
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

<div class="p-3" style="margin-top: 40px">
    <!-- Header Surat -->
    <div class="text-center mb-4">
        <h1 class="mb-0 fw-bold">{{ $surat['namaLatin'] }} <small class="text-muted fw-normal">({{ $surat['arti'] }})</small></h1>
        <h2 class="mt-2" style="font-family: 'Scheherazade New', 'Amiri', serif; font-size: 1.9rem;">{{ $surat['nama'] }}</h2>
        <div class="mt-2 d-flex justify-content-center gap-3">
            <span class="badge bg-light text-dark px-3 py-2 rounded-pill">📖 Ayat: {{ $surat['jumlahAyat'] }}</span>
            <span class="badge bg-light text-dark px-3 py-2 rounded-pill">📍 {{ $surat['tempatTurun'] }}</span>
        </div>
    </div>

    <!-- Lompat Ayat -->
    <div class="card shortcut-card mb-4 border-0">
        <div class="card-body d-flex flex-column flex-sm-row gap-3 align-items-center">
            <div class="text-muted small fw-semibold">🔍 Langsung ke Ayat</div>
            <div class="d-flex gap-2 w-100 w-sm-auto">
                <input type="number" min="1" max="{{ $surat['jumlahAyat'] }}" id="jumpAyatInput" class="form-control rounded-pill" placeholder="Nomor ayat">
                <button type="button" id="jumpAyatButton" class="btn btn-primary rounded-pill px-4">Buka</button>
            </div>
        </div>
    </div>

    <!-- Mode Tampilan -->
    <div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeFull" value="full" checked>
            <label class="form-check-label" for="modeFull">📖 Lengkap + Audio</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeArab" value="arab">
            <label class="form-check-label" for="modeArab">📜 Arab saja</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="modeArabTerjemah" value="arab_terjemah">
            <label class="form-check-label" for="modeArabTerjemah">🕌 Arab + Terjemahan</label>
        </div>
    </div>

    <!-- Kontrol Ukuran Font Arab -->
    <div class="text-center mb-4">
        <button id="fontIncrease" class="btn btn-outline-warning btn-sm rounded-pill px-3 me-2">➕ Perbesar Arab</button>
        <button id="fontDecrease" class="btn btn-outline-warning btn-sm rounded-pill px-3">➖ Perkecil Arab</button>
    </div>

    <!-- LOOPING AYAT -->
    @foreach($surat['ayat'] as $a)
    <div class="card mb-4 shadow-sm ayat-card"
         id="ayat-{{ $a['nomorAyat'] }}"
         data-ayat="{{ $a['nomorAyat'] }}"
         onclick="handleAyatClick({{ $a['nomorAyat'] }})">
        <div class="card-body">
            <!-- Arabic line + circular number behind text -->
            <div class="arabic-wrapper">
                <div class="arabic-content">
                    <div class="arab-text">
                        <span class="verse-number-badge">{{ $a['nomorAyat'] }}</span>
                        {{ $a['teksArab'] }}
                    </div>
                </div>
            </div>

            <!-- Terjemahan Indonesia (tanpa latin) -->
            <div class="terjemah">
                <p>{{ $a['teksIndonesia'] }}</p>
            </div>

            <!-- Audio Player -->
            <audio controls preload="none" class="ayat-audio w-100 mt-3" data-ayat="{{ $a['nomorAyat'] }}" onclick="event.stopPropagation()">
                <source src="{{ $a['audio']['05'] }}" type="audio/mpeg">
                Browser anda tidak mendukung pemutar audio.
            </audio>

            <!-- Label Riwayat -->
            <span class="last-read-label senin d-none">📌 Senin Pagi</span>
            <span class="last-read-label rutin d-none">⭐ Ngaji Rutin</span>
        </div>
    </div>
    @endforeach

    <!-- Navigasi Surat -->
    <div class="d-flex justify-content-between mt-4 gap-2">
        @if($surat['suratSebelumnya'])
            <a href="{{ route('quran.show', $surat['suratSebelumnya']['nomor']) }}" class="btn btn-outline-secondary rounded-pill">
                &larr; {{ $surat['suratSebelumnya']['namaLatin'] }}
            </a>
        @else
            <div></div>
        @endif

        @if($surat['suratSelanjutnya'])
            <a href="{{ route('quran.show', $surat['suratSelanjutnya']['nomor']) }}" class="btn btn-outline-primary rounded-pill">
                {{ $surat['suratSelanjutnya']['namaLatin'] }} &rarr;
            </a>
        @endif
    </div>

    <div class="text-center mt-4">
        <a href="/quran" class="btn btn-dark rounded-pill px-4">📖 Kembali ke Daftar Surat</a>
    </div>
</div>

@include('quran.partials.doa-pagi')

<!-- Modal Pilih Aktivitas -->
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">🏷️ Tandai Bacaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-3">Pilih kategori untuk ayat <strong id="modalAyatNumber">-</strong></p>
                <div class="choice-btn senin" data-type="senin">🌅 Senin Pagi</div>
                <div class="choice-btn rutin" data-type="rutin">📖 Ngaji Rutin</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
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
    
    let state = {
        senin: {{ $riwayatMap['senin'] ? (int) $riwayatMap['senin'] : 'null' }},
        rutin: {{ $riwayatMap['rutin'] ? (int) $riwayatMap['rutin'] : 'null' }}
    };
    let currentAyat = null;
    let fontSize = 2.0;  // base rem for arabic
    
    // Set initial font size
    arabTexts.forEach(text => text.style.fontSize = fontSize + "rem");
    
    window.handleAyatClick = function(no) {
        currentAyat = no;
        const modalAyatSpan = document.getElementById("modalAyatNumber");
        if (modalAyatSpan) modalAyatSpan.innerText = no;
        activityModal.show();
    };
    
    function getCard(ayat) {
        return document.querySelector(`.ayat-card[data-ayat='${ayat}']`);
    }
    
    function renderMarkers() {
        cards.forEach(card => {
            card.classList.remove("marked-senin", "marked-rutin");
            const labelSenin = card.querySelector(".last-read-label.senin");
            const labelRutin = card.querySelector(".last-read-label.rutin");
            if (labelSenin) labelSenin.classList.add("d-none");
            if (labelRutin) labelRutin.classList.add("d-none");
        });
        
        ["senin", "rutin"].forEach(type => {
            const ayat = state[type];
            if (!ayat) return;
            const card = getCard(ayat);
            if (!card) return;
            card.classList.add(type === "senin" ? "marked-senin" : "marked-rutin");
            const label = card.querySelector(`.last-read-label.${type}`);
            if (label) label.classList.remove("d-none");
        });
    }
    
    function goToAyat(ayat) {
        const targetCard = getCard(ayat);
        if (!targetCard) return false;
        jumpAyatInput.value = ayat;
        targetCard.scrollIntoView({ behavior: "smooth", block: "start" });
        setTimeout(() => window.scrollBy({ top: -110, behavior: "auto" }), 100);
        history.replaceState(null, "", `#ayat-${ayat}`);
        targetCard.classList.add("focus-target");
        setTimeout(() => targetCard.classList.remove("focus-target"), 2000);
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
            if (!res.ok || !data.success) throw new Error(data.message || "Gagal menyimpan.");
            return data;
        })
        .catch(err => {
            console.error(err);
            alert("❌ Gagal menyimpan penanda. Coba lagi.");
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
    
    function handleJumpAyat(e) {
        if (e) e.preventDefault();
        const ayat = parseInt(jumpAyatInput.value, 10);
        if (isNaN(ayat) || ayat < 1 || ayat > suratInfo.jumlahAyat) {
            alert(`⚠️ Masukkan nomor ayat antara 1 sampai ${suratInfo.jumlahAyat}.`);
            return;
        }
        goToAyat(ayat);
    }
    
    jumpAyatButton.addEventListener("click", handleJumpAyat);
    jumpAyatInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") handleJumpAyat(e);
    });
    
    // Mode tampilan: tanpa latin
    radios.forEach(radio => {
        radio.addEventListener("change", function() {
            const mode = this.value;
            cards.forEach(card => {
                const terjemahDiv = card.querySelector(".terjemah");
                const audioDiv = card.querySelector(".ayat-audio");
                if (mode === "full") {
                    terjemahDiv.style.display = "block";
                    audioDiv.style.display = "block";
                } else if (mode === "arab") {
                    terjemahDiv.style.display = "none";
                    audioDiv.style.display = "none";
                } else { // arab + terjemahan
                    terjemahDiv.style.display = "block";
                    audioDiv.style.display = "block";
                }
            });
        });
    });
    
    // Auto play next audio optional (aktif)
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
        fontSize = Math.min(fontSize + 0.2, 3.2);
        arabTexts.forEach(text => text.style.fontSize = fontSize + "rem");
    });
    document.getElementById("fontDecrease").addEventListener("click", () => {
        fontSize = Math.max(fontSize - 0.2, 1.4);
        arabTexts.forEach(text => text.style.fontSize = fontSize + "rem");
    });
    
    renderMarkers();
    
    // Determine initial target ayat
    let initialTarget = hashAyat || targetAyat;
    if (targetType && state[targetType] && !initialTarget) {
        initialTarget = state[targetType];
    }
    
    function ensureGoToAyat(ayat, attempts = 6) {
        if (!ayat || attempts < 1) return;
        const ok = goToAyat(ayat);
        if (!ok) setTimeout(() => ensureGoToAyat(ayat, attempts - 1), 350);
    }
    
    if (initialTarget > 0) {
        setTimeout(() => ensureGoToAyat(initialTarget, 6), 300);
        window.addEventListener("load", () => setTimeout(() => ensureGoToAyat(initialTarget, 5), 500));
    }
});
</script>
@endsection