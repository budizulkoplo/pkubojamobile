@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="{{ route('hris.jadwal_lembur') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Absen Lembur</div>
    <div class="right"></div>
</div>

<style>
    .webcam-capture, .webcam-capture video {
        display: inline-block;
        width: 100% !important;
        margin: auto;
        height: auto !important;
        border-radius: 15px;
    }

    #map { height: 220px; border-radius: 10px; }

    .sticky-absen-btn {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        display: flex;
        justify-content: space-around;
        padding: 10px 15px;
        background: #fff;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.15);
        z-index: 1000;
    }

    .sticky-absen-btn button {
        flex: 1;
        margin: 0 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 16px;
        font-weight: 600;
        padding: 12px 0;
        border-radius: 10px;
    }

    .content-wrapper { padding-bottom: 80px; }
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<div class="content-wrapper">

    <div class="row">
        <div class="col">
            <div class="text-center mb-2">
                <h5>{{ $lembur->pegawai_nama }}</h5>
                <p class="text-muted">{{ $lembur->alasan }}</p>
                <p><strong>{{ \Carbon\Carbon::parse($lembur->tgllembur)->translatedFormat('d F Y') }}</strong></p>
            </div>

            <div class="section-title">Ambil Foto Absen Lembur</div>
            <input type="hidden" id="lokasi">

            <div class="webcam-capture">
                <video autoplay playsinline></video>
            </div>
        </div>
    </div>

    <!-- 🔄 Tombol Switch Kamera -->
    <div class="row mt-2">
        <div class="col">
            <button id="switchCamera" class="btn btn-secondary btn-block">
                <ion-icon name="camera-reverse-outline"></ion-icon> Ganti Kamera
            </button>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col">
            <div class="section-title">Lokasi Anda</div>
            <div id="map"></div>
        </div>
    </div>
</div>

<!-- Tombol Absen -->
<div class="sticky-absen-btn">
    <button id="lemburIn" class="btn btn-primary">
        <ion-icon name="camera-outline"></ion-icon> Lembur In
    </button>
    <button id="lemburOut" class="btn btn-danger">
        <ion-icon name="exit-outline"></ion-icon> Lembur Out
    </button>
</div>

<audio id="notif_sukses">
    <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
</audio>
@endsection


@push('myscript')
<script>
var notifSukses = document.getElementById('notif_sukses');
var lokasiAktif = false;
var currentStream = null;
var videoElement = document.querySelector('.webcam-capture video');

// 🔄 VAR utk switch kamera
var isBackCamera = true;

var map, userMarker, lockMarker, radiusCircle;

// 🎥 Inisialisasi Kamera (SUDAH DITAMBAHKAN facingMode)
function initCamera() {
    stopCamera();

    const constraints = {
        video: {
            facingMode: "user",
            width: { ideal: 640 },
            height: { ideal: 480 }
        }
    };

    navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            currentStream = stream;
            videoElement.srcObject = stream;
            videoElement.play();
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Tidak bisa mengakses kamera!', 'error');
        });
}

function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
}

// 🟢 EVENT SWITCH CAMERA
$("#switchCamera").click(function () {
    isBackCamera = !isBackCamera;
    initCamera();
});

// 🗺️ Inisialisasi Lokasi & Area Absensilock
function initMapAndLocation() {
    if (!navigator.geolocation) {
        Swal.fire('Error', 'Browser tidak mendukung geolocation', 'error');
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
        lokasiAktif = true;
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        $("#lokasi").val(lat + "," + lng);

        if (!map) {
            map = L.map('map').setView([lat, lng], 18);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        }
        if (userMarker) map.removeLayer(userMarker);

        userMarker = L.marker([lat, lng]).addTo(map).bindPopup("Lokasi Anda").openPopup();

        fetch("{{ route('hris.absensilock.get') }}")
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const [latLock, lngLock] = data.lokasi.split(',').map(Number);
                    const radius = parseInt(data.radius);

                    window.absensiLockLat = latLock;
                    window.absensiLockLng = lngLock;
                    window.absensiLockRadius = radius;

                    if (lockMarker) map.removeLayer(lockMarker);
                    if (radiusCircle) map.removeLayer(radiusCircle);

                    lockMarker = L.marker([latLock, lngLock]).addTo(map).bindPopup("Titik Area Lembur");
                    radiusCircle = L.circle([latLock, lngLock], {
                        color: 'blue',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.2,
                        radius: radius
                    }).addTo(map);

                    const group = L.featureGroup([userMarker, lockMarker, radiusCircle]);
                    map.fitBounds(group.getBounds());
                } else {
                    Swal.fire('Error', 'Gagal memuat titik area lembur.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Gagal mengambil data area lembur.', 'error'));
    }, () => {
        Swal.fire('Error', 'Tidak dapat mengambil lokasi. Aktifkan GPS Anda.', 'warning');
    });
}

// 📸 Proses Absen (Masuk / Keluar)
function prosesAbsen(mode) {
    if (!lokasiAktif) {
        Swal.fire('Lokasi Belum Aktif', 'Tunggu lokasi terdeteksi dahulu.', 'info');
        return;
    }

    const lokasi = $("#lokasi").val();
    const [latUser, lngUser] = lokasi.split(',').map(Number);

    if (!window.absensiLockLat || !window.absensiLockLng || !window.absensiLockRadius) {
        Swal.fire('Area Belum Siap', 'Titik area lembur belum dimuat. Coba refresh halaman.', 'warning');
        return;
    }

    const latLock = parseFloat(window.absensiLockLat);
    const lngLock = parseFloat(window.absensiLockLng);
    const radius = parseFloat(window.absensiLockRadius);

    const R = 6371e3;
    const φ1 = latUser * Math.PI / 180;
    const φ2 = latLock * Math.PI / 180;
    const Δφ = (latLock - latUser) * Math.PI / 180;
    const Δλ = (lngLock - lngUser) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) ** 2 +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) ** 2;

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const jarak = R * c;

    if (jarak > radius) {
        Swal.fire(
            'Di Luar Area!',
            'Anda berada ' + Math.round(jarak) + ' m dari titik area lembur.',
            'warning'
        );
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = videoElement.videoWidth;
    canvas.height = videoElement.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg');

    Swal.fire({
        title: 'Memproses...',
        html: 'Mengambil foto dan lokasi...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        type: 'POST',
        url: "{{ route('hris.lembur_absen_store') }}",
        data: {
            _token: "{{ csrf_token() }}",
            idlembur: "{{ $lembur->idlembur }}",
            mode: mode,
            lokasi: lokasi,
            image: imageData
        },
        dataType: 'json',
        success: function(res) {
            Swal.close();
            if (res.status === 'success') {
                notifSukses.play().catch(() => {});
                Swal.fire('Berhasil!', res.message, 'success');
                setTimeout(() => {
                    window.location.href = "{{ route('hris.jadwal_lembur') }}";
                }, 2500);
            } else {
                Swal.fire('Gagal!', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
        }
    });
}

// 🟢 Event Button
$("#lemburIn").click(() => prosesAbsen('in'));
$("#lemburOut").click(() => prosesAbsen('out'));

// 🚀 Jalankan Saat Halaman Siap
$(document).ready(function() {
    initCamera();
    initMapAndLocation();
});
</script>
@endpush
