<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AhadPagiController extends Controller
{
    public function index(Request $request)
    {
        // Ambil data user login
        $user = Auth::guard('karyawan')->user();
        $nik = $user->nik ?? null;

        if (!$nik) {
            abort(403, 'NIK tidak ditemukan. Harap login ulang.');
        }

        // Ambil bulan dari query string, default ke bulan sekarang (Y-m format)
        $bulan = $request->input('bulan', Carbon::now()->format('Y-m'));

        // Validasi dan hitung tanggal awal dan akhir bulan
        try {
            $startOfMonth = Carbon::parse($bulan)->startOfMonth()->toDateString();
            $endOfMonth   = Carbon::parse($bulan)->endOfMonth()->toDateString();
        } catch (\Exception $e) {
            Log::error('Format bulan salah: ' . $bulan);
            abort(400, 'Format bulan tidak valid.');
        }

        $record = collect(); // default kosong

        try {
            // Panggil API eksternal
            $response = Http::withHeaders([
                'X-API-KEY' => 'pkuboja2025'
            ])->get('https://kajian.pcmboja.com/api/kehadiran', [
                'periode' => $bulan
            ]);

            if ($response->successful()) {
                $json = $response->json();

                if (isset($json['data']) && is_array($json['data'])) {
                    $record = collect($json['data'])->filter(function ($item) use ($nik, $startOfMonth, $endOfMonth) {
                        if (!isset($item['nik'], $item['tgl_presensi'])) return false;

                        $tanggal = $item['tgl_presensi'];
                        return (string)$item['nik'] === (string)$nik &&
                               $tanggal >= $startOfMonth && $tanggal <= $endOfMonth;
                    })->values(); // reset index
                } else {
                    Log::warning('API AHAD PAGI: Format data tidak valid.', $json);
                }
            } else {
                Log::warning('API AHAD PAGI gagal. Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengambil data Ahad Pagi: ' . $e->getMessage());
        }

        return view('ahadpagi.index', [
            'record' => $record,
            'bulan'  => $bulan
        ]);
    }
}
