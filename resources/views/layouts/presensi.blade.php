<!doctype html>
<html lang="en">
<head>
    <!-- Meta dan Konfigurasi Dasar -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#000000">

    <title>HRIS PKU Boja</title>
    <meta name="description" content="HRIS PKU Boja Mobile App">
    <meta name="keywords" content="HRIS, PKU Boja, mobile app, progressive web app">

    <!-- Favicon dan Icon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/192x192.png') }}">

    <!-- CSS utama dengan cache-busting -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">

    <!-- Manifest untuk PWA -->
    <link rel="manifest" href="{{ url('manifest.json') }}">

    <!-- Register Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register("{{ url('service-worker.js') }}")
                    .then(reg => console.log('✅ Service Worker registered:', reg))
                    .catch(err => console.error('❌ SW registration failed:', err));
            });
        }
    </script>
</head>

<body>
    <!-- Loader Global -->
    <div id="loader-wrapper" style="
        display:none;
        position:fixed;
        top:0; left:0;
        width:100%; height:100%;
        background:rgba(255,255,255,0.7);
        z-index:9999;
        justify-content:center;
        align-items:center;">
        <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;"></div>
    </div>

    <!-- Tombol Install PWA -->
    <button id="installButton" style="display:none;" class="floating-install-button">
        <ion-icon name="download-outline"></ion-icon>
    </button>
    <button id="enableNotificationButton" style="display:none;" class="floating-install-button enable-notification-button">
        <ion-icon name="notifications-outline"></ion-icon>
    </button>

    <!-- Header dinamis -->
    @yield('header')

    <!-- Konten Utama -->
    <div id="appCapsule">
        @yield('content')
    </div>

    <!-- Bottom Navigation -->
    {{-- Hanya tampilkan bottomNav jika bukan halaman presensi/create atau lembur --}}
    @if (!request()->is('hris/lembur/create/*') && !request()->is('hris/lembur/ajukan') && !request()->is('hris/lembur/absen/*') && !request()->is('hris/operasi/*') && !request()->is('hris/radiologi'))
        @include('layouts.bottomNav')
    @endif

    <!-- Script JS -->
    @include('layouts.script')

    <!-- Loader Logic -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const loader = document.getElementById('loader-wrapper');
        let loaderActive = false;

        // Fungsi tampilkan loader (sekali saja)
        const showLoader = () => {
            if (!loaderActive && loader) {
                loaderActive = true;
                loader.style.display = 'flex';
            }
        };

        // Fungsi sembunyikan loader
        const hideLoader = () => {
            if (loader) {
                loader.style.display = 'none';
                loaderActive = false;
            }
        };

        // Cegah loader muncul di halaman cache (back-forward cache)
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) hideLoader();
            else hideLoader(); // tetap sembunyikan di semua kasus
        });

        // Loader hanya saat benar-benar pindah halaman
        document.body.addEventListener('click', (e) => {
            const el = e.target.closest('a, button[type=submit], input[type=submit]');
            if (!el) return;

            const href = el.getAttribute('href') ?? '';
            if (el.tagName === 'A' && (
                href.startsWith('#') || 
                href.startsWith('javascript:') || 
                href === '' || 
                el.target === '_blank'
            )) return;

            showLoader();
        });

        // Loader saat form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', showLoader);
        });

        // Pastikan loader tertutup setelah load penuh
        window.addEventListener('load', hideLoader);
    });
    </script>

    @auth('karyawan')
    @php($firebaseWeb = config('firebase.web'))
    @if(!empty($firebaseWeb['apiKey']) && !empty($firebaseWeb['projectId']) && !empty($firebaseWeb['messagingSenderId']) && !empty($firebaseWeb['appId']) && !empty($firebaseWeb['vapidKey']))
    <script src="{{ asset('assets/js/firebase/firebase-app-compat.js') }}"></script>
    <script src="{{ asset('assets/js/firebase/firebase-messaging-compat.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const enableNotificationButton = document.getElementById('enableNotificationButton');

            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                return;
            }

            const firebaseConfig = @json(collect($firebaseWeb)->except('vapidKey')->filter()->all());
            const vapidKey = @json($firebaseWeb['vapidKey']);

            const getBase64UrlByteLength = (value) => {
                const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
                const padded = normalized + '='.repeat((4 - normalized.length % 4) % 4);
                return atob(padded).length;
            };

            const saveFcmToken = async () => {
                if (!window.isSecureContext) {
                    throw new Error('FCM membutuhkan HTTPS.');
                }

                if (getBase64UrlByteLength(vapidKey) !== 65) {
                    throw new Error('VAPID Key Firebase belum valid. Salin ulang Key pair dari Firebase Cloud Messaging > Web Push certificates.');
                }

                if (!firebase.apps.length) {
                    firebase.initializeApp(firebaseConfig);
                }

                const messaging = firebase.messaging();
                const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
                    scope: '/firebase-cloud-messaging-push-scope',
                    updateViaCache: 'none'
                });
                const token = await messaging.getToken({ vapidKey, serviceWorkerRegistration: registration });

                if (!token) {
                    throw new Error('Firebase tidak mengembalikan token.');
                }

                const response = await fetch("/notifications/fcm-token", {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        token,
                        platform: 'web'
                    })
                });

                if (!response.ok) {
                    throw new Error(`Gagal menyimpan token FCM. Status ${response.status}`);
                }

                enableNotificationButton.style.display = 'none';

                messaging.onMessage((payload) => {
                    const title = payload.notification?.title || payload.data?.title || 'HRIS';
                    const body = payload.notification?.body || payload.data?.body || '';
                    if (document.visibilityState === 'visible') {
                        console.log(title, body);
                    }
                });
            };

            const requestAndSaveToken = async () => {
                if (Notification.permission === 'default') {
                    await Notification.requestPermission();
                }

                if (Notification.permission === 'denied') {
                    throw new Error('Izin notifikasi masih diblokir. Buka pengaturan situs browser, lalu ubah Notifications menjadi Allow.');
                }

                if (Notification.permission !== 'granted') {
                    enableNotificationButton.style.display = 'none';
                    return;
                }

                await saveFcmToken();
            };

            enableNotificationButton.addEventListener('click', async () => {
                try {
                    await requestAndSaveToken();
                } catch (error) {
                    console.warn('FCM registration failed.', error);
                    alert(error.message || 'Gagal mengaktifkan notifikasi.');
                }
            });

            if (Notification.permission === 'granted') {
                requestAndSaveToken().catch((error) => console.warn('FCM registration failed.', error));
            } else if (Notification.permission === 'default' || Notification.permission === 'denied') {
                enableNotificationButton.style.display = 'inline-block';
            }
        });
    </script>
    @endif
    @endauth

    <!-- Install PWA Logic -->
    <script>
        let deferredPrompt;
        const installButton = document.getElementById('installButton');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installButton.style.display = 'inline-block';
        });

        installButton.addEventListener('click', () => {
            installButton.style.display = 'none';
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choice) => {
                console.log('Install prompt result:', choice.outcome);
                deferredPrompt = null;
            });
        });

        window.addEventListener('appinstalled', () => {
            installButton.style.display = 'none';
            console.log('✅ App successfully installed');
        });
    </script>
</body>
</html>
