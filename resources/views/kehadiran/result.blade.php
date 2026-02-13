@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url('/kehadiran/formScan') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Status Kehadiran</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    <h5 class="text-center">Kehadiran Kajian</h5>

    <div class="mb-2">
        <p><strong>Judul Kajian:</strong> {{ $kajian->namakajian ?? '-' }}</p>
        <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
        <p><strong>Lokasi:</strong> {{ $kajian->lokasi ?? '-' }}</p>
    </div>

    <hr>

    <p><strong>Barcode Terdeteksi:</strong> {{ $barcode ?? '-' }}</p>

    <div class="mt-3 text-center">
        @if ($status === 'sukses')
            <p style="color: green; font-size: 18px;">
                <strong>✅ Kehadiran berhasil direkam!</strong>
            </p>
        @elseif ($status === 'duplikat_barcode')
            <p style="color: red; font-size: 18px;">
                <strong>❌ Barcode sudah pernah digunakan.</strong>
            </p>
        @elseif ($status === 'duplikat_user')
            <p style="color: red; font-size: 18px;">
                <strong>❌ Anda sudah melakukan scan hari ini untuk kajian ini.</strong>
            </p>
        @else
            <p style="color: orange; font-size: 18px;">
                <strong>⚠️ {{ $message }}</strong>
            </p>
        @endif
    </div>

    <div class="mt-3">
        <a href="{{ route('form.scan.camera') }}" class="btn btn-primary w-100">
            <ion-icon name="qr-code-outline"></ion-icon> Scan Lagi
        </a>
    </div>
</div>

{{-- ✅ Tambahan suara TTS --}}
<script>
    function speak(text) {
        const synth = window.speechSynthesis;
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        synth.speak(utterance);
    }

    document.addEventListener("DOMContentLoaded", () => {
        @if ($status === 'sukses')
            speak("Kehadiran berhasil direkam. Terima kasih.");
        @elseif ($status === 'duplikat_barcode')
            speak("Barcode sudah pernah digunakan.");
        @elseif ($status === 'duplikat_user')
            speak("Anda sudah melakukan scan hari ini.");
        @elseif (!empty($message))
            speak("{{ addslashes($message) }}");
        @endif
    });
</script>
@endsection
