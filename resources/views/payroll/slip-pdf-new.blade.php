<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            width: 100%;
            max-width: 7.5cm;
            margin: auto;
            padding: 5px;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        h5, h4 { margin: 2px 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #666; font-size: 10px; }
        .fw-bold { font-weight: bold; }
        .bg-header {
            background-color: #f4f4f4;
            padding: 3px 5px;
            font-weight: bold;
            margin: 8px 0 3px 0;
        }
        .bg-header-warning {
            background-color: #fff3cd;
            padding: 3px 5px;
            font-weight: bold;
            margin: 8px 0 3px 0;
            border-left: 3px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        td, th {
            padding: 2px 0;
            vertical-align: top;
        }
        .label {
            width: 60%;
        }
        .value {
            width: 40%;
            text-align: right;
        }
        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        .total-potongan {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        .highlight {
            font-weight: bold;
            font-size: 13px;
            margin: 5px 0;
        }
        .note { font-size: 10px; margin-top: 3px; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 10px;
            background-color: #ffc107;
            color: #000;
        }
        img {
            max-height: 35px;
            margin-bottom: 2px;
        }
        p { margin: 5px 0; }
        hr {
            margin: 8px 0;
            border: 0;
            border-top: 1px dashed #ccc;
        }
        .small {
            font-size: 9px;
        }
        .zero-value {
            color: #999;
        }
    </style>
</head>
<body>

    <div class="text-center">
        <img src="{{ public_path('assets/img/' . $site['icon']) }}"><br>
        <strong>{{ $site['namaweb'] }}</strong><br>
        Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </div>

    @php
        // CEK STATUS TRAINING/OJT
        $isTraining = ($rekap['gajipokok'] ?? 0) <= 0;
        
        // Untuk OJT, semua komponen selain kehadiran dianggap 0
        if($isTraining) {
            $gajipokok = 0;
            $tunjstruktural = 0;
            $tunjkeluarga = 0;
            $tunjapotek = 0;
            $tunjfungsional = 0;
            $jmlrujukan = 0;
            $rujukan = 0;
            $totalharikerja = 0;
            $uangmakan = 0;
            $doubleshift = 0;
            $cuti = 0;
            $tugasluar = 0;
            $konversilembur = 0;
            $konversioperasi = 0;
            $lemburkhusus = 0;
            
            // Potongan semua 0
            $pph21 = 0;
            $qurban = 0;
            $potransport = 0;
            $bpjstk = 0;
            $koperasi = 0;
        } else {
            $gajipokok = $rekap['gajipokok'] ?? 0;
            $tunjstruktural = $rekap['tunjstruktural'] ?? 0;
            $tunjkeluarga = $rekap['tunjkeluarga'] ?? 0;
            $tunjapotek = $rekap['tunjapotek'] ?? 0;
            $tunjfungsional = $rekap['tunjfungsional'] ?? 0;
            $jmlrujukan = $rekap['jmlrujukan'] ?? 0;
            $rujukan = $rekap['rujukan'] ?? 0;
            $totalharikerja = $rekap['totalharikerja'] ?? 0;
            $uangmakan = $rekap['uangmakan'] ?? 0;
            $doubleshift = $rekap['doubleshift'] ?? 0;
            $cuti = $rekap['cuti'] ?? 0;
            $tugasluar = $rekap['tugasluar'] ?? 0;
            $konversilembur = $rekap['konversilembur'] ?? 0;
            $konversioperasi = $rekap['konversioperasi'] ?? 0;
            $lemburkhusus = $rekap['lemburkhusus'] ?? 0;
            
            // Potongan
            $pph21 = $rekap['pph21'] ?? 0;
            $qurban = $rekap['qurban'] ?? 0;
            $potransport = $rekap['potransport'] ?? 0;
            $bpjstk = $rekap['bpjstk'] ?? 0;
            $koperasi = $rekap['koperasi'] ?? 0;
        }

        // Nilai yang selalu ada
        $jmlabsensi = $rekap['jmlabsensi'] ?? 0;
        $kehadiran = $rekap['kehadiran'] ?? 0;
        $nilaiKehadiran = $jmlabsensi * $kehadiran;

        // Logika perhitungan lembur dan operasi
        if ($isTraining) {
            $lemburVal = 0;
            $operasiVal = 0;
        } else {
            $lemburVal = (!empty($lemburkhusus) && $lemburkhusus > 0) 
                     ? ($konversilembur ?? 0) * $lemburkhusus
                     : ($konversilembur ?? 0) * $kehadiran;
            
            $operasiVal = (!empty($lemburkhusus) && $lemburkhusus > 0) 
                     ? ($konversioperasi ?? 0) * $lemburkhusus
                     : ($konversioperasi ?? 0) * $kehadiran;
        }

        // Total penghasilan
        if($isTraining) {
            $totalPenghasilan = $nilaiKehadiran;
        } else {
            if ($rekap['use_new_system'] ?? false) {
                // SISTEM BARU (termasuk operasi)
                $totalPenghasilan =
                    $gajipokok +
                    $tunjstruktural +
                    $tunjkeluarga +
                    $tunjapotek +
                    $tunjfungsional +
                    ($jmlrujukan * $rujukan) +
                    ($jmlabsensi * $uangmakan) +
                    ($jmlabsensi * $kehadiran) +
                    ($tugasluar * $lemburkhusus) +
                    $lemburVal + 
                    $operasiVal +
                    ($doubleshift * $kehadiran);
            } else {
                // SISTEM LAMA (tanpa operasi)
                $totalPenghasilan =
                    $gajipokok +
                    $tunjstruktural +
                    $tunjkeluarga +
                    $tunjapotek +
                    $tunjfungsional +
                    ($jmlrujukan * $rujukan) +
                    ($totalharikerja * $uangmakan) +
                    ($jmlabsensi * $kehadiran) +
                    ($cuti * $kehadiran) +
                    ($tugasluar * $kehadiran) +
                    $lemburVal +
                    ($doubleshift * $kehadiran);
                
                $operasiVal = 0;
            }
        }

        // Potongan
        if($isTraining) {
            $bpjs = 0;
            $zis = 0;
            $infaqPdm = 0;
            $totalPotongan = 0;
        } else {
            $bpjs = ($totalPenghasilan > 4000000) ? 40000 : 28000;
            $zis = round($totalPenghasilan * 0.025);
            $infaqPdm = round($gajipokok * 0.01);
            $totalPotongan = $zis + $pph21 + $qurban + $potransport + $infaqPdm + $bpjs + $bpjstk + $koperasi;
        }
        
        $netto = $totalPenghasilan - $totalPotongan;
    @endphp

    {{-- IDENTITAS PEGAWAI --}}
    <p>
        <strong>Nama:</strong> {{ $rekap['pegawai_nama'] }}<br>
        <strong>NIP:</strong> {{ $rekap['pegawai_nip'] }}<br>
        <strong>Jabatan:</strong> {{ $rekap['jabatan'] }}
        @if($isTraining)
            <br><span class="badge">TRAINING/OJT</span>
        @endif
    </p>

    {{-- PENGHASILAN --}}
    <div class="bg-header">PENGHASILAN</div>
    <table>
        
        <tr>
            <td class="label">Gaji Pokok</td>
            <td class="value">Rp {{ number_format($gajipokok, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Tunj. Struktural</td>
            <td class="value">Rp {{ number_format($tunjstruktural, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Tunj. Keluarga</td>
            <td class="value">Rp {{ number_format($tunjkeluarga, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Tunj. Apotek</td>
            <td class="value">Rp {{ number_format($tunjapotek, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Tunj. Fungsional</td>
            <td class="value">Rp {{ number_format($tunjfungsional, 0, ',', '.') }}</td>
        </tr>
       

        @if($jmlrujukan > 0)
        <tr>
            <td class="label">Tunj. Rujukan <span class="text-muted small">{{ $jmlrujukan }} × Rp {{ number_format($rujukan, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($jmlrujukan * $rujukan, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if($uangmakan > 0 && !$isTraining)
        <tr>
            <td class="label">Uang Makan <span class="text-muted small">{{ $jmlabsensi }} × Rp {{ number_format($uangmakan, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($jmlabsensi * $uangmakan, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if($doubleshift > 0)
        <tr>
            <td class="label">Doubleshift <span class="text-muted small">{{ $doubleshift }} × Rp {{ number_format($kehadiran, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($doubleshift * $kehadiran, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if(!($rekap['use_new_system'] ?? false) && $cuti > 0 && !$isTraining)
        <tr>
            <td class="label">Cuti <span class="text-muted small">{{ $cuti }} × Rp {{ number_format($kehadiran, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($cuti * $kehadiran, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if($tugasluar > 0)
        <tr>
            <td class="label">Tugas Luar <span class="text-muted small">{{ $tugasluar }} × Rp {{ number_format($lemburkhusus, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($tugasluar * $lemburkhusus, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if($konversilembur > 0 && !$isTraining)
        <tr>
            <td class="label">Lembur <span class="text-muted small">{{ $konversilembur }} × Rp {{ number_format(!empty($lemburkhusus) && $lemburkhusus > 0 ? $lemburkhusus : $kehadiran, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($lemburVal, 0, ',', '.') }}</td>
        </tr>
        @endif

        @if(($rekap['use_new_system'] ?? false) && $konversioperasi > 0 && !$isTraining)
        <tr>
            <td class="label">Operasi <span class="text-muted small">{{ $konversioperasi }} × Rp {{ number_format(!empty($lemburkhusus) && $lemburkhusus > 0 ? $lemburkhusus : $kehadiran, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($operasiVal, 0, ',', '.') }}</td>
        </tr>
        @endif

        {{-- KEHADIRAN (selalu tampil) --}}
        <tr>
            <td class="label">Kehadiran <span class="text-muted small">{{ $jmlabsensi }} × Rp {{ number_format($kehadiran, 0, ',', '.') }}</span></td>
            <td class="value">Rp {{ number_format($nilaiKehadiran, 0, ',', '.') }}</td>
        </tr>

        {{-- TOTAL PENGHASILAN --}}
        <tr class="total-row">
            <td>Total Penghasilan</td>
            <td class="value">Rp {{ number_format($totalPenghasilan, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- POTONGAN --}}
    <div class="bg-header">POTONGAN</div>
    <table>
        <tr>
            <td class="label">ZIS (2.5%)</td>
            <td class="value">Rp {{ number_format($zis, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">PPH21</td>
            <td class="value">Rp {{ number_format($pph21, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Qurban</td>
            <td class="value">Rp {{ number_format($qurban, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Transport</td>
            <td class="value">Rp {{ number_format($potransport, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Infaq PDM (1% GP)</td>
            <td class="value">Rp {{ number_format($infaqPdm, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">BPJS Kes</td>
            <td class="value">Rp {{ number_format($bpjs, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">BPJS TK</td>
            <td class="value">Rp {{ number_format($bpjstk, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Koperasi</td>
            <td class="value">Rp {{ number_format($koperasi, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-potongan">
            <td>Total Potongan</td>
            <td class="value">Rp {{ number_format($totalPotongan, 0, ',', '.') }}</td>
        </tr>
    </table>

    {{-- TOTAL DITERIMA --}}
    <div class="text-center">
        <div class="highlight">Total Diterima</div>
        <div class="highlight">Rp {{ number_format($netto, 0, ',', '.') }}</div>
        <div class="note">Semoga Berkah!</div>
    </div>

    <hr>

    <div class="text-right text-muted">
        <small>Boja, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

</body>
</html>