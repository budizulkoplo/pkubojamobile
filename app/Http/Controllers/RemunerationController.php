<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RemunerationController extends Controller
{
    private const SLIPS_TABLE = 'hris_payroll_remuneration_slips';

    public function index(Request $request)
    {
        $pin = (string) Auth::guard('karyawan')->id();
        $tahun = (int) ($request->query('tahun') ?: now('Asia/Jakarta')->year);
        $slips = DB::connection('hris')->table(self::SLIPS_TABLE)
            ->where('pegawai_pin', $pin)
            ->whereYear('periode', $tahun)
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

        return view('remunerasi.index', compact('slips', 'tahun', 'tahunList'));
    }

    public function detail(int $tahun, int $bulan)
    {
        $pin = (string) Auth::guard('karyawan')->id();
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
}
