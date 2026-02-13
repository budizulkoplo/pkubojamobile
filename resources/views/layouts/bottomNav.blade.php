<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Drawer Menu</title>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      padding-bottom: 80px;
    }

    .quickMenuBar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 70px;
      background: #fff;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-around;
      align-items: center;
      z-index: 1001;
      padding: 0 10px;
    }

    .quick-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      font-size: 12px;
      color: #555;
      text-decoration: none;
      background: none;
      border: none;
      cursor: pointer;
    }

    .quick-item.active,
    .quick-item:hover {
      color: #07b8b2;
    }

    .quick-item ion-icon {
      font-size: 22px;
      transition: transform 0.3s ease;
    }

    .menu-main {
      background: #07b8b2;
      color: white;
      padding: 10px;
      border-radius: 50%;
      transform: translateY(-20%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      font-size: 18px;
    }

    .logout {
      color: red !important;
    }

    .logout ion-icon {
      color: red !important;
    }

    .bottomMenuOverlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.3);
      z-index: 1000;
      display: none;
    }

    .bottomMenuOverlay.active {
      display: block;
    }

    .bottomMenuDrawer {
      position: fixed;
      left: 0;
      right: 0;
      bottom: -100%;
      background: #fff;
      transition: bottom 0.3s ease;
      z-index: 1001;
      padding-top: 40px;
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
      box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2);
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .bottomMenuDrawer.active {
      bottom: 0;
    }

    .drawerCloseArrow {
      position: absolute;
      top: -25px;
      left: 50%;
      transform: translateX(-50%);
      background: #ffffff;
      border: none;
      border-radius: 30px;
      padding: 6px 12px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      z-index: 1002;
      animation: pulseDown 1.5s infinite;
    }

    .drawerCloseArrow ion-icon {
      font-size: 20px;
      color: #007bff;
    }

    @keyframes pulseDown {
      0%, 100% { transform: translateX(-50%) translateY(0); }
      50% { transform: translateX(-50%) translateY(5px); }
    }

    .drawerContent {
      overflow-y: auto;
      width: 100%;
      padding: 0 20px 30px;
      flex: 1;
      -webkit-overflow-scrolling: touch;
    }

    .bottomMenuGrid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      max-width: 500px;
      margin: 0 auto;
    }

    .item {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: #333;
      font-size: 13px;
    }

    .item ion-icon {
      font-size: 26px;
      color: #333;
    }

    .item.active ion-icon,
    .item.active .col {
      color: #07b8b2;
    }

    .item.logout ion-icon,
    .item.logout .col {
      color: red !important;
    }

    /* Perbaikan tampilan teks panjang */
    .item .col {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;

      max-width: 82px;              /* PROPORSIONAL & SIMETRIS */
      gap: 6px;                     /* Jarak ikon-teks ideal */
      line-height: 1.25;            /* Pas untuk 1–2 baris */
      white-space: normal;
      word-break: break-word;

      text-wrap: balance;           /* Bagi baris rapi jika support */
    }

    .item strong {
      font-size: 12px;
      line-height: 1.25;
      display: block;
      max-width: 82px;
      text-wrap: balance;
    }

    /* Responsif untuk layar kecil */
    @media (max-width: 360px) {
      .bottomMenuGrid {
        gap: 18px;
      }
      .item .col,
      .item strong {
        max-width: 72px;
      }
    }
</style>

</head>
<body>

