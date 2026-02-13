@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ url()->previous() }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Scan QR Kehadiran</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top:70px">
    <h4 class="text-center mb-2">Silakan Scan QR</h4>
    
    <!-- Tombol manual aktifkan kamera -->
    <button onclick="initCamera()" class="btn btn-primary w-100 mb-3">Aktifkan Kamera</button>

    <!-- Dropdown pilihan kamera -->
    <select id="camera-select" class="form-control mb-3" style="display: none;"></select>

    <!-- Tempat menampilkan video scan -->
    <div id="reader" style="width: 100%; max-width: 400px; margin: auto;"></div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const html5QrCode = new Html5Qrcode("reader");
    const cameraSelect = document.getElementById("camera-select");
    let scanning = false;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

    function onScanSuccess(decodedText, decodedResult) {
        if (scanning) return;
        scanning = true;

        if (decodedText.includes("/kehadiran/")) {
            window.location.href = decodedText;
        } else {
            alert("QR tidak valid:\n" + decodedText);
            scanning = false;
        }
    }

    function startCamera(cameraId) {
        html5QrCode.start(
            cameraId,
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            (err) => console.warn("Scan error:", err)
        ).catch(err => {
            console.error("Gagal memulai kamera:", err);
            alert("❌ Tidak bisa mengakses kamera.\nPastikan sudah beri izin & gunakan HTTPS.");
        });
    }

    function switchCamera(cameraId) {
        html5QrCode.stop().then(() => {
            scanning = false;
            startCamera(cameraId);
        }).catch(err => {
            console.error("Gagal stop kamera:", err);
            alert("Tidak bisa beralih kamera.");
        });
    }

    function initCamera() {
        Html5Qrcode.getCameras().then(devices => {
            if (devices.length === 0) {
                alert("Tidak ada kamera yang tersedia.");
                return;
            }

            // Kosongkan dan tampilkan pilihan kamera
            cameraSelect.innerHTML = '';
            cameraSelect.style.display = "block";

            devices.forEach((device, index) => {
                const option = document.createElement("option");
                option.value = device.id;
                option.text = device.label || `Kamera ${index + 1}`;
                cameraSelect.appendChild(option);
            });

            const preferredCam = devices.find(d =>
                d.label.toLowerCase().includes('back') || 
                d.label.toLowerCase().includes('environment')
            ) || devices[0];

            cameraSelect.value = preferredCam.id;
            startCamera(preferredCam.id);

            // Event saat kamera diganti
            cameraSelect.addEventListener("change", () => {
                switchCamera(cameraSelect.value);
            });

        }).catch(err => {
            console.error("getCameras error:", err);
            alert("❌ Gagal mengakses kamera. Pastikan sudah memberi izin.");
        });
    }

    // Opsional: auto-start di non-iOS
    if (!isIOS) {
        window.addEventListener('DOMContentLoaded', initCamera);
    }
</script>
@endsection
