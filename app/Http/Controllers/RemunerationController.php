<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RemunerationController extends Controller
{
    private const SLIPS_TABLE = 'hris_payroll_remuneration_slips';

    public function index()
    {
        return view('remunerasi.index');
    }

    public function slips(Request $request)
    {
        $pin = $this->authenticatedPegawaiPin();
        $tahun = (int) ($request->query('tahun') ?: now('Asia/Jakarta')->year);
        $slips = DB::connection('hris')->table(self::SLIPS_TABLE)
            ->where('pegawai_pin', $pin)
            ->where('periode', 'like', sprintf('%04d-', $tahun) . '%')
            ->orderByDesc('periode')
            ->get();

        $tahunList = DB::connection('hris')->table(self::SLIPS_TABLE)
            ->where('pegawai_pin', $pin)
            ->selectRaw('distinct left(periode, 4) as tahun')
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn ($value): int => (int) $value)
            ->values();

        if ($tahunList->isEmpty()) {
            $tahunList = collect([$tahun]);
        }

        return view('remunerasi.slips', compact('slips', 'tahun', 'tahunList'));
    }

    public function detail(int $tahun, int $bulan)
    {
        $pin = $this->authenticatedPegawaiPin();
        $periode = sprintf('%04d-%02d', $tahun, $bulan);
        $slip = DB::connection('hris')->table(self::SLIPS_TABLE)
            ->where('pegawai_pin', $pin)
            ->where('periode', $periode)
            ->first();

        abort_if(! $slip, 404, 'Slip remunerasi belum diterbitkan untuk periode tersebut.');

        $slip->kpi_details = json_decode((string) $slip->kpi_details, true) ?: [];

        return view('remunerasi.detail', [
            'slip' => $slip,
            'periode' => $periode,
        ]);
    }

    /**
     * Slip remunerasi memakai pegawai_pin, sedangkan session mobile memakai
     * karyawan.id. Keduanya tidak selalu memiliki nilai yang sama.
     */
    private function authenticatedPegawaiPin(): string
    {
        $user = Auth::guard('karyawan')->user();

        if (! $user) {
            return '';
        }

        $nik = trim((string) ($user->nik ?? ''));
        if ($nik !== '') {
            $pin = DB::connection('hris')
                ->table('pegawai')
                ->where('nik', $nik)
                ->value('pegawai_pin');

            if ($pin !== null && trim((string) $pin) !== '') {
                return (string) $pin;
            }
        }

        // Kompatibilitas dengan data lama yang kebetulan memakai ID karyawan
        // sebagai PIN pegawai.
        return (string) ($user->id ?? '');
    }
}
