<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AsetController extends Controller
{
    private function db()
    {
        return DB::connection('smartrs');
    }

    public function index(): View
    {
        return view('aset.index');
    }

    public function search(Request $request)
    {
        $code = trim((string) $request->query('code'));
        if ($code === '') {
            return redirect()->route('aset.index')->with('error', 'Kode barcode belum diisi.');
        }

        $asset = $this->findAsset($code);
        if (! $asset) {
            return redirect()->route('aset.index')->with('error', 'Aset dengan barcode tersebut tidak ditemukan.');
        }

        return redirect()->route('aset.show', $asset->idaset);
    }

    public function show(int $asset): View
    {
        $row = $this->asset($asset);
        $locations = $this->db()->table('lokasiaset')->orderBy('namalokasi')->get(['idlokasi', 'namalokasi', 'pic_name']);

        return view('aset.show', compact('row', 'locations'));
    }

    public function verify(Request $request, int $asset): RedirectResponse
    {
        $row = $this->asset($asset);
        $data = $request->validate([
            'status_verifikasi' => ['required', 'in:baik,diperbaiki,rusak,terjual,hilang,tidak_ditemukan'],
            'verifikasi_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->db()->table('dataaset')->where('idaset', $row->idaset)->update([
            'status_verifikasi' => $data['status_verifikasi'],
            'verifikasi_at' => now(),
            'verifikasi_by' => auth('karyawan')->id(),
            'verifikasi_note' => $data['verifikasi_note'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Verifikasi aset berhasil disimpan.');
    }

    public function mutate(Request $request, int $asset): RedirectResponse
    {
        $row = $this->asset($asset);
        $data = $request->validate([
            'location_id' => ['required', 'integer'],
            'to_location_id' => ['required', 'integer', 'different:location_id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $from = $this->db()->table('lokasiaset')->where('idlokasi', $row->idlokasi)->first();
        $to = $this->db()->table('lokasiaset')->where('idlokasi', $data['to_location_id'])->first();
        $native = $this->db()->table('aset_assets')->where('asset_code', $row->kodeaset)->first();

        abort_unless($from && $to && $native, 422, 'Aset belum memiliki data lokasi native yang diperlukan untuk mutasi.');

        $this->db()->transaction(function () use ($row, $from, $to, $native, $data): void {
            $this->db()->table('aset_mutations')->insert([
                'asset_id' => $native->id,
                'mutation_date' => now()->toDateString(),
                'from_location' => $from->namalokasi,
                'to_location' => $to->namalokasi,
                'from_pic' => $from->pic_name,
                'to_pic' => $to->pic_name,
                'reason' => $data['reason'] ?? null,
                'created_by' => auth('karyawan')->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->db()->table('aset_assets')->where('id', $native->id)->update([
                'location_id' => $to->idlokasi,
                'location' => $to->namalokasi,
                'person_in_charge' => $to->pic_name,
                'updated_at' => now(),
            ]);
            $this->db()->table('dataaset')->where('idaset', $row->idaset)->update([
                'idlokasi' => $to->idlokasi,
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Mutasi aset berhasil disimpan.');
    }

    public function maintenance(Request $request, int $asset): RedirectResponse
    {
        $row = $this->asset($asset);
        $data = $request->validate([
            'issue' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $native = $this->db()->table('aset_assets')->where('asset_code', $row->kodeaset)->first();
        abort_unless($native, 422, 'Aset belum memiliki data native untuk maintenance.');

        $prefix = 'MNT-'.now()->format('Ym').'-';
        $lastNumber = $this->db()->table('aset_maintenance_tickets')
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');
        $sequence = $lastNumber ? ((int) substr((string) $lastNumber, -3)) + 1 : 1;
        $number = $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $this->db()->table('aset_maintenance_tickets')->insert([
            'asset_id' => $native->id,
            'number' => $number,
            'reported_date' => now()->toDateString(),
            'reported_by' => auth('karyawan')->user()->nama_lengkap ?? null,
            'issue' => $data['issue'],
            'description' => $data['description'] ?? null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Permintaan maintenance berhasil dibuat.');
    }

    private function findAsset(string $code): ?object
    {
        return $this->assetQuery()
            ->where(function ($query) use ($code): void {
                $query->where('da.kodeaset', $code)->orWhere('da.barcode', $code);
            })
            ->first();
    }

    private function asset(int $id): object
    {
        $asset = $this->assetQuery()->where('da.idaset', $id)->first();
        abort_unless($asset, 404);

        return $asset;
    }

    private function assetQuery()
    {
        return $this->db()->table('dataaset as da')
            ->leftJoin('barang as b', 'b.idbarang', '=', 'da.idbarang')
            ->leftJoin('lokasiaset as l', 'l.idlokasi', '=', 'da.idlokasi')
            ->leftJoin('aset_jenis as j', 'j.idjenis', '=', 'da.idjenis')
            ->select('da.*', 'b.namabarang', 'l.namalokasi', 'l.pic_name', 'j.namajenis');
    }
}
