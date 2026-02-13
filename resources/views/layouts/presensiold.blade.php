<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">
    <title>HRIS PKU Boja</title>
    <meta name="description" content="Mobilekit HTML Mobile UI Kit">
    <meta name="keywords" content="bootstrap 4, mobile template, cordova, phonegap, mobile, html" />
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/192x192.png') }}">
    <!-- <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
    <link rel="manifest" href="/manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register("{{ url('service-worker.js') }}");
            });
        }
</script>

    <!-- css install -->
     <style>
    .floating-install-button {
        position: fixed;
        bottom: 80px;
        right: 20px;
        background-color:rgb(13, 199, 69);
        color: white;
        border: none;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        z-index: 999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    </style>

    <style>
    /* Hindari content meloncat saat scroll */
    html, body {
        height: 100%;
        overflow-y: auto;
        scroll-behavior: smooth;
        overflow-anchor: none;
        -webkit-overflow-scrolling: touch;
    }

    /* Perbaiki posisi menu bawah */
    .quickMenuBar {
        position: fixed !important;
        bottom: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: #fff;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-around;
        align-items: center;
        z-index: 9999;
        transform: translateZ(0);
        will-change: transform;
        transition: bottom 0.2s ease-in-out;
        backface-visibility: hidden;
    }

    /* Pastikan ada ruang di bawah content */
    #appCapsule {
        padding-bottom: 20px !important;
        position: relative;
    }

    /* Tambahan pengaman untuk semua isi */
    main, .container, .col {
        overflow-anchor: none;
    }
</style>


</head>

<body>

    <!-- Loader Wrapper -->
    <div id="loader-wrapper" style="display: none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:9999; justify-content:center; align-items:center;">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
    </div>
    <!-- tombol install nya bro-->
    <button id="installButton" style="display:none;" class="floating-install-button">
        <ion-icon name="download-outline"></ion-icon>
    </button>

    @yield('header')

    <!-- App Capsule -->
    <div id="appCapsule">
        @yield('content')
    </div>
    <!-- * App Capsule -->

    @if (!Request::is('presensi/create'))
        @include('layouts.bottomNav')
    @endif


    @include('layouts.script')
    
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loader = document.getElementById('loader-wrapper');

        // Tampilkan loader saat klik <a>, <button>, atau <input type="submit">
        document.querySelectorAll('a, button[type=submit], input[type=submit]').forEach(el => {
            el.addEventListener('click', function (e) {
                const href = el.getAttribute('href') ?? '';
                if (el.tagName === 'A' && (href.startsWith('http') || href.startsWith('#') || href === 'javascript:;')) {
                    return;
                }
                if (loader) loader.style.display = 'flex';
            });
        });

        // Tambahkan juga untuk event submit form
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function () {
                if (loader) loader.style.display = 'flex';
            });
        });
    });

    // Sembunyikan loader saat kembali dari cache (misalnya back button)
    window.addEventListener('pageshow', function (event) {
        const loader = document.getElementById('loader-wrapper');
        if (loader) loader.style.display = 'none';
    });
</script>

<script>
    let deferredPrompt;
    const installButton = document.getElementById('installButton');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Mencegah dialog default muncul
        e.preventDefault();
        deferredPrompt = e;

        // Tampilkan tombol install
        installButton.style.display = 'inline-block';

        installButton.addEventListener('click', () => {
            installButton.style.display = 'none';
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        });
    });

    // Jika sudah terinstall, sembunyikan tombol
    window.addEventListener('appinstalled', () => {
        installButton.style.display = 'none';
        console.log('App successfully installed');
    });
</script>


</body>

</html>


