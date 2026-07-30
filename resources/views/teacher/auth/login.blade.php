<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher Portal Login</title>
    <style>
        :root { color-scheme: light; --brand:#155e75; --brand-dark:#0e4455; --ink:#15313b; --muted:#607781; --danger:#b42318; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; font-family:system-ui,-apple-system,"Segoe UI",sans-serif; color:var(--ink); background:linear-gradient(145deg,#e6f5f7,#f8fbfc 55%,#dff2ec); }
        .card { width:min(100%,430px); padding:36px; border:1px solid #d5e5e8; border-radius:20px; background:#fff; box-shadow:0 24px 70px rgba(21,49,59,.13); }
        .brand { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
        .brand img { width:100%; height:58px; object-fit:contain; }
        .institute { margin:10px 0 6px; color:var(--brand-dark); font-size:1.25rem; line-height:1.25; font-weight:750; }
        h1 { margin:0; font-size:1.65rem; }
        .subtitle { margin:5px 0 0; color:var(--muted); }
        label { display:block; margin:18px 0 7px; font-weight:650; }
        input { width:100%; padding:12px 13px; border:1px solid #b9cdd2; border-radius:10px; font:inherit; }
        input:focus { outline:3px solid rgba(21,94,117,.16); border-color:var(--brand); }
        .password { position:relative; }
        .password input { padding-right:72px; }
        .toggle { position:absolute; right:7px; top:6px; padding:7px 9px; border:0; border-radius:7px; color:var(--brand); background:#edf7f8; cursor:pointer; }
        .submit { width:100%; margin-top:24px; padding:13px; border:0; border-radius:10px; color:#fff; background:var(--brand); font:inherit; font-weight:700; cursor:pointer; }
        .submit:hover { background:var(--brand-dark); }
        .error { margin:0 0 14px; padding:11px 12px; border-radius:9px; color:var(--danger); background:#fff0ee; }
        .back { display:block; margin-top:20px; color:var(--brand); text-align:center; }
    </style>
</head>
<body>
<main class="card">
    <div class="brand">
        <div>
            <img src="{{ asset('public/assets/images/logo.png') }}" alt="Cultivation">
            <div class="institute">{{ $instituteName }}</div>
            <h1>Teacher Portal</h1>
            <p class="subtitle">Sign in to your secure workspace</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('teacher.login.submit') }}">
        @csrf
        <label for="identifier">Email / Teacher ID / Mobile</label>
        <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" required autofocus
               maxlength="255" autocomplete="username" autocapitalize="none" autocorrect="off"
               spellcheck="false" inputmode="text">

        <label for="password">Password</label>
        <div class="password">
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button class="toggle" id="toggle-password" type="button" aria-controls="password" aria-pressed="false">Show</button>
        </div>

        <button class="submit" type="submit">Sign in</button>
    </form>
    <a class="back" href="{{ route('adminLogin') }}">Back to main login</a>
</main>
<script>
document.getElementById('toggle-password').addEventListener('click', function () {
    const password = document.getElementById('password');
    const showing = password.type === 'text';
    password.type = showing ? 'password' : 'text';
    this.textContent = showing ? 'Show' : 'Hide';
    this.setAttribute('aria-pressed', String(!showing));
});
</script>
</body>
</html>
