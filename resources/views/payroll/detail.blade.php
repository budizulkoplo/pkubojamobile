@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Slip Gaji</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<div class="p-3" style="margin-top: 40px">
    @php

        /* =========================
        STATUS
        ========================= */
        $isHarian   = ($rekap['harian'] ?? 0) == 1;
        $isDirektur = ($rekap['direktur'] ?? 0) == 1;
        $isTraining = !$isHarian && ($rekap['gajipokok'] ?? 0) <= 0;

        /* =========================
        DATA DASAR
        ========================= */
        $gajipokok      = $rekap['gajipokok'] ?? 0;
        $tunjstruktural = $rekap['tunjstruktural'] ?? 0;
        $tunjkeluarga   = $rekap['tunjkeluarga'] ?? 0;
        $tunjapotek     = $rekap['tunjapotek'] ?? 0;
        $tunjfungsional = $rekap['tunjfungsional'] ?? 0;

        $jmlrujukan = $rekap['jmlrujukan'] ?? 0;
        $rujukan    = $rekap['rujukan'] ?? 0;

        $jmlabsensi = $rekap['jmlabsensi'] ?? 0;
        $kehadiran  = $rekap['kehadiran'] ?? 0;
        $nilaiKehadiran = $jmlabsensi * $kehadiran;

        $uangmakanNominal = $rekap['uangmakan'] ?? 0;
        $uangMakan = $jmlabsensi * $uangmakanNominal;
        if ($isDirektur) $uangMakan = 0;

        $doubleshift     = $rekap['doubleshift'] ?? 0;
        $tugasluar       = $rekap['tugasluar'] ?? 0;
        $konversilembur  = $rekap['konversilembur'] ?? 0;
        $konversioperasi = $rekap['konversioperasi'] ?? 0;
        $lemburkhusus    = $rekap['lemburkhusus'] ?? 0;

        $lemburRate = ($lemburkhusus > 0) ? $lemburkhusus : $kehadiran;

        $lemburVal      = $konversilembur * $lemburRate;
        $operasiVal     = $konversioperasi * $lemburRate;
        $tugasLuarVal   = $tugasluar * $lemburRate;
        $doubleShiftVal = $doubleshift * $lemburRate;

        /* =========================
        PENGHASILAN
        ========================= */
        if ($isHarian) {

            $totalPenghasilan = $nilaiKehadiran;

        } elseif ($isTraining) {

            $totalPenghasilan = $nilaiKehadiran;

            $gajipokok = $tunjstruktural = $tunjkeluarga = $tunjapotek = $tunjfungsional = 0;
            $jmlrujukan = $rujukan = 0;
            $uangMakan = 0;
            $lemburVal = $operasiVal = $tugasLuarVal = $doubleShiftVal = 0;

        } else {

            if ($rekap['use_new_system'] ?? false) {

                $totalPenghasilan =
                    $gajipokok +
                    $tunjstruktural +
                    $tunjkeluarga +
                    $tunjapotek +
                    $tunjfungsional +
                    ($jmlrujukan * $rujukan) +
                    $uangMakan +
                    $nilaiKehadiran +
                    $tugasLuarVal +
                    $lemburVal +
                    $operasiVal +
                    $doubleShiftVal;

            } else {

                $cuti = $rekap['cuti'] ?? 0;

                $totalPenghasilan =
                    $gajipokok +
                    $tunjstruktural +
                    $tunjkeluarga +
                    $tunjapotek +
                    $tunjfungsional +
                    ($jmlrujukan * $rujukan) +
                    $uangMakan +
                    $nilaiKehadiran +
                    ($cuti * $kehadiran) +
                    $tugasLuarVal +
                    $lemburVal +
                    $doubleShiftVal;

                $operasiVal = 0;
            }
        }

        /* =========================
        POTONGAN
        ========================= */
        if ($isTraining) {

            $zis = $pph21 = $qurban = $potransport = $infaqPdm = $bpjs = $bpjstk = $koperasi = 0;
            $totalPotongan = 0;

        } else {

            $pph21      = $rekap['pph21'] ?? 0;
            $qurban     = $rekap['qurban'] ?? 0;
            $potransport= $rekap['potransport'] ?? 0;
            $bpjs       = $rekap['bpjskes'] ?? 0;
            $bpjstk     = $rekap['bpjstk'] ?? 0;
            $koperasi   = $rekap['koperasi'] ?? 0;

            $zis = round($totalPenghasilan * 0.025);
            $infaqPdm = round($gajipokok * 0.01);

            $totalPotongan =
                $zis + $pph21 + $qurban + $potransport +
                $infaqPdm + $bpjs + $bpjstk + $koperasi;
        }

        $netto = $totalPenghasilan - $totalPotongan;

        @endphp

    {{-- HEADER --}}
    <div class="text-center mb-3">
        <img src="{{ asset('assets/img/' . $site['icon']) }}" alt="{{ $site['namaweb'] }}" style="height: 50px;">
        <h6 class="mt-2 mb-0">{{ $site['namaweb'] }}</h6>
        <small class="text-muted">Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</small>
        @if($isTraining)
            <span class="badge bg-warning ms-2">Training/OJT</span>
        @endif
    </div>

    {{-- IDENTITAS PEGAWAI --}}
    <div class="mb-3 p-2 bg-light rounded">
        <table style="width: 100%;">
            <tr>
                <td style="width: 30%;"><strong>Nama</strong></td>
                <td style="width: 2%;">:</td>
                <td>{{ $rekap['pegawai_nama'] }}</td>
            </tr>
            <tr>
                <td><strong>NIP</strong></td>
                <td>:</td>
                <td>{{ $rekap['pegawai_nip'] }}</td>
            </tr>
            <tr>
                <td><strong>Jabatan</strong></td>
                <td>:</td>
                <td>{{ $rekap['jabatan'] }}</td>
            </tr>
            @if($isTraining)
            <tr>
                <td><strong>Status</strong></td>
                <td>:</td>
                <td><span class="text-warning">Training/OJT</span></td>
            </tr>
            @endif
        </table>
    </div>

    {{-- PENGHASILAN --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2 px-3">PENGHASILAN</div>
        <div class="card-body p-0">
            <table style="width: 100%;" class="table table-sm table-borderless mb-0">
                
                <tr>
                    <td style="width: 60%; padding: 5px 12px;">Gaji Pokok</td>
                    <td style="width: 40%; padding: 5px 12px;" class="text-end">Rp {{ number_format($gajipokok, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Tunj. Struktural</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($tunjstruktural, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Tunj. Keluarga</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($tunjkeluarga, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Tunj. Apotek</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($tunjapotek, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Tunj. Fungsional</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($tunjfungsional, 0, ',', '.') }}</td>
                </tr>
                

                @if($jmlrujukan > 0)
                <tr>
                    <td style="padding: 5px 12px;">
                        Tunj. Rujukan <span class="text-muted">{{ $jmlrujukan }} × Rp {{ number_format($rujukan, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($jmlrujukan * $rujukan, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($uangmakan > 0 && !$isTraining)
                <tr>
                    <td style="padding: 5px 12px;">
                        Uang Makan <span class="text-muted">{{ $jmlabsensi }} × Rp {{ number_format($uangmakan, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($jmlabsensi * $uangmakan, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($doubleshift > 0)
                <tr>
                    <td style="padding: 5px 12px;">
                        Doubleshift <span class="text-muted">{{ $doubleshift }} × Rp {{ number_format($lemburkhusus, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($doubleshift * $lemburkhusus, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if(!($rekap['use_new_system'] ?? false) && $cuti > 0 && !$isTraining)
                <tr>
                    <td style="padding: 5px 12px;">
                        Cuti <span class="text-muted">{{ $cuti }} × Rp {{ number_format($kehadiran, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($cuti * $kehadiran, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($tugasluar > 0)
                <tr>
                    <td style="padding: 5px 12px;">
                        Tugas Luar <span class="text-muted">{{ $tugasluar }} × Rp {{ number_format($lemburkhusus, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($tugasluar * $lemburkhusus, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if($konversilembur > 0 && !$isTraining)
                <tr>
                    <td style="padding: 5px 12px;">
                        Lembur <span class="text-muted">{{ $konversilembur }} × Rp {{ number_format(!empty($lemburkhusus) && $lemburkhusus > 0 ? $lemburkhusus : $kehadiran, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($lemburVal, 0, ',', '.') }}</td>
                </tr>
                @endif

                @if(($rekap['use_new_system'] ?? false) && $konversioperasi > 0 && !$isTraining)
                <tr>
                    <td style="padding: 5px 12px;">
                        Operasi <span class="text-muted">{{ $konversioperasi }} × Rp {{ number_format(!empty($lemburkhusus) && $lemburkhusus > 0 ? $lemburkhusus : $kehadiran, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($operasiVal, 0, ',', '.') }}</td>
                </tr>
                @endif

                {{-- KEHADIRAN (selalu tampil) --}}
                <tr>
                    <td style="padding: 5px 12px;">
                        Kehadiran <span class="text-muted">{{ $jmlabsensi }} × Rp {{ number_format($kehadiran, 0, ',', '.') }}</span>
                    </td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($nilaiKehadiran, 0, ',', '.') }}</td>
                </tr>

                {{-- TOTAL PENGHASILAN --}}
                <tr style="border-top: 1px solid #dee2e6;">
                    <td style="padding: 8px 12px; font-weight: bold;">Total Penghasilan</td>
                    <td style="padding: 8px 12px; font-weight: bold; color: #0d6efd;" class="text-end">Rp {{ number_format($totalPenghasilan, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- POTONGAN --}}
    <div class="card mb-3">
        <div class="card-header bg-danger text-white py-2 px-3">POTONGAN</div>
        <div class="card-body p-0">
            <table style="width: 100%;" class="table table-sm table-borderless mb-0">
                <tr>
                    <td style="width: 60%; padding: 5px 12px;">ZIS (2.5%)</td>
                    <td style="width: 40%; padding: 5px 12px;" class="text-end">Rp {{ number_format($zis, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">PPH21</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($pph21, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Qurban</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($qurban, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Transport</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($potransport, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Infaq PDM (1% GP)</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($infaqPdm, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">BPJS Kes</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($bpjs, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">BPJS TK</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($bpjstk, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 12px;">Koperasi</td>
                    <td style="padding: 5px 12px;" class="text-end">Rp {{ number_format($koperasi, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 1px solid #dee2e6;">
                    <td style="padding: 8px 12px; font-weight: bold;">Total Potongan</td>
                    <td style="padding: 8px 12px; font-weight: bold; color: #dc3545;" class="text-end">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- TOTAL DITERIMA --}}
    <div class="card mb-3 border-success">
        <div class="card-body text-center py-3">
            <h6 class="text-success mb-1">Total Diterima</h6>
            <h4 class="text-success fw-bold mb-1">Rp {{ number_format($netto, 0, ',', '.') }}</h4>
            <p class="text-muted small mb-0">Semoga Berkah!</p>
        </div>
    </div>

    {{-- TANGGAL & DOWNLOAD --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <small class="text-muted">Boja, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
        <a href="{{ route('slip.pdf', [\Carbon\Carbon::parse($periode)->format('Y'), \Carbon\Carbon::parse($periode)->format('m')]) }}" class="btn btn-sm btn-outline-primary">
            <ion-icon name="download-outline"></ion-icon> Download
        </a>
    </div>

</div>

@endsection
