<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuranController extends Controller
{
    protected function getDaftarSurat()
    {
        return Cache::rememberForever('daftar_surat_quran', function () {
            $response = Http::get('https://equran.id/api/v2/surat');

            if ($response->failed()) {
                return [];
            }

            return $response->json('data');
        });
    }

    protected function findSuratInfo($surat, $row)
    {
        return collect($surat)->first(function ($item) use ($row) {
            $nomorSurat = (int) ($item['nomor'] ?? 0);
            $idsurat = (int) ($row->idsurat ?? 0);
            $namaLatin = mb_strtolower(trim((string) ($item['namaLatin'] ?? '')));
            $namaRow = mb_strtolower(trim((string) ($row->surat ?? '')));

            if ($idsurat > 0 && $nomorSurat === $idsurat) {
                return true;
            }

            return $namaLatin !== '' && $namaLatin === $namaRow;
        });
    }

    // Halaman list semua surat
    public function index()
    {
        $user = Auth::guard('karyawan')->user();
        $surat = $this->getDaftarSurat();

        $riwayatTerakhir = collect(['rutin', 'senin'])->mapWithKeys(function ($type) use ($user, $surat) {
            $row = DB::table('ngaji')
                ->where('nik', $user->nik)
                ->where('type', $type)
                ->orderByDesc('created_at')
                ->first();

            if (!$row) {
                return [$type => null];
            }

            $suratInfo = $this->findSuratInfo($surat, $row);

            $nomorSurat = (int) ($suratInfo['nomor'] ?? ($row->idsurat ?? 0));

            return [$type => [
                'type' => $type,
                'idsurat' => (int) ($row->idsurat ?? 0),
                'surat' => $row->surat ?: ($suratInfo['namaLatin'] ?? '-'),
                'ayat' => (int) $row->ayat,
                'created_at' => $row->created_at,
                'nomor_surat' => $nomorSurat > 0 ? $nomorSurat : null,
                'nama_arab' => $suratInfo['nama'] ?? null,
                'jumlah_ayat' => $suratInfo['jumlahAyat'] ?? null,
            ]];
        });

        return view('quran.index', compact('surat', 'riwayatTerakhir'));
    }

    public function openHistory($type)
    {
        abort_unless(in_array($type, ['senin', 'rutin'], true), 404);

        $user = Auth::guard('karyawan')->user();
        $surat = $this->getDaftarSurat();

        $row = DB::table('ngaji')
            ->where('nik', $user->nik)
            ->where('type', $type)
            ->orderByDesc('created_at')
            ->first();

        if (!$row) {
            return redirect()
                ->route('quran.index')
                ->with('warning', 'Riwayat bacaan belum tersedia.');
        }

        $suratInfo = $this->findSuratInfo($surat, $row);
        $nomorSurat = (int) ($suratInfo['nomor'] ?? ($row->idsurat ?? 0));
        $ayat = max((int) ($row->ayat ?? 0), 1);

        if ($nomorSurat <= 0) {
            return redirect()
                ->route('quran.index')
                ->with('warning', 'Riwayat ditemukan, tetapi suratnya belum bisa dipetakan otomatis.');
        }

        return redirect()->to(route('quran.show', [
            'nomor' => $nomorSurat,
            'ayat' => $ayat,
            'type' => $type,
        ]) . '#ayat-' . $ayat);
    }

    // Halaman detail 1 surat
    public function show($nomor)
    {
        $user = Auth::guard('karyawan')->user();

        $cacheKey = "surat_quran_{$nomor}";
        $surat = Cache::get($cacheKey);

        if (! is_array($surat) || $surat === []) {
            try {
                $response = Http::connectTimeout(2)
                    ->timeout(5)
                    ->get("https://equran.id/api/v2/surat/{$nomor}");
            } catch (ConnectionException $exception) {
                Log::warning('Quran surah request timed out or failed.', [
                    'nomor' => $nomor,
                    'message' => $exception->getMessage(),
                ]);

                return back()->with('warning', 'Gagal mengambil data surat dari API. Silakan coba lagi.');
            }

            if ($response->failed()) {
                Log::warning('Quran surah request returned an unsuccessful response.', [
                    'nomor' => $nomor,
                    'status' => $response->status(),
                ]);

                return back()->with('warning', 'Gagal mengambil data surat dari API. Silakan coba lagi.');
            }

            $surat = $response->json('data');

            if (! is_array($surat) || $surat === []) {
                Log::warning('Quran surah response did not contain valid data.', [
                    'nomor' => $nomor,
                ]);

                return back()->with('warning', 'Data surat dari API tidak valid. Silakan coba lagi.');
            }

            Cache::put($cacheKey, $surat, now()->addDay());
        }

        // Ambil riwayat baca dari DB (nggak usah di-cache, karena ini data per user)
        $riwayat = DB::table('ngaji')
            ->where('nik', $user->nik)
            ->where('idsurat', $nomor)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('type')
            ->map(function ($rows, $type) {
                $row = $rows->first();

                return [
                    'ayat' => (int) $row->ayat,
                    'type' => $type,
                    'created_at' => $row->created_at,
                ];
            });

        $targetAyat = (int) request()->query('ayat', 0);
        $targetType = request()->query('type');

        return view('quran.show', compact('surat', 'riwayat', 'targetAyat', 'targetType'));
    }

    public function markRutin(Request $request)
    {
        $user = Auth::guard('karyawan')->user();

        $validated = $request->validate([
            'idsurat' => ['required', 'integer'],
            'surat' => ['required', 'string', 'max:100'],
            'ayat' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:senin,rutin'],
        ]);

        DB::table('ngaji')->insert([
            'nik'          => $user->nik,
            'pegawai_nama' => $user->nama_lengkap,
            'idsurat'      => $validated['idsurat'],
            'surat'        => $validated['surat'],
            'ayat'         => $validated['ayat'],
            'type'         => $validated['type'],
            'created_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penanda bacaan berhasil disimpan.',
        ]);
    }
}
