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

    $shifts = DB::table('kelompokjam')
        ->whereRaw("TIME_FORMAT(jammasuk, '%H:%i') = ?", [$currentTime])
        ->get();

    $totalSent = 0;

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

    $this->info("Shift reminders sent to {$totalSent} device(s).");
})->purpose('Send Ngaji Shift reminders when shift starts');
