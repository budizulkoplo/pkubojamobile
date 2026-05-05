@extends('layouts.presensi')
@section('header')
    <!-- App Header -->
    <div class="appHeader bg-primary text-light">
        <div class="left">
            <a href="javascript:;" class="headerButton goBack">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </a>
        </div>
        <div class="pageTitle">Absensi Ahad Pagi</div>
        <div class="right"></div>
    </div>
    <!-- * App Header -->
     <style>
        .webcam-capture,
        .webcam-capture video {
            display: inline-block;
            width: 100% !important;
            margin: auto;
            height: auto !important;
            border-radius: 15px;
        }

        #map {
             height: 200px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .small-btn {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<div class="row" style="margin-top: 70px">
    <div class="col">
        <input type="hidden" id="lokasi">
        <div class="webcam-capture"></div>
    </div>
 </div>
 <div class="row">
    <div class="col">
        <div class="button-container">
            <button id="takeabsen" class="btn btn-primary btn-block">
                <ion-icon name="camera-outline"></ion-icon>
                Absen Datang
            </button>
            <button id="switchCamera" class="btn btn-secondary small-btn">
                <ion-icon name="refresh-outline"></ion-icon> Mirror
            </button>
        </div>
    </div>
</div>
<div class="row mt-2">
    <div class="col">
        <div id="map"></div>
    </div>
</div>

<audio id="notifikasi_in">
    <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
</audio>
@endsection

@push('myscript')
<script>
   var notifikasi_in = document.getElementById('notifikasi_in');
    Webcam.set({
        height: 480,
        width: 640,
        image_format: 'jpeg',
        jpeg_quality: 80
    });
    
    Webcam.attach('.webcam-capture');
    
    var webcamVideo = document.querySelector('.webcam-capture video');
    var isBackCamera = false;
    var currentStream;
    
    function startCamera(deviceId) {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }
    
        navigator.mediaDevices.getUserMedia({
            video: { deviceId: { exact: deviceId } }
        }).then(function(stream) {
            webcamVideo.srcObject = stream;
            currentStream = stream; 
        }).catch(function(error) {
            console.error('Failed to access camera:', error);
        });
    }
    
    function switchToBackCamera() {
        navigator.mediaDevices.enumerateDevices().then(devices => {
            devices.forEach(device => {
                if (device.kind === 'videoinput' && device.label.toLowerCase().includes('back')) {
                    isBackCamera = true;
                    startCamera(device.deviceId);
                }
            });
        }).catch(error => {
            console.error('Error getting camera devices:', error);
        });
    }
    
    function switchToFrontCamera() {
        navigator.mediaDevices.enumerateDevices().then(devices => {
            devices.forEach(device => {
                if (device.kind === 'videoinput' && device.label.toLowerCase().includes('front')) {
                    isBackCamera = false;
                    startCamera(device.deviceId); 
                }
            });
        }).catch(error => {
            console.error('Error getting camera devices:', error);
        });
    }
    
    document.getElementById('switchCamera').addEventListener('click', function() {
        if (isBackCamera) {
            switchToFrontCamera();
        } else {
            switchToBackCamera();
        }
    });
    
    navigator.mediaDevices.enumerateDevices().then(devices => {
        devices.forEach(device => {
            if (device.kind === 'videoinput' && device.label.toLowerCase().includes('front')) {
                startCamera(device.deviceId);
            }
        });
    });

    // Inisialisasi lokasi dan map
    var lokasiAktif = false; 

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
    }

    function successCallback(position) {
        lokasiAktif = true;  // Berhasil aktif
        lokasi.value = position.coords.latitude + "," + position.coords.longitude;
        var map = L.map('map').setView([position.coords.latitude, position.coords.longitude], 18);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        var marker = L.marker([position.coords.latitude, position.coords.longitude]).addTo(map);
        var circle = L.circle([-7.106116792046282, 110.28551902710417], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: 30,
        }).addTo(map);
    }

    function errorCallback() {
        Swal.fire({
            title: 'Aktifkan Lokasi Anda!',
            text: 'Mohon aktifkan fitur lokasi di perangkat Anda agar dapat melanjutkan absensi.',
            icon: 'warning',
            confirmButtonText: 'Aktifkan Lokasi',
        }).then(function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
            } else {
                Swal.fire({
                    title: 'Lokasi Tidak Tersedia',
                    text: 'Lokasi tidak dapat diakses. Pastikan GPS perangkat Anda aktif.',
                    icon: 'error',
                });
            }
        });
    }

    // function take foto dan absen
    $("#takeabsen").click(function(e) {
        if (!lokasiAktif) {
            Swal.fire({
                title: 'Lokasi belum diaktifkan!',
                text: 'Mohon aktifkan layanan lokasi Anda terlebih dahulu sebelum melakukan absen.',
                icon: 'warning',
            });
            return;  // Jangan lanjutkan jika lokasi tidak aktif
        }

        // Melanjutkan proses absen jika lokasi sudah aktif
        Webcam.snap(function(uri) {
            image = uri;
        });
        var lokasi = $("#lokasi").val();
        $.ajax({
            type: 'POST',
            url: '/presensi/store',
            data: {
                _token: "{{ csrf_token() }}",
                image: image,
                lokasi: lokasi,
            },
            cache: false,
            success: function(respond) {
                if (respond.status == "success") {
                    if (respond.type == "in") {
                        notifikasi_in.play().catch(function(error) {
                            console.log("Gagal memutar suara: ", error);
                        });
                    }

                    Swal.fire({
                        title: 'Berhasil !',
                        text: 'Terimakasih, Selamat Melanjutkan Aktivitas Anda',
                        icon: 'success',
                    });
                    setTimeout(function() {
                        location.href = '/dashboard';
                    }, 3000);
                } else {
                    Swal.fire({
                        title: 'Error !',
                        text: respond.message,
                        icon: 'error',
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    title: 'Error !',
                    text: 'Terjadi kesalahan saat menghubungi server.',
                    icon: 'error',
                });
            }
        });
    });

</script>
@endpush
