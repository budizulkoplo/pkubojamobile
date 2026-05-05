<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class RedirectPastCalendar
{
    public function handle($request, Closure $next)
    {
        $bulanInput = $request->input('bulan') ?? $request->query('bulan');

        if (!$bulanInput) {
            return $next($request);
        }

        $selectedMonth = Carbon::parse($bulanInput);

        // Jika bulan < 2025-12 → pindah balik ke kalender lama
        if ($selectedMonth->lessThan(Carbon::create(2025, 12, 1))) {
            return redirect('/kalender?bulan=' . $bulanInput);
        }

        return $next($request);
    }
}
