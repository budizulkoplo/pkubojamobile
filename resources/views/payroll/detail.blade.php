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
    <div class="text-center mb-3">
        <img src="{{ asset('assets/img/' . $site['icon']) }}" alt="{{ $site['namaweb'] }}" style="height: 50px;">
        <h6 class="mt-2 mb-0">{{ $site['namaweb'] }}</h6>
        <small class="text-muted">Slip Gaji - {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}</small>
        <!-- @if($rekap['use_new_system'] ?? false)
            <span class="badge bg-success ms-2">Sistem Baru</span>
        @else
            <span class="badge bg-secondary ms-2">Sistem Lama</span>
        @endif -->
    </div>

    <div class="mb-2">
        <strong>NIP:</strong> {{ $rekap['pegawai_nip'] }}<br>
        <strong>Nama:</strong> {{ $rekap['pegawai_nama'] }}<br>
        <strong>Jabatan:</strong> {{ $rekap['jabatan'] }}
    </div>

    @php
        // Logika perhitungan berbeda untuk sistem lama dan baru
        if ($rekap['use_new_system'] ?? false) {
            // SISTEM BARU (termasuk operasi)
            $lemburVal = (!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0) 
                     ? ($rekap['konversilembur'] ?? 0) * ($rekap['lemburkhusus'] ?? 0)
                     : ($rekap['konversilembur'] ?? 0) * ($rekap['kehadiran'] ?? 0);
            
            $operasiVal = (!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0) 
                     ? ($rekap['konversioperasi'] ?? 0) * ($rekap['lemburkhusus'] ?? 0)
                     : ($rekap['konversioperasi'] ?? 0) * ($rekap['kehadiran'] ?? 0);

            $totalPenghasilan =
                $rekap['gajipokok'] +
                $rekap['tunjstruktural'] +
                $rekap['tunjkeluarga'] +
                $rekap['tunjapotek'] +
                $rekap['tunjfungsional'] +
                (($rekap['jmlrujukan'] ?? 0) * $rekap['rujukan']) +
                (($rekap['totalharikerja'] ?? 0) * $rekap['uangmakan']) +
                ($rekap['jmlabsensi'] * $rekap['kehadiran']) +
            
                ($rekap['tugasluar'] * $rekap['kehadiran']) +
                $lemburVal + $operasiVal + // Termasuk operasiVal
                (($rekap['doubleshift'] ?? 0) * ($rekap['kehadiran'] ?? 0));
        } else {
            // SISTEM LAMA (tanpa operasi)
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
                $lemburVal + // Tanpa operasiVal
                (($rekap['doubleshift'] ?? 0) * ($rekap['kehadiran'] ?? 0));
            
            $operasiVal = 0; // Default 0 untuk sistem lama
        }

        $bpjs = ($totalPenghasilan > 4000000) ? 40000 : 28000;
        $zis = round($totalPenghasilan * 0.025);
        $infaqPdm = round($rekap['gajipokok'] * 0.01);
        $totalPotongan = $zis + ($rekap['pph21'] ?? 0) + ($rekap['qurban'] ?? 0) +
                         ($rekap['potransport'] ?? 0) + $infaqPdm + $bpjs +
                         ($rekap['bpjstk'] ?? 0) + ($rekap['koperasi'] ?? 0);
        $netto = $totalPenghasilan - $totalPotongan;
    @endphp

   <div class="card mb-3">
    <div class="card-header bg-primary text-white py-2 px-3">Penghasilan</div>
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
            Gaji Pokok <span>Rp {{ number_format($rekap['gajipokok'] ?? 0) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            Tunj. Struktural <span>Rp {{ number_format($rekap['tunjstruktural'] ?? 0) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            Tunj. Keluarga <span>Rp {{ number_format($rekap['tunjkeluarga'] ?? 0) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            Tunj. Apotek <span>Rp {{ number_format($rekap['tunjapotek'] ?? 0) }}</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
            Tunj. Fungsional <span>Rp {{ number_format($rekap['tunjfungsional'] ?? 0) }}</span>
        </li>

        {{-- Tunjangan Rujukan --}}
        @if(($rekap['jmlrujukan'] ?? 0) > 0)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Tunj. Rujukan <span>Rp {{ number_format(($rekap['jmlrujukan'] ?? 0) * ($rekap['rujukan'] ?? 0)) }}</span>
                </div>
                <div class="text-muted small">
                    {{ $rekap['jmlrujukan'] ?? 0 }} × Rp {{ number_format($rekap['rujukan'] ?? 0) }}
                </div>
            </li>
        @endif

        {{-- Uang Makan --}}
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                Uang Makan <span>Rp {{ number_format(($rekap['totalharikerja'] ?? 0) * ($rekap['uangmakan'] ?? 0)) }}</span>
            </div>
            <div class="text-muted small">
                {{ $rekap['totalharikerja'] ?? 0 }} hari × Rp {{ number_format($rekap['uangmakan'] ?? 0) }}
            </div>
        </li>

        {{-- Kehadiran --}}
        <li class="list-group-item">
            <div class="d-flex justify-content-between">
                Kehadiran <span>Rp {{ number_format(($rekap['jmlabsensi'] ?? 0) * ($rekap['kehadiran'] ?? 0)) }}</span>
            </div>
            <div class="text-muted small">
                {{ $rekap['jmlabsensi'] ?? 0 }} × Rp {{ number_format($rekap['kehadiran'] ?? 0) }}
            </div>
        </li>

        {{-- Doubleshift --}}
        @if(($rekap['doubleshift'] ?? 0) > 0)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Doubleshift <span>Rp {{ number_format(($rekap['doubleshift'] ?? 0) * ($rekap['kehadiran'] ?? 0)) }}</span>
                </div>
                <div class="text-muted small">
                    {{ $rekap['doubleshift'] ?? 0 }} × Rp {{ number_format($rekap['kehadiran'] ?? 0) }}
                </div>
            </li>
        @endif

        {{-- Cuti --}}
        @if(!($rekap['use_new_system'] ?? false)) {{-- HANYA untuk sistem LAMA --}}
            @if(($rekap['cuti'] ?? 0) > 0)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        Cuti <span>Rp {{ number_format(($rekap['cuti'] ?? 0) * ($rekap['kehadiran'] ?? 0)) }}</span>
                    </div>
                    <div class="text-muted small">
                        {{ $rekap['cuti'] ?? 0 }} × Rp {{ number_format($rekap['kehadiran'] ?? 0) }}
                    </div>
                </li>
            @endif
        @endif

        {{-- Tugas Luar --}}
        @if(($rekap['tugasluar'] ?? 0) > 0)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Tugas Luar <span>Rp {{ number_format($rekap['tugasluar'] * ($rekap['kehadiran'] ?? 0)) }}</span>
                </div>
                <div class="text-muted small">
                    {{ $rekap['tugasluar'] }} × Rp {{ number_format($rekap['kehadiran'] ?? 0) }}
                </div>
            </li>
        @endif

        {{-- Lembur --}}
        @if(($rekap['konversilembur'] ?? 0) > 0)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Lembur <span>Rp {{ number_format($lemburVal) }}</span>
                </div>
                <div class="text-muted small">
                    {{ $rekap['konversilembur'] }} × Rp {{ number_format(!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0 ? $rekap['lemburkhusus'] : $rekap['kehadiran'], 0) }}
                </div>
            </li>
        @endif

        {{-- Operasi (hanya untuk sistem baru) --}}
        @if(($rekap['use_new_system'] ?? false) && ($rekap['konversioperasi'] ?? 0) > 0)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Operasi <span>Rp {{ number_format($operasiVal) }}</span>
                </div>
                <div class="text-muted small">
                    {{ $rekap['konversioperasi'] }} × Rp {{ number_format(!empty($rekap['lemburkhusus']) && $rekap['lemburkhusus'] > 0 ? $rekap['lemburkhusus'] : $rekap['kehadiran'], 0) }}
                </div>
            </li>
        @endif

        {{-- Total --}}
        <li class="list-group-item d-flex justify-content-between fw-bold text-primary">
            Total <span>Rp {{ number_format($totalPenghasilan ?? 0) }}</span>
        </li>
    </ul>
</div>

    <!-- Potongan dan bagian lainnya tetap sama -->
    <div class="card mb-3">
        <div class="card-header bg-danger text-white py-2 px-3">Potongan</div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    ZIS (2.5%) <span>Rp {{ number_format($zis ?? 0) }}</span>
                </div>
                <div class="text-muted small">2.5% × Total Penghasilan</div>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                PPH21 <span>Rp {{ number_format($rekap['pph21'] ?? 0) }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                Qurban <span>Rp {{ number_format($rekap['qurban'] ?? 0) }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                Transport <span>Rp {{ number_format($rekap['potransport'] ?? 0) }}</span>
            </li>
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    Infaq PDM (1%) <span>Rp {{ number_format($infaqPdm ?? 0) }}</span>
                </div>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                BPJS Kes <span>Rp {{ number_format($bpjs ?? 0) }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                BPJS TK <span>Rp {{ number_format($rekap['bpjstk'] ?? 0) }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                Koperasi <span>Rp {{ number_format($rekap['koperasi'] ?? 0) }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between fw-bold text-danger">
                Total <span>Rp {{ number_format($totalPotongan ?? 0) }}</span>
            </li>
        </ul>
    </div>

    <div class="card mb-3 border-success">
        <div class="card-body text-center">
            <h5 class="text-success mb-2">Total Diterima</h5>
            <h4 class="text-success">Rp {{ number_format($netto) }}</h4>
            <p class="text-muted mb-0">Semoga Berkah!</p>
        </div>
    </div>

    <div class="text-end text-muted">
        <small>Boja, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</small>
    </div>
<hr>
    <a href="{{ route('slip.pdf', [\Carbon\Carbon::parse($periode)->format('Y'), \Carbon\Carbon::parse($periode)->format('m')]) }}" class="btn btn-sm btn-outline-primary w-100">
        <ion-icon name="download-outline"></ion-icon> Download Slip
    </a>

</div>
@endsection