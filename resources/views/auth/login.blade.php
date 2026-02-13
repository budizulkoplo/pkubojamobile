<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title>HRIS PKU Boja</title>

  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}"/>
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/icon/192x192.png') }}">

  <link href="{{ asset('tabler/dist/css/tabler.min.css') }}" rel="stylesheet"/>
  <link href="{{ asset('tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet"/>
  <link href="{{ asset('tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet"/>
  <link href="{{ asset('tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet"/>
  <link href="{{ asset('tabler/dist/css/demo.min.css') }}" rel="stylesheet"/>
  <style>
    @import url('https://rsms.me/inter/inter.css');
    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }
    body {
      font-feature-settings: "cv03", "cv04", "cv11";
    }
  </style>
</head>
<body class="d-flex flex-column">
  <script src="{{ asset('tabler/dist/js/demo-theme.min.js') }}"></script>

  <div class="page page-center">
    <div class="container container-tight py-4">
      <div class="text-center mb-4">
        <a href="#" class="navbar-brand">
          <img src="{{ asset('assets/img/login/logopku.png') }}" height="130" alt="Logo">
        </a>
      </div>

      <div class="card card-md">
        <div class="card-body">
          <h2 class="h2 text-center mb-4">HRIS Login</h2>
          <h4 class="text-center mb-4 text-muted">Human Resource Information System</h4>

          {{-- Session warning --}}
          @if (Session::get('warning'))
            <div class="alert alert-warning">
              {{ Session::get('warning') }}
            </div>
          @endif

          <form method="POST" action="/proseslogin" autocomplete="off">
            @csrf

            <div class="mb-3">
              <label class="form-label">NIK</label>
              <input type="text" name="nik" class="form-control" placeholder="Masukkan NIK" required autofocus>
            </div>

            <div class="mb-2">
              <label class="form-label">Password</label>
              <div class="input-group input-group-flat">
                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                <span class="input-group-text">
                  <a href="#" class="link-secondary" title="Show password" id="toggle-password">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                      <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                    </svg>
                  </a>
                </span>
              </div>
            </div>

            <div class="form-footer">
                <button type="submit" id="loginBtn" class="btn btn-primary w-100" style="background-color: #07b8b2; color: white;">
                  Login
                </button>
              </div>
            </form>

            @if (Route::has('password.request'))
              <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="text-muted">Forgot your password?</a>
              </div>
            @endif
          </div>

          {{-- Branding menyatu di dalam card --}}
          <div class="card-footer text-center py-3" style="background-color:#ecf2f8;">
            <p class="text-muted small mb-0 mt-2">
              &copy; HRIS Mobile <b>IT</b> Project {{ date('Y') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <script src="{{ asset('tabler/dist/js/tabler.min.js') }}" defer></script>
    <script>
      // toggle show/hide password
      document.getElementById('toggle-password').addEventListener('click', function(e) {
        e.preventDefault();
        const pw = document.getElementById('password');
        pw.type = pw.type === 'password' ? 'text' : 'password';
      });

      // cegah double submit pada tombol login
      const loginBtn = document.getElementById('loginBtn');
      if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
          this.disabled = true;
          this.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Memproses...
          `;
          this.form.submit();
        });
      }
    </script>
  </body>
</html>
