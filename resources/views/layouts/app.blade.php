<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cultivation')</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f9fa; }
        .cultivation-header { background: #168c6c; color: #fff; padding: 24px 0; margin-bottom: 32px; }
        .cultivation-header h1 { font-weight: 700; }
        .card { box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
    </style>
    @stack('styles')
</head>
<body>
    <div class="cultivation-header text-center">
        <h1>{{ config('app.name', 'AR MULTIMEDIA HIGH SCHOOL') }}</h1>
        <div>S.S.C. Vocational | Shahporan Gate, Khadim Nagor, Sylhet -3100 | Estd. 1992</div>
    </div>
    <div class="container">
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
