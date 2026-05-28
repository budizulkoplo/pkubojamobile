<?php

namespace App\Libraries;

class PayrollLateInfaq
{
    public static function getRate(int $lateMinutes): float
    {
        if ($lateMinutes <= 0) {
            return 0;
        }

        if ($lateMinutes < 11) {
            return 0;
        }

        if ($lateMinutes < 30) {
            return 0.06;
        }

        if ($lateMinutes <= 60) {
            return 0.12;
        }

        if ($lateMinutes <= 90) {
            return 0.18;
        }

        if ($lateMinutes <= 120) {
            return 0.24;
        }

        return 0.30;
    }

    public static function calculate(int $lateMinutes, float $uangMakan, ?string $tglAktif, string $periode): int
    {
        if (! self::isActiveForPeriod($tglAktif, $periode)) {
            return 0;
        }

        return (int) round($uangMakan * self::getRate($lateMinutes));
    }

    public static function isActiveForPeriod(?string $tglAktif, string $periode): bool
    {
        if (empty($tglAktif)) {
            return false;
        }

        $periodeEnd = date('Y-m-25', strtotime($periode . '-01'));

        return $periodeEnd >= $tglAktif;
    }

    public static function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }
}
