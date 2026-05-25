@php
  $cfg = \App\Models\ServerConfig::first();
  $isDemo = strpos(config('app.url'), 'demoadmin.cultivationapp.com') !== false;
  $assetPath = static function (?string $path): string {
    $path = ltrim((string) $path, '/');
    $path = preg_replace('#^public/#', '', $path) ?? $path;

    return asset($path);
  };
  $appVersion = (function(){
    $candidates = [
      base_path('RELEASE_NOTES.md'),
      base_path('RELEASE.md'),
      base_path('CHANGELOG.md'),
      base_path('docs/RELEASE_NOTES.md'),
      base_path('docs/release-notes.md'),
      base_path('docs/CHANGELOG.md'),
      base_path('docs/changelog.md'),
      base_path('version.txt'),
      base_path('VERSION'),
    ];
    foreach($candidates as $p){
      if(@is_file($p)){
        $txt = @file_get_contents($p);
        if($txt === false) continue;
        if(preg_match('/^##\s*\[?v?(\d+\.\d+\.\d+[^\]\s]*)/mi', $txt, $m)) return $m[1];
        if(preg_match('/^#\s*Release[s]?\s+v?(\d+\.\d+\.\d+[^\s]*)/mi', $txt, $m)) return $m[1];
        if(preg_match('/\bv?(\d+\.\d+\.\d+(?:[-+][A-Za-z0-9\.]+)?)\b/', $txt, $m)) return $m[1];
        $trim = trim($txt);
        if($trim && strlen($trim) < 40) return $trim; // simple VERSION file
      }
    }
    return config('app.version') ?? app()->version();
  })();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cultivation | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --brand:#0e56a9; --brand-dark:#0b4486; --ink:#0f172a; --muted:#64748b;
        }

        .eye {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 68%;
            transform: translateY(-50%);
            color: #0b3d7b;
        }
        body{
            min-height:100vh;
            background-color:#f0f2f5;
          background-image:url("{{ $assetPath('loginPart/themeknit/images/240_F_572890162_r9rzijySPVmEGH5YsSVYJtMYJ6eTooXz.jpg') }}");
            background-repeat:no-repeat;
            background-position:100%;
            background-size:cover;
        }    
          .auth-wrap{min-height:100vh;display:block;padding:24px;}
          .auth-card{max-width:980px;margin:48px auto;border:none;box-shadow:0 12px 40px rgba(2,6,23,.08);border-radius:16px;overflow:hidden;background:#fff;}
        .brand-pane{background:linear-gradient(180deg,#0e56a9 0%, #0a2f62 100%);color:#fff;position:relative;padding:28px 28px 32px 28px;}
        .brand-pane .logo{height:42px;width:auto;}
        .brand-pane h4{font-weight:700;margin-top:18px;}
        .brand-pane p{color:#dbeafe;margin-bottom:0}
        .form-pane{padding:28px 28px 24px 28px}
        .form-title{font-weight:700;color:var(--ink)}
        .form-text{color:var(--muted)}
        .form-control{border-radius:10px;padding:.7rem .9rem}
      .btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}
      .btn-brand:hover,.btn-brand:focus,.btn-brand:active{background:var(--brand-dark);border-color:var(--brand-dark);color:#fff}
        .small-note{font-size:.85rem;color:var(--muted)}
        .divider{height:1px;background:#e5e7eb;margin:16px 0}
       .brand-meta{margin-top:.5rem}
      .brand-meta li{margin:.15rem 0;color:#dbeafe}
      .brand-meta svg{margin-right:.4rem;opacity:.9}
      .form-header .accent-bar{height:3px;width:56px;background:linear-gradient(90deg,var(--brand),var(--brand-dark));border-radius:2px;margin-bottom:10px}
      .form-header .form-title{font-size:1.35rem}
      .brand-pane,.form-pane{display:flex;flex-direction:column;justify-content:center;}
    </style>
        {{-- ...existing code... --}}
    <link rel="icon" href="{{ $cfg && $cfg->favicon ? $assetPath($cfg->favicon) : $assetPath('favicon.ico') }}">
    <meta name="robots" content="noindex">
    <meta name="author" content="cultivationapp.com">
    <meta name="description" content="Secure login to Cultivation back office">
</head>
<body>
<div class="auth-wrap">
  <div class="card auth-card w-100">
    <div class="row g-0 align-items-center">
        <div class="col-lg-5 d-none d-lg-block brand-pane">
        @if($cfg && $cfg->logo)
          <img src="{{ $assetPath('upload/image/cultivation/' . $cfg->logo) }}" alt="{{ $cfg->instituteName ?? 'Institute Logo' }}" class="logo">
        @else
          <img src="{{ $assetPath('loginPart/themeknit/images/logo1.png') }}" alt="Cultivation" class="logo">
        @endif

        <h4 class="mt-3 mb-1">
            {{ $cfg && $cfg->instituteName ? $cfg->instituteName : 'Welcome back' }}
        </h4>

        @if($cfg && ($cfg->address || $cfg->officeMobile || $cfg->officeEmail))
            <ul class="list-unstyled small brand-meta">
                @if($cfg->address)
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a5.53 5.53 0 0 0-5.5 5.5C2.5 9.5 8 16 8 16s5.5-6.5 5.5-10.5A5.53 5.53 0 0 0 8 0m0 7.5A2 2 0 1 1 8 3.5a2 2 0 0 1 0 4"/></svg>
                    {{ $cfg->address }}
                </li>
                @endif
                @if($cfg->officeMobile)
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11 1a2 2 0 0 1 2 2v1.5a1 1 0 0 1-1 1H9.5a.5.5 0 0 0-.5.5v2a.5.5 0 0 0 .5.5H12a1 1 0 0 1 1 1V13a2 2 0 0 1-2 2h-1A8 8 0 0 1 1 7V6a2 2 0 0 1 2-2h1a1 1 0 0 1 1 1v2.5a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5V4a1 1 0 0 1 1-1z"/></svg>
                    {{ $cfg->officeMobile }}
                </li>
                @endif
                @if($cfg->officeEmail)
                <li>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v.217L8 8 0 4.217z"/><path d="M0 4.697V12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4.697L8 9.5z"/></svg>
                    {{ $cfg->officeEmail }}
                </li>
                @endif
            </ul>
        @else
            <p>Sign in to manage admissions, results, and institute settings.</p>
        @endif
        </div>
      <div class="col-lg-7 bg-white form-pane">
        @php
          $cfg = isset($cfg) ? $cfg : \App\Models\ServerConfig::first();
          $isDemo = strpos(config('app.url'), 'demoadmin.cultivationapp.com') !== false;
        @endphp
        <div class="form-header mb-3">
            <div class="accent-bar"></div>
            <h4 class="form-title mb-1">Admin Portal</h4>
            <p class="form-text mb-0">Secure access for {{ ($cfg && $cfg->instituteName) ? $cfg->instituteName : 'Cultivation' }}</p>
        </div>

        <div class="mb-2">
          @if(session()->has('success'))
              <div class="alert alert-success py-2 mb-2">{{ session('success') }}</div>
          @endif
          @if(session()->has('error'))
              <div class="alert alert-danger py-2 mb-2">{{ session('error') }}</div>
          @endif
        </div>

        @php $isDemo = strpos(config('app.url'), 'demoadmin.cultivationapp.com') !== false; @endphp
        @if($isDemo)
          <div class="alert alert-info mb-3">
            <strong>Demo Credentials</strong><br>
            @php
              $demoUser = \DB::table('cultivation_admins')->where('userType',3)->first();
            @endphp
            Username: <b>{{ $demoUser->adminUser ?? 'demo' }}</b><br>
            Login Password: <b>demo1234</b>
          </div>
        @endif
        @if($cultivation->count()>0)
            <form method="POST" action="{{ route('cultivationLogin') }}" class="needs-validation" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label">Username</label>
                  <input type="text" name="cultivationUser" class="form-control" placeholder="Enter your username" autocomplete="username" inputmode="text" autocapitalize="off" spellcheck="false" required>
                    <div class="invalid-feedback">Username is required.</div>
                </div>
                <div class="mb-2 position-relative">
                    <label class="form-label">Password</label>
                  <input type="password" name="cultivationPass" id="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                    <span class="eye" id="togglePwd" title="Show/Hide"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg></span>
                    <div class="invalid-feedback">Password is required.</div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                  </div>
                </div>
                <div class="d-grid mt-3">
                  <button class="btn btn-brand btn-lg" type="submit">Sign in</button>
                </div>
            </form>
        @else
            <div class="small-note mb-2">First-time setup: create the first admin.</div>
            <form method="POST" action="{{ route('adminRegister') }}" class="needs-validation" novalidate>
                @csrf
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Admin name</label>
                        <input type="text" name="adminName" class="form-control" placeholder="e.g., John Doe" required>
                        <div class="invalid-feedback">Admin name is required.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Email address</label>
                        <input type="email" name="adminEmail" class="form-control" placeholder="name@example.com" required>
                        <div class="invalid-feedback">Valid email is required.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="cultivationUser" class="form-control" placeholder="choose a username" required>
                        <div class="invalid-feedback">Username is required.</div>
                    </div>
                    <div class="col-sm-6 position-relative">
                        <label class="form-label">Password</label>
                        <input type="password" name="cultivationPass" id="regPassword" class="form-control" placeholder="create a password" autocomplete="new-password" required>
                        <span class="eye" id="toggleRegPwd" title="Show/Hide"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg></span>
                        <div class="invalid-feedback">Password is required.</div>
                    </div>
                </div>
                <div class="d-grid mt-3">
                    <button class="btn btn-brand btn-lg" type="submit">Create Admin</button>
                </div>
            </form>
        @endif

        <div class="divider"></div>
        <div class="d-flex justify-content-between small-note">
            <span>&copy; {{ date('Y') }} Cultivation</span>
          <span>v{{ $appVersion }}</span>
        </div>
      </div>
    </div>
  </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// password toggles
const toggle = (btnId, inpId) => {
  const b=document.getElementById(btnId), i=document.getElementById(inpId); if(!b||!i) return;
  b.addEventListener('click', ()=>{ i.type = i.type==='password'?'text':'password'; });
}
toggle('togglePwd','password');
toggle('toggleRegPwd','regPassword');

// bootstrap validation
(() => {
  'use strict'
  const forms = document.querySelectorAll('.needs-validation')
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault(); event.stopPropagation();
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
</body>
</html>
