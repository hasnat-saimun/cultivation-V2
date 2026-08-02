<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Teacher Portal') · {{ $instituteName }}</title>
    @include('shared.ui-interaction-styles')
    <style>
        :root{--tp-brand:#155e75;--tp-brand-dark:#0d4657;--tp-accent:#0f766e;--tp-bg:#f1f6f7;--tp-surface:#fff;--tp-text:#17333d;--tp-muted:#667d85;--tp-border:#d8e5e8;--tp-focus:#f59e0b;--tp-shadow:0 10px 30px rgba(20,53,63,.08)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--tp-text);background:var(--tp-bg)}button,a{font:inherit}a:focus-visible,button:focus-visible{outline:3px solid var(--tp-focus);outline-offset:3px}
        .tp-skip{position:fixed;left:1rem;top:-5rem;z-index:100;padding:.75rem 1rem;color:#fff;background:#111827;border-radius:.5rem}.tp-skip:focus{top:1rem}
        .tp-shell{min-height:100vh;display:grid;grid-template-columns:260px minmax(0,1fr)}.tp-sidebar{position:sticky;top:0;height:100vh;padding:1.25rem;background:linear-gradient(180deg,var(--tp-brand-dark),#123842);color:#fff;z-index:40}.tp-brand{display:flex;align-items:center;gap:.8rem;padding:.35rem .4rem 1.4rem}.tp-brand img{width:42px;height:42px;object-fit:contain;background:#fff;border-radius:10px;padding:4px}.tp-brand strong,.tp-brand span{display:block}.tp-brand span{font-size:.78rem;color:#bcd4da}
        .tp-nav{display:grid;gap:.35rem}.tp-nav-item{display:flex;align-items:center;gap:.75rem;width:100%;padding:.75rem .85rem;border:0;border-radius:.65rem;color:#d9edf1;background:transparent;text-decoration:none;text-align:left}.tp-nav-item.active{color:#fff;background:rgba(255,255,255,.14);box-shadow:inset 3px 0 #5eead4}.tp-nav-item.disabled{cursor:not-allowed;opacity:.58}.tp-nav-icon{width:1.4rem;text-align:center}.tp-coming{margin-left:auto;font-size:.65rem;padding:.16rem .35rem;border:1px solid rgba(255,255,255,.35);border-radius:1rem}
        .tp-main{min-width:0}.tp-topbar{position:sticky;top:0;z-index:30;min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.8rem clamp(1rem,3vw,2rem);border-bottom:1px solid var(--tp-border);background:rgba(255,255,255,.94);backdrop-filter:blur(10px)}.tp-top-left,.tp-user{display:flex;align-items:center;gap:.8rem}.tp-menu-toggle{display:none;border:1px solid var(--tp-border);border-radius:.55rem;padding:.55rem;background:#fff;color:var(--tp-text)}.tp-page-title{margin:0;font-size:1.15rem}.tp-institute{font-size:.78rem;color:var(--tp-muted)}
        .tp-avatar{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;color:#fff;background:var(--tp-accent);font-weight:750;overflow:hidden}.tp-avatar img{width:100%;height:100%;object-fit:cover}.tp-user-name{font-size:.88rem;font-weight:650}.tp-user-menu{position:relative}.tp-user-menu summary{list-style:none;cursor:pointer}.tp-user-menu summary::-webkit-details-marker{display:none}.tp-dropdown{position:absolute;right:0;top:calc(100% + .65rem);width:190px;padding:.45rem;border:1px solid var(--tp-border);border-radius:.75rem;background:#fff;box-shadow:var(--tp-shadow)}.tp-dropdown a,.tp-dropdown button{display:block;width:100%;padding:.65rem .7rem;border:0;border-radius:.45rem;color:var(--tp-text);background:transparent;text-align:left;text-decoration:none}.tp-dropdown .disabled{color:var(--tp-muted)}.tp-dropdown a:hover,.tp-dropdown button:hover{background:#edf6f7}
        .tp-content{width:min(1180px,calc(100% - 2rem));margin:1.6rem auto 3rem}.tp-alert{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #a7d8ca;border-radius:.65rem;background:#ecfdf5}.tp-alert.error{border-color:#fecaca;background:#fff1f2;color:#9f1239}
        .tp-welcome{display:flex;justify-content:space-between;gap:1.5rem;padding:clamp(1.25rem,3vw,2rem);border-radius:1rem;color:#fff;background:linear-gradient(125deg,var(--tp-brand),var(--tp-accent));box-shadow:var(--tp-shadow)}.tp-welcome h2{margin:0 0 .45rem;font-size:clamp(1.45rem,3vw,2rem)}.tp-welcome p{margin:.25rem 0;color:#dff7f3}.tp-date{align-self:flex-start;padding:.5rem .75rem;border-radius:.55rem;background:rgba(255,255,255,.13);white-space:nowrap}
        .tp-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-top:1rem}.tp-card{padding:1.15rem;border:1px solid var(--tp-border);border-radius:.85rem;background:var(--tp-surface);box-shadow:var(--tp-shadow)}.tp-card-label{color:var(--tp-muted);font-size:.86rem}.tp-card-value{display:block;margin-top:.3rem;color:var(--tp-brand);font-size:1.8rem;font-weight:760}.tp-card-value.soon{font-size:.95rem;color:var(--tp-muted)}
        .tp-section{margin-top:1.5rem}.tp-section-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.75rem}.tp-section h2{margin:0;font-size:1.12rem}.tp-actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.8rem}.tp-action{padding:1rem;border:1px solid var(--tp-border);border-radius:.75rem;background:#fff}.tp-action strong{display:block}.tp-action span{font-size:.8rem;color:var(--tp-muted)}
        .tp-panel-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:1rem}.tp-table-wrap{overflow-x:auto}.tp-table{width:100%;border-collapse:collapse;white-space:nowrap}.tp-table th,.tp-table td{padding:.7rem .65rem;border-bottom:1px solid var(--tp-border);text-align:left;font-size:.85rem}.tp-table th{color:var(--tp-muted);font-size:.74rem;text-transform:uppercase;letter-spacing:.04em}.tp-empty{text-align:center;padding:1.8rem;color:var(--tp-muted)}.tp-activity{display:grid;gap:.75rem}.tp-activity-item{padding-bottom:.7rem;border-bottom:1px solid var(--tp-border)}.tp-activity-item:last-child{border:0}.tp-activity-item time{display:block;margin-top:.2rem;font-size:.78rem;color:var(--tp-muted)}
        .tp-form{display:grid;gap:1rem}.tp-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;align-items:end}.tp-field{display:grid;gap:.4rem;margin:0}.tp-label,.tp-field>label{font-size:.86rem;font-weight:700;color:var(--tp-text)}.tp-required{color:#b42318}.tp-help{font-size:.78rem;color:var(--tp-muted)}.tp-error{font-size:.8rem;color:#b42318}.tp-control,.tp-form input:not([type=hidden]):not([type=checkbox]):not([type=radio]),.tp-form select,.tp-form textarea,.tp-form-grid input:not([type=hidden]):not([type=checkbox]):not([type=radio]),.tp-form-grid select,.tp-form-grid textarea{width:100%;min-height:42px;padding:.62rem .72rem;border:1px solid #b8cbd0;border-radius:.55rem;color:var(--tp-text);background:#fff;font:inherit;line-height:1.35;transition:border-color .15s,box-shadow .15s}.tp-form textarea,.tp-form-grid textarea{min-height:110px;resize:vertical}.tp-form input[type=file],.tp-form-grid input[type=file]{padding:.42rem}.tp-form input[type=file]::file-selector-button,.tp-form-grid input[type=file]::file-selector-button{margin-right:.7rem;padding:.45rem .65rem;border:0;border-radius:.4rem;color:var(--tp-text);background:#e7f1f3;font-weight:650}.tp-control:focus,.tp-form input:focus,.tp-form select:focus,.tp-form textarea:focus,.tp-form-grid input:focus,.tp-form-grid select:focus,.tp-form-grid textarea:focus{outline:0;border-color:var(--tp-brand);box-shadow:0 0 0 3px rgba(21,94,117,.16)}.tp-control[disabled],.tp-control[readonly],.tp-form input[disabled],.tp-form input[readonly],.tp-form select[disabled],.tp-form textarea[disabled],.tp-form-grid input[disabled],.tp-form-grid select[disabled]{cursor:not-allowed;color:#667d85;background:#edf2f3}.tp-check{display:inline-flex;align-items:center;gap:.5rem}.tp-check input{width:1rem;height:1rem;accent-color:var(--tp-brand)}.tp-form-actions{display:flex;align-items:center;justify-content:flex-end;gap:.65rem;flex-wrap:wrap;margin-top:.25rem}.tp-btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:.58rem .85rem;border:1px solid var(--tp-border);border-radius:.55rem;color:var(--tp-brand);background:#fff;font-weight:700;text-decoration:none;cursor:pointer}.tp-btn-primary{border-color:var(--tp-brand);color:#fff;background:var(--tp-brand)}.tp-btn-danger{border-color:#9f1239;color:#fff;background:#9f1239}.tp-btn:disabled{cursor:not-allowed;opacity:.6}.tp-mark-control{width:82px!important;min-width:82px}
        .tp-footer{padding:1.5rem;text-align:center;color:var(--tp-muted);font-size:.8rem}.tp-overlay{display:none}
        .teacher-pagination{display:flex;align-items:center;justify-content:center;gap:.25rem;flex-wrap:wrap;margin-top:1rem}.teacher-pagination a,.teacher-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 .55rem;border:1px solid var(--tp-border);border-radius:.45rem;color:var(--tp-brand);background:#fff;text-decoration:none;font-size:.84rem}.teacher-pagination .active{border-color:var(--tp-brand);color:#fff;background:var(--tp-brand)}.teacher-pagination .disabled{color:var(--tp-muted);background:#edf2f3}.teacher-pagination svg{width:16px;height:16px;display:inline-block}
        @media(max-width:900px){.tp-shell{grid-template-columns:1fr}.tp-sidebar{position:fixed;left:0;transform:translateX(-105%);width:260px;transition:transform .2s ease}.tp-shell.sidebar-open .tp-sidebar{transform:translateX(0)}.tp-menu-toggle{display:inline-flex}.tp-overlay{position:fixed;inset:0;z-index:35;border:0;background:rgba(9,30,36,.52)}.tp-shell.sidebar-open .tp-overlay{display:block}.tp-grid,.tp-actions{grid-template-columns:repeat(2,minmax(0,1fr))}.tp-panel-grid{grid-template-columns:1fr}}
        @media(max-width:560px){.tp-content{width:min(100% - 1rem,1180px);margin-top:.75rem}.tp-user-name,.tp-institute{display:none}.tp-welcome{display:block}.tp-date{display:inline-block;margin-top:.8rem}.tp-grid,.tp-actions,.tp-form-grid{grid-template-columns:1fr}.tp-topbar{padding:.7rem}.tp-page-title{font-size:1rem}.tp-form-actions{justify-content:stretch}.tp-form-actions .tp-btn{flex:1}}
        @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
    </style>
</head>
<body class="ui-static-caret-scope">
<a class="tp-skip" href="#teacher-main">Skip to main content</a>
<div class="tp-shell" id="teacher-shell">
    @include('teacher.partials.sidebar')
    <button class="tp-overlay" id="teacher-overlay" type="button" aria-label="Close navigation"></button>
    <div class="tp-main">
        @include('teacher.partials.topbar')
        <main class="tp-content" id="teacher-main">
            @if (session('success')) <div class="tp-alert" role="status">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="tp-alert error" role="alert">{{ session('error') }}</div> @endif
            @if ($errors->any()) <div class="tp-alert error" role="alert">{{ $errors->first() }}</div> @endif
            @yield('content')
        </main>
        <footer class="tp-footer">&copy; {{ now()->year }} {{ $instituteName }} · Teacher Portal</footer>
    </div>
</div>
<script>
(() => {
    const shell = document.getElementById('teacher-shell');
    const toggle = document.getElementById('teacher-menu-toggle');
    const overlay = document.getElementById('teacher-overlay');
    const setOpen = open => {
        shell.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    toggle.addEventListener('click', () => setOpen(!shell.classList.contains('sidebar-open')));
    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') setOpen(false); });
})();
</script>
@stack('scripts')
</body>
</html>