<!-- Bottom Quick Menu -->
<div class="quickMenuBar">
  <a href="/dashboard" class="quick-item {{ request()->is('dashboard') ? 'active' : '' }}">
    <ion-icon name="home-outline"></ion-icon>
    <span>Home</span>
  </a>
  <a href="/presensi/agenda/list" class="quick-item {{ request()->is('presensi/agenda/list') ? 'active' : '' }}">
    <ion-icon name="document-text-outline"></ion-icon>
    <span>Agenda</span>
  </a>

  <button class="quick-item menu-main" id="menuToggle">
    <ion-icon id="menuIcon" name="apps-outline"></ion-icon>
  </button>

  <a href="/newkalender" class="quick-item {{ request()->is('kalender') ? 'active' : '' }}">
    <ion-icon name="calendar-outline"></ion-icon>
    <span>Kalender</span>
  </a>

  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="all: unset; display: contents;">
    @csrf
    <button type="submit" class="quick-item logout">
      <ion-icon name="exit-outline"></ion-icon>
      <span>Logout</span>
    </button>
  </form>
</div>

<!-- Overlay -->
<div class="bottomMenuOverlay" id="menuOverlay"></div>

<!-- Drawer -->
<div class="bottomMenuDrawer" id="menuDrawer">
  <div class="drawerCloseArrow" id="drawerClose">
    <ion-icon class="text-primary" name="chevron-down-outline"></ion-icon>
  </div>
  <div class="drawerContent">
    <div class="bottomMenuGrid">
        @foreach($drawerMenus as $menu)
          <a href="{{ $menu->link }}" class="item {{ request()->is(ltrim($menu->link, '/')) ? 'active' : '' }}">
            <div class="col">
              <ion-icon name="{{ $menu->icon }}"></ion-icon>
              <strong>{{ $menu->namamenu }}</strong>
            </div>
          </a>
        @endforeach
       @php
            use Illuminate\Support\Facades\Crypt;
            use Illuminate\Support\Facades\Auth;
        
            $userId = Auth::id();
            $expires = now()->addMinutes(10)->timestamp;
        
            // Data dasar
            $payload = $userId . '|' . $expires;
        
            // Enkripsi atau beri tanda tangan HMAC
            $signature = hash_hmac('sha256', $payload, config('app.key'));
        
            // Gabung dan encode aman untuk URL
            $token = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');
        @endphp
        
        {{-- Contoh dua item, satu lokal dan satu eksternal --}}
        
        {{-- Item 1: Link eksternal ke Project Management --}}
        <a href="https://memo.rspkuboja.com/external/{{ $token }}" 
           style="all: unset; display: contents; cursor: pointer;" 
           target="_blank">
            <div class="item">
                <div class="col">
                    <ion-icon name="list-outline"></ion-icon>
                    <strong>MEMO</strong>
                </div>
            </div>
        </a>
      <div class="item logout" onclick="document.getElementById('logoutFormHidden').submit()">
        <div class="col">
          <ion-icon name="exit-outline"></ion-icon>
          <strong>Logout</strong>
        </div>
      </div>

    </div>
  </div>
</div>
<form id="logoutFormHidden" action="{{ route('logout') }}" method="POST" style="display:none;">
    @csrf
</form>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<!-- Script -->
<script>
  const toggle = document.getElementById('menuToggle');
  const drawer = document.getElementById('menuDrawer');
  const overlay = document.getElementById('menuOverlay');
  const closeBtn = document.getElementById('drawerClose');
  const menuIcon = document.getElementById('menuIcon');

  function openDrawer() {
    drawer.classList.add('active');
    overlay.classList.add('active');
    menuIcon.setAttribute('name', 'chevron-down-outline');
  }

  function closeDrawer() {
    drawer.classList.remove('active');
    overlay.classList.remove('active');
    menuIcon.setAttribute('name', 'apps-outline');
  }

  function toggleDrawer() {
    if (drawer.classList.contains('active')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  }

  toggle.addEventListener('click', toggleDrawer);
  overlay.addEventListener('click', closeDrawer);
  closeBtn.addEventListener('click', closeDrawer);

  // Swipe-down gesture
  let startY = 0;
  drawer.addEventListener('touchstart', e => {
    startY = e.touches[0].clientY;
  });
  drawer.addEventListener('touchend', e => {
    const endY = e.changedTouches[0].clientY;
    if (endY - startY > 40) {
      closeDrawer();
    }
  });
</script>

</body>
</html>
