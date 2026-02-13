<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $hariini      = date('Y-m-d');
        $bulanini     = (int) date('m');
        $tahunini     = (int) date('Y');
        $user         = Auth::guard('karyawan')->user();
        $nik          = $user->nik;
        $pin          = $user->id;

        // nama bulan
        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $namabulanaktif = $namabulan[$bulanini];

        // --- 1. Kajian ---
        $startDate = "$tahunini-$bulanini-01";
        $endDate   = "$tahunini-$bulanini-" . date("t");

        $totalKajian = DB::table('kehadiran_kajian')
            ->where('nik', $nik)
            ->whereBetween('waktu_scan', [$startDate, $endDate])
            ->count();

        // --- 2. Ahad Pagi (API eksternal) ---
        $ahadPagi = 0;
        try {
            $periode = date('Y-m', strtotime($hariini));
            $response = Http::withHeaders([
                'X-API-KEY' => 'pkuboja2025'
            ])->get('https://kajian.pcmboja.com/api/kehadiran', [
                'periode' => $periode,
            ]);

            if ($response->successful() && isset($response['data'])) {
                $filtered = array_filter($response['data'], function ($item) use ($nik) {
                    return isset($item['nik']) && (string) $item['nik'] === (string) $nik;
                });
                $ahadPagi = count($filtered);
            }
        } catch (\Exception $e) {
            \Log::error('API AHAD PAGI gagal: ' . $e->getMessage());
        }

        // --- 3. Sisa cuti ---
        $cutiList = DB::table('cutihdr')
            ->where('pegawai_pin', $pin)
            ->whereYear('tgl_mulai', $tahunini)
            ->get();

        $cutiMelahirkanIDs = DB::table('cutihdr')
            ->where('jeniscuti', 'Cuti Melahirkan')
            ->pluck('idcuti')
            ->toArray();

        $cutiDiambil = 0;
        foreach ($cutiList as $cuti) {
            if (!in_array($cuti->idcuti, $cutiMelahirkanIDs)) {
                $cutiDiambil += (int) $cuti->jml_hari;
            }
        }
        $sisaCuti = max(0, 12 - $cutiDiambil);

        // --- 4. Saldo voucher ---
        $saldoVoucher = $user->saldo ?? 0;

        // --- 5. Jumlah minggu dalam bulan ---
        $start = Carbon::create($tahunini, $bulanini, 1);
        $end = $start->copy()->endOfMonth();
        $totalMinggu = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isSunday()) {
                $totalMinggu++;
            }
        }

        // --- 6. Terakhir baca Qur’an ---
        $lastQuran = DB::table('ngaji')
            ->where('nik', $nik)
            ->orderByDesc('created_at')
            ->first();

        $lastQuranText = $lastQuran
            ? "{$lastQuran->surat} : {$lastQuran->ayat} <br>(" . date('d/m H:i', strtotime($lastQuran->created_at)) . ")"
            : "Belum ada catatan";

        // Ambil data tiket dari API eksternal
        try {
            $response = Http::timeout(5)->get('https://memo.rspkuboja.com/api/tickets/latest-status');

            if ($response->successful()) {
                $tickets = collect($response->json());

                // Filter tiket sesuai user login
                $userTickets = $tickets->filter(fn($t) => ($t['user_id'] ?? null) == $user->id)
                    ->unique('uuid') // HINDARI DUPLIKASI BERDASARKAN UUID
                    ->values()
                    ->map(function($t) use ($tickets) {
                        // Ambil semua komentar untuk tiket ini (berdasarkan UUID) dengan filter UNIQUE
                        $comments = $tickets->filter(fn($comment) => 
                            ($comment['uuid'] ?? null) === ($t['uuid'] ?? null) && 
                            isset($comment['comment_id']) && 
                            $comment['comment_id'] !== null
                        )
                        ->unique('comment_id') // TAMBAHKAN UNIQUE BERDASARKAN COMMENT_ID
                        ->map(function($comment) {
                            return [
                                'id' => $comment['comment_id'] ?? null,
                                'text' => $comment['comment'] ?? '',
                                'user_id' => $comment['comment_user'] ?? null,
                                'created_at' => $comment['comment_created_at'] ?? null,
                                'user_name' => ($comment['comment_user_name'] ?? 'Unknown'),
                                'formatted_time' => $comment['comment_created_at'] ? 
                                    \Carbon\Carbon::parse($comment['comment_created_at'])->diffForHumans() : null
                            ];
                        })
                        ->sortByDesc('created_at')
                        ->values()
                        ->toArray();

                        return [
                            'uuid' => $t['uuid'] ?? '-',
                            'ticket_name' => $t['ticket_name'] ?? '-',
                            'created_by_name' => $t['created_by_name'] ?? '-',
                            'ticket_status' => $t['ticket_status'] ?? '-',
                            'status_created_at' => $t['status_created_at'] ?? '-',
                            'description' => $t['description'] ?? '-',
                            'start_date' => $t['start_date'] ?? '-',
                            'due_date' => $t['due_date'] ?? '-',
                            'comments' => $comments,
                            'comment_count' => count($comments),
                        ];
                    });

                // Summary untuk Task Management
                $ticketSummary = $userTickets->groupBy('ticket_status')->map(fn($group) => $group->count());
            } else {
                $userTickets = collect();
                $ticketSummary = collect();
            }
        } catch (\Exception $e) {
            $userTickets = collect();
            $ticketSummary = collect();
        }

        return view('dashboard.dashboard', array_merge(
            compact(
                'namabulanaktif', 'bulanini', 'tahunini',
                'totalKajian', 'ahadPagi', 'sisaCuti', 'saldoVoucher', 'totalMinggu', 'lastQuranText'
            ),
            [
                'ticketSummary' => $ticketSummary,
                'userTickets' => $userTickets,
            ]
        ));

    }

    public function dashboardadmin()
    {

        $hariini = date("Y-m-d");
        $rekappresensi = DB::table('presensi')
        ->selectRaw('COUNT(nik) as jmlhadir, SUM(IF(jam_in > "15:00",1,0)) as jmlterlambat')
        ->where('tgl_presensi', $hariini )
        ->first();

        $rekapizin = DB::table('pengajuan_izin')
        ->selectRaw('SUM(IF(status="i",1,0)) as jmlizin, SUM(IF(status="s",1,0)) as jmlsakit')
        ->where('tgl_izin', $hariini )
        ->first();
        return view('dashboard.dashboardadmin' ,compact('rekappresensi', 'rekapizin'));
    }
}
