<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\FcmService;

class OperanShiftController extends Controller
{
    protected function getDaftarSurat(): array
    {
        return Cache::rememberForever('daftar_surat_quran', function () {
            $response = Http::get('https://equran.id/api/v2/surat');

            if ($response->failed()) {
                return [];
            }

            return $response->json('data') ?? [];
        });
    }

    protected function getSurat(int $nomor): ?array
    {
        return Cache::remember("surat_quran_{$nomor}", now()->addDay(), function () use ($nomor) {
            $response = Http::get("https://equran.id/api/v2/surat/{$nomor}");

            if ($response->failed()) {
                return null;
            }

            return $response->json('data') ?? null;
        });
    }

    protected function getKelompokKerja()
    {
        $user = Auth::guard('karyawan')->user();

        return DB::table('kelompok_kerja')
            ->where('nik', $user->nik)
            ->orWhere('pegawai_pin', $user->id)
            ->first();
    }

    protected function findSuratInfo(array $surat, $row): ?array
    {
        return collect($surat)->first(function ($item) use ($row) {
            return (int) ($item['nomor'] ?? 0) === (int) ($row->idsurat ?? 0);
        });
    }

    protected function getLatestOperan($kelompok)
    {
        if (! $kelompok) {
            return null;
        }

        return DB::table('ngaji')
            ->where('type', 'operan')
            ->where('idkelompokkerja', $kelompok->idkelompokkerja)
            ->where(function ($query) {
                $query->where('flag', 'selesai')
                    ->orWhereNull('flag');
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function getNextPosition(?object $latest, array $surat): array
    {
        if (! $latest) {
            return ['nomor' => 1, 'ayat' => 1];
        }

        $suratInfo = $this->findSuratInfo($surat, $latest);
        $nomor = (int) ($suratInfo['nomor'] ?? $latest->idsurat ?? 1);
        $jumlahAyat = (int) ($suratInfo['jumlahAyat'] ?? 0);
        $ayat = (int) ($latest->ayat ?? 0);

        if ($jumlahAyat > 0 && $ayat >= $jumlahAyat) {
            return [
                'nomor' => min($nomor + 1, 114),
                'ayat' => $nomor >= 114 ? $jumlahAyat : 1,
            ];
        }

        return [
            'nomor' => $nomor,
            'ayat' => max($ayat + 1, 1),
        ];
    }

    public function index()
    {
        return view('operanshift.index');
    }

    public function ngaji()
    {
        $kelompok = $this->getKelompokKerja();
        $surat = $this->getDaftarSurat();
        $latest = $this->getLatestOperan($kelompok);
        $nextPosition = $this->getNextPosition($latest, $surat);

        $latestSurat = $latest ? $this->findSuratInfo($surat, $latest) : null;

        return view('operanshift.ngaji', compact('kelompok', 'latest', 'latestSurat', 'nextPosition'));
    }

    public function showNgaji($nomor)
    {
        $kelompok = $this->getKelompokKerja();
        abort_unless($kelompok, 403, 'Kelompok kerja belum ditemukan.');

        $nomor = max(1, min((int) $nomor, 114));
        $surat = $this->getSurat($nomor);

        if (! $surat) {
            return back()->with('warning', 'Gagal mengambil data surat dari API.');
        }

        $latest = $this->getLatestOperan($kelompok);
        $daftarSurat = $this->getDaftarSurat();
        $startPosition = $this->getNextPosition($latest, $daftarSurat);
        $startSurat = collect($daftarSurat)->firstWhere('nomor', $startPosition['nomor']);
        $targetAyat = (int) request()->query('ayat', $startPosition['ayat']);

        return view('operanshift.ngaji-show', compact('kelompok', 'surat', 'latest', 'targetAyat', 'startPosition', 'startSurat'));
    }

    public function markNgaji(Request $request)
    {
        $user = Auth::guard('karyawan')->user();
        $kelompok = $this->getKelompokKerja();

        if (! $kelompok) {
            return response()->json([
                'success' => false,
                'message' => 'Kelompok kerja belum ditemukan.',
            ], 422);
        }

        $validated = $request->validate([
            'start_idsurat' => ['required', 'integer', 'min:1', 'max:114'],
            'start_surat' => ['required', 'string', 'max:100'],
            'start_ayat' => ['required', 'integer', 'min:1'],
            'idsurat' => ['required', 'integer', 'min:1', 'max:114'],
            'surat' => ['required', 'string', 'max:100'],
            'ayat' => ['required', 'integer', 'min:1'],
        ]);

        $now = now();
        $baseData = [
            'nik' => $user->nik,
            'pegawai_nama' => $user->nama_lengkap,
            'type' => 'operan',
            'idkelompokkerja' => $kelompok->idkelompokkerja,
            'created_at' => $now,
        ];

        DB::table('ngaji')->insert([
            array_merge($baseData, [
                'idsurat' => $validated['start_idsurat'],
                'surat' => $validated['start_surat'],
                'ayat' => $validated['start_ayat'],
                'flag' => 'mulai',
            ]),
            array_merge($baseData, [
                'idsurat' => $validated['idsurat'],
                'surat' => $validated['surat'],
                'ayat' => $validated['ayat'],
                'flag' => 'selesai',
            ]),
        ]);

        $tokens = DB::table('fcm_tokens as ft')
            ->join('kelompok_kerja as kk', function ($join) use ($kelompok) {
                $join->on('kk.nik', '=', 'ft.nik')
                    ->where('kk.idkelompokkerja', '=', $kelompok->idkelompokkerja);
            })
            ->pluck('ft.token')
            ->unique()
            ->all();

        $sentNotifications = app(FcmService::class)->sendToTokens(
            $tokens,
            'Ngaji Shift selesai',
            $user->nama_lengkap . ' selesai membaca sampai ' . $validated['surat'] . ' ayat ' . $validated['ayat'] . '.',
            [
                'url' => route('operan.ngaji'),
                'type' => 'ngaji_operan_selesai',
                'idkelompokkerja' => $kelompok->idkelompokkerja,
            ]
        );

        Log::info('Ngaji Shift notification processed.', [
            'idkelompokkerja' => $kelompok->idkelompokkerja,
            'token_count' => count($tokens),
            'sent' => $sentNotifications,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Penanda ngaji shift berhasil disimpan.',
            'redirect' => route('operan.ngaji'),
        ]);
    }
}
