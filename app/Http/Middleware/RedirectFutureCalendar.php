<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class RedirectFutureCalendar
{
    public function handle($request, Closure $next)
    {
        // Ambil bulan input dari request POST
        $bulanInput = $request->input('bulan');

        // Jika tidak ada bulan di request, langsung lanjut saja
        if (!$bulanInput) {
            return $next($request);
        }

        // Ubah bulan menjadi Carbon date
        $selectedMonth = Carbon::parse($bulanInput);

        // Cek apakah bulan >= Desember 2025
        if ($selectedMonth->greaterThanOrEqualTo(Carbon::create(2025, 12, 1))) {
            
            // Redirect KE HALAMAN BARU DENGAN PARAMETER BULAN
            return redirect('/newkalender?bulan=' . $bulanInput);
        }

        return $next($request);
    }
}
