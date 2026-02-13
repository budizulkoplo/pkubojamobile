<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mengatur skema URL menjadi HTTPS secara paksa
        URL::forceScheme('https');
        // Mengatur lokal Carbon (tanggal/waktu) ke Bahasa Indonesia
        Carbon::setLocale('id');

        // load drawer menu
        View::composer(['layouts.bottomNav', 'layouts.presensi'], function ($view) {
            // Memastikan pengguna 'karyawan' telah login
            if (Auth::guard('karyawan')->check()) {
                // Mengambil role/level pengguna
                $role = Auth::guard('karyawan')->user()->peoplepower;

                // --- BAGIAN INI MENGHILANGKAN CACHE ---

                // Mengambil data menu drawer langsung dari database
                $drawerMenus = DB::table('mobilemenu')
                    ->where('status', 'drawer')
                    ->where('level', 'like', "%$role%")
                    ->orderBy('idmenu')
                    ->get();
                
                // --- AKHIR PENGHILANGAN CACHE ---

                // Meneruskan data menu ke view
                $view->with('drawerMenus', $drawerMenus);
            }
        });
        // load drawer menu
    }
}
