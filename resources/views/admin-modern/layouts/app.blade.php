<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Modern') | Cultivation V2</title>
    @vite(['resources/css/admin-modern.css', 'resources/js/admin-modern.js'])
    @stack('styles')
</head>
<body class="am-body">
    <a class="am-skip-link" href="#main-content">Skip to main content</a>
    <div class="am-app" id="adminModernApp">
        <x-admin-modern.sidebar />

        <div class="am-main-wrap">
            <x-admin-modern.navbar />

            <main class="am-main" id="main-content" tabindex="-1">
                <x-admin-modern.flash />
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
