<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class CekVerifikasiWajah
{
    public function handle($request, Closure $next)
    {
        if (!Session::get('wajah_terverifikasi')) {
            return redirect('/verifikasi-wajah')->with('message', 'Silakan verifikasi wajah terlebih dahulu.');
        }

        return $next($request);
    }
}
