<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
public function proseslogin(Request $request)
{
    $credentials = ['nik' => $request->nik, 'password' => $request->password];

    $user = \App\Models\Karyawan::where('nik', $request->nik)->first();

    if ($user && Hash::check($request->password, $user->password)) {
        Auth::guard('karyawan')->login($user); // Ini tidak rehash
        return redirect('/dashboard');
    }

    return redirect('/')->with(['warning' => 'Nik / Password Salah']);
}


    public function proseslogout(Request $request)
{
    // Logout guard karyawan jika aktif
    if (Auth::guard('karyawan')->check()) {
        Auth::guard('karyawan')->logout();
    }

    // Tambahan jika kamu pakai guard 'user' juga
    if (Auth::guard('user')->check()) {
        Auth::guard('user')->logout();
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/'); // atau ke login page
}



    public function prosesloginadmin(Request $request)
    {
        if (Auth::guard('user')->attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            $user = Auth::guard('user')->user();

            

            return redirect('/panel/dashboardadmin');
        } else {
            return redirect('/panel')->with(['warning' => 'Username / Password Salah']);
        }
    }

    public function proseslogoutadmin()
    {
        if (Auth::guard('user')->check()) {
            Auth::guard('user')->logout();
            return redirect('/panel');
        }
    }
}   