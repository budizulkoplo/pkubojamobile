@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Target Taqwa</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<style>
    .operan-wrap { margin-top: 58px; padding: 16px; padding-bottom: 96px; }
    .menu-grid { display: grid; gap: 12px; }
    .menu-card {
        display: block;
        border: 0;
        border-radius: 14px;
        background: #fff;
        color: inherit;
        text-decoration: none;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .menu-card .card-body { display: flex; gap: 14px; align-items: center; padding: 18px; }
    .menu-icon {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #e7f7f6;
        color: #078f8a;
        flex: 0 0 auto;
    }
    .menu-card.is-button { width: 100%; text-align: left; }
    .menu-icon.fardhu { background: #e9f2ff; color: #1769e0; }
    .menu-icon.sunnah { background: #fff7e6; color: #b77904; }
    .menu-icon.tahajud { background: #f0edff; color: #5f3dc4; }
    .menu-icon.laporan { background: #ecfdf5; color: #15803d; }
    .menu-icon ion-icon { font-size: 26px; }
    .menu-title { font-weight: 800; margin-bottom: 4px; }
    .menu-subtitle { color: #6c757d; font-size: 0.86rem; line-height: 1.35; }
    .choice-panel {
        display: none;
        margin: 10px 0 16px;
        padding: 12px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #edf2f7;
    }
    .choice-panel.show { display: block; }
    .choice-title { font-size: 0.78rem; font-weight: 800; color: #64748b; margin: 2px 2px 10px; text-transform: uppercase; }
    .choice-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .choice-button {
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        background: #fff;
        color: #1f2937;
        font-weight: 700;
        min-height: 42px;
        padding: 9px 10px;
    }
    .choice-button:active { transform: translateY(1px); }
    .report-form { display: grid; grid-template-columns: 1fr auto; gap: 8px; }
    .report-form input {
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        min-height: 42px;
        padding: 8px 10px;
        background: #fff;
    }
    .report-form button {
        border: 0;
        border-radius: 10px;
        min-height: 42px;
        padding: 8px 14px;
        background: #078f8a;
        color: #fff;
        font-weight: 800;
    }
    .report-list { display: grid; gap: 8px; margin-top: 12px; }
    .report-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: 10px 12px;
    }
    .report-item-title { font-weight: 800; color: #1f2937; }
    .report-item-meta { color: #64748b; font-size: 0.82rem; margin-top: 3px; }
    .report-empty { color: #64748b; font-weight: 700; padding: 8px 2px; }
    .taqwa-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.46);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }
    .taqwa-modal-backdrop.show { display: flex; }
    .taqwa-modal {
        width: min(100%, 360px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        padding: 18px;
    }
    .taqwa-modal-title { font-weight: 900; font-size: 1.05rem; margin-bottom: 8px; }
    .taqwa-modal-message { color: #475569; line-height: 1.45; margin-bottom: 16px; }
    .taqwa-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .taqwa-modal-actions.single { grid-template-columns: 1fr; }
    .taqwa-modal-actions button {
        min-height: 44px;
        border-radius: 10px;
        border: 0;
        font-weight: 800;
    }
    .btn-jamaah { background: #078f8a; color: #fff; }
    .btn-sendiri { background: #e9f2ff; color: #1769e0; }
    .btn-cancel { background: #f1f5f9; color: #334155; margin-top: 8px; width: 100%; min-height: 42px; border-radius: 10px; border: 0; font-weight: 700; }
    .save-alert {
        display: none;
        margin-bottom: 12px;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 0.88rem;
        font-weight: 700;
    }
    .save-alert.show { display: block; }
    .save-alert.success { background: #dcfce7; color: #166534; }
    .save-alert.error { background: #fee2e2; color: #991b1b; }
</style>

<div class="operan-wrap">
    <div id="saveAlert" class="save-alert"></div>

    <div class="menu-grid">
        <a href="{{ route('operan.ngaji') }}" class="menu-card">
            <div class="card-body">
                <div class="menu-icon">
                    <ion-icon name="book-outline"></ion-icon>
                </div>
                <div>
                    <div class="menu-title">Ngaji Shift</div>
                    <div class="menu-subtitle">Lanjutkan bacaan Quran bersama dalam satu kelompok kerja.</div>
                </div>
            </div>
        </a>

        <button type="button" class="menu-card is-button" data-toggle-panel="fardhuPanel">
            <div class="card-body">
                <div class="menu-icon fardhu">
                    <ion-icon name="moon-outline"></ion-icon>
                </div>
                <div>
                    <div class="menu-title">Sholat Fardhu</div>
                    <div class="menu-subtitle">Catatan sholat wajib harian.</div>
                </div>
            </div>
        </button>

        <div id="fardhuPanel" class="choice-panel">
            <div class="choice-title">Pilih Sholat Fardhu</div>
            <div class="choice-grid">
                @foreach(['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'] as $sholat)
                    <button type="button" class="choice-button" data-sholat="{{ $sholat }}" data-jenis="fardhu" data-mode="jamaah">
                        {{ $sholat }}
                    </button>
                @endforeach
            </div>
        </div>

        <button type="button" class="menu-card is-button" data-toggle-panel="sunnahPanel">
            <div class="card-body">
                <div class="menu-icon sunnah">
                    <ion-icon name="sparkles-outline"></ion-icon>
                </div>
                <div>
                    <div class="menu-title">Sholat Sunnah</div>
                    <div class="menu-subtitle">Catat sholat sunnah rawatib.</div>
                </div>
            </div>
        </button>

        <div id="sunnahPanel" class="choice-panel">
            <div class="choice-title">Pilih Sholat Sunnah</div>
            <div class="choice-grid">
                @foreach(["Qobliyah Subuh", "Qobliyah Dzuhur", "Ba'diyah Dzuhur", "Qobliyah Ashar", "Qobliyah Maghrib", "Ba'diyah Maghrib", "Qobliyah Isya", "Ba'diyah Isya"] as $sholat)
                    <button type="button" class="choice-button" data-sholat="{{ $sholat }}" data-jenis="sunnah" data-mode="jamaah">
                        {{ $sholat }}
                    </button>
                @endforeach
            </div>
        </div>

        <button type="button" class="menu-card is-button" data-sholat="Tahajud" data-jenis="sunnah" data-mode="tahajud">
            <div class="card-body">
                <div class="menu-icon tahajud">
                    <ion-icon name="cloudy-night-outline"></ion-icon>
                </div>
                <div>
                    <div class="menu-title">Tahajud</div>
                    <div class="menu-subtitle">Catat ikhtiar malam dalam sujud dan doa yang tenang.</div>
                </div>
            </div>
        </button>

        <button type="button" class="menu-card is-button" data-toggle-panel="laporanPanel">
            <div class="card-body">
                <div class="menu-icon laporan">
                    <ion-icon name="document-text-outline"></ion-icon>
                </div>
                <div>
                    <div class="menu-title">Laporan</div>
                    <div class="menu-subtitle">Lihat catatan Target Taqwa yang sudah dijalankan pada tanggal tertentu.</div>
                </div>
            </div>
        </button>

        <div id="laporanPanel" class="choice-panel">
            <div class="choice-title">Filter Tanggal</div>
            <form id="reportForm" class="report-form">
                <input type="date" id="reportDate" value="{{ now()->toDateString() }}" required>
                <button type="submit">Tampil</button>
            </form>
            <div id="reportList" class="report-list"></div>
        </div>
    </div>
</div>

<div id="taqwaModalBackdrop" class="taqwa-modal-backdrop">
    <div class="taqwa-modal">
        <div id="taqwaModalTitle" class="taqwa-modal-title">Target Taqwa</div>
        <div id="taqwaModalMessage" class="taqwa-modal-message"></div>
        <div id="taqwaModalActions" class="taqwa-modal-actions">
            <button type="button" class="btn-jamaah" data-save-jamaah="ya">Jamaah</button>
            <button type="button" class="btn-sendiri" data-save-jamaah="tidak">Sendiri</button>
        </div>
        <button type="button" id="taqwaModalCancel" class="btn-cancel">Batal</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const panels = document.querySelectorAll('.choice-panel');
    const modalBackdrop = document.getElementById('taqwaModalBackdrop');
    const modalMessage = document.getElementById('taqwaModalMessage');
    const modalActions = document.getElementById('taqwaModalActions');
    const modalCancel = document.getElementById('taqwaModalCancel');
    const alertBox = document.getElementById('saveAlert');
    const reportForm = document.getElementById('reportForm');
    const reportDate = document.getElementById('reportDate');
    const reportList = document.getElementById('reportList');
    let selectedPrayer = null;

    const showAlert = (type, message) => {
        alertBox.className = `save-alert show ${type}`;
        alertBox.textContent = message;
        window.setTimeout(() => {
            alertBox.className = 'save-alert';
            alertBox.textContent = '';
        }, 3200);
    };

    const togglePanel = (panelId) => {
        panels.forEach(panel => {
            panel.classList.toggle('show', panel.id === panelId && !panel.classList.contains('show'));
        });
    };

    const openModal = (button) => {
        const namaSholat = button.dataset.sholat;
        const jenis = button.dataset.jenis;
        const mode = button.dataset.mode;

        if (mode === 'tahajud') {
            const now = new Date();
            const minutesNow = (now.getHours() * 60) + now.getMinutes();
            if (minutesNow > 240) {
                showAlert('error', 'Maaf saat ini bukan waktunya sholat tahajud.');
                return;
            }
        }

        selectedPrayer = { nama_sholat: namaSholat, jenis };

        if (mode === 'tahajud') {
            modalMessage.textContent = 'Bismillah saya sholat tahajud semoga memberi kebaikan kepada saya';
            modalActions.classList.add('single');
            modalActions.innerHTML = '<button type="button" class="btn-jamaah" data-save-jamaah="tidak">Simpan</button>';
        } else {
            modalMessage.textContent = `Alhamdulillah saya masih di beri kesempatan sholat ${namaSholat === 'Subuh' ? 'Shubuh' : namaSholat}`;
            modalActions.classList.remove('single');
            modalActions.innerHTML = '<button type="button" class="btn-jamaah" data-save-jamaah="ya">Jamaah</button><button type="button" class="btn-sendiri" data-save-jamaah="tidak">Sendiri</button>';
        }

        modalBackdrop.classList.add('show');
    };

    const closeModal = () => {
        modalBackdrop.classList.remove('show');
        selectedPrayer = null;
    };

    const savePrayer = async (jamaah) => {
        if (!selectedPrayer) {
            return;
        }

        try {
            const response = await fetch("{{ route('operan.sholat.mark') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    ...selectedPrayer,
                    jamaah
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal menyimpan catatan sholat.');
            }

            closeModal();
            showAlert('success', data.message || 'Catatan sholat berhasil disimpan.');

            if (Notification.permission === 'granted') {
                new Notification('Target Taqwa', {
                    body: data.message || 'Catatan sholat berhasil disimpan.',
                    icon: '/assets/img/icon/logo.png'
                });
            }
        } catch (error) {
            showAlert('error', error.message || 'Gagal menyimpan catatan sholat.');
        }
    };

    const formatTime = (value) => {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    };

    const renderReport = (data) => {
        const items = [];

        (data.sholat || []).forEach(row => {
            items.push(`
                <div class="report-item">
                    <div class="report-item-title">${row.nama_sholat}</div>
                    <div class="report-item-meta">Sholat ${row.jenis} • ${row.jamaah === 'ya' ? 'Jamaah' : 'Sendiri'} • ${formatTime(row.created_at)}</div>
                </div>
            `);
        });

        const ngajiSelesai = (data.ngaji || []).filter(row => row.flag === 'selesai' || row.flag === null);
        ngajiSelesai.forEach(row => {
            items.push(`
                <div class="report-item">
                    <div class="report-item-title">Ngaji Shift</div>
                    <div class="report-item-meta">Sampai ${row.surat} ayat ${row.ayat} • ${formatTime(row.created_at)}</div>
                </div>
            `);
        });

        reportList.innerHTML = items.length ? items.join('') : '<div class="report-empty">Belum ada catatan Target Taqwa pada tanggal ini.</div>';
    };

    const loadReport = async () => {
        reportList.innerHTML = '<div class="report-empty">Memuat laporan...</div>';

        try {
            const params = new URLSearchParams({ tanggal: reportDate.value });
            const response = await fetch(`{{ route('operan.taqwa.report') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal memuat laporan.');
            }

            renderReport(data);
        } catch (error) {
            reportList.innerHTML = `<div class="report-empty">${error.message || 'Gagal memuat laporan.'}</div>`;
        }
    };

    document.querySelectorAll('[data-toggle-panel]').forEach(button => {
        button.addEventListener('click', () => togglePanel(button.dataset.togglePanel));
    });

    document.querySelectorAll('[data-sholat]').forEach(button => {
        button.addEventListener('click', () => openModal(button));
    });

    modalActions.addEventListener('click', (event) => {
        const button = event.target.closest('[data-save-jamaah]');
        if (button) {
            savePrayer(button.dataset.saveJamaah);
        }
    });

    modalCancel.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', (event) => {
        if (event.target === modalBackdrop) {
            closeModal();
        }
    });

    reportForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadReport();
    });
});
</script>
@endsection
