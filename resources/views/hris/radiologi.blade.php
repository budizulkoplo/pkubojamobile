@extends('layouts.presensi')

@section('header')
    <!-- App Header -->
    <div class="appHeader bg-primary text-light">
        <div class="left">
            <a href="javascript:;" class="headerButton goBack">
                <ion-icon name="chevron-back-outline"></ion-icon>
            </a>
        </div>
        <div class="pageTitle">Presensi Radiologi</div>
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
            margin-top: 10px;
        }

        .small-btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        .presensi-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .section-title {
            font-weight: bold;
            margin: 15px 0 10px 0;
            font-size: 16px;
            color: #333;
        }

        .validation-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .scroll-down-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 1000;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #3880ff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .sticky-absen-btn {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background-color: white;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .content-wrapper {
            padding-bottom: 80px; /* Space for sticky button */
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endsection

@section('content')
<div class="content-wrapper">
    <div class="row" style="margin-top: 70px">
        <div class="col">
            <div class="presensi-form">
                <div class="form-group">
                    <label class="form-label" for="kegiatan">Kegiatan*</label>
                    <input type="text" class="form-control" id="kegiatan" placeholder="Contoh: Absen harian">
                    <div class="validation-message" id="kegiatan-error">Deskripsi kegiatan harus diisi</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="section-title">Ambil Foto Presensi</div>
            <input type="hidden" id="lokasi">
            <div class="webcam-capture">
                <video autoplay playsinline></video>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="button-container">
                <button id="switchCamera" class="btn btn-secondary btn-block">
                    <ion-icon name="camera-reverse-outline"></ion-icon> Switch Camera
                </button>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col">
            <div class="section-title">Lokasi Anda</div>
            <div id="map"></div>
        </div>
    </div>

    <!-- Scroll down button -->
    <button id="scrollDownBtn" class="scroll-down-btn">
        <ion-icon name="chevron-down-outline"></ion-icon>
    </button>
</div>

<!-- Sticky Absen Button -->
<div class="sticky-absen-btn">
    <button id="takeabsen" class="btn btn-primary btn-block">
        <ion-icon name="camera-outline"></ion-icon>
        Absen Masuk
    </button>
</div>

<audio id="notifikasi_in">
    <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
</audio>
@endsection

@push('myscript')
<script>
    var notifikasi_in = document.getElementById('notifikasi_in');
    var lokasiAktif = false;
    var currentStream = null;
    var isBackCamera = false;
    var videoElement = document.querySelector('.webcam-capture video');
    
    // Inisialisasi Webcam
    function initializeWebcam() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            stopCamera();
            
            const constraints = {
                video: {
                    facingMode: isBackCamera ? 'environment' : 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            };
            
            navigator.mediaDevices.getUserMedia(constraints)
                .then(function(stream) {
                    currentStream = stream;
                    videoElement.srcObject = stream;
                    videoElement.play();
                })
                .catch(function(error) {
                    console.error('Error accessing camera:', error);
                    Swal.fire({
                        title: 'Kamera Error',
                        text: 'Gagal mengakses kamera. Pastikan aplikasi memiliki izin kamera.',
                        icon: 'error'
                    });
                });
        }
    }
    
    // Stop camera stream
    function stopCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => {
                track.stop();
            });
            currentStream = null;
        }
    }
    
    // Switch Camera
    document.getElementById('switchCamera').addEventListener('click', function() {
        isBackCamera = !isBackCamera;
        initializeWebcam();
    });

    // Geolocation
    function initializeLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            });
        } else {
            Swal.fire({
                title: 'Browser Tidak Support',
                text: 'Browser Anda tidak mendukung geolocation',
                icon: 'error'
            });
        }
    }
    
    function successCallback(position) {
        lokasiAktif = true;
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        $("#lokasi").val(lat + "," + lng);
        
        // Initialize Map
        var map = L.map('map').setView([lat, lng], 18);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        
        // Add marker
        var marker = L.marker([lat, lng]).addTo(map)
            .bindPopup("Lokasi Anda saat ini")
            .openPopup();
        
        // Add circle (area presensi)
        var circle = L.circle([-7.106116792046282, 110.28551902710417], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: 30,
        }).addTo(map).bindPopup("Area Presensi");
    }
    
    function errorCallback(error) {
        let errorMessage = '';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = "Pengguna menolak permintaan geolokasi.";
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = "Informasi lokasi tidak tersedia.";
                break;
            case error.TIMEOUT:
                errorMessage = "Permintaan lokasi pengguna habis waktunya.";
                break;
            case error.UNKNOWN_ERROR:
                errorMessage = "Terjadi kesalahan yang tidak diketahui.";
                break;
        }
        
        Swal.fire({
            title: 'Lokasi Error',
            text: errorMessage,
            icon: 'warning',
            confirmButtonText: 'Coba Lagi',
            showCancelButton: true,
            cancelButtonText: 'Lanjutkan Tanpa Lokasi'
        }).then((result) => {
            if (result.isConfirmed) {
                initializeLocation();
            } else {
                $("#lokasi").val("0,0");
            }
        });
    }

    // Validasi form dengan SweetAlert
    function validateForm() {
        const kegiatan = $("#kegiatan").val().trim();
        let errors = [];
        
        if (kegiatan === "") {
            errors.push("kegiatan harus diisi");
        }
        
        if (errors.length > 0) {
            Swal.fire({
                title: 'Form Tidak Lengkap',
                html: errors.join('<br>'),
                icon: 'warning',
                confirmButtonText: 'Mengerti'
            });
            return false;
        }
        
        return true;
    }

    // Take Absen
    $("#takeabsen").click(function(e) {
        e.preventDefault();
        
        // Validasi form
        if (!validateForm()) {
            return;
        }
        
        if (!lokasiAktif) {
            Swal.fire({
                title: 'Lokasi Belum Aktif',
                text: 'Sedang mencoba mengaktifkan lokasi...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            // Coba dapatkan lokasi sekali lagi
            initializeLocation();
            return;
        }
        
        Swal.fire({
            title: 'Sedang Memproses',
            html: 'Mengambil foto dan lokasi...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Ambil foto dari webcam
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        canvas.getContext('2d').drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        
        const imageData = canvas.toDataURL('image/jpeg');
        const lokasi = $("#lokasi").val();
        const kegiatan = $("#kegiatan").val().trim();
        
        $.ajax({
            type: 'POST',
            url: 'radiologi/store',
            data: {
                _token: "{{ csrf_token() }}",
                image: imageData,
                lokasi: lokasi,
                kegiatan: kegiatan,

            },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.status == "success") {
                    if (response.type == "in") {
                        notifikasi_in.play().catch(e => console.log("Error sound:", e));
                    }
                    
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message,
                        icon: 'success',
                        timer: 5000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '/dashboard';
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan pada server',
                    icon: 'error'
                });
                console.error(xhr.responseText);
            }
        });
    });

    // Scroll down button functionality
    document.getElementById('scrollDownBtn').addEventListener('click', function() {
        window.scrollTo({
            top: document.body.scrollHeight,
            behavior: 'smooth'
        });
    });

    // Show/hide scroll down button based on scroll position
    window.addEventListener('scroll', function() {
        const scrollBtn = document.getElementById('scrollDownBtn');
        if (window.scrollY > 100) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });

    // Initialize on page load
    $(document).ready(function() {
        initializeWebcam();
        initializeLocation();
    });
</script>
@endpush