<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\FcmService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('ngaji:shift-reminders {--time= : Simulasikan waktu HH:MM} {--force-random : Paksa kirim pengingat acak untuk test}', function (FcmService $fcm) {
    $now = now();
    $timeOption = $this->option('time');
    $currentTime = $timeOption ?: $now->format('H:i');

    if (! preg_match('/^\d{2}:\d{2}$/', $currentTime)) {
        $this->error('Format --time harus HH:MM, contoh: --time=08:00');
        return 1;
    }

    $today = $now->toDateString();
    $forceRandom = (bool) $this->option('force-random');
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
    $randomTokenCount = 0;
    $randomSkippedReason = null;

    if ($forceRandom || in_array($currentTime, $randomReminderTimes, true)) {
        $scopeKey = 'all|' . $currentTime;

        $alreadySent = ! $forceRandom && DB::table('notification_logs')
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
            $randomTokenCount = count($tokens);

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

            if (! $forceRandom) {
                DB::table('notification_logs')->insert([
                    'type' => 'ngaji_random_reminder',
                    'scope_key' => $scopeKey,
                    'sent_date' => $today,
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $randomSkippedReason = 'sudah pernah dikirim hari ini untuk jam ' . $currentTime;
        }
    } else {
        $randomSkippedReason = 'jam ' . $currentTime . ' bukan jadwal pengingat acak';
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

    $this->info("Checked time {$currentTime}.");
    $this->info("Shift reminders sent to {$totalSent} device(s). Random reminders sent to {$randomSent} device(s).");

    if ($randomSkippedReason) {
        $this->comment('Random reminder skipped: ' . $randomSkippedReason . '.');
    } else {
        $this->comment("Random reminder token count: {$randomTokenCount}.");
    }
})->purpose('Send Ngaji Shift reminders when shift starts');
