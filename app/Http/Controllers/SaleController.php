<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{



public function index(Request $request)
{
    $user = Auth::guard('karyawan')->user();
    $pin = $user->id;

    $bulan = $request->input('bulan', date('Y-m'));
    $startDate = Carbon::parse($bulan . '-01')->startOfMonth()->toDateString();
    $endDate = Carbon::parse($bulan . '-01')->endOfMonth()->toDateString();

    $items = DB::connection('koperasi')
        ->table('sale_item as si')
        ->join('sale as s', 'si.sale_id', '=', 's.id')
        ->select(
            'si.id', 'si.sale_id', 'si.name', 'si.harga', 'si.qty', 'si.satuan',
            DB::raw('(si.harga * si.qty) as total'),
            'si.cancel', 's.tgl_transaksi'
        )
        ->where('si.cancel', 'tidak')
        ->whereIn('s.id', function ($query) use ($pin) {
            $query->select('id')
                  ->from('sale')
                  ->where('idmember', function ($q) use ($pin) {
                      $q->select('idmember')
                        ->from('tblmember')
                        ->where('pegawai_pin', $pin)
                        ->limit(1);
                  });
        })
        ->whereBetween('s.tgl_transaksi', [$startDate, $endDate])
        ->orderBy('s.tgl_transaksi', 'desc')
        ->get();

    // Group per sale_id (nota)
    $groupedItems = $items->groupBy('sale_id');

    return view('sale.index', [
        'items' => $groupedItems,
        'bulan' => $bulan
    ]);
}


}
