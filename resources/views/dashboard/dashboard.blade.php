@extends('layouts.presensi')

@section('content')

<link rel="stylesheet" href="{{ asset('assets/css/homes.css') }}">

<div id="user-section">
    <div id="user-detail">
        <div class="avatar">
            @if(!empty(Auth::guard('karyawan')->user()->foto))
                @php
                    $path = Storage::url('uploads/karyawan/' . Auth::guard('karyawan')->user()->foto);
                @endphp
                <img src="{{ url($path) }}" alt="avatar">
            @else
                <img src="{{ asset('assets/img/sample/avatar/avatar1.jpg') }}" alt="avatar" loading="lazy">
            @endif
        </div>
        
        <div id="user-info">
            <div id="user-role">Assalamu'alaikum..</div>
            <h3>{{ Auth::guard('karyawan')->user()->nama_lengkap }}</h3>
            <div id="user-role">Jabatan: <strong>{{ Auth::guard('karyawan')->user()->jabatan }}</strong></div>
        </div>
    </div>
</div>
<div class="performance-card">
    <div class="title">Bulan ini: <small>{{ $sisaCuti }} x Sisa Cuti</small></div>

    <div class="performance-grid">
        {{-- Kajian --}}
        <a href="/presensi/recordkajian" class="perf-item perf-kajian {{ request()->routeIs('presensi.recordkajian') ? 'active' : '' }}">
            <ion-icon name="library-outline"></ion-icon>
            <div class="perf-text">
                <div class="label">Kajian</div>
                <div class="value">{{ $totalKajian }}x Hadir</div>
            </div>
        </a>

        {{-- Ahad Pagi --}}
        <a href="/ahadpagi" class="perf-item perf-ahad {{ request()->is('ahadpagi') ? 'active' : '' }}">
            <ion-icon name="sunny-outline"></ion-icon>
            <div class="perf-text">
                <div class="label">Ahad Pagi</div>
                @if(is_numeric($ahadPagi))
                    <div class="value">{{ $ahadPagi }} dari {{ $totalMinggu }}</div>
                    <div class="progress mt-1" style="height: 6px;">
                        @php
                            $persen = $totalMinggu > 0 ? round(($ahadPagi / $totalMinggu) * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: {{ $persen }}%;"
                            aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                @else
                    <small style="font-size:11px;color:#999;">{{ $ahadPagi }}</small>
                @endif
            </div>
        </a>

        <a href="/quran" class="perf-item perf-cuti">
            <ion-icon name="book-outline"></ion-icon>
            <div class="perf-text">
                <div class="value">Qur'an</div>
                <div class="label">{!! $lastQuranText !!}</div>

            </div>
        </a>

        {{-- Saldo Voucher --}}
        <a href="/sale" class="perf-item perf-saldo text-decoration-none">
            <ion-icon name="wallet-outline"></ion-icon>
            <div class="perf-text">
                <div class="label">Saldo Voucher</div>
                <div class="value">Rp {{ number_format(Auth::guard('karyawan')->user()->saldo, 0, ',', '.') }}</div>
            </div>
        </a>
    </div>
    <div class="text-center mt-2" style="font-size: 11px; color: #777;">
        <img src="{{ asset('assets/img/logopku.png') }}" alt="Logo" width="16" class="me-1 align-middle">
        <strong>HRIS Mobile v3.0</strong> – RS PKU Muhammadiyah Boja
    </div>
</div>

<!-- Task Management Summary -->
<div class="performance-card mb-3">
    <div class="todaypresence">
        <div class="rekappresensi">
            <h3 class="section-title">Memo Internal</h3>
            <div class="row text-center">
                @if($ticketSummary->count() > 0)
                    @foreach($ticketSummary as $status => $count)
                        @php
                            $color = match(strtolower($status)) {
                                'to do' => 'secondary',
                                'in progress' => 'info',
                                'review' => 'warning',
                                'done' => 'success',
                                default => 'dark'
                            };
                            
                            // Hitung total komentar untuk tiket dengan status ini
                            $ticketsForStatus = $userTickets->where('ticket_status', $status);
                            $totalComments = $ticketsForStatus->sum('comment_count');
                        @endphp
                        <div class="col-3 mb-2">
                            <div class="card stat-card" data-type="ticket" data-status="{{ $status }}">
                                <div class="card-body position-relative p-2">
                                    <!-- Jumlah Tiket di pojok KIRI ATAS -->
                                    @if($count > 0)
                                        <span class="badge bg-{{ $color }} position-absolute count-badge" 
                                              style="top: -5px; left: -5px; font-size: 0.5rem; padding: 2px 4px;">
                                            {{ $count }}
                                        </span>
                                    @endif
                                    
                                    <!-- Jumlah Komentar di pojok KANAN ATAS -->
                                    @if($totalComments > 0)
                                        <span class="badge bg-danger position-absolute comment-badge" 
                                              style="top: -5px; right: -5px; font-size: 0.5rem; padding: 2px 4px;">
                                            {{ $totalComments }}
                                            <ion-icon name="chatbubble" style="font-size: 0.5rem;"></ion-icon>
                                        </span>
                                    @endif
                                    
                                    <!-- Icon dan Label -->
                                    <ion-icon name="clipboard-outline" class="text-{{ $color }} stat-icon" style="font-size: 1.5rem;"></ion-icon>
                                 
                                    <span class="stat-label" style="font-size: 0.65rem;">{{ $status }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <p class="text-muted small">Tidak ada tiket</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- Task Management Summary -->

<div class="scrollable-content">
    <div class="rekappresensi">
        <h4 class="text-center">Akses Cepat:</h4>

        <button onclick="location.href='{{ route('form.scan.camera') }}'" class="btn btn-success w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="qr-code-outline"></ion-icon>
            <span>| Scan QR Kehadiran Kajian</span>
        </button>

        <!-- Tombol Absen Ahad Pagi - buka di tab/browser baru -->
        <button onclick="window.open('https://kajian.pcmboja.com', '_blank')" class="btn btn-success w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="calendar-outline"></ion-icon>
            <span>| Absen Ahad Pagi</span>
        </button>

        <button onclick="location.href='{{ route('presensi.recordkajian') }}'" class="btn btn-primary w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="school-outline"></ion-icon>
            <span>| Record Kehadiran Kajian</span>
        </button>

        @php $idUser = Auth::guard('karyawan')->user()->jabatan ?? ''; @endphp

        @if(in_array($idUser, ['Security', 'Pemegang Saham', 'SDI','IT']))
        <button onclick="location.href='{{ route('inventaris.index') }}'" class="btn btn-warning w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="briefcase-outline"></ion-icon>
            <span>| Inventaris Security</span>
        </button>
        @endif

        @if(in_array($idUser, ['Security','Pemegang Saham', 'IT', 'SDI']))
        <button onclick="location.href='{{ route('kegiatan.index') }}'" class="btn btn-warning w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="clipboard-outline"></ion-icon>
            <span>| Kegiatan Harian Security</span>
        </button>
        @endif

        @if(in_array($idUser, ['SDI', 'Kabid Pelayanan', 'IT Network', 'IT','Direktur', 'Binroh']))
        <button onclick="location.href='{{ route('presensi.agenda') }}'" class="btn btn-info w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="document-text-outline"></ion-icon>
            <span>| Input Agenda</span>
        </button>
        @endif

        {{-- Website Rumah Sakit --}}
        <button onclick="openWebsite()" class="btn btn-info w-100 d-flex align-items-center justify-content-start gap-3 mb-2">
            <ion-icon name="medkit-outline"></ion-icon>
            <span>| Website Rumah Sakit</span>
            <span id="spinner-rs" class="spinner-border spinner-border-sm ms-auto d-none" role="status"></span>
        </button>

        <button onclick="loadPasien()" class="btn btn-info w-100 d-flex align-items-center justify-content-start gap-3">
            <ion-icon name="medkit-outline"></ion-icon>
            <span>| Update Pasien</span>
            <span id="spinner" class="spinner-border spinner-border-sm ms-auto d-none" role="status"></span>
        </button>

    </div>
</div>

<!-- Modal Tiket -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="section-title text-white" id="ticketModalLabel">Daftar Tiket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="list-group" id="ticketList">
          <!-- List tiket akan diisi lewat JS -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== GENERAL STYLES ===== */
.performance-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.section-title {
    font-size: 1.1rem;
    margin-bottom: 16px;
    color: #2c3e50;
    font-weight: 600;
    text-align: center;
    padding-bottom: 0;
}

/* ===== STAT CARDS ===== */
.stat-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.stat-card .card-body {
    padding: 8px !important;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 50px;
}

/* Badge positioning - FIXED */
.count-badge {
    position: absolute;
    top: -8px;
    left: -8px;
    font-size: 0.5rem;
    padding: 2px 4px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    z-index: 10;
    border: 1px solid #fff;
}

.comment-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 0.5rem;
    padding: 2px 4px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(220,53,69,0.3);
    z-index: 10;
    background: linear-gradient(135deg, #dc3545, #ff6b6b) !important;
    border: 1px solid #fff;
}

.comment-badge ion-icon {
    margin-left: 1px;
    font-size: 0.5rem;
}

/* ICON CENTERED */
.stat-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    margin-bottom: 4px;
    height: 40px;
}

.stat-icon {
    font-size: 1.8rem;
    text-align: center;
}

.stat-label {
    font-size: 0.65rem;
    font-weight: 500;
    color: #495057;
    text-align: center;
    margin-top: 2px;
    width: 100%;
}

/* ===== TABS ===== */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 16px;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    border-radius: 6px 6px 0 0;
    font-size: 0.7rem;
    padding: 2px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: #f8f9fa;
    border-bottom: 3px solid #007bff;
}

