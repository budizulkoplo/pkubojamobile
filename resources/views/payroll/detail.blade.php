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
   STATUS PEGAWAI
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
   PERHITUNGAN PENGHASILAN
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
    <img src="{{ asset('assets/img/' . $site['icon']) }}" style="height:50px;">
    <h6 class="mt-2 mb-0">{{ $site['namaweb'] }}</h6>
    <small class="text-muted">
        Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </small>

    @if($isHarian)
        <span class="badge bg-info ms-2">Harian</span>
    @elseif($isTraining)
        <span class="badge bg-warning ms-2">Training/OJT</span>
    @endif
</div>

{{-- IDENTITAS --}}
<div class="mb-3 p-2 bg-light rounded">
    <strong>{{ $rekap['pegawai_nama'] }}</strong><br>
    NIP: {{ $rekap['pegawai_nip'] }}<br>
    Jabatan: {{ $rekap['jabatan'] }}
</div>

{{-- PENGHASILAN --}}
<div class="card mb-3">
<div class="card-header bg-primary text-white">PENGHASILAN</div>
<div class="card-body p-0">
<table class="table table-sm table-borderless mb-0">

<tr><td>Gaji Pokok</td><td class="text-end">Rp {{ number_format($gajipokok,0,',','.') }}</td></tr>
<tr><td>Tunj. Struktural</td><td class="text-end">Rp {{ number_format($tunjstruktural,0,',','.') }}</td></tr>
<tr><td>Tunj. Keluarga</td><td class="text-end">Rp {{ number_format($tunjkeluarga,0,',','.') }}</td></tr>
<tr><td>Tunj. Apotek</td><td class="text-end">Rp {{ number_format($tunjapotek,0,',','.') }}</td></tr>
<tr><td>Tunj. Fungsional</td><td class="text-end">Rp {{ number_format($tunjfungsional,0,',','.') }}</td></tr>

@if(!$isHarian && !$isTraining && !$isDirektur && $uangmakanNominal > 0)
<tr>
<td>Uang Makan ({{ $jmlabsensi }} × Rp {{ number_format($uangmakanNominal,0,',','.') }})</td>
<td class="text-end">Rp {{ number_format($uangMakan,0,',','.') }}</td>
</tr>
@endif

<tr>
<td>Kehadiran ({{ $jmlabsensi }} × Rp {{ number_format($kehadiran,0,',','.') }})</td>
<td class="text-end">Rp {{ number_format($nilaiKehadiran,0,',','.') }}</td>
</tr>

<tr class="border-top">
<td><strong>Total Penghasilan</strong></td>
<td class="text-end text-primary fw-bold">
Rp {{ number_format($totalPenghasilan,0,',','.') }}
</td>
</tr>

</table>
</div>
</div>

{{-- POTONGAN --}}
<div class="card mb-3">
<div class="card-header bg-danger text-white">POTONGAN</div>
<div class="card-body p-0">
<table class="table table-sm table-borderless mb-0">

<tr><td>ZIS (2.5%)</td><td class="text-end">Rp {{ number_format($zis,0,',','.') }}</td></tr>
<tr><td>PPH21</td><td class="text-end">Rp {{ number_format($pph21,0,',','.') }}</td></tr>
<tr><td>Qurban</td><td class="text-end">Rp {{ number_format($qurban,0,',','.') }}</td></tr>
<tr><td>Transport</td><td class="text-end">Rp {{ number_format($potransport,0,',','.') }}</td></tr>
<tr><td>Infaq PDM</td><td class="text-end">Rp {{ number_format($infaqPdm,0,',','.') }}</td></tr>
<tr><td>BPJS Kes</td><td class="text-end">Rp {{ number_format($bpjs,0,',','.') }}</td></tr>
<tr><td>BPJS TK</td><td class="text-end">Rp {{ number_format($bpjstk,0,',','.') }}</td></tr>
<tr><td>Koperasi</td><td class="text-end">Rp {{ number_format($koperasi,0,',','.') }}</td></tr>

<tr class="border-top">
<td><strong>Total Potongan</strong></td>
<td class="text-end text-danger fw-bold">
Rp {{ number_format($totalPotongan,0,',','.') }}
</td>
</tr>

</table>
</div>
</div>

{{-- NETTO --}}
<div class="card border-success mb-3">
<div class="card-body text-center">
<h6 class="text-success">Total Diterima</h6>
<h4 class="text-success fw-bold">
Rp {{ number_format($netto,0,',','.') }}
</h4>
</div>
</div>

</div>
@endsection