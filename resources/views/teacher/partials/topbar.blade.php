<header class="tp-topbar">
    <div class="tp-top-left">
        <button class="tp-menu-toggle" id="teacher-menu-toggle" type="button" aria-label="Open navigation" aria-controls="teacher-sidebar" aria-expanded="false">☰</button>
        <div>
            <h1 class="tp-page-title">@yield('page-title', 'Dashboard')</h1>
            <div class="tp-institute">{{ $instituteName }}</div>
        </div>
    </div>
    <details class="tp-user-menu">
        <summary aria-label="Open teacher account menu">
            <span class="tp-user">
                <span class="tp-avatar">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="">
                    @else
                        {{ $avatarInitials }}
                    @endif
                </span>
                <span class="tp-user-name">{{ $teacher->adminName ?: 'Teacher' }}</span>
                <span aria-hidden="true">⌄</span>
            </span>
        </summary>
        <div class="tp-dropdown">
            <a href="{{ route('teacher.dashboard') }}">Dashboard</a>
            <a href="{{ route('teacher.profile.show') }}">Profile &amp; settings</a>
            <form method="POST" action="{{ route('teacher.logout') }}" onsubmit="this.querySelector('button').disabled=true">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        </div>
    </details>
</header>
