<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function firebaseMessagingSw()
    {
        $config = collect(config('firebase.web'))
            ->except('vapidKey')
            ->filter()
            ->all();

        $js = 'importScripts("/assets/js/firebase/firebase-app-compat.js");' . "\n"
            . 'importScripts("/assets/js/firebase/firebase-messaging-compat.js");' . "\n"
            . 'firebase.initializeApp(' . json_encode($config, JSON_UNESCAPED_SLASHES) . ');' . "\n"
            . 'try {' . "\n"
            . '  var messaging = firebase.messaging();' . "\n"
            . '  messaging.onBackgroundMessage(function (payload) {' . "\n"
            . '    payload = payload || {};' . "\n"
            . '    var notification = payload.notification || {};' . "\n"
            . '    var data = payload.data || {};' . "\n"
            . '    var title = notification.title || data.title || "HRIS";' . "\n"
            . '    var options = {' . "\n"
            . '      body: notification.body || data.body || "",' . "\n"
            . '      icon: "/assets/img/icon/logo.png",' . "\n"
            . '      data: { url: data.url || "/dashboard" }' . "\n"
            . '    };' . "\n"
            . '    self.registration.showNotification(title, options);' . "\n"
            . '  });' . "\n"
            . '} catch (error) {' . "\n"
            . '  console.warn("FCM background handler failed.", error);' . "\n"
            . '}' . "\n"
            . 'self.addEventListener("notificationclick", function (event) {' . "\n"
            . '  var url = "/dashboard";' . "\n"
            . '  if (event.notification && event.notification.data && event.notification.data.url) {' . "\n"
            . '    url = event.notification.data.url;' . "\n"
            . '  }' . "\n"
            . '  event.notification.close();' . "\n"
            . '  event.waitUntil(clients.openWindow(url));' . "\n"
            . '});';

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function storeToken(Request $request)
    {
        $user = Auth::guard('karyawan')->user();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        DB::table('fcm_tokens')->updateOrInsert(
            ['token' => $validated['token']],
            [
                'nik' => $user->nik,
                'pegawai_pin' => $user->id,
                'platform' => $validated['platform'] ?? 'web',
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Log::info('FCM token tersimpan.', [
            'nik' => $user->nik,
            'platform' => $validated['platform'] ?? 'web',
        ]);

        return response()->json(['success' => true]);
    }
}
