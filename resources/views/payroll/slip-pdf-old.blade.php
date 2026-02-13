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
            /* color: #0d6efd; */
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        .total-potongan {
            font-weight: bold;
            /* color: red; */
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        .highlight {
            /* color: green; */
            font-weight: bold;
            font-size: 13px;
            margin: 5px 0;
        }
        .note { font-size: 10px; margin-top: 3px; }
        img {
            max-height: 35px;
            margin-bottom: 2px;
        }
        p { margin: 5px 0; }
    </style>
</head>
<body>

    <div class="text-center">
        <img src="{{ public_path('assets/img/' . $site['icon']) }}"><br>
        <strong>{{ $site['namaweb'] }}</strong><br>
        Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
    </div>

    <p>
        <strong>Nama:</strong> {{ $rekap['pegawai_nama'] }}<br>
        <strong>NIP:</strong> {{ $rekap['pegawai_nip'] }}<br>
        <strong>Jabatan:</strong> {{ $rekap['jabatan'] }}
    </p>

    @php
        $lemburVal = (!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0) 
                 ? ($rekap['konversilembur'] ?? 0) * ($rekap['lemburkhusus'] ?? 0)
                 : ($rekap['konversilembur'] ?? 0) * ($rekap['kehadiran'] ?? 0);

        $totalPenghasilan =
            $rekap['gajipokok'] +
            $rekap['tunjstruktural'] +
            $rekap['tunjkeluarga'] +
            $rekap['tunjapotek'] +
            $rekap['tunjfungsional'] +
            (($rekap['jmlrujukan'] ?? 0) * $rekap['rujukan']) +
            (($rekap['totalharikerja'] ?? 0) * $rekap['uangmakan']) +
            ($rekap['jmlabsensi'] * $rekap['kehadiran']) +
            ($rekap['cuti'] * $rekap['kehadiran']) +
            ($rekap['tugasluar'] * $rekap['kehadiran']) +
            $lemburVal +
            (($rekap['doubleshift'] ?? 0) * ($rekap['kehadiran'] ?? 0));

        $bpjs = ($totalPenghasilan > 4000000) ? 40000 : 28000;
        $zis = round($totalPenghasilan * 0.025);
        $infaqPdm = round($rekap['gajipokok'] * 0.01);
        $totalPotongan = $zis + ($rekap['pph21'] ?? 0) + ($rekap['qurban'] ?? 0) +
                        ($rekap['potransport'] ?? 0) + $infaqPdm + $bpjs +
                        ($rekap['bpjstk'] ?? 0) + ($rekap['koperasi'] ?? 0);
        $netto = $totalPenghasilan - $totalPotongan;
    @endphp

    <div class="bg-header">Penghasilan</div>
    <table>
    <tr><td class="label">Gaji Pokok</td>
        <td class="value">Rp {{ number_format($rekap['gajipokok'] ?? 0) }}</td></tr>

    <tr><td class="label">Tunj. Struktural</td>
        <td class="value">Rp {{ number_format($rekap['tunjstruktural'] ?? 0) }}</td></tr>

    <tr><td class="label">Tunj. Keluarga</td>
        <td class="value">Rp {{ number_format($rekap['tunjkeluarga'] ?? 0) }}</td></tr>

    <tr><td class="label">Tunj. Apotek</td>
        <td class="value">Rp {{ number_format($rekap['tunjapotek'] ?? 0) }}</td></tr>

    <tr><td class="label">Tunj. Fungsional</td>
        <td class="value">Rp {{ number_format($rekap['tunjfungsional'] ?? 0) }}</td></tr>

    @if(($rekap['jmlrujukan'] ?? 0) > 0)
    <tr>
        <td class="label">Tunj. Rujukan {{ $rekap['jmlrujukan'] ?? 0 }} x Rp {{ number_format($rekap['rujukan'] ?? 0) }} </td>
        <td class="value">
            
            Rp {{ number_format(($rekap['jmlrujukan'] ?? 0) * ($rekap['rujukan'] ?? 0)) }}
        </td>
    </tr>
    @endif

    <tr>
        <td class="label">Uang Makan {{ $rekap['totalharikerja'] ?? 0 }} x Rp {{ number_format($rekap['uangmakan'] ?? 0) }} </td>
        <td class="value">
            
            Rp {{ number_format(($rekap['totalharikerja'] ?? 0) * ($rekap['uangmakan'] ?? 0)) }}
        </td>
    </tr>

    <tr>
        <td class="label">Kehadiran {{ $rekap['jmlabsensi'] ?? 0 }} x Rp {{ number_format($rekap['kehadiran'] ?? 0) }}</td>
        <td class="value">
            Rp {{ number_format(($rekap['jmlabsensi'] ?? 0) * ($rekap['kehadiran'] ?? 0)) }}
        </td>
    </tr>
    @if(($rekap['doubleshift'] ?? 0) > 0)
    <tr>
        <td class="label">Doubleshift {{ $rekap['doubleshift'] ?? 0 }} x Rp {{ number_format($rekap['kehadiran'] ?? 0) }}</td>
        <td class="value">
            Rp {{ number_format($rekap['doubleshift'] * ($rekap['kehadiran'] ?? 0)) }}
        </td>
    </tr>
    @endif
    @if(($rekap['cuti'] ?? 0) > 0)
    <tr>
        <td class="label">Cuti {{ $rekap['cuti'] ?? 0 }} x Rp {{ number_format($rekap['kehadiran'] ?? 0) }}</td>
        <td class="value">
            Rp {{ number_format($rekap['cuti'] * ($rekap['kehadiran'] ?? 0)) }}
        </td>
    </tr>
    @endif    

    @if(($rekap['tugasluar'] ?? 0) > 0)
    <tr>
        <td class="label">Tugas Luar {{ $rekap['tugasluar'] ?? 0 }} x Rp {{ number_format($rekap['kehadiran'] ?? 0) }}</td>
        <td class="value">
            Rp {{ number_format($rekap['tugasluar'] * ($rekap['kehadiran'] ?? 0)) }}
        </td>
    </tr>
    @endif

    {{-- Lembur --}}
    @if(($rekap['konversilembur'] ?? 0) > 0)
    <tr>
        <td class="label">Lembur {{ $rekap['konversilembur'] ?? 0 }} x Rp {{ number_format(!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0 ? $rekap['lemburkhusus'] : $rekap['kehadiran'], 0) }}</td>
        <td class="value">Rp {{ number_format($lemburVal) }}</td>
    </tr>
    @endif

    <tr class="total-row">
        <td>Total Penghasilan</td>
        <td class="value">Rp {{ number_format($totalPenghasilan ?? 0) }}</td>
    </tr>
</table>


    <div class="bg-header">Potongan</div>
    <table>
        <tr><td class="label">ZIS (2.5%)</td><td class="value">Rp {{ number_format($zis ?? 0) }}</td></tr>
        <tr><td class="label">PPH21</td><td class="value">Rp {{ number_format($rekap['pph21'] ?? 0) }}</td></tr>
        <tr><td class="label">Qurban</td><td class="value">Rp {{ number_format($rekap['qurban'] ?? 0) }}</td></tr>
        <tr><td class="label">Transport</td><td class="value">Rp {{ number_format($rekap['potransport'] ?? 0) }}</td></tr>
        <tr><td class="label">Infaq PDM</td><td class="value">Rp {{ number_format($infaqPdm ?? 0) }}</td></tr>
        <tr><td class="label">BPJS Kes</td><td class="value">Rp {{ number_format($bpjs ?? 0) }}</td></tr>
        <tr><td class="label">BPJS TK</td><td class="value">Rp {{ number_format($rekap['bpjstk'] ?? 0) }}</td></tr>
        <tr><td class="label">Koperasi</td><td class="value">Rp {{ number_format($rekap['koperasi'] ?? 0) }}</td></tr>
        <tr class="total-potongan"><td>Total Potongan</td><td class="value">Rp {{ number_format($totalPotongan ?? 0) }}</td></tr>
    </table>

    <div class="text-center">
        <div class="highlight">Total Diterima</div>
        <div class="highlight">Rp {{ number_format($netto ?? 0) }}</div>
        <div class="note">Semoga Berkah!</div>
    </div>

    <div class="text-right text-muted">
        <small>Boja, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>

</body>
</html>