.tab-icon {
    font-size: 1.2rem;
}

/* Tab Sections */
.tab-section {
    background-color: transparent;
    border-radius: 8px;
    padding: 0;
}

.tab-section-title {
    font-size: 0.95rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 12px;
    color: #2c3e50;
    padding-bottom: 0;
    border-bottom: none;
}

/* ===== PRESENCE CARDS ===== */
.stylish-presence .presence-card {
    background: #fff;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 3px solid #007bff;
}

.presence-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.8rem;
    margin-bottom: 8px;
    color: #2c3e50;
}

.presence-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}

.presence-item {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.presence-icon-container {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
}

.presence-icon-container ion-icon {
    font-size: 0.9rem;
}

.presence-info {
    flex: 1;
}

.presence-label {
    display: block;
    font-size: 0.65rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 1px;
}

.presence-time {
    font-size: 0.8rem;
    margin: 0;
    font-weight: 600;
    line-height: 1.2;
}

.presence-divider {
    width: 1px;
    height: 24px;
    background: #e9ecef;
    margin: 0 6px;
}

/* ===== LEADERBOARD CARDS ===== */
.leaderboard-presence .leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    border-left: 3px solid #28a745;
}

.leaderboard-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.leaderboard-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 3px;
}

.user-name {
    font-size: 0.85rem;
    color: #2c3e50;
    margin: 0;
    line-height: 1.2;
}

