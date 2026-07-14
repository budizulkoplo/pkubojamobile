@extends('layouts.presensi')

@section('header')
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="/dashboard" class="headerButton">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTitle">Pengantaran Obat</div>
    <div class="right"></div>
</div>
@endsection

@section('content')
<style>
    .delivery-mobile-wrap { margin-top: 58px; padding: 14px; padding-bottom: 96px; }
    .delivery-alert { border-radius: 12px; font-weight: 700; margin-bottom: 12px; padding: 11px 12px; }
    .delivery-alert.success { background: #dcfce7; color: #166534; }
    .delivery-alert.error { background: #fee2e2; color: #991b1b; }
    .summary-card { background: linear-gradient(135deg, #078f8a, #0ea5a0); border-radius: 16px; color: #fff; padding: 16px; margin-bottom: 14px; box-shadow: 0 14px 30px rgba(14, 165, 160, .22); }
    .summary-title { font-size: .86rem; opacity: .9; margin-bottom: 8px; }
    .summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .summary-value { font-size: 1.25rem; font-weight: 900; }
    .tabs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; margin-bottom: 12px; }
    .tab-button { border: 1px solid #dbe4ef; border-radius: 999px; background: #fff; color: #334155; font-size: .78rem; font-weight: 800; min-height: 38px; padding: 7px 8px; }
    .tab-button.active { background: #078f8a; border-color: #078f8a; color: #fff; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .delivery-card { background: #fff; border: 1px solid #edf2f7; border-radius: 14px; box-shadow: 0 10px 24px rgba(15, 23, 42, .07); margin-bottom: 12px; overflow: hidden; }
    .delivery-card-body { padding: 14px; }
    .delivery-title { color: #0f172a; font-size: 1rem; font-weight: 900; margin-bottom: 3px; }
    .delivery-meta { color: #64748b; font-size: .8rem; line-height: 1.45; margin-bottom: 10px; }
    .delivery-address { background: #f8fafc; border-radius: 10px; color: #334155; line-height: 1.45; margin-bottom: 10px; padding: 10px; }
    .delivery-row { display: flex; justify-content: space-between; gap: 12px; border-top: 1px dashed #e2e8f0; padding-top: 9px; margin-top: 9px; font-size: .85rem; }
    .delivery-row strong { color: #0f172a; }
    .delivery-actions { display: grid; gap: 8px; grid-template-columns: 1fr 1fr; margin-top: 12px; }
    .delivery-actions.single { grid-template-columns: 1fr; }
    .delivery-btn { border: 0; border-radius: 10px; font-weight: 900; min-height: 43px; padding: 10px; text-align: center; text-decoration: none; }
    .delivery-btn.primary { background: #078f8a; color: #fff; }
    .delivery-btn.success { background: #16a34a; color: #fff; }
    .delivery-btn.light { background: #e9f2ff; color: #1769e0; display: flex; align-items: center; justify-content: center; }
    .empty-state { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; color: #64748b; padding: 24px 16px; text-align: center; }
    .empty-state ion-icon { color: #94a3b8; font-size: 34px; margin-bottom: 8px; }
    .medicine-list { color: #475569; font-size: .8rem; margin: 8px 0 0; padding-left: 18px; }
</style>

<div class="delivery-mobile-wrap">
    @if (session('success'))
        <div class="delivery-alert success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="delivery-alert error">{{ session('error') }}</div>
    @endif

    <div class="summary-card">
        <div class="summary-title">Rekap pengantaran selesai hari ini</div>
        <div class="summary-grid">
            <div>
                <div class="summary-value">{{ $summary->total_orders }}</div>
                <div>Order</div>
            </div>
            <div>
                <div class="summary-value">Rp {{ number_format($summary->total_fee, 0, ',', '.') }}</div>
                <div>Billing</div>
            </div>
        </div>
    </div>

    <div class="tabs">
        <button type="button" class="tab-button active" data-tab="available">Siap</button>
        <button type="button" class="tab-button" data-tab="mine">Saya Antar</button>
        <button type="button" class="tab-button" data-tab="history">Riwayat</button>
    </div>

    <section class="tab-panel active" id="tab-available">
        @forelse ($availableDeliveries as $delivery)
            @include('pharmacy-deliveries.partials.card', ['delivery' => $delivery, 'mode' => 'available'])
        @empty
            <div class="empty-state">
                <ion-icon name="cube-outline"></ion-icon>
                <div>Belum ada order yang siap diantar.</div>
            </div>
        @endforelse
    </section>

    <section class="tab-panel" id="tab-mine">
        @forelse ($myActiveDeliveries as $delivery)
            @include('pharmacy-deliveries.partials.card', ['delivery' => $delivery, 'mode' => 'mine'])
        @empty
            <div class="empty-state">
                <ion-icon name="bicycle-outline"></ion-icon>
                <div>Belum ada pengantaran dalam tanggung jawab Anda.</div>
            </div>
        @endforelse
    </section>

    <section class="tab-panel" id="tab-history">
        @forelse ($historyDeliveries as $delivery)
            @include('pharmacy-deliveries.partials.card', ['delivery' => $delivery, 'mode' => 'history'])
        @empty
            <div class="empty-state">
                <ion-icon name="time-outline"></ion-icon>
                <div>Riwayat pengantaran masih kosong.</div>
            </div>
        @endforelse
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.tab-panel');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            buttons.forEach(item => item.classList.toggle('active', item === button));
            panels.forEach(panel => panel.classList.toggle('active', panel.id === 'tab-' + button.dataset.tab));
        });
    });
});
</script>
@endsection
