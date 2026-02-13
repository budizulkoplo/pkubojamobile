<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class QuranController extends Controller
{
    // Halaman list semua surat
    public function index()
    {
        $surat = Cache::rememberForever('daftar_surat_quran', function () {
            $response = Http::get('https://equran.id/api/v2/surat');

            if ($response->failed()) {
                return [];
            }

            return $response->json('data');
        });

        return view('quran.index', compact('surat'));
    }

    // Halaman detail 1 surat
    public function show($nomor)
    {
        $user = Auth::guard('karyawan')->user();

        // Cache per surat (misalnya simpan 1 hari)
        $surat = Cache::remember("surat_quran_{$nomor}", now()->addDay(), function () use ($nomor) {
            $response = Http::get("https://equran.id/api/v2/surat/{$nomor}");

            if ($response->failed()) {
                return null; // biar nggak error, nanti dicek di bawah
            }

            return $response->json()['data'] ?? null;
        });

        if (!$surat) {
            return back()->with('warning', 'Gagal mengambil data surat dari API.');
        }

        // Ambil riwayat baca dari DB (nggak usah di-cache, karena ini data per user)
        $riwayat = DB::table('ngaji')
            ->where('nik', $user->nik)
            ->where('surat', $surat['namaLatin'])
            ->get()
            ->map(function ($row) {
                return [
                    'ayat'  => $row->ayat,
                    'senin' => $row->type === 'senin',
                    'rutin' => $row->type === 'rutin',
                ];
            });

        return view('quran.show', compact('surat', 'riwayat'));
    }

    public function markRutin(Request $request)
    {
        $user = Auth::guard('karyawan')->user();

        DB::table('ngaji')->insert([
            'nik'          => $user->nik,
            'pegawai_nama' => $user->nama_lengkap,
            'surat'        => $request->surat,
            'ayat'         => $request->ayat,
            'created_at'   => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
