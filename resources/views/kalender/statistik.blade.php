@extends('layouts.presensi')

@section('header')
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="/dashboard" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Statistik Absensi</div>
    <div class="right"></div>
</div>
<!-- * App Header -->
@endsection

@section('content')
<div class="row" style="margin-top:70px">
    <div class="col">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('statistik') }}">
                            <div class="row">
                                
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <input type="month" name="bulan" value="{{ $bulan }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <ion-icon name="pie-chart-outline"></ion-icon> Tampilkan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if($selectedEmployee)
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <p class="card-title mb-0">Statistik {{ $selectedEmployee->pegawai_nama }}</p>
                        <p class="text-muted mb-0 small">{{ \Carbon\Carbon::parse($bulan)->translatedFormat('F Y') }}</p>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <!-- Hari Kerja -->
                            <div class="col-6 col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-1">Hari Kerja</h6>
                                        <h4 class="text-primary mb-0">{{ $totalWorkDays }}</h4>
                                        <small class="text-muted">hari</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total Terlambat -->
                            <div class="col-6 col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-1">Total Terlambat</h6>
                                        <h4 class="text-warning mb-0">{{ $terlambatFormatted }}</h4>
                                        <small class="text-muted">jam:menit</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total Lembur -->
                            <div class="col-6 col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-1">Total Lembur</h6>
                                        <h4 class="text-success mb-0">{{ $lemburFormatted }}</h4>
                                        <small class="text-muted">jam:menit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-1">Double Shift</h6>
                                        <h4 class="text-danger mb-0">{{ $doubleShift }}</h4>
                                        <small class="text-muted">hari</small>
                                    </div>
                                </div>
                            </div>
                            <!-- Hari Cuti -->
                            <div class="col-6 col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="mb-1">Hari Cuti</h6>
                                        <h4 class="text-info mb-0">{{ $totalCuti }}</h4>
                                        <small class="text-muted">hari</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waktu Aktual -->
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Rata-rata Kehadiran</h5>
                    </div>
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Masuk</span>
                            <span class="badge rounded-pill px-3
                                {{ $avgSelisihMasuk > 0 ? 'bg-danger' : ($avgSelisihMasuk < 0 ? 'bg-success' : 'bg-secondary') }}">
                                @if ($avgSelisihMasuk !== null)
                                    {{ abs(round($avgSelisihMasuk, 1)) }} menit 
                                    {{ $avgSelisihMasuk > 0 ? 'terlambat' : ($avgSelisihMasuk < 0 ? 'lebih awal' : 'tepat waktu') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Pulang</span>
                            <span class="badge rounded-pill px-3
                                {{ $avgSelisihPulang < 0 ? 'bg-danger' : ($avgSelisihPulang > 0 ? 'bg-success' : 'bg-secondary') }}">
                                @if ($avgSelisihPulang !== null)
                                    {{ abs(round($avgSelisihPulang, 1)) }} menit 
                                    {{ $avgSelisihPulang > 0 ? 'pulang lebih lambat' : ($avgSelisihPulang < 0 ? 'pulang lebih awal' : 'tepat waktu') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <small>
                        <ion-icon name="information-circle-outline"></ion-icon>
                        Perhitungan berdasarkan {{ $countShiftDays }} hari dengan jadwal shift.
                        <br>
                        Masuk: warna hijau = lebih awal, merah = terlambat.
                        <br>
                        Pulang: warna hijau = pulang lebih lambat, merah = pulang lebih awal.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Grafik -->
    <div class="row mt-3">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kehadiran Bulanan</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartKehadiran"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tepat Waktu vs Terlambat</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTepatTerlambat"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tren Keterlambatan Harian (Menit)</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartKeterlambatanHarian"></canvas>
                </div>
            </div>
        </div>
    </div>

        <!-- Detail Hari Kerja -->
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail Hari Kerja</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr align="center">
                                        <th>Tanggal</th>
                                        <th>Masuk</th>
                                        <th>Pulang</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataKalender as $tgl => $item)
                                        @if(!empty($item['jam_masuk']) || !empty($item['jam_pulang']) || !empty($item['status_khusus']))
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($tgl)->translatedFormat('d M Y') }}</td>
                                                <td>{{ $item['jam_masuk'] ?? '-' }}</td>
                                                <td>{{ $item['jam_pulang'] ?? '-' }}</td>
                                                <td align="center">
                                                    @if(!empty($item['status_khusus']))
                                                        {!! '<span class="badge bg-info px-3">' . $item['status_khusus'] . '</span>' !!}
                                                    @else
                                                        <span class="badge bg-success px-3">Masuk</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="row mt-2">
            <div class="col-12">
                <div class="alert alert-info">
                    <ion-icon name="information-circle-outline"></ion-icon>
                    Silakan pilih pegawai untuk melihat statistik.
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@push('myscript')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

    // === 1. Pie Chart Kehadiran ===
    new Chart(document.getElementById('chartKehadiran'), {
        type: 'pie',
        data: {
            labels: ['Masuk', 'Cuti', 'Tugas Luar', 'Tidak Hadir'],
            datasets: [{
                data: [
                    {{ $totalWorkDays }},
                    {{ $totalCuti }},
                    {{ $totalTugasLuar }},
                    {{ $totalHariPeriode - ($totalWorkDays + $totalCuti + $totalTugasLuar) }}
                ],
                backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'],
            }]
        }
    });

    // === 2. Bar Chart Terlambat vs Tepat Waktu ===
    new Chart(document.getElementById('chartTepatTerlambat'), {
        type: 'bar',
        data: {
            labels: ['Tepat Waktu', 'Terlambat', 'Pulang Awal', 'Pulang Lambat'],
            datasets: [{
                label: 'Jumlah Hari',
                data: [
                    {{ $jumlahTepatWaktu ?? 0 }},
                    {{ $jumlahTerlambat ?? 0 }},
                    {{ $jumlahPulangAwal ?? 0 }},
                    {{ $jumlahPulangLambat ?? 0 }}
                ],
                backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#198754']
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }
        }
    });

    // === 3. Line Chart Tren Keterlambatan ===
    new Chart(document.getElementById('chartKeterlambatanHarian'), {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels ?? []) !!},
            datasets: [{
                label: 'Keterlambatan (menit)',
                data: {!! json_encode($chartValues ?? []) !!},
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@endsection