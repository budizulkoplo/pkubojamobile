<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

use App\Services\FcmService;

/*
|--------------------------------------------------------------------------
| NGAJI SHIFT REMINDER COMMAND
|--------------------------------------------------------------------------
*/

Artisan::command(
    'ngaji:shift-reminders 
    {--time= : Simulasikan waktu HH:MM}
    {--force-random : Paksa kirim pengingat acak untuk test}',

    function (FcmService $fcm) {

        $startedAt = microtime(true);

        $now = now();

        $timeOption = $this->option('time');

        $currentTime = $timeOption ?: $now->format('H:i');

        /*
        |--------------------------------------------------------------------------
        | Validate Time Format
        |--------------------------------------------------------------------------
        */

        if (!preg_match('/^\d{2}:\d{2}$/', $currentTime)) {

            $this->error('Format --time harus HH:MM');

            return 1;
        }

        $today = $now->toDateString();

        $forceRandom = (bool) $this->option('force-random');

        /*
        |--------------------------------------------------------------------------
        | Random Reminder Times
        |--------------------------------------------------------------------------
        */

        $randomReminderTimes = [
            '03:00', // Tahajud reminder
            '05:00',
            '08:00',
            '12:00',
            '14:00',
            '18:00',
            '21:00',
        ];

        /*
        |--------------------------------------------------------------------------
        | Random Reminder Messages
        |--------------------------------------------------------------------------
        */

        $randomReminderMessages = [

            // Tahajud khusus jam 03:00
            '03:00' => [
                'Ketika ikhtiarmu di siang hari belum menemukan jalan keluar, biarkan sujud Tahajud di sepertiga malam yang membukakan pintunya. Yuk, bangun dan jemput keajaiban malam!',
            ],

            // General random reminders
            'default' => [

                "Titipan Allah ada di tanganmu. Baca Qur'an, pahami, resapi.",

                "Mungkin ini ayat yang kamu butuhkan hari ini. Buka mushafmu 📖.",

                'Jangan biarkan hari berlalu tanpa menyapa Kalamullah.',

                'Tenang... Allah menyapamu lewat ayat-ayat-Nya. Ngaji yuk? ✨',

                'Hati gundah? Mungkin ia rindu disentuh ayat-ayat Allah. Yuk, mengaji sebentar 📖.',

                "Tanganmu lelah scrolling? Istirahatkan dengan memegang Al-Qur'an ❤️.",

                'Sudah Mengaji sampai mana hari ini?',

                'Sibuk banget, jeda ngaji sejenak yuk..',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Shift Query
        |--------------------------------------------------------------------------
        */

        $shifts = DB::table('kelompokjam')
            ->whereRaw("TIME_FORMAT(jammasuk, '%H:%i') = ?", [$currentTime])
            ->get();

        $totalSent = 0;

        $randomSent = 0;

        $randomTokenCount = 0;

        $randomSkippedReason = null;

        /*
        |--------------------------------------------------------------------------
        | RANDOM REMINDER
        |--------------------------------------------------------------------------
        */

        if ($forceRandom || in_array($currentTime, $randomReminderTimes, true)) {

            $scopeKey = 'all|' . $currentTime;

            $alreadySent = !$forceRandom && DB::table('notification_logs')
                ->where('type', 'ngaji_random_reminder')
                ->where('scope_key', $scopeKey)
                ->where('sent_date', $today)
                ->exists();

            if (!$alreadySent) {

                /*
                |--------------------------------------------------------------------------
                | Select Message
                |--------------------------------------------------------------------------
                */

                if (isset($randomReminderMessages[$currentTime])) {

                    $messagePool = $randomReminderMessages[$currentTime];

                } else {

                    $messagePool = $randomReminderMessages['default'];
                }

                $message = $messagePool[array_rand($messagePool)];

                /*
                |--------------------------------------------------------------------------
                | Get Tokens
                |--------------------------------------------------------------------------
                */

                $tokens = DB::table('fcm_tokens')
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $randomTokenCount = count($tokens);

                /*
                |--------------------------------------------------------------------------
                | Send Notification
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Save Log
                |--------------------------------------------------------------------------
                */

                if (!$forceRandom) {

                    DB::table('notification_logs')->insert([
                        'type' => 'ngaji_random_reminder',
                        'scope_key' => $scopeKey,
                        'sent_date' => $today,
                        'sent_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info('Random ngaji reminder sent.', [
                    'time' => $currentTime,
                    'tokens' => $randomTokenCount,
                    'sent' => $randomSent,
                ]);

            } else {

                $randomSkippedReason =
                    'sudah pernah dikirim hari ini untuk jam ' . $currentTime;
            }

        } else {

            $randomSkippedReason =
                'jam ' . $currentTime . ' bukan jadwal pengingat acak';
        }

        /*
        |--------------------------------------------------------------------------
        | SHIFT REMINDERS
        |--------------------------------------------------------------------------
        */

        foreach ($shifts as $shift) {

            $scopeKey =
                ($shift->bagian ?? '-') . '|' .
                ($shift->shift ?? '-') . '|' .
                $currentTime;

            $alreadySent = DB::table('notification_logs')
                ->where('type', 'ngaji_shift_reminder')
                ->where('scope_key', $scopeKey)
                ->where('sent_date', $today)
                ->exists();

            if ($alreadySent) {

                Log::info('Shift reminder skipped.', [
                    'scope_key' => $scopeKey,
                ]);

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Get Tokens By Shift
            |--------------------------------------------------------------------------
            */

            $tokens = DB::table('fcm_tokens as ft')
                ->join('kelompok_kerja as kk', 'kk.nik', '=', 'ft.nik')
                ->where('kk.namakelompok', $shift->bagian)
                ->pluck('ft.token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            /*
            |--------------------------------------------------------------------------
            | Send Shift Notification
            |--------------------------------------------------------------------------
            */

            $sent = $fcm->sendToTokens(
                $tokens,
                'Waktunya Ngaji Shift',
                'Shift ' . $shift->shift . ' ' . $shift->bagian .
                ' sudah dimulai. Jangan lupa lanjutkan Ngaji Shift.',
                [
                    'url' => route('operan.ngaji'),
                    'type' => 'ngaji_shift_reminder',
                    'bagian' => $shift->bagian,
                    'shift' => $shift->shift,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Save Log
            |--------------------------------------------------------------------------
            */

            DB::table('notification_logs')->insert([
                'type' => 'ngaji_shift_reminder',
                'scope_key' => $scopeKey,
                'sent_date' => $today,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalSent += $sent;

            Log::info('Shift reminder sent.', [
                'bagian' => $shift->bagian,
                'shift' => $shift->shift,
                'tokens' => count($tokens),
                'sent' => $sent,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Final Output
        |--------------------------------------------------------------------------
        */

        $duration = round(microtime(true) - $startedAt, 2);

        $this->info("Checked time {$currentTime}");

        $this->info(
            "Shift reminders sent: {$totalSent} device(s)"
        );

        $this->info(
            "Random reminders sent: {$randomSent} device(s)"
        );

        if ($randomSkippedReason) {

            $this->comment(
                'Random reminder skipped: ' .
                $randomSkippedReason
            );

        } else {

            $this->comment(
                "Random reminder token count: {$randomTokenCount}"
            );
        }

        $this->comment("Execution time: {$duration}s");

        Log::info('Ngaji reminder command finished.', [
            'time' => $currentTime,
            'duration_seconds' => $duration,
            'shift_sent' => $totalSent,
            'random_sent' => $randomSent,
        ]);
    }

)->purpose('Send Ngaji Shift reminders when shift starts');


/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
*/

Schedule::command('ngaji:shift-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ngaji-reminder.log'));
