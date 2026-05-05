<style>
   .appBottomMenu {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    border-top: 1px solid #ccc;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 65px;
    z-index: 999;
    padding: 0 10px;
}

.appBottomMenu .scroll-area {
    display: flex;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: none;
    -ms-overflow-style: none;
    flex: 1;
    gap: 20px; /* Tambahkan jarak antar item menu */
    padding-right: 15px; /* Beri ruang sebelum tombol logout */
}

.appBottomMenu .scroll-area::-webkit-scrollbar {
    display: none;
}

.appBottomMenu .item {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    padding: 5px 0;
    text-decoration: none;
}

.appBottomMenu .item .col {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 12px;
    color: #444;
    gap: 3px;
}

.appBottomMenu .item.active .col {
    color: #007bff;
}

.appBottomMenu .logout-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    padding: 5px 0;
    text-decoration: none;
}

.appBottomMenu .logout-btn .col {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: red;
    gap: 3px;
    font-size: 12px;
}

/* Memastikan icon dan teks sejajar */
.appBottomMenu .item ion-icon,
.appBottomMenu .logout-btn ion-icon {
    font-size: 20px;
}

.appBottomMenu .item strong,
.appBottomMenu .logout-btn strong {
    font-weight: 500;
}
</style>

<div class="appBottomMenu">
    <div class="scroll-area">
        <a href="/dashboard" class="item {{ request()->is('dashboard') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="home-outline"></ion-icon>
                <strong>Home</strong>
            </div>
        </a>
        <a href="/editprofile" class="item {{ request()->is('editprofile') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="person-outline"></ion-icon>
                <strong>Profil</strong>
            </div>
        </a>

        <a href="/presensi/agenda/list" class="item {{ request()->is('presensi/agenda/list') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="document-text-outline"></ion-icon>
                <strong>Agenda</strong>
            </div>
        </a>
        <a href="/kalender" class="item {{ request()->is('kalender') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="calendar-outline"></ion-icon>
                <strong>Kalender</strong>
            </div>
        </a>
        <a href="/kalender/lembur" class="item {{ request()->is('kalender/lembur') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="finger-print-outline"></ion-icon>
                <strong>Lembur</strong>
            </div>
        </a>
        <a href="/statistik" class="item {{ request()->is('statistik') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="pie-chart-outline"></ion-icon>
                <strong>Statistik</strong>
            </div>
        </a>
        <a href="/payroll" class="item {{ request()->is('payroll') ? 'active' : '' }}">
            <div class="col">
                <ion-icon name="receipt-outline"></ion-icon>
                <strong>Payroll</strong>
            </div>
        </a>
    </div>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit" class="logout-btn" style="all: unset; cursor: pointer;">
        <div class="col">
            <ion-icon name="exit-outline"></ion-icon>
            <strong>Logout</strong>
        </div>
    </button>
</form>


</div>