.user-position {
    font-size: 0.7rem;
    color: #6c757d;
    line-height: 1.2;
}

.time-badge {
    font-size: 0.65rem;
    font-weight: 500;
    padding: 3px 6px;
    border-radius: 4px;
    min-width: 55px;
    text-align: center;
}

.badge-ontime {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-late {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f1b0b7;
}

.badge-pulang {
    background: #ffeaa7;
    color: #856404;
    border: 1px solid #ffda6a;
}

.badge-nopulang {
    background: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

/* ===== EMPTY STATES ===== */
.empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.empty-state ion-icon {
    font-size: 2.5rem;
    color: #ced4da;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 0.85rem;
    margin: 0;
    color: #6c757d;
}

/* ===== TICKET & COMMENT STYLES ===== */
.ticket-description {
    line-height: 1.5;
}

.ticket-description p {
    margin-bottom: 0.5rem;
}

.ticket-description figure {
    margin: 1rem 0;
    padding: 0.5rem;
    background: #e6e6e6ff;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.ticket-description img,
.comment-text-bg img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: block;
    margin: 8px 0;
}

.comment-background {
    background-color: #fff8e1 !important;
    border-left: 3px solid #ffc107 !important;
    border-radius: 8px !important;
    margin-bottom: 10px !important;
}

.comment-text-bg {
    background-color: #fffde7 !important;
    font-style: italic !important;
    line-height: 1.5 !important;
    color: #5d4037 !important;
    border-radius: 6px !important;
    border: 1px solid #ffecb3 !important;
}

.comment-author {
    color: #e65100 !important;
    font-size: 0.9rem !important;
}

.comment-time {
    color: #8d6e63 !important;
    font-size: 0.75rem !important;
}

.comments-section {
    background: linear-gradient(to bottom, #f9f9f9, #fff);
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
}

.empty-comments {
    background-color: #f5f5f5;
    border-radius: 8px;
    border: 2px dashed #bdbdbd;
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

/* ===== CLEANUP ===== */
.listview.image-listview::before,
.listview.image-listview::after,
.presence-card::before,
.presence-card::after,
.leaderboard-item::before,
.leaderboard-item::after {
    display: none !important;
}

.stylish-presence,
.leaderboard-presence {
    border: none !important;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 576px) {
    .performance-card {
        padding: 12px;
    }
    
    .section-title {
        font-size: 1rem;
    }
    
    .stat-card .card-body {
        padding: 6px !important;
        min-height: 80px;
    }
    
    .count-badge,
    .comment-badge {
        font-size: 0.45rem !important;
        padding: 1px 3px !important;
        min-width: 16px;
        height: 16px;
        top: -6px;
    }
    
    .count-badge {
        left: -6px;
    }
    
    .comment-badge {
        right: -6px;
    }
    
    .stat-icon {
        font-size: 1.6rem !important;
    }
    
    .stat-icon-container {
        height: 35px;
    }
    
    .stat-label {
        font-size: 0.6rem !important;
    }
    
    .presence-card {
        padding: 8px 10px;
    }
    
    .leaderboard-item {
        padding: 8px 10px;
    }
    
    .comment-background {
        padding: 12px !important;
    }
    
    .comment-text-bg {
        font-size: 0.9rem !important;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.comment-badge {
    animation: pulse 2s infinite;
}

/* ===== UTILITY CLASSES ===== */
.img-fluid {
    max-width: 100%;
    height: auto;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allTickets = @json($userTickets ?? []);
    const presensiModalEl = document.getElementById('presensiModal');
    const ticketModalEl = document.getElementById('ticketModal');
    const presensiList = document.getElementById('presensiList');
    const ticketList = document.getElementById('ticketList');

    // Data presensi dari controller (akan diisi via AJAX atau langsung dari data yang ada)
    const presensiData = @json($rekapPresensiBulanIni ?? []);

    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function () {
            const type = this.getAttribute('data-type');
            const label = this.getAttribute('data-label');
            const status = this.getAttribute('data-status');

            if (type === 'presensi') {
                showPresensiModal(label);
            } else if (type === 'ticket') {
                showTicketModal(status);
            }
        });
    });

    function showPresensiModal(label) {
        let filteredData = [];
        let modalTitle = 'Detail Presensi';
        
        // Filter data berdasarkan label
        Object.keys(presensiData).forEach(tanggal => {
            const data = presensiData[tanggal];
            const jamMasuk = data.masuk?.jam_in;
            const jamPulang = data.pulang?.jam_in;
            const jamMasukShift = data.jam_masuk_shift;
            
            let status = 'Hadir';
            if (!jamMasuk && !jamPulang) {
                status = 'Tidak Hadir';
            } else if (jamMasuk && jamMasukShift) {
                const isTerlambat = new Date(`1970-01-01T${jamMasuk}`) > new Date(`1970-01-01T${jamMasukShift}`);
                if (isTerlambat) {
                    status = 'Terlambat';
                }
            }

            // Filter sesuai label
            if (label === 'Hadir' && (status === 'Hadir' || status === 'Terlambat')) {
                filteredData.push({ tanggal, data, status });
            } else if (label === 'Telat' && status === 'Terlambat') {
                filteredData.push({ tanggal, data, status });
            }
        });

        // Jika label Izin atau Sakit, tampilkan pesan kosong
        if (label === 'Izin' || label === 'Sakit') {
            presensiList.innerHTML = `<div class="text-center p-3 text-muted">
                Tidak ada data ${label.toLowerCase()} untuk bulan ini.
            </div>`;
            document.getElementById('presensiModalLabel').textContent = `${modalTitle} - ${label}`;
            showModal(presensiModalEl);
            return;
        }

        // Tampilkan hasil filter
        presensiList.innerHTML = '';

        if (filteredData.length === 0) {
            presensiList.innerHTML = `<div class="text-center p-3 text-muted">
                Tidak ada data presensi untuk <b>${label}</b>.
            </div>`;
        } else {
            filteredData.forEach(({ tanggal, data, status }) => {
                const jamMasuk = data.masuk?.jam_in;
                const jamPulang = data.pulang?.jam_in;
                const jamMasukShift = data.jam_masuk_shift;
                
                const listItem = document.createElement('div');
                listItem.className = 'list-group-item list-group-item-action border-bottom';
                
                const tglLabel = new Date(tanggal).toLocaleDateString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                listItem.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-dark">${tglLabel}</h6>
                        <span class="badge px-3 bg-${getPresensiStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div class="row small text-muted">
                        <div class="col-6">
                            <strong>Masuk:</strong> ${jamMasuk ? new Date(`1970-01-01T${jamMasuk}`).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) : '-'}
                        </div>
                        <div class="col-6">
                            <strong>Pulang:</strong> ${jamPulang ? new Date(`1970-01-01T${jamPulang}`).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) : '-'}
                        </div>
                    </div>
                    ${data.shift && data.shift !== '-' ? `<small class="text-muted">Shift: ${data.shift}</small>` : ''}
                    ${jamMasukShift ? `<small class="text-muted d-block">Jam Shift: ${jamMasukShift}</small>` : ''}
                `;
                presensiList.appendChild(listItem);
            });
        }

        document.getElementById('presensiModalLabel').textContent = `${modalTitle} - ${label}`;
        showModal(presensiModalEl);
    }


    function showTicketModal(status) {
    // Filter tiket berdasarkan status
    const filtered = allTickets.filter(t => 
        t.ticket_status && t.ticket_status.toLowerCase() === status.toLowerCase()
    );

    ticketList.innerHTML = '';

    if (!filtered.length) {
        ticketList.innerHTML = `<div class="text-center p-3 text-muted">
            Tidak ada tiket dengan status <b>${status}</b>.
        </div>`;
    } else {
        filtered.forEach(ticket => {
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item border-bottom p-3';
            
            const processedDescription = decodeHtmlEntities(ticket.description || '');
            const hasComments = ticket.comments && ticket.comments.length > 0;
            
            listItem.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-dark fw-bold">${ticket.ticket_name || '-'}</h6>
                        <h6 class="mb-1 text-dark fw-bold">${ticket.created_by_name || '-'}</h6>
                        <small class="text-muted d-block">
                            <ion-icon name="time-outline" class="align-middle me-1"></ion-icon>
                            ${formatDate(ticket.status_created_at)}
                        </small>
                    </div>
                    <span class="badge px-3 py-2 bg-${getStatusColor(ticket.ticket_status)} ms-2">
                        ${ticket.ticket_status}
                    </span>
                </div>
                
                <div class="ticket-description bg-light rounded">
                    <h6 class="text-muted mb-2">
                        <ion-icon name="document-text-outline" class="me-1"></ion-icon>
                        Deskripsi
                    </h6>
                    ${processedDescription}
                </div>
                
                <div class="ticket-meta d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <small class="text-muted">
                            <ion-icon name="calendar-outline" class="me-1"></ion-icon>
                            Mulai: <span class="text-success fw-semibold">${formatSimpleDate(ticket.start_date)}</span>
                        </small>
                        <small class="text-muted ms-3">
                            <ion-icon name="flag-outline" class="me-1"></ion-icon>
                            Deadline: <span class="text-danger fw-semibold">${formatSimpleDate(ticket.due_date)}</span>
                        </small>
                    </div>
                    
                    ${hasComments ? `
                        <small class="text-primary">
                            <ion-icon name="chatbubble-ellipses-outline" class="me-1"></ion-icon>
                            ${ticket.comments.length} komentar
                        </small>
                    ` : ''}
                </div>
                
                ${hasComments ? `
                    <div class="comments-section mt-3 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <ion-icon name="chatbubble-outline" class="me-1"></ion-icon>
                            Komentar (${ticket.comments.length})
                        </h6>
                        ${renderComments(ticket.comments)}
                    </div>
                ` : ''}
            `;
            ticketList.appendChild(listItem);
        });
    }

    document.getElementById('ticketModalLabel').textContent = `Tiket - ${status}`;
    showModal(ticketModalEl);
}

// Fungsi untuk render komentar dengan styling khusus
function renderComments(comments) {
    if (!comments || comments.length === 0) {
        return '<div class="empty-comments text-center p-4 text-muted">Tidak ada komentar</div>';
    }
    
    // Filter komentar unik berdasarkan ID
    const uniqueComments = comments.filter((comment, index, self) =>
        index === self.findIndex((c) => (
            c.id === comment.id && c.id !== null && c.id !== undefined
        ))
    );
    
    // Sort comments by date (newest first)
    const sortedComments = uniqueComments.sort((a, b) => {
        const dateA = a.created_at ? new Date(a.created_at) : new Date(0);
        const dateB = b.created_at ? new Date(b.created_at) : new Date(0);
        return dateB - dateA;
    });
    
    return sortedComments.map((comment, idx) => {
        // Process teks komentar
        let processedText = '';
        if (comment.text && comment.text.trim() !== '') {
            processedText = decodeHtmlEntities(comment.text);
        } else {
            processedText = '<span class="text-muted fst-italic">[Tidak ada teks]</span>';
        }
        
        return `
            <div class="comment-item mb-3 p-3 rounded comment-background">
                <div class="comment-meta d-flex justify-content-between align-items-center mb-2">
                    <span class="comment-user d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 28px; height: 28px; margin-right: 8px; font-size: 0.8rem;">
                            <ion-icon name="person" style="font-size: 0.9rem;"></ion-icon>
                        </div>
                        <div>
                            <strong class="comment-author">${comment.user_name || 'Anonymous'}</strong>
                            <div class="comment-time text-muted" style="font-size: 0.7rem;">
                                ${formatCommentTime(comment.created_at)}
                            </div>
                        </div>
                    </span>
                </div>
                <div class="comment-text mt-2 p-2 rounded comment-text-bg">
                    ${processedText}
                </div>
            </div>
        `;
    }).join('');
}

// Fungsi format waktu komentar
function formatCommentTime(timeString) {
    if (!timeString) return '';
    
    try {
        const date = new Date(timeString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return `${diffMins} menit lalu`;
        if (diffHours < 24) return `${diffHours} jam lalu`;
        if (diffDays < 7) return `${diffDays} hari lalu`;
        
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return timeString;
    }
}

// Fungsi format tanggal lengkap
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

// Fungsi format tanggal singkat
function formatSimpleDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

    function showModal(modalEl) {
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
        
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    // Close handlers untuk semua modal
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeAllModals();
        });
    });

    // Close ketika klik outside modal
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAllModals();
            }
        });
    });

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('show');
        });
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }

    // Helper functions
    function decodeHtmlEntities(text) {
        if (!text) return '<span class="text-muted">Tidak ada deskripsi</span>';
        
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        const decoded = textArea.value;
        
        return processImages(decoded);
    }

    function processImages(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        const images = tempDiv.querySelectorAll('img');
        images.forEach(img => {
            img.classList.add('img-fluid', 'rounded');
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            
            const figure = img.closest('figure');
            if (figure) {
                figure.style.margin = '10px 0';
                figure.style.textAlign = 'center';
            }
        });
        
        const links = tempDiv.querySelectorAll('a');
        links.forEach(link => {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        });
        
        return tempDiv.innerHTML;
    }

    function getStatusColor(status) {
        if (!status) return 'dark';
        switch(status.toLowerCase()) {
            case 'done': return 'success';
            case 'review': return 'warning';
            case 'in progress': return 'info';
            case 'to do': return 'secondary';
            default: return 'dark';
        }
    }

    function getPresensiStatusColor(status) {
        switch(status) {
            case 'Hadir': return 'success';
            case 'Terlambat': return 'danger';
            case 'Tidak Hadir': return 'secondary';
            default: return 'dark';
        }
    }
    
});

    function openWebsite() {
        const spinner = document.getElementById('spinner-rs');
        spinner.classList.remove('d-none');

        setTimeout(() => {
            window.open("https://rspkuboja.com", "_blank");
            spinner.classList.add('d-none');
        }, 500); // waktu loading sebelum membuka tab baru
    }

    function loadPasien() {
        const spinner = document.getElementById('spinner');
        spinner.classList.remove('d-none');
        setTimeout(() => {
            window.location.href = "{{ route('presensi.pasien') }}";
        }, 300);
    }
</script>
@endsection
