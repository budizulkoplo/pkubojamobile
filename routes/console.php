<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\FcmService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('ngaji:shift-reminders', function (FcmService $fcm) {
    $now = now();
    $currentTime = $now->format('H:i');
    $today = $now->toDateString();
    $randomReminderTimes = ['05:00', '08:00', '12:00', '14:00', '18:00', '21:00'];
    $randomReminderMessages = [
        "Titipan Allah ada di tanganmu. Baca Qur'an, pahami, resapi.",
        "Mungkin ini ayat yang kamu butuhkan hari ini. Buka mushafmu 📖.",
        'Jangan biarkan hari berlalu tanpa menyapa Kalamullah.',
        'Tenang... Allah menyapamu lewat ayat-ayat-Nya. Ngaji yuk? ✨',
        'Hati gundah? Mungkin ia rindu disentuh ayat-ayat Allah. Yuk, mengaji sebentar 📖.',
        "Tanganmu lelah scrolling? Istirahatkan dengan memegang Al-Qur'an ❤️.",
        'Sudah Mengaji sampai mana hari ini?',
        'Sibuk banget, jeda ngaji sejenak yuk..',
    ];

    $shifts = DB::table('kelompokjam')
        ->whereRaw("TIME_FORMAT(jammasuk, '%H:%i') = ?", [$currentTime])
        ->get();

    $totalSent = 0;
    $randomSent = 0;

    if (in_array($currentTime, $randomReminderTimes, true)) {
        $scopeKey = 'all|' . $currentTime;

        $alreadySent = DB::table('notification_logs')
            ->where('type', 'ngaji_random_reminder')
            ->where('scope_key', $scopeKey)
            ->where('sent_date', $today)
            ->exists();

        if (! $alreadySent) {
            $message = $randomReminderMessages[array_rand($randomReminderMessages)];
            $tokens = DB::table('fcm_tokens')
                ->pluck('token')
                ->unique()
                ->all();

            $randomSent = $fcm->sendToTokens(
                $tokens,
                'Pengingat Ngaji',
                $message,
                [
                    'url' => route('operan.ngaji'),
                    'type' => 'ngaji_random_reminder',
                    'time' => $currentTime,
                ]
            );

            DB::table('notification_logs')->insert([
                'type' => 'ngaji_random_reminder',
                'scope_key' => $scopeKey,
                'sent_date' => $today,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    foreach ($shifts as $shift) {
        $scopeKey = ($shift->bagian ?? '-') . '|' . ($shift->shift ?? '-') . '|' . $currentTime;

        $alreadySent = DB::table('notification_logs')
            ->where('type', 'ngaji_shift_reminder')
            ->where('scope_key', $scopeKey)
            ->where('sent_date', $today)
            ->exists();

        if ($alreadySent) {
            continue;
        }

        $tokens = DB::table('fcm_tokens as ft')
            ->join('kelompok_kerja as kk', 'kk.nik', '=', 'ft.nik')
            ->where('kk.namakelompok', $shift->bagian)
            ->pluck('ft.token')
            ->unique()
            ->all();

        $sent = $fcm->sendToTokens(
            $tokens,
            'Waktunya Ngaji Shift',
            'Shift ' . $shift->shift . ' ' . $shift->bagian . ' sudah dimulai. Jangan lupa lanjutkan Ngaji Shift.',
            [
                'url' => route('operan.ngaji'),
                'type' => 'ngaji_shift_reminder',
                'bagian' => $shift->bagian,
                'shift' => $shift->shift,
            ]
        );

        DB::table('notification_logs')->insert([
            'type' => 'ngaji_shift_reminder',
            'scope_key' => $scopeKey,
            'sent_date' => $today,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $totalSent += $sent;
    }

    $this->info("Shift reminders sent to {$totalSent} device(s). Random reminders sent to {$randomSent} device(s).");
})->purpose('Send Ngaji Shift reminders when shift starts');
