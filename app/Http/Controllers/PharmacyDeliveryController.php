<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PharmacyDeliveryController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('karyawan')->user();
        $pin = (string) ($user->pegawai_pin ?? $user->id ?? '');

        return view('pharmacy-deliveries.index', [
            'pageTitle' => 'Pengantaran Obat',
            'availableDeliveries' => $this->baseQuery()
                ->where('status', 'location_set')
                ->whereNull('courier_pin')
                ->orderByDesc('location_set_at')
                ->get(),
            'myActiveDeliveries' => $this->baseQuery()
                ->where('status', 'in_delivery')
                ->where('courier_pin', $pin)
                ->orderByDesc('taken_at')
                ->get(),
            'historyDeliveries' => $this->baseQuery()
                ->whereIn('status', ['completed', 'in_delivery'])
                ->where('courier_pin', $pin)
                ->orderByDesc(DB::raw('coalesce(completed_at, taken_at, updated_at)'))
                ->limit(50)
                ->get(),
            'summary' => $this->summary($pin),
        ]);
    }

    public function take(int $deliveryId): RedirectResponse
    {
        $user = Auth::guard('karyawan')->user();
        $updated = DB::connection('smartrs')
            ->table('pharmacy_deliveries')
            ->where('id', $deliveryId)
            ->where('status', 'location_set')
            ->whereNull('courier_pin')
            ->update([
                'status' => 'in_delivery',
                'courier_pin' => (string) ($user->pegawai_pin ?? $user->id ?? ''),
                'courier_name' => $user->pegawai_nama ?? $user->nama_lengkap ?? $user->name ?? null,
                'courier_phone' => $user->nohp ?? $user->no_hp ?? null,
                'taken_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('pharmacy-deliveries.index')
            ->with($updated ? 'success' : 'error', $updated ? 'Pengantaran obat berhasil diambil.' : 'Pengantaran sudah diambil pegawai lain atau belum siap.');
    }

    public function complete(int $deliveryId): RedirectResponse
    {
        $user = Auth::guard('karyawan')->user();
        $pin = (string) ($user->pegawai_pin ?? $user->id ?? '');

        $updated = DB::connection('smartrs')
            ->table('pharmacy_deliveries')
            ->where('id', $deliveryId)
            ->where('status', 'in_delivery')
            ->where('courier_pin', $pin)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('pharmacy-deliveries.index')
            ->with($updated ? 'success' : 'error', $updated ? 'Pengantaran obat berhasil diselesaikan.' : 'Pengantaran tidak ditemukan pada tanggung jawab Anda.');
    }

    private function baseQuery()
    {
        return DB::connection('smartrs')
            ->table('pharmacy_deliveries')
            ->select([
                'id',
                'noorder',
                'tglorder',
                'noregistrasi',
                'nocm',
                'namapasien',
                'items',
                'address_detail',
                'recipient_name',
                'recipient_phone',
                'status',
                'latitude',
                'longitude',
                'distance_km',
                'delivery_fee',
                'location_source',
                'location_set_at',
                'courier_pin',
                'courier_name',
                'taken_at',
                'completed_at',
                'updated_at',
            ]);
    }

    private function summary(string $pin): object
    {
        $rows = DB::connection('smartrs')
            ->table('pharmacy_deliveries')
            ->where('courier_pin', $pin)
            ->where('status', 'completed')
            ->whereDate('completed_at', now()->toDateString())
            ->selectRaw('count(*) as total_orders, coalesce(sum(delivery_fee), 0) as total_fee')
            ->first();

        return (object) [
            'total_orders' => (int) ($rows->total_orders ?? 0),
            'total_fee' => (float) ($rows->total_fee ?? 0),
        ];
    }
}
